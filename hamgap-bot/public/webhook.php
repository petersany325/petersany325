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
require __DIR__ . '/src/Telegram.php';
require __DIR__ . '/src/Keyboards.php';
require __DIR__ . '/src/Matcher.php';
require __DIR__ . '/src/Handlers.php';

// Optional secret token in URL: webhook.php?secret=...
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
    $tg = new Telegram($config['bot_token']);
    $matcher = new Matcher($db);
    $handler = new Handlers($config, $db, $tg, $matcher);
    $handler->handle($update);
    echo 'ok';
} catch (Throwable $e) {
    // Avoid leaking details to Telegram; log locally if possible.
    @file_put_contents(__DIR__ . '/storage/error.log', date('c') . ' ' . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(200); // prevent endless retries storms for logic bugs
    echo 'err';
}
