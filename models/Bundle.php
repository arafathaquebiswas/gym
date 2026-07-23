<?php

/** A curated set of products sold together at a fixed price — detected automatically when the cart contains the full set, not a separate SKU. */
final class Bundle extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM bundles ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function allActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM bundles
             WHERE is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at > NOW())
             ORDER BY created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM bundles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $bundle = $stmt->fetch();
        return $bundle ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM bundles WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $bundle = $stmt->fetch();
        return $bundle ?: null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM bundles WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Bundle items joined with live product info, for display and for computing regular-price savings. */
    public function itemsFor(int $bundleId): array
    {
        $stmt = $this->db->prepare(
            'SELECT bi.*, p.name AS product_name, p.slug AS product_slug, p.selling_price, p.image
             FROM bundle_items bi JOIN products p ON p.id = bi.product_id
             WHERE bi.bundle_id = :bundle_id'
        );
        $stmt->execute(['bundle_id' => $bundleId]);
        return $stmt->fetchAll();
    }

    /**
     * Which active bundles are fully satisfied by the given cart lines (each bundle's every
     * item present at >= its required qty). Returns each matched bundle with its computed
     * savings (sum of component regular prices at the required qty, minus the bundle price).
     *
     * @param array<int, array{product_id:int, qty:int}> $cartLines
     */
    public function matchFor(array $cartLines): array
    {
        $cartQty = [];
        foreach ($cartLines as $line) {
            $cartQty[(int) $line['product_id']] = ($cartQty[(int) $line['product_id']] ?? 0) + (int) $line['qty'];
        }

        $matches = [];
        foreach ($this->allActive() as $bundle) {
            $items = $this->itemsFor((int) $bundle['id']);
            if (!$items) {
                continue;
            }

            $regularTotal = 0.0;
            $satisfied = true;
            foreach ($items as $item) {
                if (($cartQty[(int) $item['product_id']] ?? 0) < (int) $item['qty']) {
                    $satisfied = false;
                    break;
                }
                $regularTotal += (float) $item['selling_price'] * (int) $item['qty'];
            }

            if ($satisfied) {
                $savings = round($regularTotal - (float) $bundle['bundle_price'], 2);
                if ($savings > 0) {
                    $matches[] = ['bundle' => $bundle, 'items' => $items, 'savings' => $savings];
                }
            }
        }

        return $matches;
    }

    /** @param array<int, array{product_id:int, qty:int}> $items */
    public function create(array $data, array $items): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO bundles (name, slug, bundle_price, image, is_active, starts_at, ends_at)
                 VALUES (:name, :slug, :bundle_price, :image, :is_active, :starts_at, :ends_at)'
            );
            $stmt->execute([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'bundle_price' => $data['bundle_price'],
                'image' => $data['image'] ?? null,
                'is_active' => $data['is_active'] ?? 1,
                'starts_at' => $data['starts_at'] ?: null,
                'ends_at' => $data['ends_at'] ?: null,
            ]);
            $bundleId = (int) $this->db->lastInsertId();

            $this->replaceItems($bundleId, $items);

            $this->db->commit();
            return $bundleId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** @param array<int, array{product_id:int, qty:int}> $items */
    public function update(int $id, array $data, ?array $items = null): void
    {
        $fields = array_intersect_key($data, array_flip(['name', 'bundle_price', 'image', 'is_active', 'starts_at', 'ends_at']));
        if ($fields) {
            $fields['starts_at'] = $fields['starts_at'] ?: null;
            $fields['ends_at'] = $fields['ends_at'] ?: null;
            $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
            $fields['id'] = $id;
            $this->db->prepare("UPDATE bundles SET $set WHERE id = :id")->execute($fields);
        }

        if ($items !== null) {
            $this->replaceItems($id, $items);
        }
    }

    public function toggleActive(int $id): void
    {
        $this->db->prepare('UPDATE bundles SET is_active = NOT is_active WHERE id = :id')->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM bundles WHERE id = :id')->execute(['id' => $id]);
    }

    /** @param array<int, array{product_id:int, qty:int}> $items */
    private function replaceItems(int $bundleId, array $items): void
    {
        $this->db->prepare('DELETE FROM bundle_items WHERE bundle_id = :bundle_id')->execute(['bundle_id' => $bundleId]);

        $stmt = $this->db->prepare('INSERT INTO bundle_items (bundle_id, product_id, qty) VALUES (:bundle_id, :product_id, :qty)');
        foreach ($items as $item) {
            if ((int) $item['product_id'] <= 0 || (int) $item['qty'] <= 0) {
                continue;
            }
            $stmt->execute(['bundle_id' => $bundleId, 'product_id' => $item['product_id'], 'qty' => $item['qty']]);
        }
    }
}
