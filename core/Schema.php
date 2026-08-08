<?php

/**
 * Asks the database what it actually has, so a query can adapt to a column that
 * a migration has not created yet.
 *
 * This exists because of how the site is deployed: pushing to master publishes
 * the code immediately, while migrations are run by hand afterwards. Any query
 * naming a brand-new column therefore has a window where it runs against the
 * old schema and takes the whole admin panel down with it. Guarding the handful
 * of queries that sit on hot paths — the dashboard, the reports — turns that
 * window from an outage into a temporarily incomplete filter.
 *
 * It is not a substitute for running the migration, and it is not meant to be
 * sprinkled everywhere. Use it where a failure would break a page that has
 * nothing to do with the new feature.
 */
final class Schema
{
    /** @var array<string, array<string, bool>> table => column => exists */
    private static array $cache = [];

    public static function hasColumn(string $table, string $column): bool
    {
        if (!isset(self::$cache[$table])) {
            self::$cache[$table] = self::columnsFor($table);
        }

        return isset(self::$cache[$table][$column]);
    }

    /** @return array<string, bool> */
    private static function columnsFor(string $table): array
    {
        $db = Database::connection();

        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $rows = $db->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC);
                $names = array_column($rows, 'name');
            } else {
                $rows = $db->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
                $names = array_column($rows, 'Field');
            }
        } catch (PDOException $e) {
            // A missing table answers the question just as well as an empty one:
            // nothing here has the column being asked about.
            return [];
        }

        return array_fill_keys($names, true);
    }
}
