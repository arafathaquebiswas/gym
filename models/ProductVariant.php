<?php

/**
 * A sellable variation of a product (e.g. Gloves → Size M / Size XL), defined as a
 * combination of attribute values. price/offer_price/image are nullable and fall back
 * to the parent product's own selling_price/offer_price/image when unset — a product
 * doesn't need every variant to override every field.
 */
final class ProductVariant extends Model
{
    /** All variants for a product, each with its attribute-value labels attached (e.g. "Size: Medium"). */
    public function forProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM product_variants WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['product_id' => $productId]);
        $variants = $stmt->fetchAll();

        $valueModel = new AttributeValue();
        foreach ($variants as &$variant) {
            $variant['attribute_values'] = $valueModel->withAttributeNames($this->valueIdsFor((int) $variant['id']));
        }
        unset($variant);

        return $variants;
    }

    /** Only active variants with stock — used by the storefront variant switcher. */
    public function activeForProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM product_variants WHERE product_id = :product_id AND status = 'active' ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute(['product_id' => $productId]);
        $variants = $stmt->fetchAll();

        $valueModel = new AttributeValue();
        foreach ($variants as &$variant) {
            $variant['attribute_values'] = $valueModel->withAttributeNames($this->valueIdsFor((int) $variant['id']));
        }
        unset($variant);

        return $variants;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_variants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $variant = $stmt->fetch();
        return $variant ?: null;
    }

    public function valueIdsFor(int $variantId): array
    {
        $stmt = $this->db->prepare('SELECT attribute_value_id FROM product_variant_values WHERE variant_id = :variant_id');
        $stmt->execute(['variant_id' => $variantId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'attribute_value_id'));
    }

    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM product_variants WHERE sku = :sku';
        $params = ['sku' => $sku];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function barcodeExists(string $barcode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM product_variants WHERE barcode = :barcode';
        $params = ['barcode' => $barcode];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** @param array<int> $attributeValueIds */
    public function create(array $data, array $attributeValueIds): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO product_variants (product_id, sku, barcode, price, offer_price, stock_qty, weight, image, status, sort_order)
                 VALUES (:product_id, :sku, :barcode, :price, :offer_price, :stock_qty, :weight, :image, :status, :sort_order)'
            );
            $stmt->execute([
                'product_id' => $data['product_id'],
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?: null,
                'price' => $data['price'] ?: null,
                'offer_price' => $data['offer_price'] ?: null,
                'stock_qty' => $data['stock_qty'] ?? 0,
                'weight' => $data['weight'] ?: null,
                'image' => $data['image'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
            $variantId = (int) $this->db->lastInsertId();

            $this->setValues($variantId, $attributeValueIds);

            $this->db->commit();
            return $variantId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** @param array<int>|null $attributeValueIds */
    public function update(int $id, array $data, ?array $attributeValueIds = null): void
    {
        $fields = array_intersect_key($data, array_flip(['sku', 'barcode', 'price', 'offer_price', 'stock_qty', 'weight', 'image', 'status', 'sort_order']));
        if ($fields) {
            if (array_key_exists('barcode', $fields)) {
                $fields['barcode'] = $fields['barcode'] ?: null;
            }
            if (array_key_exists('price', $fields)) {
                $fields['price'] = $fields['price'] ?: null;
            }
            if (array_key_exists('offer_price', $fields)) {
                $fields['offer_price'] = $fields['offer_price'] ?: null;
            }
            if (array_key_exists('weight', $fields)) {
                $fields['weight'] = $fields['weight'] ?: null;
            }
            $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
            $fields['id'] = $id;
            $this->db->prepare("UPDATE product_variants SET $set WHERE id = :id")->execute($fields);
        }

        if ($attributeValueIds !== null) {
            $this->setValues($id, $attributeValueIds);
        }
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM product_variants WHERE id = :id')->execute(['id' => $id]);
    }

    /** Manual stock correction for a specific variant — mirrors Product::adjustStock(). */
    public function adjustStock(int $id, int $delta): void
    {
        $this->db->prepare('UPDATE product_variants SET stock_qty = stock_qty + :delta WHERE id = :id')
            ->execute(['delta' => $delta, 'id' => $id]);
    }

    public function decrementStockIfAvailable(int $id, int $qty): bool
    {
        $stmt = $this->db->prepare('UPDATE product_variants SET stock_qty = stock_qty - :qty WHERE id = :id AND stock_qty >= :qty2');
        $stmt->execute(['qty' => $qty, 'id' => $id, 'qty2' => $qty]);
        return $stmt->rowCount() > 0;
    }

    /** Merges a variant's fields on top of its parent product's, so callers get one flat "what to sell at" record. */
    public function withResolvedPricing(array $variant, array $product): array
    {
        $variant['effective_sku'] = $variant['sku'];
        $variant['effective_barcode'] = $variant['barcode'];
        $variant['effective_price'] = $variant['price'] !== null ? (float) $variant['price'] : (float) $product['selling_price'];
        $variant['effective_offer_price'] = $variant['offer_price'] !== null ? (float) $variant['offer_price'] : null;
        $variant['effective_stock'] = (int) $variant['stock_qty'];
        $variant['effective_image'] = $variant['image'] ?: $product['image'];

        return $variant;
    }

    /** @param array<int> $attributeValueIds */
    private function setValues(int $variantId, array $attributeValueIds): void
    {
        $this->db->prepare('DELETE FROM product_variant_values WHERE variant_id = :variant_id')->execute(['variant_id' => $variantId]);

        $stmt = $this->db->prepare(
            'INSERT INTO product_variant_values (variant_id, attribute_value_id) VALUES (:variant_id, :attribute_value_id)'
        );
        foreach ($attributeValueIds as $valueId) {
            if ((int) $valueId <= 0) {
                continue;
            }
            $stmt->execute(['variant_id' => $variantId, 'attribute_value_id' => (int) $valueId]);
        }
    }
}
