<?php

/**
 * Resolves whether the current user can do something with a module — the single choke point
 * for the Main Admin / Super Admin / Staff hierarchy. Mirrors core/Feature.php's static-helper
 * style. See database/schema.sql (user_permissions, module_locks) and models/UserPermission.php
 * / models/ModuleLock.php for the underlying storage.
 */
final class Permission
{
    /** Which non-Main-Admin roles a lock scope admits, beyond the implicit Main Admin. Empty array = Main Admin only. */
    private const SCOPE_ROLES = [
        'everyone' => ['super_admin', 'admin', 'staff', 'delivery'],
        'main_admin_super_admin' => ['super_admin', 'admin'],
        'main_admin_staff' => ['staff'],
        'main_admin_delivery' => ['delivery'],
        'main_admin_super_admin_staff' => ['super_admin', 'admin', 'staff'],
        'main_admin_super_admin_delivery' => ['super_admin', 'admin', 'delivery'],
        'main_admin_only' => [],
    ];

    /** @var array<string, string>|null */
    private static ?array $lockCache = null;

    /** @var array<int, array<string, array>>|null */
    private static array $permissionCache = [];

    public static function can(string $moduleKey, string $action = 'view'): bool
    {
        // 1. Only Main Admin is the absolute owner — never subject to a lock scope or permission row.
        if (Auth::hasRole('main_admin')) {
            return true;
        }

        if (!Auth::check()) {
            return false;
        }

        // 2. If Main Admin locked this module specifically to 'main_admin_only', nobody else can access it.
        $scope = self::lockScope($moduleKey);
        if ($scope === 'main_admin_only') {
            return false;
        }

        // 3. Check individual custom permissions assigned specifically to this user ID
        $userId = (int) Auth::user()['id'];
        $permissions = self::permissionsFor($userId);
        $row = $permissions[$moduleKey] ?? null;

        if ($row !== null) {
            return (bool) ($row["can_$action"] ?? false);
        }

        // 4. Group Module Lock scope check for users without individual custom permission rows
        $allowedRoles = self::SCOPE_ROLES[$scope] ?? [];
        return !empty($allowedRoles) && Auth::hasRole(...$allowedRoles);
    }

    /** Same as can(), but 403s immediately when denied — the single-line gate every AdminController subclass calls. */
    public static function require(string $moduleKey, string $action = 'view'): void
    {
        if (!self::can($moduleKey, $action)) {
            http_response_code(403);
            die('403 - Permission Denied');
        }
    }

    private static function lockScope(string $moduleKey): string
    {
        if (self::$lockCache === null) {
            self::$lockCache = (new ModuleLock())->all();
        }
        return self::$lockCache[$moduleKey] ?? 'everyone';
    }

    private static function permissionsFor(int $userId): array
    {
        if (!isset(self::$permissionCache[$userId])) {
            self::$permissionCache[$userId] = (new UserPermission())->forUser($userId);
        }
        return self::$permissionCache[$userId];
    }

    /** Clears the in-request cache — call after any permission/lock write so a subsequent read in the same request sees it. */
    public static function clearCache(): void
    {
        self::$lockCache = null;
        self::$permissionCache = [];
    }
}
