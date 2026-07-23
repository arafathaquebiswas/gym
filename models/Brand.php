<?php

final class Brand extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM brands ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM brands WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $brand = $stmt->fetch();
        return $brand ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM brands WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $brand = $stmt->fetch();
        return $brand ?: null;
    }

    /** All brands with a live product count, for the admin list. */
    public function allWithProductCount(): array
    {
        $stmt = $this->db->query(
            "SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id) AS product_count
             FROM brands b ORDER BY b.name ASC"
        );
        return $stmt->fetchAll();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM brands WHERE slug = :slug';
        $params = ['slug' => $slug];
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
        $stmt = $this->db->prepare(
            'INSERT INTO brands (name, slug, description, logo) VALUES (:name, :slug, :description, :logo)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?: null,
            'logo' => $data['logo'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip([
            'name', 'slug', 'description', 'logo',
            'offer_enabled', 'offer_percent', 'offer_start_date', 'offer_end_date',
        ]));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE brands SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    /** Blocked at the controller layer if products still reference this brand (FK is ON DELETE SET NULL, so this is a soft guard, not a hard DB constraint). */
    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM brands WHERE id = :id')->execute(['id' => $id]);
    }

    public function productCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE brand_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
