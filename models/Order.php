<?php

final class Order extends Model
{
    private const CANCELLED_STATUSES = ['cancelled', 'returned'];

    /**
     * @param array<int, array{product_id:int,qty:int}> $cartLines
     * @param array{guest_name:?string,guest_email:?string,guest_phone:?string,delivery_address:string,delivery_city:string,delivery_area:?string,delivery_postal_code:?string,order_notes:?string} $customer
     * @param array{method:string,discount:float,couponCode:?string,reference_no:?string} $payment
     * @return array{id:int, order_no:string}
     */
    public function create(array $cartLines, ?int $userId, array $customer, array $payment): array
    {
        if (!$cartLines) {
            throw new InvalidArgumentException('Your cart is empty.');
        }

        $productModel = new Product();
        $this->db->beginTransaction();
        try {
            $resolvedLines = [];
            $subtotal = 0.0;

            foreach ($cartLines as $line) {
                $product = $productModel->find((int) $line['product_id']);
                if (!$product || !$product['is_active']) {
                    throw new RuntimeException('One of the items in your cart is no longer available.');
                }
                $qty = (int) $line['qty'];
                if ($qty <= 0) {
                    continue;
                }

                if ((int) $product['stock_qty'] >= $qty) {
                    $productModel->decrementStockIfAvailable((int) $product['id'], $qty);
                } elseif ((int) $product['allow_preorder'] === 1) {
                    $productModel->adjustStock((int) $product['id'], -$qty); // may go negative — backorder demand
                } else {
                    throw new RuntimeException("Not enough stock for {$product['name']} (only {$product['stock_qty']} left).");
                }

                $unitPrice = (float) $product['display_price'];
                $resolvedLines[] = [
                    'product_id' => (int) $product['id'],
                    'product_name' => $product['name'],
                    'sku' => $product['sku'],
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => round($unitPrice * $qty, 2),
                ];
                $subtotal += $unitPrice * $qty;
            }

            if (!$resolvedLines) {
                throw new InvalidArgumentException('Your cart is empty.');
            }

            $memberId = null;
            if ($userId !== null) {
                $member = (new Member())->findByUserId($userId);
                $memberId = $member ? (int) $member['id'] : null;
            }
            $guestEmail = $userId === null ? ($customer['guest_email'] ?? null) : null;

            $discount = (float) ($payment['discount'] ?? 0);
            $promotionModel = new Promotion();
            $promotion = !empty($payment['couponCode'])
                ? $promotionModel->validCoupon($payment['couponCode'], $subtotal, 'product', $memberId, $guestEmail)
                : null;
            if (!empty($payment['couponCode']) && !$promotion) {
                throw new RuntimeException('That coupon code is invalid, expired, or no longer applicable.');
            }
            if ($promotion) {
                $discount += $promotionModel->computeDiscount($promotion, $subtotal);
            }

            $settingModel = new Setting();
            $netAfterDiscount = max(0, $subtotal - $discount);
            $freeShippingMin = (float) $settingModel->get('free_shipping_min_amount', '0');
            $shipping = ($freeShippingMin > 0 && $netAfterDiscount >= $freeShippingMin)
                ? 0.0
                : (float) $settingModel->get('shipping_flat_rate', '0');
            $taxPercent = (float) $settingModel->get('tax_percent', '0');
            $tax = round($netAfterDiscount * ($taxPercent / 100), 2);

            $total = max(0, round($netAfterDiscount + $shipping + $tax, 2));
            $orderNo = $this->generateOrderNo();

            $stmt = $this->db->prepare(
                'INSERT INTO orders (order_no, user_id, guest_name, guest_email, guest_phone,
                    delivery_address, delivery_city, delivery_area, delivery_postal_code, order_notes,
                    subtotal, discount, shipping_charge, tax, total, promotion_id, payment_method, payment_status, status)
                 VALUES (:order_no, :user_id, :guest_name, :guest_email, :guest_phone,
                    :delivery_address, :delivery_city, :delivery_area, :delivery_postal_code, :order_notes,
                    :subtotal, :discount, :shipping_charge, :tax, :total, :promotion_id, :payment_method, "pending", "pending")'
            );
            $stmt->execute([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'guest_name' => $customer['guest_name'] ?? null,
                'guest_email' => $customer['guest_email'] ?? null,
                'guest_phone' => $customer['guest_phone'] ?? null,
                'delivery_address' => $customer['delivery_address'],
                'delivery_city' => $customer['delivery_city'],
                'delivery_area' => $customer['delivery_area'] ?? null,
                'delivery_postal_code' => $customer['delivery_postal_code'] ?? null,
                'order_notes' => $customer['order_notes'] ?? null,
                'shipping_charge' => $shipping,
                'tax' => $tax,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'promotion_id' => $promotion['id'] ?? null,
                'payment_method' => $payment['method'],
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, sku, qty, unit_price, subtotal)
                 VALUES (:order_id, :product_id, :product_name, :sku, :qty, :unit_price, :subtotal)'
            );
            foreach ($resolvedLines as $line) {
                $itemStmt->execute(array_merge(['order_id' => $orderId], $line));
            }

            $this->db->prepare(
                'INSERT INTO order_status_history (order_id, status, note) VALUES (:order_id, "pending", "Order placed")'
            )->execute(['order_id' => $orderId]);

            if (!empty($payment['reference_no'])) {
                $this->db->prepare(
                    'INSERT INTO payment_transactions (order_id, method, amount, reference_no, status)
                     VALUES (:order_id, :method, :amount, :reference_no, "pending")'
                )->execute([
                    'order_id' => $orderId, 'method' => $payment['method'],
                    'amount' => $total, 'reference_no' => $payment['reference_no'],
                ]);
            }

            if ($promotion) {
                $promotionModel->recordUsage((int) $promotion['id'], $memberId, null, null, $orderId);
            }

            $this->db->commit();
            return ['id' => $orderId, 'order_no' => $orderNo];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, u.name AS account_name, u.email AS account_email, u.phone AS account_phone
             FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE o.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function findByOrderNo(string $orderNo): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, u.name AS account_name, u.email AS account_email, u.phone AS account_phone
             FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE o.order_no = :order_no LIMIT 1'
        );
        $stmt->execute(['order_no' => $orderNo]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function updateAdminNotes(int $id, string $notes): void
    {
        $this->db->prepare('UPDATE orders SET admin_notes = :notes WHERE id = :id')->execute(['notes' => $notes ?: null, 'id' => $id]);
    }

    /** @param array{status?:string,search?:string} $filters */
    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $joins = 'FROM orders o LEFT JOIN users u ON u.id = o.user_id';

        $countStmt = $this->db->prepare("SELECT COUNT(*) $joins" . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT o.*, COALESCE(u.name, o.guest_name) AS customer_name $joins" . $whereSql
            . ' ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page,
            'perPage' => $perPage, 'totalPages' => $totalPages,
        ];
    }

