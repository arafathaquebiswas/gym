<?php

/** Admin-defined attribute types (Size, Color, Flavor, Weight, ...) — unlimited, never hardcoded. */
final class ProductAttribute extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM product_attributes ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    /** All attributes with their values attached, for admin management screens. */
    public function allWithValues(): array
    {
        $attributes = $this->all();
        $valueModel = new AttributeValue();
        foreach ($attributes as &$attribute) {
            $attribute['values'] = $valueModel->forAttribute((int) $attribute['id']);
        }
        unset($attribute);
        return $attributes;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_attributes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $attribute = $stmt->fetch();
        return $attribute ?: null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM product_attributes WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(string $name, string $slug): int
    {
        $stmt = $this->db->prepare('INSERT INTO product_attributes (name, slug) VALUES (:name, :slug)');
        $stmt->execute(['name' => $name, 'slug' => $slug]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name): void
    {
        $this->db->prepare('UPDATE product_attributes SET name = :name WHERE id = :id')->execute(['name' => $name, 'id' => $id]);
    }

    /** Blocked at the controller layer if any product still uses this attribute (FK is ON DELETE CASCADE at the link/value level, so this would otherwise silently wipe variant data). */
    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM product_attributes WHERE id = :id')->execute(['id' => $id]);
    }

    public function usageCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM product_attribute_links WHERE attribute_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /** Attributes linked to a specific product, in display order — drives the variant-builder UI. */
    public function forProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.* FROM product_attributes a
             JOIN product_attribute_links l ON l.attribute_id = a.id
             WHERE l.product_id = :product_id
             ORDER BY l.sort_order ASC, a.name ASC'
        );
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    /** Replaces which attributes a product uses. Existing variants referencing a removed attribute keep their values (harmless orphans) — the admin is expected to rebuild variants after changing this. */
    public function setForProduct(int $productId, array $attributeIds): void
    {
        $this->db->prepare('DELETE FROM product_attribute_links WHERE product_id = :product_id')->execute(['product_id' => $productId]);

        $stmt = $this->db->prepare(
            'INSERT INTO product_attribute_links (product_id, attribute_id, sort_order) VALUES (:product_id, :attribute_id, :sort_order)'
        );
        foreach (array_values($attributeIds) as $i => $attributeId) {
            $stmt->execute(['product_id' => $productId, 'attribute_id' => (int) $attributeId, 'sort_order' => $i]);
        }
    }
}
