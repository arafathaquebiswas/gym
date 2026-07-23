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
}
