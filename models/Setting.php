<?php

final class Setting extends Model
{
    private static ?array $cache = null;

    /** @return array<string, string> */
    public function all(): array
    {
        if (self::$cache === null) {
            $stmt = $this->db->query('SELECT setting_key, setting_value FROM settings');
            self::$cache = [];
            foreach ($stmt->fetchAll() as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
        }
        return self::$cache;
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = :value2'
        );
        $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
        self::$cache = null;
    }

    /** @param array<string, string> $pairs */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->set($key, (string) $value);
        }
    }
}
