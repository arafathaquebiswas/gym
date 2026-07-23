<?php

final class Payment extends Model
{
    /**
     * Shared ledger insert for every admin-recorded charge (membership renewal/purchase,
     * trainer fee, locker fine). Centralizes the payments insert so mandatory-payment-method
     * and reference-number handling stay consistent across all charge points.
     */
    public function record(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payments (member_id, subscription_id, trainer_id, sale_id, type, amount, method, reference_no, status, paid_at, recorded_by)
             VALUES (:member_id, :subscription_id, :trainer_id, :sale_id, :type, :amount, :method, :reference_no, :status, NOW(), :recorded_by)'
        );
        $stmt->execute([
            'member_id' => $data['member_id'] ?? null,
            'subscription_id' => $data['subscription_id'] ?? null,
            'trainer_id' => $data['trainer_id'] ?? null,
            'sale_id' => $data['sale_id'] ?? null,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'reference_no' => $data['reference_no'] ?? null,
            'status' => $data['status'] ?? 'completed',
            'recorded_by' => $data['recorded_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Full payment history for one member, oldest first, with each row labeled by
     * type — the first membership payment is "New Membership", every one after it
     * is "Renewal" (both share the member's one permanent money_received_no).
     */
    public function forMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, mp.name AS package_name
             FROM payments p
             LEFT JOIN member_subscriptions ms ON ms.id = p.subscription_id
             LEFT JOIN membership_packages mp ON mp.id = ms.package_id
             WHERE p.member_id = :member_id
             ORDER BY p.paid_at ASC, p.id ASC'
        );
        $stmt->execute(['member_id' => $memberId]);
        $rows = $stmt->fetchAll();

        $typeLabels = [
            'trainer_fee' => 'Trainer Fee',
            'locker_fine' => 'Locker Fine',
            'store_sale' => 'Store Purchase',
            'admission' => 'Admission',
        ];

        $seenMembership = false;
        foreach ($rows as &$row) {
            if ($row['type'] === 'membership') {
                $row['type_label'] = $seenMembership ? 'Renewal' : 'New Membership';
                $seenMembership = true;
            } else {
                $row['type_label'] = $typeLabels[$row['type']] ?? ucfirst($row['type']);
            }
        }

        return $rows;
    }
}
