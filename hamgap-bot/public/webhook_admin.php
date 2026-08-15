<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo 'config missing';
    exit;
}

$config = require $configFile;
require __DIR__ . '/src/Database.php';
require __DIR__ . '/src/Settings.php';
require __DIR__ . '/src/Migrator.php';
require __DIR__ . '/src/Telegram.php';
require __DIR__ . '/src/Gender.php';
require __DIR__ . '/src/Occupation.php';
require __DIR__ . '/src/IranLocations.php';
require __DIR__ . '/src/Keyboards.php';
require __DIR__ . '/src/CoinCatalog.php';
require __DIR__ . '/src/AdminHandlers.php';

$secret = $_GET['secret'] ?? '';
if (!hash_equals((string)$config['webhook_secret'], (string)$secret)) {
    http_response_code(403);
    echo 'forbidden';
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$update = json_decode($raw, true);
if (!is_array($update)) {
    http_response_code(400);
    echo 'bad json';
    exit;
}

try {
    $db = new Database($config['db']);
    Migrator::ensure($db);
    $settings = new Settings($db);
    $token = (string)($config['admin_bot_token'] ?? '');
    if ($token === '') {
        $token = $settings->get('admin_bot_token', '');
    }
    if ($token === '') {
        http_response_code(503);
        echo 'admin bot not configured';
        exit;
    }
    $tg = new Telegram($token);
    $handler = new AdminHandlers($config, $db, $tg, $settings);
    $handler->handle($update);
    echo 'ok';
} catch (Throwable $e) {
    @file_put_contents(__DIR__ . '/storage/error.log', date('c') . ' admin ' . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(200);
    echo 'err';
}
