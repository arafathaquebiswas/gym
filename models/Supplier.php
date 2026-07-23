<?php

final class Supplier extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM suppliers ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM suppliers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $supplier = $stmt->fetch();
        return $supplier ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO suppliers (name, contact_person, phone, email, address)
             VALUES (:name, :contact_person, :phone, :email, :address)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?: null,
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'address' => $data['address'] ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(['name', 'contact_person', 'phone', 'email', 'address']));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE suppliers SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    /** Blocked at the controller layer if products/purchases still reference this supplier (both FKs are ON DELETE SET NULL). */
    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM suppliers WHERE id = :id')->execute(['id' => $id]);
    }

    public function productCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE supplier_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public function purchaseCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM purchases WHERE supplier_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
