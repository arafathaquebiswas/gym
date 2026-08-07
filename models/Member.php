<?php

final class Member extends Model
{
    /**
     * 'status' is intentionally excluded — it is a derived field (see recomputeStatus()/
     * syncAllStatuses()), never a manually-set one: pending until a package is purchased,
     * active while the latest subscription hasn't lapsed, expired once it has.
     */
    private const WRITABLE_FIELDS = [
        'dob', 'gender', 'blood_group', 'emergency_contact', 'address',
        'height_cm', 'weight_kg', 'target_weight_kg', 'fitness_goal', 'medical_notes',
        'join_date', 'trainer_id', 'locker_number', 'photo',
        'notify_email', 'notify_promotions',
        'preferred_package_id', 'registration_notes',
        'reported_payment_method', 'reported_payment_reference', 'reported_payer_number',
        'registration_coupon_code', 'registration_coupon_discount', 'registration_amount',
        'payment_method', 'payment_type', 'transaction_id', 'payment_screenshot', 'payment_status',
    ];

    private const BASE_SELECT = "SELECT m.*, u.name, u.email, u.phone, u.status AS account_status,
             t.name AS trainer_name,
             sub.package_name, sub.end_date AS subscription_end, sub.status AS subscription_status,
             sub.is_lifetime AS subscription_is_lifetime, sub.grant_type AS subscription_grant_type,
             (SELECT COUNT(*) FROM attendance a WHERE a.member_id = m.id AND a.check_in >= DATE_FORMAT(NOW(), '%Y-%m-01')) AS attendance_this_month
             FROM members m
             JOIN users u ON u.id = m.user_id
             LEFT JOIN trainers t ON t.id = m.trainer_id
             LEFT JOIN (
                 SELECT ms.member_id, mp.name AS package_name, ms.end_date, ms.status,
                        ms.is_lifetime, ms.grant_type
                 FROM member_subscriptions ms
                 JOIN membership_packages mp ON mp.id = ms.package_id
                 WHERE ms.id IN (SELECT MAX(id) FROM member_subscriptions GROUP BY member_id)
             ) sub ON sub.member_id = m.id";


    /** Admin-created walk-in member: base row + whatever details were captured on the form. */
    public function createForNewUser(int $userId, array $data): int
    {
        $memberCode = 'PSG-' . date('y') . '-' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT);
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        $fields['user_id'] = $userId;
        $fields['member_code'] = $memberCode;
        $fields['join_date'] = $fields['join_date'] ?? date('Y-m-d');
        // Always starts pending — recomputeStatus() flips it to active once an initial
        // package is attached (see MemberAdminController::store()).
        $fields['status'] = 'pending';

