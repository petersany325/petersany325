<?php

return [
    'key' => env('LICENSE_KEY'),
    'domain' => env('LICENSE_DOMAIN'),
    'token' => env('LICENSE_TOKEN'),
    'server' => rtrim((string) env('LICENSE_SERVER', 'https://support.hdd-land.ir'), '/'),
    // Public storefront for purchase / transfer when license is blocked
    'purchase_url' => rtrim((string) env('LICENSE_PURCHASE_URL', 'https://hdd-land.ir'), '/'),
    // Seller-side secret for signing issued license tokens
    'issuer_secret' => env('PRODUCT_LICENSE_SECRET', ''),

    // Snapshot from activation (shown on customer install)
    'plan' => env('LICENSE_PLAN'),
    'plan_code' => env('LICENSE_PLAN_CODE'),
    'months' => env('LICENSE_MONTHS'),
    'price' => env('LICENSE_PRICE'),
    'activated_at' => env('LICENSE_ACTIVATED_AT'),
    'expires_at' => env('LICENSE_EXPIRES_AT'),
];
