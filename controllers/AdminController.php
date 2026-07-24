<?php

/**
 * Base for every admin-area controller — role-gates by construction so no
 * individual action can accidentally skip the check.
 */
abstract class AdminController extends Controller
{
    /** Every concrete subclass sets this to its Modules::ALL key so Permission::require() knows what to check. Left blank = no per-module gate (only RoleAdminController, which has its own hard-coded access rules). */
    protected string $moduleKey = '';

    public function __construct()
    {
        Auth::requireRole('main_admin', 'super_admin', 'staff', 'admin');

        if ($this->moduleKey !== '') {
            Permission::require($this->moduleKey);
        }
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
