<?php

final class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByRole(string $roleSlug): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.* FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.slug = :role ORDER BY u.name ASC'
        );
        $stmt->execute(['role' => $roleSlug]);
        return $stmt->fetchAll();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(string $name, string $email, string $phone, string $password, string $roleSlug = 'member'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (role_id, name, email, phone, password_hash, status, created_at)
             VALUES ((SELECT id FROM roles WHERE slug = :role), :name, :email, :phone, :password, "active", NOW())'
        );
        $stmt->execute([
            'role' => $roleSlug,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function touchLastLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private const WRITABLE_FIELDS = ['name', 'email', 'phone', 'status'];

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE users SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    public function updatePassword(int $id, string $newPassword): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $id]);
    }

    /** Cascades (via FK ON DELETE CASCADE) to members, member_subscriptions, attendance, etc. */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
