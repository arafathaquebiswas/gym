<?php

final class Faq extends Model
{
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC');
        return $stmt->fetchAll();
    }

    /** @param string[] $categories */
    public function byCategories(array $categories): array
    {
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $stmt = $this->db->prepare(
            "SELECT * FROM faqs WHERE is_active = 1 AND category IN ($placeholders) ORDER BY sort_order ASC"
        );
        $stmt->execute(array_values($categories));
        return $stmt->fetchAll();
    }
}
