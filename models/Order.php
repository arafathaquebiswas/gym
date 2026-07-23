<?php

final class Order extends Model
{
    private const CANCELLED_STATUSES = ['cancelled', 'returned'];

    /**
     * @param array<int, array{product_id:int,qty:int}> $cartLines
     * @param array{fulfillment_method?:string,zone_id?:?int,time_slot_id?:?int,guest_name:?string,guest_email:?string,guest_phone:?string,delivery_address:?string,delivery_city:?string,delivery_area:?string,delivery_postal_code:?string,order_notes:?string} $customer
     * @param array{method:string,discount:float,couponCode:?string,reference_no:?string,payer_number?:?string} $payment
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
            $lineMeta = [];
            $stockChanges = [];
            $subtotal = 0.0;
            $maxShippingOverride = null;

            foreach ($cartLines as $line) {
                $product = $productModel->find((int) $line['product_id']);
                if (!$product || $product['status'] !== 'published') {
                    throw new RuntimeException('One of the items in your cart is no longer available.');
                }
                $qty = (int) $line['qty'];
                if ($qty <= 0) {
                    continue;
                }
                if ($product['shipping_charge'] !== null && $product['shipping_charge'] !== '') {
                    $maxShippingOverride = max($maxShippingOverride ?? 0.0, (float) $product['shipping_charge']);
                }

                if ((int) $product['stock_qty'] >= $qty) {
                    $productModel->decrementStockIfAvailable((int) $product['id'], $qty);
                    $stockChanges[] = ['product_id' => (int) $product['id'], 'qty' => $qty, 'note' => 'Order placed'];
                    LowStockAlerter::checkAndNotify($product, (int) $product['stock_qty'] - $qty);
                } elseif ((int) $product['allow_preorder'] === 1 && Feature::on('preorder')) {
                    $productModel->adjustStock((int) $product['id'], -$qty); // may go negative — backorder demand
                    $stockChanges[] = ['product_id' => (int) $product['id'], 'qty' => $qty, 'note' => 'Pre-order / backorder'];
                    LowStockAlerter::checkAndNotify($product, (int) $product['stock_qty'] - $qty);
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
                // Parallel to $resolvedLines (same index) — kept separate so these extra keys
                // never reach the order_items INSERT (real prepared statements reject unknown params).
                $lineMeta[] = [
                    'had_offer' => (bool) $product['offer_is_live'],
                    'regular_price' => (float) $product['selling_price'],
                    'bogo_enabled' => (bool) $product['bogo_enabled'],
                    'discount_source' => $product['offer_is_live'] ? $product['discount_source'] : null,
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

            $settingModel = new Setting();
            $discount = (float) ($payment['discount'] ?? 0);
            $promotionModel = new Promotion();
            $promotion = !empty($payment['couponCode'])
                ? $promotionModel->validCoupon($payment['couponCode'], $subtotal, 'product', $memberId, $guestEmail)
                : null;
            if (!empty($payment['couponCode']) && !$promotion) {
                throw new RuntimeException('That coupon code is invalid, expired, or no longer applicable.');
            }

            // Priority rule: Coupon > Flash Sale > Product Offer > Category Offer > Regular Price.
            // A valid coupon outranks every item-level offer tier, so — unless the admin has
            // explicitly enabled stacking — any line currently riding a flash/product/category/
            // brand offer reverts to its regular price before the coupon discount is computed.
            // BOGO and Bundle savings are a separate, quantity-based mechanic (not a price tier)
            // and always apply regardless of this setting.
            if ($promotion && !$settingModel->getBool('discount_stacking_enabled', false)) {
                $subtotal = 0.0;
                foreach ($resolvedLines as $i => &$rl) {
                    if ($lineMeta[$i]['had_offer']) {
                        $rl['unit_price'] = $lineMeta[$i]['regular_price'];
                        $rl['subtotal'] = round($lineMeta[$i]['regular_price'] * $rl['qty'], 2);
                        $lineMeta[$i]['discount_source'] = null; // reverted to regular price — the coupon is what actually discounted this order.
                    }
                    $subtotal += $rl['subtotal'];
                }
                unset($rl);
            }

            if ($promotion) {
                $discount += $promotionModel->computeDiscount($promotion, $subtotal);
            }

            $bogoDiscount = 0.0;
            foreach ($resolvedLines as $i => $rl) {
                if ($lineMeta[$i]['bogo_enabled']) {
                    $freeUnits = intdiv($rl['qty'], 2);
                    $bogoDiscount += $freeUnits * $rl['unit_price'];
                }
            }
            $discount += round($bogoDiscount, 2);

            $bundleDiscount = 0.0;
            foreach ((new Bundle())->matchFor($cartLines) as $match) {
                $bundleDiscount += $match['savings'];
            }
            $discount += round($bundleDiscount, 2);

            $netAfterDiscount = max(0, $subtotal - $discount);
            $fulfillmentMethod = $customer['fulfillment_method'] ?? 'delivery';

            $shipping = 0.0;
            if ($fulfillmentMethod !== 'pickup' && $settingModel->getBool('shipping_enabled')) {
                $freeShippingMin = (float) $settingModel->get('free_shipping_min_amount', '0');
                if ($freeShippingMin > 0 && $netAfterDiscount >= $freeShippingMin) {
                    $shipping = 0.0;
                } else {
                    // A selected delivery zone's charge takes priority over the flat rate; falls back
                    // to the flat rate when no zone is selected (e.g. no zones configured yet).
                    $baseRate = null;
                    if (!empty($customer['zone_id'])) {
                        $zone = (new DeliveryZone())->find((int) $customer['zone_id']);
                        if ($zone && $zone['is_active']) {
                            $baseRate = (float) $zone['charge'];
                        }
                    }
                    if ($baseRate === null) {
                        $baseRate = (float) $settingModel->get('shipping_flat_rate', '0');
                    }
                    $shipping = $maxShippingOverride !== null ? max($baseRate, $maxShippingOverride) : $baseRate;
                }
            }

            $pickupPin = $fulfillmentMethod === 'pickup' ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT) : null;

            $tax = 0.0;
            if ($settingModel->getBool('tax_enabled')) {
                $taxPercent = (float) $settingModel->get('tax_percent', '0');
                $tax = round($netAfterDiscount * ($taxPercent / 100), 2);
            }

            $total = max(0, round($netAfterDiscount + $shipping + $tax, 2));
            $orderNo = $this->generateOrderNo();

            $stmt = $this->db->prepare(
                'INSERT INTO orders (order_no, user_id, fulfillment_method, zone_id, time_slot_id, pickup_pin,
                    guest_name, guest_email, guest_phone,
                    delivery_address, delivery_city, delivery_area, delivery_postal_code, order_notes,
                    subtotal, discount, shipping_charge, tax, total, promotion_id, payment_method, payment_status, status)
                 VALUES (:order_no, :user_id, :fulfillment_method, :zone_id, :time_slot_id, :pickup_pin,
                    :guest_name, :guest_email, :guest_phone,
                    :delivery_address, :delivery_city, :delivery_area, :delivery_postal_code, :order_notes,
                    :subtotal, :discount, :shipping_charge, :tax, :total, :promotion_id, :payment_method, "pending", "pending")'
            );
            $stmt->execute([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'fulfillment_method' => $fulfillmentMethod,
                'zone_id' => $fulfillmentMethod === 'delivery' ? ($customer['zone_id'] ?? null) : null,
                'time_slot_id' => $customer['time_slot_id'] ?? null,
                'pickup_pin' => $pickupPin,
                'guest_name' => $customer['guest_name'] ?? null,
                'guest_email' => $customer['guest_email'] ?? null,
                'guest_phone' => $customer['guest_phone'] ?? null,
                'delivery_address' => $fulfillmentMethod === 'pickup' ? null : $customer['delivery_address'],
                'delivery_city' => $fulfillmentMethod === 'pickup' ? null : $customer['delivery_city'],
                'delivery_area' => $fulfillmentMethod === 'pickup' ? null : ($customer['delivery_area'] ?? null),
                'delivery_postal_code' => $fulfillmentMethod === 'pickup' ? null : ($customer['delivery_postal_code'] ?? null),
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
                'INSERT INTO order_items (order_id, product_id, product_name, sku, qty, unit_price, subtotal, discount_source)
                 VALUES (:order_id, :product_id, :product_name, :sku, :qty, :unit_price, :subtotal, :discount_source)'
            );
            foreach ($resolvedLines as $i => $line) {
                $itemStmt->execute(array_merge(['order_id' => $orderId, 'discount_source' => $lineMeta[$i]['discount_source']], $line));
            }

            $stockMovementModel = new StockMovement();
            foreach ($stockChanges as $change) {
                $stockMovementModel->record($change['product_id'], -$change['qty'], 'order', $orderId, $change['note']);
            }

            $this->db->prepare(
                'INSERT INTO order_status_history (order_id, status, note) VALUES (:order_id, "pending", "Order placed")'
            )->execute(['order_id' => $orderId]);

            if (!empty($payment['reference_no'])) {
                $this->db->prepare(
                    'INSERT INTO payment_transactions (order_id, method, amount, reference_no, payer_number, status)
                     VALUES (:order_id, :method, :amount, :reference_no, :payer_number, "pending")'
                )->execute([
                    'order_id' => $orderId, 'method' => $payment['method'],
                    'amount' => $total, 'reference_no' => $payment['reference_no'],
                    'payer_number' => $payment['payer_number'] ?? null,
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

    private const BASE_SELECT = "SELECT o.*, u.name AS account_name, u.email AS account_email, u.phone AS account_phone,
             z.name AS zone_name, ts.label AS time_slot_label, dp.name AS delivery_person_name, dp.phone AS delivery_person_phone
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             LEFT JOIN delivery_zones z ON z.id = o.zone_id
             LEFT JOIN delivery_time_slots ts ON ts.id = o.time_slot_id
             LEFT JOIN users dp ON dp.id = o.delivery_person_id";

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::BASE_SELECT . ' WHERE o.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function findByOrderNo(string $orderNo): ?array
    {
        $stmt = $this->db->prepare(self::BASE_SELECT . ' WHERE o.order_no = :order_no LIMIT 1');
        $stmt->execute(['order_no' => $orderNo]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    /** Orders assigned to a given delivery person, for the Delivery Dashboard. */
    public function forDeliveryPerson(int $deliveryPersonId): array
    {
        $stmt = $this->db->prepare(
            self::BASE_SELECT . " WHERE o.delivery_person_id = :delivery_person_id
             AND o.status NOT IN ('cancelled', 'returned')
             ORDER BY (o.status = 'delivered') ASC, o.created_at ASC"
        );
        $stmt->execute(['delivery_person_id' => $deliveryPersonId]);
        return $stmt->fetchAll();
    }

