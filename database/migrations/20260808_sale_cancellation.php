<?php
/**
 * Lets a completed POS sale be cancelled without deleting it.
 *
 * The sale row must survive: invoice_no is unique and sequential, and removing
 * one would leave a hole in the numbering that no audit could explain. A status
 * column marks it instead, so the invoice still exists and still prints — just
 * stamped CANCELLED.
 *
 * sales.payment_status already has 'refunded', but that describes the money, not
 * the sale. A cancelled sale and a refunded-but-valid sale are different things
 * and reports need to tell them apart.
 *
 * Re-runnable: every step checks first.
 *
 * Usage: /opt/alt/php83/usr/bin/php database/migrations/20260808_sale_cancellation.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require dirname(__DIR__, 2) . '/config/config.php';
require BASE_PATH . '/core/bootstrap.php';

$db = Database::connection();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
echo "Driver: $driver\n";

$columns = static function (string $table) use ($db, $driver): array {
    if ($driver === 'sqlite') {
        return array_column($db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC), 'name');
    }
    return array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
};

$addColumn = static function (string $table, string $column, string $definition) use ($db, $columns): void {
    if (in_array($column, $columns($table), true)) {
        echo "  $table.$column already present\n";
        return;
    }
    $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
    echo "  $table.$column added\n";
};

// VARCHAR not ENUM so the ALTER parses on SQLite too; values validated in PHP.
$addColumn('sales', 'status', "VARCHAR(20) NOT NULL DEFAULT 'completed'");
$addColumn('sales', 'cancelled_at', 'DATETIME NULL');
// No foreign key on cancelled_by: removing a staff account must not erase who voided a sale.
$addColumn('sales', 'cancelled_by', 'INT UNSIGNED NULL');

echo "Done.\n";
