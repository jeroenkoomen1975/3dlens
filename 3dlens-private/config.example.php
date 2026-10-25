<?php
declare(strict_types=1);

// Copy to config.local.php OUTSIDE public_html. Never commit the real credentials.
return [
    'app_url' => 'https://jouw-domein.nl',
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'YOUR_DATABASE_NAME',
    'db_user' => 'YOUR_DATABASE_USER',
    'db_password' => 'YOUR_DATABASE_PASSWORD',
    // Choose a unique, random key of at least 32 characters. Used ONLY for setup.
    // Empty this value after creating your account.
    'setup_key' => '',
    'require_https' => true,
    'storage_path' => __DIR__ . '/storage',
    // Also check upload_max_filesize and post_max_size in Hostinger.
    'max_upload_bytes' => 200 * 1024 * 1024,
    'idle_timeout_seconds' => 7200,
    'session_lifetime_seconds' => 43200,
];
