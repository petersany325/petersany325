<?php

return [
    /*
    |--------------------------------------------------------------------------
    | نسخه نرم‌افزار (پایه)
    |--------------------------------------------------------------------------
    | پس از نصب موفق آپدیت، نسخه واقعی در storage/app/installed_version.json
    | ذخیره می‌شود و بر این مقدار اولویت دارد.
    */
    'version' => env('APP_UPDATE_VERSION', '1.0.0'),

    'channel' => env('APP_UPDATE_CHANNEL', 'stable'),

    'product' => env('APP_UPDATE_PRODUCT', 'hddland-repair'),

    /** فاصله چک زنده در صفحه آپدیت (ثانیه) */
    'poll_seconds' => (int) env('APP_UPDATE_POLL_SECONDS', 45),

    /** کش نتیجه «آپدیت موجود است» در پنل (ثانیه) */
    'banner_cache_seconds' => (int) env('APP_UPDATE_BANNER_CACHE', 120),
];
