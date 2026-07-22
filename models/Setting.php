<?php

final class Setting extends Model
{
    /** @return array<string, string> */
    public function all(): array
    {
        static $cache = null;
        if ($cache === null) {
            $stmt = $this->db->query('SELECT setting_key, setting_value FROM settings');
            $cache = [];
            foreach ($stmt->fetchAll() as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $cache;
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->all()[$key] ?? $default;
    }
}
