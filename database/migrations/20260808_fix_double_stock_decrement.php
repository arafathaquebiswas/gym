<?php
/**
 * Stops POS sales from removing stock twice.
 *
 * schema.sql ships trg_sale_items_after_insert, which subtracts NEW.qty from
 * products.stock_qty whenever a sale_items row is inserted. Sale::create() was
 * later given its own explicit "UPDATE products SET stock_qty = stock_qty - :qty"
 * — needed because SQLite installs have no such trigger, and because the PHP
 * path also writes the stock_movements row and fires the low-stock alert. Nobody
 * removed the trigger, so on MySQL both fire and a sale of 2 units drops the
 * count by 4.
 *
 * The PHP side wins and the trigger goes: it is the only one of the two that
 * works on both engines, and the only one that leaves an audit trail. The
 * purchases trigger is deliberately left alone — Purchase::create() has no
 * explicit update and relies on it.
 *
 * This does NOT correct stock counts that have already drifted. Every past sale
 * took one qty too many, but an admin may since have re-counted a shelf by hand,
 * and adding the difference back would then overshoot. The script reports the
 * drift per product and leaves the decision to a human.
 *
 * Re-runnable. SQLite has no trigger to drop, so it is a no-op there.
 *
 * Usage: /opt/alt/php83/usr/bin/php database/migrations/20260808_fix_double_stock_decrement.php
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

$triggerExists = static function () use ($db, $driver): bool {
    if ($driver === 'sqlite') {
        return (bool) $db->query(
            "SELECT COUNT(*) FROM sqlite_master WHERE type = 'trigger' AND name = 'trg_sale_items_after_insert'"
        )->fetchColumn();
    }
    foreach ($db->query("SHOW TRIGGERS LIKE 'sale_items'")->fetchAll(PDO::FETCH_ASSOC) as $trigger) {
        if (($trigger['Trigger'] ?? '') === 'trg_sale_items_after_insert') {
            return true;
        }
    }
    return false;
};

if ($triggerExists()) {
    $db->exec('DROP TRIGGER trg_sale_items_after_insert');
    echo "  trg_sale_items_after_insert dropped\n";
} else {
    echo "  trg_sale_items_after_insert not present (nothing to do)\n";
}

// Report only. See the note above on why this does not write anything.
echo "\nStock drift from the period when both the trigger and the PHP update ran.\n";
echo "Each unit sold in that window was deducted once too often.\n\n";

$rows = $db->query(
    'SELECT p.id, p.name, p.stock_qty, SUM(si.qty) AS sold
       FROM sale_items si
       JOIN products p ON p.id = si.product_id
      GROUP BY p.id, p.name, p.stock_qty
      ORDER BY sold DESC'
)->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "  No sales recorded — no drift possible.\n";
} else {
    printf("  %-6s %-38s %10s %10s\n", 'ID', 'PRODUCT', 'STOCK NOW', 'EVER SOLD');
    foreach ($rows as $row) {
        printf(
            "  %-6s %-38s %10s %10s\n",
            $row['id'],
            mb_strimwidth((string) $row['name'], 0, 38, '…'),
            $row['stock_qty'],
            $row['sold']
        );
    }
    echo "\n  'EVER SOLD' is the upper bound on how far a product may be under-counted.\n";
    echo "  Sales made before the double-decrement was introduced are correct, so the\n";
    echo "  real figure is lower. Verify against a physical count before adjusting, and\n";
    echo "  make any correction through Products -> Adjust Stock so it is audited.\n";
}

echo "\nDone.\n";
