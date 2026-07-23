<?php

final class Product extends Model
{
    private const WRITABLE_FIELDS = [
        'category_id', 'brand_id', 'supplier_id', 'sku', 'barcode', 'name', 'slug', 'description',
        'buying_price', 'selling_price', 'stock_qty', 'min_stock', 'expiry_date', 'image',
        'offer_price', 'offer_enabled', 'offer_start_date', 'offer_end_date', 'status',
        'ingredients', 'nutrition_facts', 'allow_preorder', 'shipping_charge',
        'bogo_enabled', 'is_featured',
    ];

    public const STATUSES = ['draft', 'published', 'hidden'];

    public function paginate(int $page, int $perPage, ?string $categorySlug = null, ?string $search = null, bool $inStockOnly = false, ?string $sort = null, ?string $brandSlug = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ["p.status = 'published'"];
        $params = [];

        if ($categorySlug) {
            $where[] = 'c.slug = :category_slug';
            $params['category_slug'] = $categorySlug;
        }
        if ($brandSlug) {
            $where[] = 'b.slug = :brand_slug';
            $params['brand_slug'] = $brandSlug;
        }
        if ($search) {
            $where[] = '(p.name LIKE :search_name OR b.name LIKE :search_brand)';
            $params['search_name'] = '%' . $search . '%';
            $params['search_brand'] = '%' . $search . '%';
        }
        if ($inStockOnly) {
            $where[] = 'p.stock_qty > 0';
        }
        $whereSql = implode(' AND ', $where);
        $joinSql = 'JOIN product_categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id';

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM products p $joinSql WHERE $whereSql"
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
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, b.slug AS brand_slug
             FROM products p $joinSql
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
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, b.slug AS brand_slug
             FROM products p JOIN product_categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.slug = :slug AND p.status = 'published' LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch();
        return $product ? $this->withComputedOffer($product) : null;
    }

