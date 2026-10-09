<?php
declare(strict_types=1);

return [
    'app_name' => 'حساب پیشگام',
    'base_url' => 'https://hdd-land.ir/hesab',
    'timezone' => 'Asia/Tehran',
    // Override in production before issuing paid license keys
    'license_secret' => '',
    'db' => [
        'host' => 'localhost',
        'name' => 'DBNAME_hesab',
        'user' => 'DBUSER_hesab_user',
        'pass' => 'CHANGE_ME_DB_PASSWORD',
        'charset' => 'utf8mb4',
    ],
];
