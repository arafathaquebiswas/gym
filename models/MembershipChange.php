<?php

/**
 * Audit trail for manual membership grants and edits.
 *
 * Append-only by design: there is no update() or delete() here, and no admin
 * route reaches this table, so a record of who changed what cannot be rewritten
 * from the UI. Subscriptions themselves are the record of each *term*; this is
 * the record of the *changes* an admin made to them.
 */
final class MembershipChange extends Model
{
    public const ACTIONS = [
        'granted' => 'Granted',
        'extended' => 'Extended',
        'edited' => 'Edited',
    ];

    /**
     * @param array{member_id:int,subscription_id:?int,action:string,reason:?string} $data
     *        plus optional "previous_" and "new_" keys; anything absent is stored NULL.
     */
    public function record(array $data): int
    {
        $columns = [
            'member_id', 'subscription_id', 'action',
            'previous_package_id', 'new_package_id',
            'previous_start_date', 'new_start_date',
            'previous_end_date', 'new_end_date',
            'previous_grant_type', 'new_grant_type',
            'previous_price', 'new_price',
            'previous_lifetime', 'new_lifetime',
            'reason', 'changed_by',
        ];

        $values = [];
        foreach ($columns as $column) {
            $values[$column] = $data[$column] ?? null;
        }
        $values['changed_by'] = $data['changed_by'] ?? (Auth::check() ? (int) Auth::user()['id'] : null);

        $stmt = $this->db->prepare(
            'INSERT INTO membership_changes (' . implode(', ', $columns) . ', created_at)
             VALUES (:' . implode(', :', $columns) . ', NOW())'
        );
        $stmt->execute($values);

        return (int) $this->db->lastInsertId();
    }

    /** Newest first, with the package names and the admin's name resolved for display. */
    public function forMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*,
                    prev.name AS previous_package_name,
                    cur.name  AS new_package_name,
                    u.name    AS changed_by_name
             FROM membership_changes c
             LEFT JOIN membership_packages prev ON prev.id = c.previous_package_id
             LEFT JOIN membership_packages cur  ON cur.id  = c.new_package_id
             LEFT JOIN users u ON u.id = c.changed_by
             WHERE c.member_id = :member_id
             ORDER BY c.id DESC'
        );
        $stmt->execute(['member_id' => $memberId]);

        return $stmt->fetchAll();
    }
}
