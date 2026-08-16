<?php
declare(strict_types=1);

// One-time installer: creates tables. Delete or protect after use.
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('config missing');
}
$config = require $configFile;
$key = $_GET['key'] ?? '';
if (!hash_equals((string)$config['webhook_secret'], (string)$key)) {
    http_response_code(403);
    exit('forbidden');
}

require __DIR__ . '/src/Database.php';
$db = new Database($config['db']);
$db->migrate(__DIR__ . '/sql/schema.sql');

@mkdir(__DIR__ . '/storage', 0755, true);
echo 'migrated ok';