    public function paginateForUser(int $userId, int $page = 1, int $perPage = 10): array
    {
        return $this->paginateForAdmin(['user_id' => $userId], $page, $perPage);
    }

    public function statusCounts(): array
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status');
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['cnt'];
        }
        return $counts;
    }

    public function updateStatus(int $id, string $status, ?int $changedBy, ?string $note = null): void
    {
        $order = $this->find($id);
        if (!$order) {
            return;
        }

        $wasCancelled = in_array($order['status'], self::CANCELLED_STATUSES, true);
        $nowCancelled = in_array($status, self::CANCELLED_STATUSES, true);

        $this->db->prepare('UPDATE orders SET status = :status WHERE id = :id')->execute(['status' => $status, 'id' => $id]);
        $this->db->prepare(
            'INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (:order_id, :status, :note, :changed_by)'
        )->execute(['order_id' => $id, 'status' => $status, 'note' => $note, 'changed_by' => $changedBy]);

        if ($nowCancelled && !$wasCancelled) {
            $productModel = new Product();
            foreach ((new OrderItem())->forOrder($id) as $item) {
                $productModel->adjustStock((int) $item['product_id'], (int) $item['qty']);
            }
        }
    }

    public function updatePaymentStatus(int $id, string $status): void
    {
        $this->db->prepare('UPDATE orders SET payment_status = :status WHERE id = :id')->execute(['status' => $status, 'id' => $id]);
    }

    /** Dedicated refund action — records the event, flips payment_status, and logs it to the status timeline. */
    public function refund(int $id, float $amount, string $reason, ?int $refundedBy): void
    {
        $this->db->prepare(
            'INSERT INTO refunds (order_id, amount, reason, refunded_by) VALUES (:order_id, :amount, :reason, :refunded_by)'
        )->execute(['order_id' => $id, 'amount' => $amount, 'reason' => $reason, 'refunded_by' => $refundedBy]);

        $this->updatePaymentStatus($id, 'refunded');

        $this->db->prepare(
            'INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (:order_id, :status, :note, :changed_by)'
        )->execute([
            'order_id' => $id, 'status' => 'refunded',
            'note' => 'Refunded ' . money($amount) . ' — ' . $reason, 'changed_by' => $refundedBy,
        ]);
    }

    public function refundsForOrder(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.name AS refunded_by_name FROM refunds r LEFT JOIN users u ON u.id = r.refunded_by
             WHERE r.order_id = :order_id ORDER BY r.created_at DESC'
        );
        $stmt->execute(['order_id' => $id]);
        return $stmt->fetchAll();
    }

    /** Cascades to order_items/order_status_history/payment_transactions/refunds via ON DELETE CASCADE. */
    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM orders WHERE id = :id')->execute(['id' => $id]);
    }

    public function statusHistory(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT h.*, u.name AS changed_by_name FROM order_status_history h
             LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.order_id = :order_id ORDER BY h.created_at ASC'
        );
        $stmt->execute(['order_id' => $id]);
        return $stmt->fetchAll();
    }

    private function buildFilterClause(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'o.user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(o.order_no LIKE :search_no OR u.name LIKE :search_name OR o.guest_name LIKE :search_guest)';
            $params['search_no'] = '%' . $filters['search'] . '%';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_guest'] = '%' . $filters['search'] . '%';
        }

        return [$where, $params];
    }

    private function generateOrderNo(): string
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()');
        $seq = (int) $stmt->fetchColumn() + 1;
        return 'ORD-' . date('Ymd') . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
