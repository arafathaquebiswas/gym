<?php

final class Notification extends Model
{
    /** Canonical category map: slug => [icon, badge-colour, label] */
    public const CATEGORIES = [
        'order'      => ['icon' => '🛒', 'color' => 'bg-orange',  'label' => 'Orders'],
        'member'     => ['icon' => '👤', 'color' => 'bg-primary', 'label' => 'Members'],
        'payment'    => ['icon' => '💳', 'color' => 'bg-success', 'label' => 'Payments'],
        'attendance' => ['icon' => '📋', 'color' => 'bg-info',    'label' => 'Attendance'],
        'inventory'  => ['icon' => '📦', 'color' => 'bg-warning', 'label' => 'Inventory'],
        'message'    => ['icon' => '✉️', 'color' => 'bg-purple',  'label' => 'Messages'],
        'system'     => ['icon' => '⚙️', 'color' => 'bg-secondary','label' => 'System'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    /* ──────────────────────────────────────────────────────────────
       Schema bootstrap  (runs once per request; no-op if up to date)
    ────────────────────────────────────────────────────────────── */
    private function ensureTableExists(): void
    {
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS notifications (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    title VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    link VARCHAR(255) NULL,
                    type VARCHAR(50) NOT NULL DEFAULT 'order',
                    category VARCHAR(50) NOT NULL DEFAULT 'order',
                    is_read TINYINT(1) NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_notif_user_read (user_id, is_read, created_at),
                    INDEX idx_notif_user_cat  (user_id, category, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
            );

            // Migrate: add category column if missing (safe ALTER)
            $cols = array_column(
                $this->db->query("SHOW COLUMNS FROM notifications")->fetchAll(),
                'Field'
            );
            if (!in_array('category', $cols, true)) {
                $this->db->exec(
                    "ALTER TABLE notifications ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'order'
                     AFTER type,
                     ADD INDEX idx_notif_user_cat (user_id, category, created_at)"
                );
            }
        } catch (Throwable) {
            // Ignore: table already exists or constraint conflict
        }
    }

    /* ──────────────────────────────────────────────────────────────
       Fan-out helpers — called by Order, Member, Payment, etc.
    ────────────────────────────────────────────────────────────── */

    /** Fan notification to a list of user-ids. */
    public static function fanOut(
        array $userIds,
        string $category,
        string $title,
        string $message,
        string $link = ''
    ): void {
        if (empty($userIds)) return;
        $db = Database::connection();
        $stmt = $db->prepare(
            "INSERT IGNORE INTO notifications (user_id, title, message, link, type, category, is_read, created_at)
             VALUES (:user_id, :title, :message, :link, :category, :category2, 0, NOW())"
        );
        foreach (array_unique($userIds) as $uid) {
            // Honour per-user category opt-out preference
            $pref = (new Setting())->get("user_notif_cat_{$uid}_{$category}", '1');
            if ($pref === '0') continue;
            $stmt->execute([
                'user_id'   => (int) $uid,
                'title'     => $title,
                'message'   => $message,
                'link'      => $link,
                'category'  => $category,
                'category2' => $category,
            ]);
        }
    }

    /** Returns all active admin/staff user-ids who have the given role slugs. */
    public static function adminUserIds(array $roleSlugs): array
    {
        $in = implode(',', array_fill(0, count($roleSlugs), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT u.id FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.status = 'active' AND r.slug IN ({$in})"
        );
        $stmt->execute(array_values($roleSlugs));
        return array_column($stmt->fetchAll(), 'id');
    }

    /** Order notification fan-out (called by Order::create()). */
    public static function notifyNewOrder(array $order): void
    {
        $userIds = self::adminUserIds([
            'main_admin', 'super_admin', 'admin', 'staff', 'receptionist', 'store_manager',
        ]);
        $orderId     = (int) $order['id'];
        $orderNo     = $order['order_no'] ?? ('#' . $orderId);
        $customer    = $order['customer_name'] ?? 'Customer';
        $total       = '৳' . number_format((float) ($order['total'] ?? 0), 2);
        $method      = strtoupper($order['payment_method'] ?? 'CASH');
        $status      = ucfirst(str_replace('_', ' ', $order['status'] ?? 'pending'));

        self::fanOut(
            $userIds,
            'order',
            "🛒 New Order: {$orderNo}",
            "Customer: {$customer} | Total: {$total} | Payment: {$method} | Status: {$status}",
            url("/admin/orders/{$orderId}")
        );
    }

    /* ──────────────────────────────────────────────────────────────
       Read queries
    ────────────────────────────────────────────────────────────── */

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0"
        );
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function latestForUser(int $userId, int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications
             WHERE user_id = :uid
             ORDER BY created_at DESC, id DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Filtered, searched, paginated list for the history page.
     *
     * @param array{filter?:string, category?:string, search?:string, date_from?:string, date_to?:string} $filters
     */
    public function paginateForUser(
        int $userId,
        int $page    = 1,
        int $perPage = 25,
        array $filters = []
    ): array {
        $where  = ['user_id = :uid'];
        $params = ['uid' => $userId];

        // read-status filter
        $readFilter = $filters['filter'] ?? 'all';
        if ($readFilter === 'unread') {
            $where[] = 'is_read = 0';
        } elseif ($readFilter === 'read') {
            $where[] = 'is_read = 1';
        }

        // category filter
        $cat = $filters['category'] ?? '';
        if ($cat !== '' && array_key_exists($cat, self::CATEGORIES)) {
            $where[] = 'category = :cat';
            $params['cat'] = $cat;
        }

        // keyword search
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $where[] = '(title LIKE :srch OR message LIKE :srch2)';
            $params['srch']  = "%{$search}%";
            $params['srch2'] = "%{$search}%";
        }

        // date range
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo   = $filters['date_to']   ?? '';
        if ($dateFrom !== '') {
            $where[] = 'created_at >= :df';
            $params['df'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $where[] = 'created_at <= :dt';
            $params['dt'] = $dateTo . ' 23:59:59';
        }

        $sql   = 'WHERE ' . implode(' AND ', $where);
        $count = (int) $this->db->prepare("SELECT COUNT(*) FROM notifications {$sql}")
                       ->execute($params) ? $this->db->prepare("SELECT COUNT(*) FROM notifications {$sql}")
                       ->execute($params) : 0;

        // Re-execute count properly
        $cStmt = $this->db->prepare("SELECT COUNT(*) FROM notifications {$sql}");
        $cStmt->execute($params);
        $total = (int) $cStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $lStmt  = $this->db->prepare(
            "SELECT * FROM notifications {$sql}
             ORDER BY created_at DESC, id DESC
             LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) {
            $lStmt->bindValue(':' . $k, $v);
        }
        $lStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $lStmt->bindValue(':off', $offset,  PDO::PARAM_INT);
        $lStmt->execute();

        return [
            'items'      => $lStmt->fetchAll(),
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /* ──────────────────────────────────────────────────────────────
       Write operations
    ────────────────────────────────────────────────────────────── */

    public function markAsRead(int $id, int $userId): void
    {
        $this->db->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid"
        )->execute(['id' => $id, 'uid' => $userId]);
    }

    public function markAllAsRead(int $userId, string $category = ''): void
    {
        if ($category !== '' && array_key_exists($category, self::CATEGORIES)) {
            $this->db->prepare(
                "UPDATE notifications SET is_read = 1
                 WHERE user_id = :uid AND is_read = 0 AND category = :cat"
            )->execute(['uid' => $userId, 'cat' => $category]);
        } else {
            $this->db->prepare(
                "UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0"
            )->execute(['uid' => $userId]);
        }
    }

    /** Delete notifications older than $days days. Returns deleted row count. */
    public static function cleanup(int $days = 90): int
    {
        $stmt = Database::connection()->prepare(
            "DELETE FROM notifications WHERE created_at < NOW() - INTERVAL :d DAY"
        );
        $stmt->bindValue(':d', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
