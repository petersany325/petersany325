<?php
/**
 * Copy to config.php on server (not web-readable ideally).
 * Values are filled by deploy; do not commit real secrets.
 */
return [
    'bot_token' => 'REPLACE_BOT_TOKEN',
    'bot_username' => 'HamGapXBot',
    'bot_name' => 'هم‌گپ',
    'admin_ids' => [],

    'db' => [
        'host' => 'localhost',
        'name' => 'hddrecov_chat',
        'user' => 'hddrecov_rom',
        'pass' => 'REPLACE_DB_PASSWORD',
        'charset' => 'utf8mb4',
    ],

    // Absolute or relative to public/webhook.php
    'assets_path' => __DIR__ . '/assets/banners',
    'webhook_secret' => 'REPLACE_WEBHOOK_SECRET',
];
