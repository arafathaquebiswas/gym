<?php

final class Member extends Model
{
    public function createForUser(int $userId): int
    {
        $memberCode = 'PSG-' . date('y') . '-' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare(
            'INSERT INTO members (user_id, member_code, join_date, status, created_at)
             VALUES (:user_id, :member_code, CURDATE(), "pending", NOW())'
        );
        $stmt->execute(['user_id' => $userId, 'member_code' => $memberCode]);
        return (int) $this->db->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, t.name AS trainer_name
             FROM members m LEFT JOIN trainers t ON t.id = m.trainer_id
             WHERE m.user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $member = $stmt->fetch();
        return $member ?: null;
    }

    public function activeSubscription(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, p.name AS package_name
             FROM member_subscriptions s JOIN membership_packages p ON p.id = s.package_id
             WHERE s.member_id = :member_id AND s.status = "active"
             ORDER BY s.end_date DESC LIMIT 1'
        );
        $stmt->execute(['member_id' => $memberId]);
        $sub = $stmt->fetch();
        return $sub ?: null;
    }
}