    /** Completed (delivered/returned) deliveries for this driver — the Delivery History page, separate from their active worklist. */
    public function forDeliveryPersonHistory(int $deliveryPersonId, int $page = 1, int $perPage = 20): array
    {
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM orders WHERE delivery_person_id = :delivery_person_id AND status IN ('delivered', 'returned')"
        );
        $countStmt->execute(['delivery_person_id' => $deliveryPersonId]);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            self::BASE_SELECT . " WHERE o.delivery_person_id = :delivery_person_id
             AND o.status IN ('delivered', 'returned')
             ORDER BY o.updated_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':delivery_person_id', $deliveryPersonId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        // Attach the note recorded at the moment this order reached its current (terminal) status.
        foreach ($orders as &$order) {
            $order['delivery_note'] = null;
            foreach (array_reverse($this->statusHistory((int) $order['id'])) as $entry) {
                if ($entry['status'] === $order['status']) {
                    $order['delivery_note'] = $entry['note'];
                    break;
                }
            }
        }
        unset($order);

        return ['items' => $orders, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => $totalPages];
    }

    /** Today's workload snapshot for a driver's dashboard: how many new orders came in for them today, and how many they've completed today. */
    public function todaysStatsForDeliveryPerson(int $deliveryPersonId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM orders WHERE delivery_person_id = :id AND DATE(created_at) = CURDATE()'
        );
        $stmt->execute(['id' => $deliveryPersonId]);
        $assignedToday = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM order_status_history h
             JOIN orders o ON o.id = h.order_id
             WHERE o.delivery_person_id = :id AND h.status = 'delivered' AND h.changed_by = :id2 AND DATE(h.created_at) = CURDATE()"
        );
        $stmt->execute(['id' => $deliveryPersonId, 'id2' => $deliveryPersonId]);
        $deliveredToday = (int) $stmt->fetchColumn();

        return ['assignedToday' => $assignedToday, 'deliveredToday' => $deliveredToday];
    }

    /** Completed deliveries within [start, end] (inclusive) by this driver — used for the optional earnings estimate. */
    public function completedCountForDeliveryPersonInRange(int $deliveryPersonId, string $start, string $end): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT h.order_id) FROM order_status_history h
             JOIN orders o ON o.id = h.order_id
             WHERE o.delivery_person_id = :id AND h.status = 'delivered' AND h.changed_by = :id2
               AND DATE(h.created_at) BETWEEN :start AND :end"
        );
        $stmt->execute(['id' => $deliveryPersonId, 'id2' => $deliveryPersonId, 'start' => $start, 'end' => $end]);
        return (int) $stmt->fetchColumn();
    }

    public function assignDeliveryPerson(int $id, ?int $deliveryPersonId): void
    {
        $this->db->prepare('UPDATE orders SET delivery_person_id = :delivery_person_id WHERE id = :id')
            ->execute(['delivery_person_id' => $deliveryPersonId, 'id' => $id]);
    }

    /** Verifies the customer-supplied PIN against the order's stored pickup_pin. Does not change status itself. */
    public function pinMatches(array $order, string $pin): bool
    {
        return !empty($order['pickup_pin']) && hash_equals((string) $order['pickup_pin'], trim($pin));
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
            $stockMovementModel = new StockMovement();
            $movementType = $status === 'returned' ? 'return' : 'order';
            $note = $status === 'returned' ? 'Order returned — stock restored' : 'Order cancelled — stock restored';

            foreach ((new OrderItem())->forOrder($id) as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['qty'];
                $before = $productModel->find($productId);

                $productModel->adjustStock($productId, $qty);
                $stockMovementModel->record($productId, $qty, $movementType, $id, $note, $changedBy);

                if ($before && (int) $before['stock_qty'] <= 0 && (new Setting())->getBool('auto_email_notifications')) {
                    StockNotifier::notifyBackInStock($before);
                }
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

    /** The same customer's other orders — by account if registered, else by guest email — for the admin order page. */
    public function historyForCustomer(array $order, int $excludeId, int $limit = 10): array
    {
        if (!empty($order['user_id'])) {
            $stmt = $this->db->prepare(
                'SELECT id, order_no, created_at, total, status FROM orders
                 WHERE user_id = :user_id AND id != :exclude_id
                 ORDER BY created_at DESC LIMIT :limit'
            );
            $stmt->bindValue(':user_id', (int) $order['user_id'], PDO::PARAM_INT);
        } elseif (!empty($order['guest_email'])) {
            $stmt = $this->db->prepare(
                'SELECT id, order_no, created_at, total, status FROM orders
                 WHERE user_id IS NULL AND guest_email = :guest_email AND id != :exclude_id
                 ORDER BY created_at DESC LIMIT :limit'
            );
            $stmt->bindValue(':guest_email', $order['guest_email']);
        } else {
            return [];
        }

        $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
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
