<?php
/**
 * One-off migration runner — CLI only.
 *
 * Applies a .sql migration through the application's own Database connection,
 * so it lands on whichever engine is actually live (MySQL or the SQLite file)
 * without having to know which that is.
 *
 * Usage:
 *   php database/migrate.php migrations/20260806_add_missing_roles.sql
 *
 * On Hostinger shared hosting with no SSH, run it once as a cron job:
 *   php /home/USER/domains/DOMAIN/public_html/database/migrate.php \
 *       migrations/20260806_add_missing_roles.sql
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/core/bootstrap.php';

$relative = $argv[1] ?? null;
if ($relative === null) {
    fwrite(STDERR, "Usage: php database/migrate.php <file.sql>\n");
    exit(1);
}

// Resolved against database/ and confined to it, so a stray argument cannot
// reach arbitrary files elsewhere on the server.
$path = realpath(__DIR__ . '/' . $relative);
if ($path === false || !str_starts_with($path, __DIR__ . DIRECTORY_SEPARATOR) || !is_file($path)) {
    fwrite(STDERR, "Not a readable file inside database/: $relative\n");
    exit(1);
}

$db = Database::connection();
echo 'Driver: ' . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;

// Split on statement boundaries, dropping comment-only and blank fragments.
$statements = array_filter(array_map('trim', explode(';', file_get_contents($path))), static function (string $s): bool {
    foreach (explode("\n", $s) as $line) {
        $line = trim($line);
        if ($line !== '' && !str_starts_with($line, '--')) {
            return true;
        }
    }
    return false;
});

$db->beginTransaction();
try {
    foreach ($statements as $i => $sql) {
        $affected = $db->exec($sql);
        echo 'Statement ' . ($i + 1) . ": OK ($affected row(s))" . PHP_EOL;
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, 'Migration failed, rolled back: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo PHP_EOL . 'Roles now present:' . PHP_EOL;
foreach ($db->query('SELECT id, slug, name FROM roles ORDER BY id')->fetchAll() as $role) {
    echo "  {$role['id']}  {$role['slug']}  ({$role['name']})" . PHP_EOL;
}

echo PHP_EOL . 'Done.' . PHP_EOL;
