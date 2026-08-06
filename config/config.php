<?php
/**
 * Application configuration.
 *
 * Credentials are NOT stored in this file — it is committed to git. They live in
 * a git-ignored env file, resolved in this order:
 *
 *   1. real environment variables (getenv), if set
 *   2. config/env.local.php  — exists only on a development machine
 *   3. config/env.php        — the deployed environment's config (uploaded to the server)
 *   4. built-in development defaults
 *
 * Because env.local.php never gets uploaded, the server naturally falls through
 * to env.php. This works under both mod_php and PHP-CLI (Hostinger cron jobs),
 * which hostname sniffing would not.
 */

$envFile = is_file(__DIR__ . '/env.local.php') ? __DIR__ . '/env.local.php'
         : (is_file(__DIR__ . '/env.php') ? __DIR__ . '/env.php' : null);

$envValues = $envFile ? require $envFile : [];

// Recorded so a failed connection can report which file its settings came from.
// A file that exists but does not `return` an array is the same as no file at
// all, and that is worth being able to see rather than having to infer.
$envSource = $envFile === null
    ? 'no env file found — using built-in defaults'
    : ('config/' . basename($envFile) . (is_array($envValues) ? '' : ' (ignored: it does not return an array)'));

if (!is_array($envValues)) {
    $envValues = [];
}

define('ENV_SOURCE', $envSource);

/** Resolve one setting: real env var, then the env file, then the supplied default. */
$env = static function (string $key, $default = null) use ($envValues) {
    $fromEnv = getenv($key);
    if ($fromEnv !== false && $fromEnv !== '') {
        return $fromEnv;
    }
    return $envValues[$key] ?? $default;
};

// ---- Environment ---------------------------------------------------------
define('APP_ENV', $env('APP_ENV', 'development')); // development | production
define('APP_DEBUG', APP_ENV !== 'production');

// ---- Database -------------------------------------------------------------
define('DB_DRIVER', $env('DB_DRIVER', 'mysql'));
define('DB_HOST', $env('DB_HOST', '127.0.0.1'));
define('DB_PORT', $env('DB_PORT', '3306'));
define('DB_NAME', $env('DB_NAME', 'gym_powersurge'));
define('DB_USER', $env('DB_USER', 'root'));
define('DB_PASS', $env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'POWERSURGE GYM & NUTRITION');

// Merchant number shown on the online-payment step of membership registration,
// alongside the QR at assets/images/payment/bkash-qr.png. Overridable per
// environment so a test deployment can point somewhere harmless.
define('PAYMENT_MERCHANT_NUMBER', $env('PAYMENT_MERCHANT_NUMBER', '01612839232'));
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
$scheme = (($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
define('APP_URL', $env('APP_URL', "$scheme://$host"));
define('BASE_PATH', dirname(__DIR__));

// ---- Uploads ----------------------------------------------------------------
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// ---- Session ----------------------------------------------------------------
define('SESSION_NAME', 'powersurge_session');
define('SESSION_LIFETIME', 60 * 60 * 2); // 2 hours idle timeout

// ---- Mail (SMTP) ------------------------------------------------------------
define('SMTP_HOST', $env('SMTP_HOST', ''));
define('SMTP_PORT', $env('SMTP_PORT', 587));
define('SMTP_USER', $env('SMTP_USER', ''));
define('SMTP_PASS', $env('SMTP_PASS', ''));
define('SMTP_FROM_EMAIL', $env('SMTP_FROM_EMAIL', 'no-reply@powersurgegym.test'));
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
