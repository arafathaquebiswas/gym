<?php
/**
 * Dev-only router for PHP's built-in server: `php -S localhost:8000 router.php`
 * Serves real files (assets/uploads) as-is, routes everything else through
 * the front controller — mirrors what .htaccess does on Apache.
 */

$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

$blocked = ['/config/', '/core/', '/models/', '/controllers/', '/database/', '/vendor/'];
foreach ($blocked as $prefix) {
    if (str_starts_with($path, $prefix)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

if ($path !== '/' && file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/index.php';