    /** Admin-curated is_featured products first; falls back to latest published so the homepage section never sits empty before anything's been marked. */
    public function featured(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, b.name AS brand_name, b.slug AS brand_slug FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.status = 'published'
             ORDER BY p.is_featured DESC, p.created_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    public function toggleFeatured(int $id): void
    {
        $this->db->prepare('UPDATE products SET is_featured = NOT is_featured WHERE id = :id')->execute(['id' => $id]);
    }

    public function toggleArchived(int $id): void
    {
        $this->db->prepare('UPDATE products SET is_archived = NOT is_archived WHERE id = :id')->execute(['id' => $id]);
    }

    public function hasVariants(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM product_variants WHERE product_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Clones a product's own fields plus its attribute links and variants (each variant gets a
     * fresh auto-suffixed SKU/barcode so uniqueness constraints hold). Gallery photos and reviews
     * are deliberately not copied — a duplicate is a starting point for a new listing, not a
     * full mirror of another product's history.
     */
    public function duplicate(int $id): int
    {
        $original = $this->find($id);
        if (!$original) {
            throw new RuntimeException('Product not found.');
        }

        $this->db->beginTransaction();
        try {
            $data = array_intersect_key($original, array_flip(self::WRITABLE_FIELDS));
            $data['sku'] = $this->uniqueSuffixedSku($original['sku']);
            $data['status'] = 'draft';
            $data['is_featured'] = 0;

            $name = $original['name'] . ' (Copy)';
            $slug = $original['slug'] . '-copy';
            $i = 2;
            while ($this->slugExists($slug)) {
                $slug = $original['slug'] . '-copy-' . $i++;
            }
            $data['name'] = $name;
            $data['slug'] = $slug;

            $newId = $this->create($data);

            $attributeIds = array_column((new ProductAttribute())->forProduct($id), 'id');
            if ($attributeIds) {
                (new ProductAttribute())->setForProduct($newId, $attributeIds);
            }

            $variantModel = new ProductVariant();
            foreach ($variantModel->forProduct($id) as $variant) {
                $variantModel->create([
                    'product_id' => $newId,
                    'sku' => $this->uniqueSuffixedSku($variant['sku'], $variantModel),
                    'barcode' => null,
                    'price' => $variant['price'],
                    'offer_price' => $variant['offer_price'],
                    'stock_qty' => 0,
                    'weight' => $variant['weight'],
                    'image' => $variant['image'],
                    'status' => $variant['status'],
                    'sort_order' => $variant['sort_order'],
                ], array_column($variant['attribute_values'], 'id'));
            }

            $this->db->commit();
            return $newId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function uniqueSuffixedSku(string $baseSku, ?ProductVariant $variantModel = null): string
    {
        $sku = $baseSku . '-COPY';
        $i = 2;
        $exists = fn (string $s) => $variantModel ? $variantModel->skuExists($s) : $this->skuExists($s);
        while ($exists($sku)) {
            $sku = $baseSku . '-COPY' . $i++;
        }
        return $sku;
    }

    /** All sellable products for the POS screen's client-side search/cart — published or hidden (staff can still sell in person), never draft. */
    public function allActiveInStock(): array
    {
        $stmt = $this->db->query(
            "SELECT p.id, p.name, p.sku, p.barcode, p.selling_price, p.offer_price, p.offer_enabled,
                    p.offer_start_date, p.offer_end_date, p.stock_qty, p.image, c.name AS category_name
             FROM products p JOIN product_categories c ON c.id = p.category_id
             WHERE p.status != 'draft' AND p.stock_qty > 0
             ORDER BY p.name ASC"
        );
        $products = array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
        foreach ($products as &$product) {
            $product['image_url'] = $product['image']
                ? (str_starts_with($product['image'], 'uploads/') ? url($product['image']) : asset('images/' . $product['image']))
                : null;
        }
        unset($product);
        return $products;
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
            "SELECT * FROM products
             WHERE category_id = :category_id AND id != :product_id AND status = 'published'
             ORDER BY RAND() LIMIT :limit"
        );
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    /** Products frequently purchased alongside this one, computed from real order/sale co-occurrence — never hardcoded. */
    public function frequentlyBoughtWith(int $productId, int $limit = 4): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, COUNT(*) AS cnt FROM (
                SELECT oi2.product_id FROM order_items oi1
                JOIN order_items oi2 ON oi2.order_id = oi1.order_id AND oi2.product_id != oi1.product_id
                WHERE oi1.product_id = :pid1
                UNION ALL
                SELECT si2.product_id FROM sale_items si1
                JOIN sale_items si2 ON si2.sale_id = si1.sale_id AND si2.product_id != si1.product_id
                WHERE si1.product_id = :pid2
            ) co
            JOIN products p ON p.id = co.product_id AND p.status = 'published'
            GROUP BY p.id
            ORDER BY cnt DESC LIMIT :limit"
        );
        $stmt->bindValue(':pid1', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':pid2', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name, b.name AS brand_name, b.slug AS brand_slug FROM products p
             JOIN product_categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
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
        $joinSql = ' JOIN product_categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id';

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM products p' . $joinSql . $whereSql
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT p.*, c.name AS category_name, b.name AS brand_name FROM products p' . $joinSql . $whereSql
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
             FROM products WHERE status != 'draft'"
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
            "SELECT p.*, c.name AS category_name FROM products p
             JOIN product_categories c ON c.id = p.category_id
             WHERE p.stock_qty <= p.min_stock AND p.status != 'draft'
             ORDER BY p.stock_qty ASC"
        );
        return $stmt->fetchAll();
    }

    /** Every product regardless of status/stock — for admin pickers (e.g. recording a purchase) where even out-of-stock/draft items must be selectable. */
    public function allForAdminPicker(): array
    {
        $stmt = $this->db->query('SELECT id, name, sku, stock_qty FROM products ORDER BY name ASC');
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

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            return;
        }
        $this->db->prepare('UPDATE products SET status = :status WHERE id = :id')->execute(['status' => $status, 'id' => $id]);
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
     * Resolves the single effective price for a product by checking each discount tier in
     * strict priority order — the first one that's currently live wins, full stop (never the
     * biggest discount, never stacked). Order: Flash Sale > Product Offer > Category Offer >
     * Brand Offer > Regular Price. Coupons are a separate, checkout-time-only tier layered on
     * top by Order::create() (see its stacking-rule comment) — they never affect display_price.
     *
     * Public so callers holding a raw product row from elsewhere (e.g. Cart::forIdentity()'s join) can
     * compute the same display price without duplicating this logic. category_id/brand_id must be
     * present on $product for tiers 3/4 to apply — every query in this class selects them via `p.*`
     * except the POS picker (allActiveInStock()), which only needs the product-offer tier anyway.
     */
    public function withComputedOffer(array $product): array
    {
        $product['discount_source'] = null;
        $product['discount_label'] = null;
        $product['offer_ends_at'] = null;

        $flashSale = (new FlashSale())->liveForProduct($product);
        if ($flashSale) {
            $product['offer_is_live'] = true;
            $product['discount_source'] = 'flash_sale';
            $product['discount_label'] = $flashSale['name'];
            $product['display_price'] = round((float) $product['selling_price'] * (1 - (float) $flashSale['discount_percent'] / 100), 2);
            // MySQL DATETIME ("Y-m-d H:i:s") isn't reliably parsed by `new Date()` in every browser — ISO 8601 ("T" separator) is.
            $product['offer_ends_at'] = str_replace(' ', 'T', $flashSale['ends_at']);
            return $product;
        }

        $now = new DateTimeImmutable();
        $startOk = empty($product['offer_start_date']) || new DateTimeImmutable($product['offer_start_date']) <= $now;
        $endOk = empty($product['offer_end_date']) || new DateTimeImmutable($product['offer_end_date']) > $now;
        if ((bool) $product['offer_enabled'] && !empty($product['offer_price']) && $startOk && $endOk) {
            $product['offer_is_live'] = true;
            $product['discount_source'] = 'product_offer';
            $product['display_price'] = (float) $product['offer_price'];
            $product['offer_ends_at'] = $product['offer_end_date'];
            return $product;
        }

        $category = !empty($product['category_id']) ? (new ProductCategory())->find((int) $product['category_id']) : null;
        if ($category && self::offerWindowLive($category)) {
            $product['offer_is_live'] = true;
            $product['discount_source'] = 'category_offer';
            $product['discount_label'] = $category['name'] . ' Sale';
            $product['display_price'] = round((float) $product['selling_price'] * (1 - (float) $category['offer_percent'] / 100), 2);
            $product['offer_ends_at'] = $category['offer_end_date'];
            return $product;
        }

        $brand = !empty($product['brand_id']) ? (new Brand())->find((int) $product['brand_id']) : null;
        if ($brand && self::offerWindowLive($brand)) {
            $product['offer_is_live'] = true;
            $product['discount_source'] = 'brand_offer';
            $product['discount_label'] = $brand['name'] . ' Sale';
            $product['display_price'] = round((float) $product['selling_price'] * (1 - (float) $brand['offer_percent'] / 100), 2);
            $product['offer_ends_at'] = $brand['offer_end_date'];
            return $product;
        }

        $product['offer_is_live'] = false;
        $product['display_price'] = $product['selling_price'];
        return $product;
    }

    private static function offerWindowLive(array $entity): bool
    {
        if (empty($entity['offer_enabled']) || empty($entity['offer_percent'])) {
            return false;
        }
        $now = new DateTimeImmutable();
        $startOk = empty($entity['offer_start_date']) || new DateTimeImmutable($entity['offer_start_date']) <= $now;
        $endOk = empty($entity['offer_end_date']) || new DateTimeImmutable($entity['offer_end_date']) > $now;
        return $startOk && $endOk;
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
        if (!empty($filters['brand_id'])) {
            $where[] = 'p.brand_id = :brand_id';
            $params['brand_id'] = $filters['brand_id'];
        }
        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
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
