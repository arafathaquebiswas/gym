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

            $total = max(0, round($subtotal - $discount, 2));
            $invoiceNo = $this->generateInvoiceNo();

            $stmt = $this->db->prepare(
                'INSERT INTO sales (invoice_no, member_id, sold_by, sale_date, subtotal, discount, tax, total, payment_method, payment_status, promotion_id)
                 VALUES (:invoice_no, :member_id, :sold_by, NOW(), :subtotal, :discount, 0, :total, :payment_method, "paid", :promotion_id)'
            );
            $stmt->execute([
                'invoice_no' => $invoiceNo,
                'member_id' => $memberId,
                'sold_by' => $soldBy,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'promotion_id' => $promotion['id'] ?? null,
            ]);
            $saleId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO sale_items (sale_id, product_id, qty, unit_price, subtotal)
                 VALUES (:sale_id, :product_id, :qty, :unit_price, :subtotal)'
            );
            foreach ($cart as $line) {
                $itemStmt->execute([
                    'sale_id' => $saleId,
                    'product_id' => $line['product_id'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => round($line['unit_price'] * $line['qty'], 2),
                ]);
                // trg_sale_items_after_insert decrements products.stock_qty automatically.
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