        $columns = array_keys($fields);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);
        $stmt = $this->db->prepare(
            'INSERT INTO members (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($fields);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Has this bKash/Nagad Transaction ID already been submitted by anyone?
     * Backed by a unique index — this exists to report the clash as a form error
     * rather than letting it surface as a constraint violation.
     */
    public function transactionIdExists(string $transactionId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM members WHERE transaction_id = :trx');
        $stmt->execute(['trx' => $transactionId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** The three payment states a registration can be in. NULL means "paid at gym", which is not part of this workflow at all. */
    public const PAYMENT_STATUSES = [
        'pending' => 'Pending Verification',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ];

    /**
     * Registrations that claimed an online bKash/Nagad payment, newest first.
     * payment_status IS NOT NULL is what excludes "Pay at Gym" sign-ups, which have
     * no payment to check.
     */
    public function paymentQueue(string $status = ''): array
    {
        $where = 'm.payment_status IS NOT NULL';
        $params = [];
        if (isset(self::PAYMENT_STATUSES[$status])) {
            $where .= ' AND m.payment_status = :status';
            $params['status'] = $status;
        }

        $stmt = $this->db->prepare(
            "SELECT m.*, u.name, u.phone, u.email,
                    p.name AS package_name,
                    v.name AS verified_by_name
             FROM members m
             JOIN users u ON u.id = m.user_id
             LEFT JOIN membership_packages p ON p.id = m.preferred_package_id
             LEFT JOIN users v ON v.id = m.verified_by
             WHERE $where
             ORDER BY CASE WHEN m.payment_status = 'pending' THEN 0 ELSE 1 END, m.created_at DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<string,int> status => count, for the queue's filter tabs. */
    public function paymentStatusCounts(): array
    {
        $counts = array_fill_keys(array_keys(self::PAYMENT_STATUSES), 0);
        $rows = $this->db->query(
            'SELECT payment_status, COUNT(*) AS total FROM members
             WHERE payment_status IS NOT NULL GROUP BY payment_status'
        )->fetchAll();

        foreach ($rows as $row) {
            $counts[$row['payment_status']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Records an admin's verify/reject decision. The rejection reason is cleared on
     * verify so a re-verified payment does not keep displaying why it was once refused.
     */
    public function setPaymentStatus(int $memberId, string $status, int $adminUserId, ?string $rejectionReason = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE members
             SET payment_status = :status, verified_by = :admin, verified_at = :at,
                 rejection_reason = :reason
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'admin' => $adminUserId,
            'at' => date('Y-m-d H:i:s'),
            'reason' => $status === 'rejected' ? $rejectionReason : null,
            'id' => $memberId,
        ]);
    }

    /** Lightweight list for pickers (e.g. POS customer selection) — every member regardless of subscription status, since a walk-in purchase isn't gated on membership standing. */
    public function allForPicker(): array
    {
        $stmt = $this->db->query(
            "SELECT m.id, m.member_code, u.name, u.phone
             FROM members m JOIN users u ON u.id = m.user_id
             ORDER BY u.name ASC"
        );
        return $stmt->fetchAll();
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

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::BASE_SELECT . ' WHERE m.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $member = $stmt->fetch();
        return $member ? $this->withBmi($member) : null;
    }

    /**
     * @param array{search?:string,status?:string,trainer_id?:string,sort?:string} $filters
     */
    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 15): array
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM members m JOIN users u ON u.id = m.user_id' . $whereSql
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = self::BASE_SELECT . $whereSql . ' ORDER BY ' . $this->sortClause($filters['sort'] ?? '')
            . ' LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map([$this, 'withBmi'], $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public function adminStatistics(): array
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'active') AS active,
                    SUM(status = 'pending') AS pending,
                    SUM(status = 'expired') AS expired
             FROM members"
        );
        $row = $stmt->fetch();
        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'expired' => (int) ($row['expired'] ?? 0),
        ];
    }

    public function attendanceCountThisMonth(int $memberId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM attendance
             WHERE member_id = :id AND check_in >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        $stmt->execute(['id' => $memberId]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE members SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    /**
     * Recomputes one member's status from ground truth (their subscription history) —
     * pending (never purchased a package), active (latest subscription still within its
     * end date + grace period), or expired (past it). Called after any write that can
     * affect subscription state, so status is never hand-set and never goes stale.
     */
    public function recomputeStatus(int $memberId): void
    {
        $stmt = $this->db->prepare(
            'SELECT end_date FROM member_subscriptions WHERE member_id = :id ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['id' => $memberId]);
        $latest = $stmt->fetch();

        if (!$latest) {
            $status = 'pending';
        } else {
            $graceDays = (new Setting())->getInt('membership_grace_days', 0);
            $cutoff = (new DateTimeImmutable($latest['end_date']))->modify("+$graceDays days");
            $status = $cutoff >= new DateTimeImmutable(date('Y-m-d')) ? 'active' : 'expired';
        }

        $this->db->prepare('UPDATE members SET status = :status WHERE id = :id')
            ->execute(['status' => $status, 'id' => $memberId]);
    }

    /**
     * Bulk version of recomputeStatus() for every member at once — run on admin
     * members/dashboard page loads so "today > expiry" flips a member to Expired with
     * no manual action required. Gated by the auto_expire_memberships setting and honors
     * membership_grace_days, both already exposed in Admin Settings.
     */
    public function syncAllStatuses(): void
    {
        $settingModel = new Setting();
        if (!$settingModel->getBool('auto_expire_memberships', true)) {
            return;
        }
        $graceDays = (int) $settingModel->getInt('membership_grace_days', 0);
        $graceCutoff = date('Y-m-d', strtotime("-$graceDays days"));

        $this->db->prepare(
            "UPDATE members SET status = 'active'
             WHERE id IN (
                 SELECT member_id FROM member_subscriptions
                 WHERE end_date >= :cutoff1
             )"
        )->execute(['cutoff1' => $graceCutoff]);

        $this->db->prepare(
            "UPDATE members SET status = 'expired'
             WHERE id IN (
                 SELECT ms1.member_id FROM member_subscriptions ms1
                 JOIN (SELECT member_id, MAX(end_date) AS max_end FROM member_subscriptions GROUP BY member_id) ms2
                   ON ms1.member_id = ms2.member_id AND ms1.end_date = ms2.max_end
                 WHERE ms1.end_date < :cutoff2
             )"
        )->execute(['cutoff2' => $graceCutoff]);

        $this->db->exec(
            "UPDATE members SET status = 'pending'
             WHERE id NOT IN (SELECT DISTINCT member_id FROM member_subscriptions WHERE member_id IS NOT NULL)
               AND status != 'pending'"
        );
    }

    /**
     * Generates and stores this member's Money Received Number on their very first
     * successful payment of any kind, then leaves it untouched forever — every later
     * renewal/charge reuses the same receipt number. No-op if one already exists.
     */
    public function ensureMoneyReceivedNo(int $memberId): void
    {
        $stmt = $this->db->prepare('SELECT money_received_no FROM members WHERE id = :id');
        $stmt->execute(['id' => $memberId]);
        if ($stmt->fetchColumn()) {
            return;
        }

        $max = (int) $this->db->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(money_received_no, 4) AS UNSIGNED)), 0) FROM members WHERE money_received_no LIKE 'MR-%'"
        )->fetchColumn();
        $mrNo = 'MR-' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);

        $this->db->prepare('UPDATE members SET money_received_no = :mr WHERE id = :id')
            ->execute(['mr' => $mrNo, 'id' => $memberId]);
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

    /** Standard BMI calculation + category banding from height_cm/weight_kg, computed live (not stored). */
    private function withBmi(array $member): array
    {
        $heightCm = $member['height_cm'] ?? null;
        $weightKg = $member['weight_kg'] ?? null;

        if (!$heightCm || !$weightKg) {
            $member['bmi'] = null;
            $member['bmi_category'] = null;
            return $member;
        }

        $heightM = $heightCm / 100;
        $bmi = round($weightKg / ($heightM * $heightM), 1);

        $category = match (true) {
            $bmi < 18.5 => 'Underweight',
            $bmi < 25 => 'Normal',
            $bmi < 30 => 'Overweight',
            default => 'Obese',
        };

        $member['bmi'] = $bmi;
        $member['bmi_category'] = $category;
        return $member;
    }

    private function buildFilterClause(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE :search_name OR u.phone LIKE :search_phone OR u.email LIKE :search_email
                          OR m.member_code LIKE :search_code OR m.money_received_no LIKE :search_mr
                          OR m.reported_payment_reference LIKE :search_reported_ref
                          OR m.reported_payer_number LIKE :search_reported_payer
                          OR EXISTS (SELECT 1 FROM payments py WHERE py.member_id = m.id AND py.reference_no LIKE :search_ref))';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_phone'] = '%' . $filters['search'] . '%';
            $params['search_email'] = '%' . $filters['search'] . '%';
            $params['search_code'] = '%' . $filters['search'] . '%';
            $params['search_mr'] = '%' . $filters['search'] . '%';
            $params['search_ref'] = '%' . $filters['search'] . '%';
            $params['search_reported_ref'] = '%' . $filters['search'] . '%';
            $params['search_reported_payer'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 'm.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['trainer_id'])) {
            $where[] = 'm.trainer_id = :trainer_id';
            $params['trainer_id'] = $filters['trainer_id'];
        }

        return [$where, $params];
    }

    private function sortClause(string $sort): string
    {
        $sortMap = [
            'name' => 'u.name ASC',
            'expiry' => 'sub.end_date ASC',
            'oldest' => 'm.created_at ASC',
        ];
        return $sortMap[$sort] ?? 'm.created_at DESC';
    }
}
