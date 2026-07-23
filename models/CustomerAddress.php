<?php

final class CustomerAddress extends Model
{
    private const WRITABLE_FIELDS = ['label', 'full_name', 'phone', 'address', 'city', 'area', 'postal_code'];

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM customer_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customer_addresses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function defaultForUser(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customer_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $userId, array $data): int
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        $fields['user_id'] = $userId;
        $columns = array_keys($fields);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);

        $stmt = $this->db->prepare(
            'INSERT INTO customer_addresses (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($fields);
        $id = (int) $this->db->lastInsertId();

        if (count($this->forUser($userId)) === 1) {
            $this->setDefault($userId, $id);
        }

        return $id;
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $this->db->prepare("UPDATE customer_addresses SET $set WHERE id = :id")->execute($fields);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM customer_addresses WHERE id = :id')->execute(['id' => $id]);
    }

    public function setDefault(int $userId, int $addressId): void
    {
        $this->db->prepare('UPDATE customer_addresses SET is_default = 0 WHERE user_id = :user_id')->execute(['user_id' => $userId]);
        $this->db->prepare('UPDATE customer_addresses SET is_default = 1 WHERE id = :id AND user_id = :user_id')
            ->execute(['id' => $addressId, 'user_id' => $userId]);
    }
}
