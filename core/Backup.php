<?php

/**
 * Pure-PHP database backup/restore — no shell_exec/mysqldump dependency,
 * since shared hosting (this app's deploy target, per config/config.php)
 * often disables shell execution entirely.
 */
final class Backup
{
    public static function export(): string
    {
        $db = Database::connection();
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            return self::exportSqlite($db);
        }

        return self::exportMysql($db);
    }

    private static function exportMysql(PDO $db): string
    {
        $sql = "-- POWERSURGE GYM & NUTRITION database backup — generated " . date('Y-m-d H:i:s') . "\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $table)) {
                continue;
            }
            $createRow = $db->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
            $createSql = $createRow['Create Table'] ?? null;
            if ($createSql === null) {
                continue;
            }

            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createSql . ";\n\n";

            $rows = $db->query('SELECT * FROM `' . $table . '`');
            foreach ($rows as $row) {
                $columns = array_map(fn ($c) => '`' . $c . '`', array_keys($row));
                $values = array_map(function ($value) use ($db) {
                    return $value === null ? 'NULL' : $db->quote((string) $value);
                }, array_values($row));

                $sql .= 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES ('
                    . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        $triggers = $db->query(
            'SELECT TRIGGER_NAME, ACTION_TIMING, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_STATEMENT
             FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()'
        )->fetchAll();

        foreach ($triggers as $trigger) {
            $body = preg_replace('/\s+/', ' ', trim($trigger['ACTION_STATEMENT']));
            $sql .= 'DROP TRIGGER IF EXISTS `' . $trigger['TRIGGER_NAME'] . "`;\n";
            $sql .= 'CREATE TRIGGER `' . $trigger['TRIGGER_NAME'] . '` ' . $trigger['ACTION_TIMING'] . ' ' . $trigger['EVENT_MANIPULATION']
                . ' ON `' . $trigger['EVENT_OBJECT_TABLE'] . "` FOR EACH ROW $body;\n\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    private static function exportSqlite(PDO $db): string
    {
        $sql = "-- POWERSURGE GYM & NUTRITION database backup — generated " . date('Y-m-d H:i:s') . "\n";
        $sql .= "PRAGMA foreign_keys = OFF;\n\n";

        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $createSql = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name = " . $db->quote($table))->fetchColumn();
            if (!$createSql) continue;

            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createSql . ";\n\n";

            $rows = $db->query('SELECT * FROM `' . $table . '`');
            foreach ($rows as $row) {
                $columns = array_map(fn ($c) => '`' . $c . '`', array_keys($row));
                $values = array_map(function ($value) use ($db) {
                    return $value === null ? 'NULL' : $db->quote((string) $value);
                }, array_values($row));

                $sql .= 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES ('
                    . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        $triggers = $db->query("SELECT sql FROM sqlite_master WHERE type='trigger'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($triggers as $triggerSql) {
            if ($triggerSql) {
                $sql .= $triggerSql . ";\n\n";
            }
        }

        $sql .= "PRAGMA foreign_keys = ON;\n";
        return $sql;
    }

    public static function import(string $sql): void
    {
        $db = Database::connection();
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $db->exec('PRAGMA foreign_keys = OFF;');
        }

        foreach (self::splitStatements($sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }
            if ($driver === 'sqlite' && (str_starts_with(strtoupper($statement), 'SET ') || str_starts_with(strtoupper($statement), 'LOCK ') || str_starts_with(strtoupper($statement), 'UNLOCK '))) {
                continue;
            }
            try {
                $db->exec($statement);
            } catch (Throwable $e) {
                error_log("Backup import warning: " . $e->getMessage());
            }
        }

        if ($driver === 'sqlite') {
            $db->exec('PRAGMA foreign_keys = ON;');
        }
    }

    /** Creates a zip archive of the uploads directory for media/file backup. */
    public static function exportMediaZip(): string
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'media_backup_') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Failed to create media zip archive');
        }

        $uploadsDir = realpath(__DIR__ . '/../uploads');
        if ($uploadsDir && is_dir($uploadsDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'uploads/' . substr($filePath, strlen($uploadsDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();
        return $zipFile;
    }

    /** Triggers automated daily backup storing SQL dump & media zip in backups/daily/. */
    public static function runDailyBackup(int $keepDays = 7): array
    {
        $backupDir = __DIR__ . '/../backups/daily';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $dateStr = date('Y-m-d');
        $sqlPath = "$backupDir/daily-db-$dateStr.sql";
        file_put_contents($sqlPath, self::export());

        $zipTmp = self::exportMediaZip();
        $zipPath = "$backupDir/daily-media-$dateStr.zip";
        copy($zipTmp, $zipPath);
        @unlink($zipTmp);

        // Prune older daily backups
        $cutoff = strtotime("-$keepDays days");
        foreach (glob("$backupDir/daily-*.*") as $oldFile) {
            if (filemtime($oldFile) < $cutoff) {
                @unlink($oldFile);
            }
        }

        return ['sql' => $sqlPath, 'media' => $zipPath];
    }

    /** @return string[] */
    private static function splitStatements(string $sql): array
    {
        $lines = explode("\n", $sql);
        $statements = [];
        $buffer = '';

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '--')) {
                continue;
            }
            $buffer .= $line . "\n";
            if (str_ends_with(rtrim($line), ';')) {
                $statements[] = $buffer;
                $buffer = '';
            }
        }
        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}

