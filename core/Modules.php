<?php

/**
 * The fixed registry of admin modules that permissions/locks are checked against — developer
 * defined, not admin-creatable, so this is a plain constant list rather than a database table.
 * 'inventory'/'sales'/'expenses' don't have a fully standalone admin page yet (inventory is
 * stock-adjustment actions inside ProductAdminController, sales is a read-only history view,
 * expenses has no admin UI at all) — they're still registered so Role Management can show a
 * checkbox for them now, ready for whichever of those gets a real page later.
 */
final class Modules
{
    public const ALL = [
        'dashboard' => 'Dashboard',
        'members' => 'Members',
        'trainers' => 'Trainers',
        'packages' => 'Packages',
        'store' => 'Store',
        'pos' => 'POS',
        'orders' => 'Orders',
        'reports' => 'Reports',
        'reviews' => 'Reviews',
        'messages' => 'Messages',
        'audit_logs' => 'Audit Logs',
        'settings' => 'Settings',
        'coupons' => 'Coupons',
        'inventory' => 'Inventory',
        'purchases' => 'Purchases',
        'sales' => 'Sales',
        'expenses' => 'Expenses',
    ];

    public static function label(string $key): string
    {
        return self::ALL[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}
