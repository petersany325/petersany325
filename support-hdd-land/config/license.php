<?php

return [
    'key' => env('LICENSE_KEY'),
    'domain' => env('LICENSE_DOMAIN'),
    'token' => env('LICENSE_TOKEN'),
    'server' => rtrim((string) env('LICENSE_SERVER', 'https://support.hdd-land.ir'), '/'),
    // Seller-side secret for signing issued license tokens
    'issuer_secret' => env('PRODUCT_LICENSE_SECRET', ''),
];
