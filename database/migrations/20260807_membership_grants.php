<?php
/**
 * Manual membership grants: free/gift memberships, lifetime terms, and an audit
 * trail for changes an admin makes to an existing membership.
 *
 * Reuses member_subscriptions rather than introducing a parallel "memberships"
 * table — it already carries member, package, start/end, price, notes, status,
 * created_by and created_at, and Member::recomputeStatus() already derives a
 * member's Active/Expired state from it. Two columns are all it lacks.
 *
 * Lifetime is stored as is_lifetime = 1 plus end_date = 9999-12-31 rather than a
 * NULL end_date. end_date is NOT NULL and feeds expiry queries, reports and
 * recomputeStatus(); making it nullable would mean a table rebuild on SQLite and
 * a NULL check in every one of those places. A far-future sentinel keeps all of
 * them working untouched, and is_lifetime tells the UI to print "Lifetime"
 * instead of the date.
 *
 * membership_changes is a genuine new table: each subscription row is already an
 * append-only record of a *term*, but editing one (extending it, converting paid
 * to gift, correcting a date) overwrites values that have to survive for audit.
 * It records before and after for exactly the fields an admin can change.
 *
 * Re-runnable: every step checks first.
 *
 * Usage: /opt/alt/php83/usr/bin/php database/migrations/20260807_membership_grants.php
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

/** @return string[] */
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

$tableExists = static function (string $table) use ($db, $driver): bool {
    if ($driver === 'sqlite') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name = :t");
    } else {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
    }
    $stmt->execute(['t' => $table]);
    return (int) $stmt->fetchColumn() > 0;
};

// ---- member_subscriptions: how the term was granted, and whether it ever ends ----
// 'paid' default so every existing subscription keeps its current meaning.
$addColumn('member_subscriptions', 'grant_type', "VARCHAR(20) NOT NULL DEFAULT 'paid'");
$addColumn('member_subscriptions', 'is_lifetime', 'TINYINT(1) NOT NULL DEFAULT 0');

// ---- membership_changes: before/after for every admin edit ----
if ($tableExists('membership_changes')) {
    echo "  membership_changes already present\n";
} else {
    $pk = $driver === 'sqlite'
        ? 'id INTEGER PRIMARY KEY AUTOINCREMENT'
        : 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY';

    $db->exec(
        "CREATE TABLE membership_changes (
            $pk,
            member_id           INT UNSIGNED NOT NULL,
            subscription_id     INT UNSIGNED NULL,
            action              VARCHAR(20) NOT NULL,
            previous_package_id INT UNSIGNED NULL,
            new_package_id      INT UNSIGNED NULL,
            previous_start_date DATE NULL,
            new_start_date      DATE NULL,
            previous_end_date   DATE NULL,
            new_end_date        DATE NULL,
            previous_grant_type VARCHAR(20) NULL,
            new_grant_type      VARCHAR(20) NULL,
            previous_price      DECIMAL(10,2) NULL,
            new_price           DECIMAL(10,2) NULL,
            previous_lifetime   TINYINT(1) NULL,
            new_lifetime        TINYINT(1) NULL,
            reason              VARCHAR(255) NULL,
            changed_by          INT UNSIGNED NULL,
            created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );
    echo "  membership_changes created\n";

    // No foreign keys on changed_by: removing a staff account must never take a
    // member's audit history with it. member_id is indexed because the member
    // page reads this per member.
    $db->exec('CREATE INDEX idx_membership_changes_member ON membership_changes (member_id)');
    echo "  idx_membership_changes_member created\n";
}

echo "Done.\n";
