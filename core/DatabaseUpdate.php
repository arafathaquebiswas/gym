<?php

/**
 * The schema changes a release needs, in a form both the CLI migrations and the
 * admin panel can run.
 *
 * Pushing to master publishes code immediately while migrations are run by hand
 * afterwards, and on shared hosting "by hand" means finding a terminal. That gap
 * has repeatedly shipped code referencing columns the database did not have yet.
 * Listing the updates here lets Settings offer a button, so the person who owns
 * the site can close the gap without a shell.
 *
 * Every update must be safe to run twice: isApplied() is asked first, and apply()
 * only ever adds or removes the one thing it names. Nothing here touches a row of
 * business data — schema only. Anything that rewrites data belongs in a migration
 * a human runs deliberately, not behind a button.
 */
final class DatabaseUpdate
{
    /**
     * @return array<int, array{key:string, label:string, detail:string, isApplied:callable, apply:callable}>
     */
    private static function definitions(): array
    {
        return [
            [
                'key' => 'sale_cancellation',
                'label' => 'Cancel Sell support',
                'detail' => 'Adds status, cancelled_at and cancelled_by to sales, so a completed'
                    . ' invoice can be voided and its stock returned.',
                'isApplied' => static fn (): bool => Schema::hasColumn('sales', 'status')
                    && Schema::hasColumn('sales', 'cancelled_at')
                    && Schema::hasColumn('sales', 'cancelled_by'),
                'apply' => static function (PDO $db): void {
                    // VARCHAR not ENUM so the statement parses on SQLite too.
                    if (!Schema::hasColumn('sales', 'status')) {
                        $db->exec("ALTER TABLE sales ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'completed'");
                    }
                    if (!Schema::hasColumn('sales', 'cancelled_at')) {
                        $db->exec('ALTER TABLE sales ADD COLUMN cancelled_at DATETIME NULL');
                    }
                    // No foreign key: removing a staff account must not erase who voided a sale.
                    if (!Schema::hasColumn('sales', 'cancelled_by')) {
                        $db->exec('ALTER TABLE sales ADD COLUMN cancelled_by INT UNSIGNED NULL');
                    }
                },
            ],
            [
                'key' => 'drop_sale_stock_trigger',
                'label' => 'Stop sales deducting stock twice',
                'detail' => 'Removes the trg_sale_items_after_insert trigger. It subtracted stock, and'
                    . ' Sale::create() subtracts it as well, so every sale removed double what it sold.'
                    . ' Does not correct counts that have already drifted.',
                'isApplied' => static fn (): bool => !self::saleTriggerExists(),
                'apply' => static function (PDO $db): void {
                    if (self::saleTriggerExists()) {
                        $db->exec('DROP TRIGGER trg_sale_items_after_insert');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<int, array{key:string, label:string, detail:string, applied:bool}>
     */
    public static function status(): array
    {
        $out = [];
        foreach (self::definitions() as $update) {
            $out[] = [
                'key' => $update['key'],
                'label' => $update['label'],
                'detail' => $update['detail'],
                'applied' => (bool) ($update['isApplied'])(),
            ];
        }

        return $out;
    }

    public static function pendingCount(): int
    {
        $count = 0;
        foreach (self::status() as $update) {
            if (!$update['applied']) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Applies everything outstanding.
     *
     * Each update stands alone rather than sharing one transaction: MySQL commits
     * implicitly on DDL, so wrapping them would be a comforting lie. Applying them
     * one at a time means a failure halfway leaves the earlier ones genuinely
     * done, and the report says exactly which.
     *
     * @return array{applied: string[], failed: array<string, string>}
     */
    public static function runPending(): array
    {
        $db = Database::connection();
        $applied = [];
        $failed = [];

        foreach (self::definitions() as $update) {
            if (($update['isApplied'])()) {
                continue;
            }

            try {
                ($update['apply'])($db);
                Schema::forget();
                $applied[] = $update['label'];
            } catch (Throwable $e) {
                error_log('Database update "' . $update['key'] . '" failed: ' . $e->getMessage());
                $failed[$update['label']] = $e->getMessage();
            }
        }

        return ['applied' => $applied, 'failed' => $failed];
    }

    private static function saleTriggerExists(): bool
    {
        $db = Database::connection();

        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                return (bool) $db->query(
                    "SELECT COUNT(*) FROM sqlite_master WHERE type = 'trigger' AND name = 'trg_sale_items_after_insert'"
                )->fetchColumn();
            }
            foreach ($db->query("SHOW TRIGGERS LIKE 'sale_items'")->fetchAll(PDO::FETCH_ASSOC) as $trigger) {
                if (($trigger['Trigger'] ?? '') === 'trg_sale_items_after_insert') {
                    return true;
                }
            }
        } catch (PDOException $e) {
            return false;
        }

        return false;
    }
}
