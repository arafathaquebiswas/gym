<?php

final class DeliveryTimeSlot extends Model
{
    public const TYPES = ['delivery', 'pickup'];

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM delivery_time_slots ORDER BY type ASC, sort_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    public function allActiveByType(string $type): array
    {
        $stmt = $this->db->prepare('SELECT * FROM delivery_time_slots WHERE type = :type AND is_active = 1 ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM delivery_time_slots WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $slot = $stmt->fetch();
        return $slot ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO delivery_time_slots (type, label, is_active, sort_order) VALUES (:type, :label, :is_active, :sort_order)'
        );
        $stmt->execute([
            'type' => $data['type'],
            'label' => $data['label'],
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(['type', 'label', 'is_active', 'sort_order']));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE delivery_time_slots SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    public function toggleActive(int $id): void
    {
        $this->db->prepare('UPDATE delivery_time_slots SET is_active = NOT is_active WHERE id = :id')->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM delivery_time_slots WHERE id = :id')->execute(['id' => $id]);
    }
}
