<?php
/**
 * SQLite Schema and Seed generator for POWERSURGE GYM & NUTRITION
 */

$dbFile = __DIR__ . '/gym.sqlite';
if (file_exists($dbFile)) {
    unlink($dbFile);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = OFF;');

function cleanAndSplitSql(string $sql): array
{
    // Strip comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Remove DB selection & SET statements
    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS[^;]+;/i', '', $sql);
    $sql = preg_replace('/USE [^;]+;/i', '', $sql);
    $sql = preg_replace('/SET NAMES [^;]+;/i', '', $sql);
    $sql = preg_replace('/SET FOREIGN_KEY_CHECKS = [01];/i', '', $sql);
    $sql = preg_replace('/DELIMITER.*$/m', '', $sql);
    $sql = preg_replace('/END\/\//', '', $sql);

    // Types & column attributes adaptation for SQLite
    $sql = preg_replace('/BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
    $sql = preg_replace('/INT UNSIGNED AUTO_INCREMENT PRIMARY KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
    $sql = preg_replace('/BIGINT UNSIGNED/i', 'INTEGER', $sql);
    $sql = preg_replace('/INT UNSIGNED/i', 'INTEGER', $sql);
    $sql = preg_replace('/TINYINT UNSIGNED/i', 'INTEGER', $sql);
    $sql = preg_replace('/TINYINT\([0-9]+\)/i', 'INTEGER', $sql);
    $sql = preg_replace('/MEDIUMTEXT/i', 'TEXT', $sql);
    $sql = preg_replace('/COMMENT\s+\'([^\'\\\\]|\\\\.)*\'/s', '', $sql);
    $sql = preg_replace('/ENGINE\s*=\s*InnoDB/i', '', $sql);
    $sql = preg_replace('/CHARACTER SET [^\s;,)]+/i', '', $sql);
    $sql = preg_replace('/COLLATE [^\s;,)]+/i', '', $sql);
    $sql = preg_replace('/ENUM\([^)]+\)/i', 'TEXT', $sql);
    $sql = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);
    $sql = preg_replace('/UNIQUE KEY\s+\w+\s*\(([^)]+)\)/i', 'UNIQUE ($1)', $sql);
    $sql = preg_replace('/CONSTRAINT\s+\w+\s+CHECK\s*\([^)]+\)/i', '', $sql);

    $statements = explode(';', $sql);
    $cleanStatements = [];
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;

        // Skip ALTER TABLE ADD CONSTRAINT and triggers for SQLite
        if (preg_match('/^ALTER TABLE/i', $stmt) || preg_match('/^CREATE TRIGGER/i', $stmt)) {
            continue;
        }

        if (preg_match('/^CREATE TABLE/i', $stmt)) {
            $lines = explode("\n", $stmt);
            $cleanLines = [];
            foreach ($lines as $line) {
                if (preg_match('/^\s*INDEX\s+/i', $line) || preg_match('/^\s*KEY\s+/i', $line)) {
                    continue;
                }
                $cleanLines[] = $line;
            }
            $stmtStr = implode("\n", $cleanLines);
            $stmtStr = preg_replace('/,\s*\)/', "\n)", $stmtStr);
            $stmt = $stmtStr;
        }

        $cleanStatements[] = $stmt;
    }
    return $cleanStatements;
}

$schemaSql = file_get_contents(__DIR__ . '/schema.sql');

// Add brand column to products schema if missing
$schemaSql = str_replace('brand_id        INT UNSIGNED NULL,', "brand_id        INT UNSIGNED NULL,\n    brand           VARCHAR(100) NULL,", $schemaSql);

$schemaStmts = cleanAndSplitSql($schemaSql);
$schemaErrors = 0;
foreach ($schemaStmts as $stmt) {
    try {
        $pdo->exec($stmt);
    } catch (PDOException $e) {
        $schemaErrors++;
        echo "Schema Error: " . $e->getMessage() . "\nSQL: " . substr($stmt, 0, 100) . "...\n\n";
    }
}

$seedSql = file_get_contents(__DIR__ . '/seed.sql');
$seedSql = str_replace('NOW()', "datetime('now', 'localtime')", $seedSql);
$seedStmts = cleanAndSplitSql($seedSql);
$seedErrors = 0;
foreach ($seedStmts as $stmt) {
    try {
        $pdo->exec($stmt);
    } catch (PDOException $e) {
        $seedErrors++;
        echo "Seed Error: " . $e->getMessage() . "\nSQL: " . substr($stmt, 0, 100) . "...\n\n";
    }
}

$pdo->exec('PRAGMA foreign_keys = ON;');
echo "SQLite database successfully created and seeded! Total errors: Schema ($schemaErrors), Seed ($seedErrors).\n";
