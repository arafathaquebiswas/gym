<?php

/**
 * Resolves whether the current user can do something with a module — the single choke point
 * for the Main Admin / Super Admin / Staff hierarchy. Mirrors core/Feature.php's static-helper
 * style. See database/schema.sql (user_permissions, module_locks) and models/UserPermission.php
 * / models/ModuleLock.php for the underlying storage.
 */
final class Permission
{
    /** @var array<string, string>|null */
    private static ?array $lockCache = null;

    /** @var array<int, array<string, array>>|null */
    private static array $permissionCache = [];

    public static function can(string $moduleKey, string $action = 'view'): bool
    {
        // Main Admin is never subject to a lock scope or a permission row — hard-coded, not
        // data-driven, so it can never be misconfigured into locking itself out.
        if (Auth::hasRole('main_admin')) {
            return true;
        }
        if (!Auth::check()) {
            return false;
        }

        $scope = self::lockScope($moduleKey);
        if ($scope === 'main_admin_only') {
            return false;
        }
        if ($scope === 'main_admin_super_admin' && Auth::hasRole('staff')) {
            return false;
        }
        if ($scope === 'main_admin_staff' && Auth::hasRole('super_admin')) {
            return false;
        }

        $userId = (int) Auth::user()['id'];
        $permissions = self::permissionsFor($userId);
        $row = $permissions[$moduleKey] ?? null;

        if ($row === null) {
            // No explicit row: super_admin keeps its existing unrestricted behavior by default;
            // staff must be explicitly granted every module.
            return Auth::hasRole('super_admin');
        }

        return (bool) ($row["can_$action"] ?? false);
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
