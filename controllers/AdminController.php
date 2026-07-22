<?php

/**
 * Base for every admin-area controller — role-gates by construction so no
 * individual action can accidentally skip the check.
 */
abstract class AdminController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('super_admin', 'admin');
    }

    protected function logActivity(string $action, string $description): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
             VALUES (:user_id, :action, :description, :ip, NOW())'
        );
        $stmt->execute([
            'user_id' => Auth::user()['id'],
            'action' => $action,
            'description' => $description,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    }
}
