<?php

final class FreeTrialRegistration extends Model
{
    public function create(string $name, string $phone, ?string $email): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO free_trial_registrations (name, phone, email, created_at) VALUES (:name, :phone, :email, NOW())'
        );
        $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email]);
        return (int) $this->db->lastInsertId();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM free_trial_registrations')->fetchColumn();
    }

    public function paginateForAdmin(int $page = 1, int $perPage = 25): array
    {
        $total = $this->count();
        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare('SELECT * FROM free_trial_registrations ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
        ];
    }
}
