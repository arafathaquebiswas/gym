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
}
