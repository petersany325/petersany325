<?php
/**
 * تنظیمات دمو — فقط برای تست مشتری (بدون SMS/پرداخت واقعی)
 * روی هاست: DB_* را از پنل MySQL دمو پر کنید.
 */
return [
    'app_name' => 'سرزمین هارد',
    'base_url' => '',
    'demo_banner' => 'DEMO — نمونه زنده سیستم مدیریت تعمیرات · بدون پیامک و پرداخت واقعی · جدا از فروشگاه',
    'timezone' => 'Asia/Tehran',
    'session_name' => 'hl_demo',
    'db' => [
        'driver' => getenv('DEMO_DB_DRIVER') ?: 'mysql',
        'host' => getenv('DEMO_DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('DEMO_DB_PORT') ?: 3306),
        'database' => getenv('DEMO_DB_DATABASE') ?: 'demo_db',
        'username' => getenv('DEMO_DB_USERNAME') ?: 'demo_user',
        'password' => getenv('DEMO_DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'sqlite_path' => __DIR__ . '/data/demo.sqlite',
    ],
    'demo_password' => '1234',
    'demo_otp' => '1234',
    'logo' => 'https://support.hdd-land.ir/images/logo.png?v=1',
    'favicon' => 'https://support.hdd-land.ir/favicon.ico?v=hd1',
];
