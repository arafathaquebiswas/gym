<?php

final class Product extends Model
{
    public function paginate(int $page, int $perPage, ?string $categorySlug = null, ?string $search = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ['p.is_active = 1'];
        $params = [];

        if ($categorySlug) {
            $where[] = 'c.slug = :category_slug';
            $params['category_slug'] = $categorySlug;
        }
        if ($search) {
            $where[] = '(p.name LIKE :search OR p.brand LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM products p JOIN product_categories c ON c.id = p.category_id WHERE $whereSql"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN product_categories c ON c.id = p.category_id
             WHERE $whereSql
             ORDER BY p.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN product_categories c ON c.id = p.category_id
             WHERE p.slug = :slug AND p.is_active = 1 LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    public function featured(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
