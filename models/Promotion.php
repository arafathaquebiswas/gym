<?php

final class Promotion extends Model
{
    private const WRITABLE_FIELDS = [
        'title', 'code', 'description', 'discount_type', 'discount_value', 'max_discount_amount',
        'applies_to', 'min_purchase', 'usage_limit', 'per_customer_limit',
        'start_date', 'end_date', 'is_active',
    ];

    public function active(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM promotions
             WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date
             ORDER BY end_date ASC'
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM promotions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $promo = $stmt->fetch();
        return $promo ?: null;
    }

    /** @param array{search?:string,status?:string} $filters */
    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(code LIKE :search_code OR title LIKE :search_title)';
            $params['search_code'] = '%' . $filters['search'] . '%';
            $params['search_title'] = '%' . $filters['search'] . '%';
        }
        if (($filters['status'] ?? '') === 'active') {
            $where[] = 'is_active = 1';
        } elseif (($filters['status'] ?? '') === 'inactive') {
            $where[] = 'is_active = 0';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM promotions' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            'SELECT * FROM promotions' . $whereSql . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
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

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM promotions WHERE code = :code';
        $params = ['code' => $code];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        $columns = array_keys($fields);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);

        $stmt = $this->db->prepare(
            'INSERT INTO promotions (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($fields);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $this->db->prepare("UPDATE promotions SET $set WHERE id = :id")->execute($fields);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM promotions WHERE id = :id')->execute(['id' => $id]);
    }

    public function toggleActive(int $id): void
    {
        $this->db->prepare('UPDATE promotions SET is_active = NOT is_active WHERE id = :id')->execute(['id' => $id]);
    }

    /** Clones a coupon with a fresh code, reset usage, and left inactive so it doesn't collide until reviewed. */
    public function duplicate(int $id): ?int
    {
        $promo = $this->find($id);
        if (!$promo) {
            return null;
        }

        $baseCode = $promo['code'] ?? 'COUPON';
        $newCode = $baseCode . '-COPY';
        $i = 2;
        while ($this->codeExists($newCode)) {
            $newCode = $baseCode . '-COPY' . $i++;
        }

        return $this->create([
            'title' => $promo['title'] . ' (Copy)',
            'code' => $newCode,
            'description' => $promo['description'],
            'discount_type' => $promo['discount_type'],
            'discount_value' => $promo['discount_value'],
            'max_discount_amount' => $promo['max_discount_amount'],
            'applies_to' => $promo['applies_to'],
            'min_purchase' => $promo['min_purchase'],
            'usage_limit' => $promo['usage_limit'],
            'per_customer_limit' => $promo['per_customer_limit'],
            'start_date' => $promo['start_date'],
            'end_date' => $promo['end_date'],
            'is_active' => 0,
        ]);
    }

    /**
     * Shared cart-discount coupon validation, used by POS (models/Sale.php), the online
     * storefront (models/Order.php), and membership renewals (models/Member.php via
     * MemberAdminController). Only percent/fixed discount types apply as a simple
     * cart-level discount — bogo/free_item coupons need item substitution logic that
     * none of these checkout flows implement.
     *
     * $context is 'product' or 'membership' — matched against applies_to alongside 'both'.
     * $memberId/$guestEmail (whichever is known) enforce per_customer_limit if set.
     */
    public function validCoupon(string $code, float $subtotal, string $context = 'product', ?int $memberId = null, ?string $guestEmail = null): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM promotions
             WHERE code = :code AND is_active = 1
               AND applies_to IN (:context, 'both')
               AND start_date <= CURDATE() AND end_date >= CURDATE()
               AND (usage_limit IS NULL OR used_count < usage_limit)
             LIMIT 1"
        );
        $stmt->execute(['code' => $code, 'context' => $context]);
        $promo = $stmt->fetch();

        if (!$promo || $subtotal < (float) $promo['min_purchase']) {
            return null;
        }
        if (!in_array($promo['discount_type'], ['percent', 'fixed'], true)) {
            return null;
        }
        if ($promo['per_customer_limit'] !== null && ($memberId !== null || $guestEmail !== null)) {
            $used = $this->customerUsageCount((int) $promo['id'], $memberId, $guestEmail);
            if ($used >= (int) $promo['per_customer_limit']) {
                return null;
            }
        }

        return $promo;
    }

    public function computeDiscount(array $promo, float $subtotal): float
    {
        $discount = $promo['discount_type'] === 'percent'
            ? round($subtotal * ((float) $promo['discount_value'] / 100), 2)
            : min($subtotal, (float) $promo['discount_value']);

        if (!empty($promo['max_discount_amount'])) {
            $discount = min($discount, (float) $promo['max_discount_amount']);
        }

        return $discount;
    }

    /** How many times this specific customer has already used this coupon (POS sale via member_id, or online order via guest_email). */
    public function customerUsageCount(int $promotionId, ?int $memberId, ?string $guestEmail): int
    {
        if ($memberId !== null) {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM coupon_usages WHERE promotion_id = :pid AND member_id = :member_id'
            );
            $stmt->execute(['pid' => $promotionId, 'member_id' => $memberId]);
            return (int) $stmt->fetchColumn();
        }
        if ($guestEmail !== null) {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM coupon_usages cu JOIN orders o ON o.id = cu.order_id
                 WHERE cu.promotion_id = :pid AND o.guest_email = :email'
            );
            $stmt->execute(['pid' => $promotionId, 'email' => $guestEmail]);
            return (int) $stmt->fetchColumn();
        }
        return 0;
    }

    public function recordUsage(int $promotionId, ?int $memberId, ?int $saleId, ?int $subscriptionId = null, ?int $orderId = null): void
    {
        $this->db->prepare('UPDATE promotions SET used_count = used_count + 1 WHERE id = :id')->execute(['id' => $promotionId]);
        $this->db->prepare(
            'INSERT INTO coupon_usages (promotion_id, member_id, sale_id, subscription_id, order_id, used_at)
             VALUES (:promotion_id, :member_id, :sale_id, :subscription_id, :order_id, NOW())'
        )->execute([
            'promotion_id' => $promotionId, 'member_id' => $memberId, 'sale_id' => $saleId,
            'subscription_id' => $subscriptionId, 'order_id' => $orderId,
        ]);
    }
}
