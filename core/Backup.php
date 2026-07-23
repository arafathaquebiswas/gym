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
        $sql = "-- PowerSurge Gym database backup — generated " . date('Y-m-d H:i:s') . "\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
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

        // SHOW CREATE TABLE does not include triggers — dump them separately, after all
        // tables exist. Each is flattened to one line so the naive line-based statement
        // splitter in import() (which has no DELIMITER concept) doesn't cut the trigger
        // body's internal semicolons as if they ended the statement.
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

    /**
     * Runs each statement in the dump in order. Not wrapped in a single PDO transaction:
     * MySQL implicitly commits on every DDL statement (CREATE/DROP TABLE), which would
     * silently end a wrapping transaction anyway — the real safety net for restore is the
     * automatic pre-restore backup taken by the caller before this runs, not a rollback here.
     */
    public static function import(string $sql): void
    {
        $db = Database::connection();
        foreach (self::splitStatements($sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }
            $db->exec($statement);
        }
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
