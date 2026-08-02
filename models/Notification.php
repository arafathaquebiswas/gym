<?php

final class Notification extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            link VARCHAR(255) NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'order',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_notifications_user_read (user_id, is_read, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        try {
            $this->db->exec($sql);
        } catch (Throwable $e) {
            // Ignore if table already exists or constraint exists
        }
    }

    public static function notifyNewOrder(array $order): void
    {
        $db = Database::connection();

        // Find all active staff/admin users who can view orders
        $stmt = $db->query(
            "SELECT u.id, r.slug AS role_slug 
             FROM users u 
             JOIN roles r ON r.id = u.role_id 
             WHERE u.status = 'active' 
               AND r.slug IN ('main_admin', 'super_admin', 'admin', 'staff', 'receptionist', 'store_manager')"
        );
        $users = $stmt->fetchAll();

        $orderId = (int) $order['id'];
        $orderNo = $order['order_no'] ?? ('#' . $orderId);
        $customerName = $order['customer_name'] ?? 'Walk-in Customer';
        $totalAmount = '৳' . number_format((float) ($order['total'] ?? 0), 2);
        $paymentMethod = strtoupper((string) ($order['payment_method'] ?? 'CASH'));
        $orderStatus = ucfirst(str_replace('_', ' ', (string) ($order['status'] ?? 'pending')));
        $dateTime = date('d M Y, h:i A');

        $title = "🛒 New Order Received: {$orderNo}";
        $message = "Customer: {$customerName} | Total: {$totalAmount} | Payment: {$paymentMethod} | Status: {$orderStatus} | Time: {$dateTime}";
        $link = url("/admin/orders/{$orderId}");

        $insertStmt = $db->prepare(
            "INSERT INTO notifications (user_id, title, message, link, type, is_read, created_at)
             VALUES (:user_id, :title, :message, :link, 'order', 0, NOW())"
        );

        foreach ($users as $user) {
            $userId = (int) $user['id'];
            $role = $user['role_slug'];

            // Main Admin, Super Admin, Admin, and Store Manager always get order notifications
            // Staff & Receptionist get notification if permitted
            if (in_array($role, ['main_admin', 'super_admin', 'admin', 'store_manager', 'receptionist'], true)
                || Permission::can('orders', 'view')) {
                $insertStmt->execute([
                    'user_id' => $userId,
                    'title' => $title,
                    'message' => $message,
                    'link' => $link,
                ]);
            }
        }
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function latestForUser(int $userId, int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC, id DESC 
             LIMIT :limit"
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function paginateForUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id");
        $countStmt->execute(['user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM notifications 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC, id DESC 
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function markAsRead(int $id, int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function markAllAsRead(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0");
        $stmt->execute(['user_id' => $userId]);
    }
}
