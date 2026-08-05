<?php

final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'mysql';
            
            if ($driver === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_PORT,
                    DB_NAME,
                    DB_CHARSET
                );

                try {
                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } catch (PDOException $e) {
                    // Fall back to SQLite if MySQL fails
                    $driver = 'sqlite';
                }
            }

            if ($driver === 'sqlite' || self::$instance === null) {
                $sqliteFile = BASE_PATH . '/database/gym.sqlite';
                if (!file_exists($sqliteFile)) {
                    require_once BASE_PATH . '/database/setup_sqlite.php';
                }
                self::$instance = new PDO('sqlite:' . $sqliteFile, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                
                // Helper for function registration across PHP versions
                $addFunc = function ($name, $callable) {
                    if (method_exists(self::$instance, 'createFunction')) {
                        self::$instance->createFunction($name, $callable);
                    } else {
                        @self::$instance->sqliteCreateFunction($name, $callable);
                    }
                };

                // SQL compatibility functions for SQLite
                $addFunc('NOW', fn() => date('Y-m-d H:i:s'));
                $addFunc('CURDATE', fn() => date('Y-m-d'));
                $addFunc('IF', fn($cond, $t, $f) => $cond ? $t : $f);
                $addFunc('FIELD', function($val, ...$list) {
                    $idx = array_search($val, $list, false);
                    return $idx !== false ? $idx + 1 : 0;
                });
                $addFunc('DATE_ADD', function($date, $expr = null) {
                    return date('Y-m-d H:i:s', strtotime($date . ' ' . ($expr ?? '')));
                });
                $addFunc('DATE_SUB', function($date, $expr = null) {
                    return date('Y-m-d H:i:s', strtotime($date . ' -' . ($expr ?? '')));
                });
                $addFunc('DATE_FORMAT', function($date, $format) {
                    if (!$date) return null;
                    $map = [
                        '%Y' => 'Y', '%m' => 'm', '%d' => 'd',
                        '%H' => 'H', '%i' => 'i', '%s' => 's',
                        '%W' => 'l', '%M' => 'F', '%b' => 'M',
                        '%a' => 'D', '%c' => 'n', '%e' => 'j',
                    ];
                    $phpFormat = strtr($format, $map);
                    $ts = strtotime($date);
                    return $ts !== false ? date($phpFormat, $ts) : null;
                });
                $addFunc('DATE', function($date) {
                    if (!$date) return null;
                    return date('Y-m-d', strtotime($date));
                });
            }
        }

        return self::$instance;
    }
}
