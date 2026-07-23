<?php

/** Admin-defined values for an attribute (e.g. Size → Small/Medium/Large) — unlimited, never hardcoded. */
final class AttributeValue extends Model
{
    public function forAttribute(int $attributeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM attribute_values WHERE attribute_id = :attribute_id ORDER BY sort_order ASC, value ASC'
        );
        $stmt->execute(['attribute_id' => $attributeId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM attribute_values WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $value = $stmt->fetch();
        return $value ?: null;
    }

    /** Attribute-value pairs for a set of value IDs, e.g. to render "Size: Medium, Color: Red" for a variant. */
    public function withAttributeNames(array $valueIds): array
    {
        if (!$valueIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($valueIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT v.*, a.name AS attribute_name FROM attribute_values v
             JOIN product_attributes a ON a.id = v.attribute_id
             WHERE v.id IN ($placeholders)"
        );
        $stmt->execute(array_map('intval', $valueIds));
        return $stmt->fetchAll();
    }

    public function valueExists(int $attributeId, string $value, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM attribute_values WHERE attribute_id = :attribute_id AND value = :value';
        $params = ['attribute_id' => $attributeId, 'value' => $value];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(int $attributeId, string $value, int $sortOrder = 0): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO attribute_values (attribute_id, value, sort_order) VALUES (:attribute_id, :value, :sort_order)'
        );
        $stmt->execute(['attribute_id' => $attributeId, 'value' => $value, 'sort_order' => $sortOrder]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $value): void
    {
        $this->db->prepare('UPDATE attribute_values SET value = :value WHERE id = :id')->execute(['value' => $value, 'id' => $id]);
    }

    /** Blocked at the controller layer if any variant still uses this value (FK is ON DELETE CASCADE at the variant-value level, so this would otherwise silently strip a live variant's attribute). */
    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM attribute_values WHERE id = :id')->execute(['id' => $id]);
    }

    public function usageCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM product_variant_values WHERE attribute_value_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
