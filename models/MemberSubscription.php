<?php

final class MemberSubscription extends Model
{
    public function latestForMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, p.name AS package_name
             FROM member_subscriptions s JOIN membership_packages p ON p.id = s.package_id
             WHERE s.member_id = :member_id
             ORDER BY s.end_date DESC LIMIT 1'
        );
        $stmt->execute(['member_id' => $memberId]);
        $sub = $stmt->fetch();
        return $sub ?: null;
    }

    public function history(int $memberId): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, p.name AS package_name
             FROM member_subscriptions s JOIN membership_packages p ON p.id = s.package_id
             WHERE s.member_id = :member_id
             ORDER BY s.start_date DESC'
        );
        $stmt->execute(['member_id' => $memberId]);
        return $stmt->fetchAll();
    }

    /**
     * @param array{member_id:int,package_id:int,start_date:string,end_date:string,price_paid:float,created_by:?int,discount_amount?:?float,notes?:?string} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO member_subscriptions (member_id, package_id, start_date, end_date, price_paid, discount_amount, notes, status, created_by, created_at)
             VALUES (:member_id, :package_id, :start_date, :end_date, :price_paid, :discount_amount, :notes, "active", :created_by, NOW())'
        );
        $stmt->execute([
            'member_id' => $data['member_id'],
            'package_id' => $data['package_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'price_paid' => $data['price_paid'],
            'discount_amount' => $data['discount_amount'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
