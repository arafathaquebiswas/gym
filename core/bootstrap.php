<?php

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/core/helpers.php';

// Simple directory-based autoloader for our own flat class files.
spl_autoload_register(function (string $class): void {
    foreach (['core', 'controllers', 'models'] as $dir) {
        $file = BASE_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

Security::startSecureSession();
Security::sendSecurityHeaders();
