<?php

final class ContactMessage extends Model
{
    public function create(string $name, string $email, string $phone, string $subject, string $message): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at)
             VALUES (:name, :email, :phone, :subject, :message, "new", NOW())'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $message = $stmt->fetch();
        return $message ?: null;
    }

    /**
     * @param array{status?:string} $filters
     */
    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM contact_messages' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            'SELECT * FROM contact_messages' . $whereSql . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function newCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
        return (int) $stmt->fetchColumn();
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE contact_messages SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM contact_messages WHERE id = :id')->execute(['id' => $id]);
    }
}
