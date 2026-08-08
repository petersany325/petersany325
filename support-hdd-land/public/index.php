<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$installedLock = __DIR__.'/../storage/app/installed.lock';
$envFile = __DIR__.'/../.env';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$isInstall = str_contains($path, 'install.php') || str_starts_with($path, '/install');

$needsInstall = ! is_file($installedLock);
if ($needsInstall && is_file($envFile)) {
    $envBody = (string) file_get_contents($envFile);
    // Already-configured production sites (no lock yet): don't force installer.
    if (preg_match('/^APP_KEY\\s*=\\s*base64:/m', $envBody)) {
        @mkdir(dirname($installedLock), 0775, true);
        @file_put_contents($installedLock, json_encode([
            'installed_at' => date('c'),
            'legacy' => true,
        ], JSON_UNESCAPED_UNICODE));
        $needsInstall = false;
    }
}

if ($needsInstall && ! $isInstall) {
    header('Location: /install.php');
    exit;
}

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
