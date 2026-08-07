<?php

final class MemberSubscription extends Model
{
    /**
     * Lifetime terms carry this instead of a NULL end_date — see the
     * 20260807_membership_grants migration for why a sentinel beats nullable.
     */
    public const LIFETIME_END_DATE = '9999-12-31';

    /** Terms an admin can grant. months = null means the term needs explicit handling. */
    public const TERMS = [
        '1m' => ['label' => '1 Month', 'months' => 1],
        '3m' => ['label' => '3 Months', 'months' => 3],
        '6m' => ['label' => '6 Months', 'months' => 6],
        '12m' => ['label' => '12 Months', 'months' => 12],
        '2y' => ['label' => '2 Years', 'months' => 24],
        '4y' => ['label' => '4 Years', 'months' => 48],
        'lifetime' => ['label' => 'Lifetime', 'months' => null],
        'custom' => ['label' => 'Custom end date', 'months' => null],
    ];

    public const GRANT_TYPES = [
        'paid' => 'Paid',
        'gift' => 'Gift',
        'complimentary' => 'Complimentary',
    ];

    /**
     * The end date a term reaches from $startDate.
     *
     * Counted in months rather than days so a "1 Month" term from 31 January
     * lands on 28 February, not 2 March. PHP's relative months overflow, so the
     * day is clamped to the target month's length.
     */
    public static function endDateFor(string $term, string $startDate, ?string $customEnd = null): string
    {
        if ($term === 'lifetime') {
            return self::LIFETIME_END_DATE;
        }
        if ($term === 'custom') {
            return $customEnd ?: $startDate;
        }

        $months = self::TERMS[$term]['months'] ?? null;
        if ($months === null) {
            return $startDate;
        }

        $start = new DateTimeImmutable($startDate);
        $target = $start->modify("+$months months");

        // "+1 month" from 31 Jan gives 3 Mar; step back to the last day of the
        // month we actually meant.
        if ((int) $target->format('d') !== (int) $start->format('d')) {
            $target = $target->modify('last day of previous month');
        }

        return $target->format('Y-m-d');
    }

    /** "Lifetime", "365 days remaining", or "Expired 07 Aug 2026" — whichever fits the row. */
    public static function remainingLabel(array $subscription): string
    {
        if (!empty($subscription['is_lifetime'])) {
            return 'Lifetime';
        }

        $end = new DateTimeImmutable($subscription['end_date']);
        $today = new DateTimeImmutable(date('Y-m-d'));
        $days = (int) $today->diff($end)->format('%r%a');

        if ($days < 0) {
            return 'Expired ' . $end->format('d M Y');
        }
        if ($days === 0) {
            return 'Expires today';
        }

        return $days . ' day' . ($days === 1 ? '' : 's') . ' remaining';
    }

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

    /** One term, scoped to its member so an id from another member's history can't be reached. */
    public function findForMember(int $subscriptionId, int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM member_subscriptions WHERE id = :id AND member_id = :member_id LIMIT 1'
        );
        $stmt->execute(['id' => $subscriptionId, 'member_id' => $memberId]);

        return $stmt->fetch() ?: null;
    }

    /** Edits one term in place. Callers must write the audit row first — see MembershipChange. */
    public function updateTerm(int $subscriptionId, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE member_subscriptions
             SET package_id = :package_id, start_date = :start_date, end_date = :end_date,
                 price_paid = :price_paid, grant_type = :grant_type, is_lifetime = :is_lifetime,
                 notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            'package_id' => $data['package_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'price_paid' => $data['price_paid'],
            'grant_type' => $data['grant_type'],
            'is_lifetime' => $data['is_lifetime'],
            'notes' => $data['notes'] ?? null,
            'id' => $subscriptionId,
        ]);
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
            "INSERT INTO member_subscriptions (member_id, package_id, start_date, end_date, price_paid, discount_amount, notes, status, grant_type, is_lifetime, created_by, created_at)
             VALUES (:member_id, :package_id, :start_date, :end_date, :price_paid, :discount_amount, :notes, 'active', :grant_type, :is_lifetime, :created_by, NOW())"
        );
        $stmt->execute([
            'member_id' => $data['member_id'],
            'package_id' => $data['package_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'price_paid' => $data['price_paid'],
            'discount_amount' => $data['discount_amount'] ?? null,
            'notes' => $data['notes'] ?? null,
            // Defaults keep every existing caller (renewMember, POS, registration)
            // producing exactly the rows it produced before.
            'grant_type' => $data['grant_type'] ?? 'paid',
            'is_lifetime' => !empty($data['is_lifetime']) ? 1 : 0,
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
