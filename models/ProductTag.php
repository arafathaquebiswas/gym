<?php

/** Free-form tags for search/filtering — admin types any tag name, unlimited, never hardcoded. */
final class ProductTag extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM product_tags ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function forProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.* FROM product_tags t JOIN product_tag_map m ON m.tag_id = t.id
             WHERE m.product_id = :product_id ORDER BY t.name ASC'
        );
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    private function findOrCreateByName(string $name): int
    {
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
        $stmt = $this->db->prepare('SELECT id FROM product_tags WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        $stmt = $this->db->prepare('INSERT INTO product_tags (name, slug) VALUES (:name, :slug)');
        $stmt->execute(['name' => $name, 'slug' => $slug]);
        return (int) $this->db->lastInsertId();
    }

    /** @param array<string> $tagNames */
    public function setForProduct(int $productId, array $tagNames): void
    {
        $this->db->prepare('DELETE FROM product_tag_map WHERE product_id = :product_id')->execute(['product_id' => $productId]);

        $stmt = $this->db->prepare('INSERT IGNORE INTO product_tag_map (product_id, tag_id) VALUES (:product_id, :tag_id)');
        foreach ($tagNames as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $stmt->execute(['product_id' => $productId, 'tag_id' => $this->findOrCreateByName($name)]);
        }
    }
}
