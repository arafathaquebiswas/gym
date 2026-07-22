<?php
/**
 * Application configuration.
 * On shared hosting, keep this file outside the public webroot if possible;
 * on a single-docroot host (Hostinger shared hosting), .htaccess denies
 * direct HTTP access to this folder instead.
 */

// ---- Environment ---------------------------------------------------------
define('APP_ENV', 'development'); // development | production
define('APP_DEBUG', APP_ENV === 'development');

// ---- Database -------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'gym_powersurge');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ---- App ------------------------------------------------------------------
define('APP_NAME', 'PowerSurge Gym');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
define('BASE_PATH', dirname(__DIR__));

// ---- Uploads ----------------------------------------------------------------
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// ---- Session ----------------------------------------------------------------
define('SESSION_NAME', 'powersurge_session');
define('SESSION_LIFETIME', 60 * 60 * 2); // 2 hours idle timeout

// ---- Mail (SMTP) ------------------------------------------------------------
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'no-reply@powersurgegym.test');
define('SMTP_FROM_NAME', APP_NAME);

// ---- Error reporting ----------------------------------------------------------
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/logs/php-error.log');
}

date_default_timezone_set('Asia/Dhaka');
