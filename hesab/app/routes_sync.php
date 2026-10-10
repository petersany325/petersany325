<?php
declare(strict_types=1);

/** @var Router $router */

require_once __DIR__ . '/SyncApi.php';

$router->get('/api/sync', static function (): void {
    SyncApi::status();
});

$router->get('/api/sync/pull', static function (): void {
    if (!Installer::isInstalled()) {
        SyncApi::json(['ok' => false, 'error' => 'not_installed'], 503);
        return;
    }
    SyncApi::pull();
});

$router->post('/api/sync/push', static function (): void {
    if (!Installer::isInstalled()) {
        SyncApi::json(['ok' => false, 'error' => 'not_installed'], 503);
        return;
    }
    SyncApi::push();
});
