<?php

final class FlashSale extends Model
{
    /** Per-request cache — a store listing page resolves this for every product, so avoid re-querying each time. */
    private static ?array $activeCache = null;

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM flash_sales ORDER BY starts_at DESC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM flash_sales WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $sale = $stmt->fetch();
        return $sale ?: null;
    }

    /** Currently-active (is_active=1 and within the time window) flash sales, most specific scope first. */
    private function active(): array
    {
        if (self::$activeCache !== null) {
            return self::$activeCache;
        }

        $stmt = $this->db->query(
            "SELECT * FROM flash_sales
             WHERE is_active = 1 AND NOW() BETWEEN starts_at AND ends_at
             ORDER BY FIELD(scope, 'product', 'category', 'brand', 'all')"
        );
        self::$activeCache = $stmt->fetchAll();
        return self::$activeCache;
    }

    /** The best-matching live flash sale for this product (product-specific wins over category/brand/site-wide), or null. */
    public function liveForProduct(array $product): ?array
    {
        foreach ($this->active() as $sale) {
            $matches = match ($sale['scope']) {
                'all' => true,
                'product' => (int) $sale['scope_id'] === (int) ($product['id'] ?? 0),
                'category' => !empty($product['category_id']) && (int) $sale['scope_id'] === (int) $product['category_id'],
                'brand' => !empty($product['brand_id']) && (int) $sale['scope_id'] === (int) $product['brand_id'],
                default => false,
            };
            if ($matches) {
                return $sale;
            }
        }
        return null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO flash_sales (name, discount_percent, scope, scope_id, starts_at, ends_at, is_active)
             VALUES (:name, :discount_percent, :scope, :scope_id, :starts_at, :ends_at, :is_active)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'discount_percent' => $data['discount_percent'],
            'scope' => $data['scope'],
            'scope_id' => $data['scope'] === 'all' ? null : $data['scope_id'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => $data['is_active'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(['name', 'discount_percent', 'scope', 'scope_id', 'starts_at', 'ends_at', 'is_active']));
        if (!$fields) {
            return;
        }
        if (($fields['scope'] ?? null) === 'all') {
            $fields['scope_id'] = null;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE flash_sales SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    public function toggleActive(int $id): void
    {
        $this->db->prepare('UPDATE flash_sales SET is_active = NOT is_active WHERE id = :id')->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM flash_sales WHERE id = :id')->execute(['id' => $id]);
    }
}
