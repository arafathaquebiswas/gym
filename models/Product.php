<?php

final class Product extends Model
{
    private const WRITABLE_FIELDS = [
        'category_id', 'supplier_id', 'sku', 'barcode', 'name', 'slug', 'brand', 'description',
        'buying_price', 'selling_price', 'stock_qty', 'min_stock', 'expiry_date', 'image',
        'offer_price', 'offer_enabled', 'offer_start_date', 'offer_end_date', 'is_active',
        'ingredients', 'nutrition_facts', 'allow_preorder',
    ];

    public function paginate(int $page, int $perPage, ?string $categorySlug = null, ?string $search = null, bool $inStockOnly = false, ?string $sort = null): array
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
            $where[] = '(p.name LIKE :search_name OR p.brand LIKE :search_brand)';
            $params['search_name'] = '%' . $search . '%';
            $params['search_brand'] = '%' . $search . '%';
        }
        if ($inStockOnly) {
            $where[] = 'p.stock_qty > 0';
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM products p JOIN product_categories c ON c.id = p.category_id WHERE $whereSql"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sortMap = [
            'price_low' => 'p.selling_price ASC',
            'price_high' => 'p.selling_price DESC',
            'newest' => 'p.created_at DESC',
        ];
        $orderBy = $sortMap[$sort ?? ''] ?? 'p.created_at DESC';

        $stmt = $this->db->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN product_categories c ON c.id = p.category_id
             WHERE $whereSql
             ORDER BY $orderBy
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => array_map([$this, 'withComputedOffer'], $stmt->fetchAll()), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
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
        return $product ? $this->withComputedOffer($product) : null;
    }

    public function featured(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    /** All sellable products for the POS screen's client-side search/cart. */
    public function allActiveInStock(): array
    {
        $stmt = $this->db->query(
            'SELECT p.id, p.name, p.sku, p.barcode, p.selling_price, p.offer_price, p.offer_enabled,
                    p.offer_start_date, p.offer_end_date, p.stock_qty, c.name AS category_name
             FROM products p JOIN product_categories c ON c.id = p.category_id
             WHERE p.is_active = 1 AND p.stock_qty > 0
             ORDER BY p.name ASC'
        );
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    /** Top sellers by combined online + in-store quantity over the last 90 days. */
    public function bestSellerIds(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT product_id, SUM(qty) AS total_qty FROM (
                SELECT oi.product_id, oi.qty FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                UNION ALL
                SELECT si.product_id, si.qty FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                WHERE s.sale_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            ) combined
            GROUP BY product_id ORDER BY total_qty DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', array_column($stmt->fetchAll(), 'product_id'));
    }

    /** Top sellers by combined online + in-store quantity over the last 90 days, with names attached (dashboard tile). */
    public function topSellingForDashboard(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, SUM(combined.qty) AS total_qty FROM (
                SELECT oi.product_id, oi.qty FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                UNION ALL
                SELECT si.product_id, si.qty FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                WHERE s.sale_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            ) combined
            JOIN products p ON p.id = combined.product_id
            GROUP BY p.id, p.name ORDER BY total_qty DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Most-wishlisted products — a real "popular" signal instead of an invented metric. */
    public function popularIds(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT product_id, COUNT(*) AS cnt FROM wishlist GROUP BY product_id ORDER BY cnt DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', array_column($stmt->fetchAll(), 'product_id'));
    }

    public function relatedProducts(int $productId, int $categoryId, int $limit = 4): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM products
             WHERE category_id = :category_id AND id != :product_id AND is_active = 1
             ORDER BY RAND() LIMIT :limit'
        );
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name FROM products p
             JOIN product_categories c ON c.id = p.category_id
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
        return $product ? $this->withComputedOffer($product) : null;
    }

    /**
     * @param array{search?:string,category_id?:string,low_stock?:string,sort?:string} $filters
     */
    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM products p JOIN product_categories c ON c.id = p.category_id' . $whereSql
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT p.*, c.name AS category_name FROM products p
                JOIN product_categories c ON c.id = p.category_id' . $whereSql
            . ' ORDER BY ' . $this->sortClause($filters['sort'] ?? '')
            . ' LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map([$this, 'withComputedOffer'], $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function adminStatistics(): array
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(stock_qty <= min_stock) AS low_stock,
                    SUM(stock_qty * buying_price) AS stock_value
             FROM products WHERE is_active = 1"
        );
        $row = $stmt->fetch();
        return [
            'total' => (int) ($row['total'] ?? 0),
            'lowStock' => (int) ($row['low_stock'] ?? 0),
            'stockValue' => (float) ($row['stock_value'] ?? 0),
        ];
    }

    public function lowStock(): array
    {
        $stmt = $this->db->query(
            'SELECT p.*, c.name AS category_name FROM products p
             JOIN product_categories c ON c.id = p.category_id
             WHERE p.stock_qty <= p.min_stock AND p.is_active = 1
             ORDER BY p.stock_qty ASC'
        );
        return $stmt->fetchAll();
    }

    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM products WHERE sku = :sku';
        $params = ['sku' => $sku];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM products WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Ported from Package::validateOfferPrice() — same rule, different table. */
    public function validateOfferPrice(?float $regularPrice, ?float $offerPrice): ?string
    {
        if ($offerPrice === null) {
            return null;
        }
        if ($regularPrice === null || $offerPrice >= $regularPrice) {
            return 'Offer price must be lower than the selling price.';
        }
        if ($offerPrice <= 0) {
            return 'Offer price must be greater than zero.';
        }
        return null;
    }

    public function create(array $data): int
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        $columns = array_keys($fields);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);

        $stmt = $this->db->prepare(
            'INSERT INTO products (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
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

        $stmt = $this->db->prepare("UPDATE products SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM products WHERE id = :id')->execute(['id' => $id]);
    }

    public function toggleActive(int $id): void
    {
        $this->db->prepare('UPDATE products SET is_active = NOT is_active WHERE id = :id')->execute(['id' => $id]);
    }

    /** Manual stock correction (damage, stock-take, etc) — sales/purchases adjust stock via DB triggers instead. */
    public function adjustStock(int $id, int $delta): void
    {
        $this->db->prepare('UPDATE products SET stock_qty = stock_qty + :delta WHERE id = :id')
            ->execute(['delta' => $delta, 'id' => $id]);
    }

    public function decrementStockIfAvailable(int $id, int $qty): bool
    {
        $stmt = $this->db->prepare('UPDATE products SET stock_qty = stock_qty - :qty WHERE id = :id AND stock_qty >= :qty2');
        $stmt->execute(['qty' => $qty, 'id' => $id, 'qty2' => $qty]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Ported from Package::withComputedOffer() — same live-offer-window logic, different table/columns.
     * Public so callers holding a raw product row from elsewhere (e.g. Cart::forIdentity()'s join) can
     * compute the same display price without duplicating this logic.
     */
    public function withComputedOffer(array $product): array
    {
        $now = new DateTimeImmutable();
        $startOk = empty($product['offer_start_date']) || new DateTimeImmutable($product['offer_start_date']) <= $now;
        $endOk = empty($product['offer_end_date']) || new DateTimeImmutable($product['offer_end_date']) > $now;

        $isLive = (bool) $product['offer_enabled'] && !empty($product['offer_price']) && $startOk && $endOk;

        $product['offer_is_live'] = $isLive;
        $product['display_price'] = $isLive ? $product['offer_price'] : $product['selling_price'];

        return $product;
    }

    private function buildFilterClause(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE :search_name OR p.sku LIKE :search_sku OR p.barcode LIKE :search_barcode)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_sku'] = '%' . $filters['search'] . '%';
            $params['search_barcode'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        if (!empty($filters['low_stock'])) {
            $where[] = 'p.stock_qty <= p.min_stock';
        }

        return [$where, $params];
    }

    private function sortClause(string $sort): string
    {
        $sortMap = [
            'stock_low' => 'p.stock_qty ASC',
            'price_high' => 'p.selling_price DESC',
            'name' => 'p.name ASC',
        ];
        return $sortMap[$sort] ?? 'p.created_at DESC';
    }
}
