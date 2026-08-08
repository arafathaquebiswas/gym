<?php

final class Sale extends Model
{
    /**
     * @param array<int, array{product_id:int, qty:int, unit_price:float}> $cart
     * @return array{id:int, invoice_no:string}
     */
    public function create(array $cart, ?int $memberId, float $discount, string $paymentMethod, ?string $couponCode, int $soldBy): array
    {
        if (!$cart) {
            throw new InvalidArgumentException('Cart is empty.');
        }

        $this->db->beginTransaction();
        try {
            $subtotal = 0.0;
            foreach ($cart as $line) {
                $subtotal += $line['unit_price'] * $line['qty'];
            }

            $promotionModel = new Promotion();
            $promotion = $couponCode ? $promotionModel->validCoupon($couponCode, $subtotal, 'product', $memberId) : null;
            if ($couponCode && !$promotion) {
                throw new RuntimeException('That coupon code is invalid, expired, or no longer applicable.');
            }
            if ($promotion) {
                $discount += $promotionModel->computeDiscount($promotion, $subtotal);
            }

            $netAfterDiscount = max(0, $subtotal - $discount);

            // Same tax_enabled/tax_percent settings the online store checkout uses — POS previously hardcoded tax to 0.
            $settingModel = new Setting();
            $tax = 0.0;
            if ($settingModel->getBool('tax_enabled')) {
                $tax = round($netAfterDiscount * ((float) $settingModel->get('tax_percent', '0') / 100), 2);
            }

            $total = max(0, round($netAfterDiscount + $tax, 2));
            $invoiceNo = $this->generateInvoiceNo();

            $stmt = $this->db->prepare(
                'INSERT INTO sales (invoice_no, member_id, sold_by, sale_date, subtotal, discount, tax, total, payment_method, payment_status, promotion_id)
                 VALUES (:invoice_no, :member_id, :sold_by, NOW(), :subtotal, :discount, :tax, :total, :payment_method, "paid", :promotion_id)'
            );
            $stmt->execute([
                'invoice_no' => $invoiceNo,
                'member_id' => $memberId,
                'sold_by' => $soldBy,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'promotion_id' => $promotion['id'] ?? null,
            ]);
            $saleId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO sale_items (sale_id, product_id, qty, unit_price, subtotal)
                 VALUES (:sale_id, :product_id, :qty, :unit_price, :subtotal)'
            );
            $stockMovementModel = new StockMovement();
            $productModel = new Product();
            foreach ($cart as $line) {
                $productBefore = $productModel->find((int) $line['product_id']);

                $itemStmt->execute([
                    'sale_id' => $saleId,
                    'product_id' => $line['product_id'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => round($line['unit_price'] * $line['qty'], 2),
                ]);

                $this->db->prepare('UPDATE products SET stock_qty = stock_qty - :qty WHERE id = :product_id')
                    ->execute(['qty' => (int) $line['qty'], 'product_id' => (int) $line['product_id']]);

                $stockMovementModel->record((int) $line['product_id'], -(int) $line['qty'], 'sale', $saleId, 'POS sale', $soldBy);
                if ($productBefore) {
                    LowStockAlerter::checkAndNotify($productBefore, (int) $productBefore['stock_qty'] - (int) $line['qty']);
                }
            }

            $paymentStmt = $this->db->prepare(
                'INSERT INTO payments (member_id, sale_id, type, amount, method, status, paid_at, recorded_by)
                 VALUES (:member_id, :sale_id, "store_sale", :amount, :method, "completed", NOW(), :recorded_by)'
            );
            $paymentStmt->execute([
                'member_id' => $memberId,
                'sale_id' => $saleId,
                'amount' => $total,
                'method' => $paymentMethod,
                'recorded_by' => $soldBy,
            ]);

            if ($promotion) {
                $promotionModel->recordUsage((int) $promotion['id'], $memberId, $saleId);
            }

            $this->db->commit();
            return ['id' => $saleId, 'invoice_no' => $invoiceNo];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Reverses a completed POS sale: the stock goes back on the shelf, a refund
     * is recorded against the takings, and any coupon becomes usable again.
     *
     * The sale row itself survives. invoice_no is unique and sequential, so
     * deleting one would leave a hole in the numbering that no audit could ever
     * explain; the row is stamped 'cancelled' instead and still prints, marked
     * as such.
     *
     * @return bool false when the sale does not exist or was already cancelled.
     *              The caller must treat that as "nothing happened" — cancelling
     *              twice would hand back the same stock twice.
     */
    public function cancel(int $saleId, int $cancelledBy): bool
    {
        $now = date('Y-m-d H:i:s');

        $this->db->beginTransaction();
        try {
            // Claim the sale before touching anything else. "AND status <> 'cancelled'"
            // is the whole guard: whichever request updates the row wins, and a second
            // click — or a double-submitted form — matches zero rows and stops here.
            // Reading the status first and then writing would leave a window in which
            // two requests both passed the check and both restored the stock.
            $claim = $this->db->prepare(
                "UPDATE sales
                    SET status = 'cancelled', payment_status = 'refunded',
                        cancelled_at = :cancelled_at, cancelled_by = :cancelled_by
                  WHERE id = :id AND status <> 'cancelled'"
            );
            $claim->execute([
                'cancelled_at' => $now,
                'cancelled_by' => $cancelledBy,
                'id' => $saleId,
            ]);
            if ($claim->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            $saleStmt = $this->db->prepare(
                'SELECT invoice_no, member_id, total, payment_method, promotion_id
                   FROM sales WHERE id = :id'
            );
            $saleStmt->execute(['id' => $saleId]);
            $sale = $saleStmt->fetch();

            $itemStmt = $this->db->prepare('SELECT product_id, qty FROM sale_items WHERE sale_id = :sale_id');
            $itemStmt->execute(['sale_id' => $saleId]);

            $restoreStmt = $this->db->prepare('UPDATE products SET stock_qty = stock_qty + :qty WHERE id = :product_id');
            $stockMovementModel = new StockMovement();

            foreach ($itemStmt->fetchAll() as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['qty'];

                $restoreStmt->execute(['qty' => $qty, 'product_id' => $productId]);

                // 'return' is the existing movement type for stock coming back in;
                // the positive change_qty mirrors the negative one create() wrote, so
                // the movement history for the product still sums to the real count.
                $stockMovementModel->record(
                    $productId,
                    $qty,
                    'return',
                    $saleId,
                    'Cancelled sale ' . $sale['invoice_no'],
                    $cancelledBy
                );
            }

            // A negative refund row rather than deleting the original payment: the
            // takings must show that money came in and then went back out, not that
            // it was never taken. Every report that sums payments.amount then
            // self-corrects with no query changes.
            $this->db->prepare(
                'INSERT INTO payments (member_id, sale_id, type, amount, method, status, paid_at, recorded_by)
                 VALUES (:member_id, :sale_id, "refund", :amount, :method, "completed", :paid_at, :recorded_by)'
            )->execute([
                'member_id' => $sale['member_id'],
                'sale_id' => $saleId,
                'amount' => -(float) $sale['total'],
                'method' => $sale['payment_method'],
                'paid_at' => $now,
                'recorded_by' => $cancelledBy,
            ]);

            // Give the coupon back. Without this a single-use code stays spent on a
            // sale that no longer exists, and the customer cannot re-ring the order.
            if (!empty($sale['promotion_id'])) {
                $this->db->prepare(
                    'UPDATE promotions SET used_count = CASE WHEN used_count > 0 THEN used_count - 1 ELSE 0 END
                      WHERE id = :id'
                )->execute(['id' => (int) $sale['promotion_id']]);
                $this->db->prepare('DELETE FROM coupon_usages WHERE sale_id = :sale_id')
                    ->execute(['sale_id' => $saleId]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, m_u.name AS member_name, sold_u.name AS sold_by_name
             FROM sales s
             LEFT JOIN members m ON m.id = s.member_id
             LEFT JOIN users m_u ON m_u.id = m.user_id
             LEFT JOIN users sold_u ON sold_u.id = s.sold_by
             WHERE s.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $sale = $stmt->fetch();
        return $sale ?: null;
    }

    /**
     * @param array{search?:string,payment_method?:string} $filters
     */
    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(s.invoice_no LIKE :search_invoice OR m_u.name LIKE :search_name)';
            $params['search_invoice'] = '%' . $filters['search'] . '%';
            $params['search_name'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['payment_method'])) {
            $where[] = 's.payment_method = :payment_method';
            $params['payment_method'] = $filters['payment_method'];
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $joins = 'FROM sales s LEFT JOIN members m ON m.id = s.member_id LEFT JOIN users m_u ON m_u.id = m.user_id';

        $countStmt = $this->db->prepare("SELECT COUNT(*) $joins" . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT s.*, m_u.name AS member_name $joins" . $whereSql . '
             ORDER BY s.sale_date DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    private function generateInvoiceNo(): string
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()');
        $seq = (int) $stmt->fetchColumn() + 1;
        return 'INV-' . date('Ymd') . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
