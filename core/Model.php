<?php

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * True when the live connection is SQLite rather than MySQL.
     *
     * These dialect helpers are static so the handful of static model methods
     * (Notification::fanOut(), ::cleanup()) can reach them too; the connection
     * is a singleton, so it is the same one $this->db holds.
     */
    protected static function isSqlite(): bool
    {
        return Database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    /**
     * "Insert, unless a row already exists that would violate a unique index."
     * Same effect on both engines, different spelling — a MySQL-only
     * INSERT IGNORE is a syntax error on SQLite, not a silent no-op.
     */
    protected static function insertOrIgnoreInto(): string
    {
        return self::isSqlite() ? 'INSERT OR IGNORE INTO' : 'INSERT IGNORE INTO';
    }

    /**
     * Upsert tail. SQLite's ON CONFLICT needs the conflicting columns named;
     * MySQL infers them, so it ignores the list.
     *
     * @param string[] $conflictColumns columns covered by the unique index
     * @param string   $assignments     SET list, e.g. "qty = qty + :qty2"
     */
    protected static function onDuplicateKeyUpdate(array $conflictColumns, string $assignments): string
    {
        return self::isSqlite()
            ? 'ON CONFLICT(' . implode(', ', $conflictColumns) . ') DO UPDATE SET ' . $assignments
            : 'ON DUPLICATE KEY UPDATE ' . $assignments;
    }
}
