<?php

/**
 * Clears trading history so the gym can start from a clean slate after testing
 * with demo data.
 *
 * Deliberately narrow: it removes what the business *did* (sales, payments,
 * members, movements, logs) and never what the business *is* (products,
 * packages, trainers, blog, gallery, coupons, zones) or how it is run (admin
 * logins, settings, roles, permissions). Wiping a catalogue is a different
 * decision from wiping a ledger and should not hide behind the same button.
 *
 * Ordering is by foreign key, children first, so the delete succeeds with
 * PRAGMA foreign_keys ON rather than relying on cascade behaviour that differs
 * between SQLite and MySQL. The whole thing runs in one transaction: a partial
 * reset would leave orphaned rows that are worse than either end state.
 */
final class DataReset
{
    /**
     * Children before parents. `members` is absent on purpose — member rows are
     * removed by deleting their user, which cascades, so the two can never
     * disagree about who exists.
     */
    private const TRANSACTIONAL_TABLES = [
        'coupon_usages',
        'payments',
        'sale_items',
        'sales',
        'order_items',
        'orders',
        'stock_movements',
        'attendance',
        'trainer_booking',
        'member_subscriptions',
        'notifications',
        'activity_logs',
    ];

    /**
     * @return array<string,int> table => rows removed, for the confirmation message
     */
    public static function clearTransactions(): array
    {
        $db = Database::connection();
        $removed = [];

        $db->beginTransaction();
        try {
            foreach (self::TRANSACTIONAL_TABLES as $table) {
                $removed[$table] = self::deleteAll($db, $table);
            }

            // Members live half in `members` and half in `users`. Deleting the
            // user cascades to the member row; deleting the member row alone
            // would strand a login that can never be used.
            $stmt = $db->prepare(
                "DELETE FROM users
                 WHERE role_id = (SELECT id FROM roles WHERE slug = 'member')"
            );
            $stmt->execute();
            $removed['members'] = $stmt->rowCount();

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        return array_filter($removed);
    }

    /** A missing table is skipped rather than fatal — an install may predate one. */
    private static function deleteAll(PDO $db, string $table): int
    {
        try {
            $stmt = $db->prepare("DELETE FROM $table");
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }
}
