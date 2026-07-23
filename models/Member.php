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
        'height_cm', 'weight_kg', 'fitness_goal', 'medical_notes',
        'join_date', 'trainer_id', 'locker_number', 'photo',
        'notify_email', 'notify_promotions',
    ];

    private const BASE_SELECT = "SELECT m.*, u.name, u.email, u.phone, u.status AS account_status,
             t.name AS trainer_name,
             sub.package_name, sub.end_date AS subscription_end, sub.status AS subscription_status,
             (SELECT COUNT(*) FROM attendance a WHERE a.member_id = m.id AND a.check_in >= DATE_FORMAT(NOW(), '%Y-%m-01')) AS attendance_this_month
             FROM members m
             JOIN users u ON u.id = m.user_id
             LEFT JOIN trainers t ON t.id = m.trainer_id
             LEFT JOIN (
                 SELECT ms.member_id, mp.name AS package_name, ms.end_date, ms.status
                 FROM member_subscriptions ms
                 JOIN membership_packages mp ON mp.id = ms.package_id
                 WHERE ms.id IN (SELECT MAX(id) FROM member_subscriptions GROUP BY member_id)
             ) sub ON sub.member_id = m.id";

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
        $graceDays = $settingModel->getInt('membership_grace_days', 0);

        $this->db->exec(
            "UPDATE members m
             JOIN (SELECT member_id, MAX(id) AS latest_id FROM member_subscriptions GROUP BY member_id) x
                ON x.member_id = m.id
             JOIN member_subscriptions ms ON ms.id = x.latest_id
             SET m.status = IF(DATE_ADD(ms.end_date, INTERVAL $graceDays DAY) >= CURDATE(), 'active', 'expired')"
        );

        $this->db->exec(
            "UPDATE members m
             LEFT JOIN member_subscriptions ms ON ms.member_id = m.id
             SET m.status = 'pending'
             WHERE ms.id IS NULL AND m.status != 'pending'"
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
            $where[] = '(u.name LIKE :search_name OR u.phone LIKE :search_phone OR u.email LIKE :search_email OR m.member_code LIKE :search_code)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_phone'] = '%' . $filters['search'] . '%';
            $params['search_email'] = '%' . $filters['search'] . '%';
            $params['search_code'] = '%' . $filters['search'] . '%';
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
