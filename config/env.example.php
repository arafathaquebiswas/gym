<?php
/**
 * Copy this file to config/env.php and fill in the real values.
 *
 * config/env.php is git-ignored — it holds the credentials and must never be
 * committed. It DOES have to be uploaded to the server, since the app reads its
 * database settings from here.
 */

return [
    // 'production' on the live site, 'development' locally.
    // Production turns off on-screen error output and logs to logs/php-error.log.
    'APP_ENV'  => 'production',

    'DB_DRIVER' => 'mysql',
    'DB_HOST'   => 'localhost',
    'DB_PORT'   => '3306',
    'DB_NAME'   => 'your_database_name',
    'DB_USER'   => 'your_database_user',
    'DB_PASS'   => 'your_database_password',

    'APP_URL'   => 'https://example.com',

    // Optional — leave blank to disable outgoing mail.
    'SMTP_HOST'       => '',
    'SMTP_PORT'       => 587,
    'SMTP_USER'       => '',
    'SMTP_PASS'       => '',
    'SMTP_FROM_EMAIL' => '',
];
