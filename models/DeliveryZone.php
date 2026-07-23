<?php

final class DeliveryZone extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM delivery_zones ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll();
    }

    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM delivery_zones WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM delivery_zones WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $zone = $stmt->fetch();
        return $zone ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO delivery_zones (name, charge, is_active, sort_order) VALUES (:name, :charge, :is_active, :sort_order)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'charge' => $data['charge'],
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(['name', 'charge', 'is_active', 'sort_order']));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE delivery_zones SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    public function toggleActive(int $id): void
    {
        $this->db->prepare('UPDATE delivery_zones SET is_active = NOT is_active WHERE id = :id')->execute(['id' => $id]);
    }

    /** FK is ON DELETE SET NULL, so this is a soft guard rather than a hard DB constraint — avoids silently orphaning historical orders' zone reference. */
    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM delivery_zones WHERE id = :id')->execute(['id' => $id]);
    }

    public function orderCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE zone_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
