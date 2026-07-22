<?php

final class Promotion extends Model
{
    public function active(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM promotions
             WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date
             ORDER BY end_date ASC'
        );
        return $stmt->fetchAll();
    }
}
