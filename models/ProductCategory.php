<?php

final class ProductCategory extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM product_categories ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    /** All categories with their parent's name attached, top-level first then children — for the admin picker. */
    public function allWithParent(): array
    {
        $stmt = $this->db->query(
            "SELECT c.*, p.name AS parent_name,
                    (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count
             FROM product_categories c
             LEFT JOIN product_categories p ON p.id = c.parent_id
             ORDER BY COALESCE(p.name, c.name) ASC, c.parent_id IS NULL DESC, c.name ASC"
        );
        return $stmt->fetchAll();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM product_categories WHERE slug = :slug';
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
            'INSERT INTO product_categories (parent_id, name, slug, description, image) VALUES (:parent_id, :name, :slug, :description, :image)'
        );
        $stmt->execute([
            'parent_id' => $data['parent_id'] ?: null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?: null,
            'image' => $data['image'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(['parent_id', 'name', 'slug', 'description', 'image']));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE product_categories SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    /** Blocked at the controller layer if products still reference this category (FK is ON DELETE RESTRICT). */
    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM product_categories WHERE id = :id')->execute(['id' => $id]);
    }

    public function productCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
