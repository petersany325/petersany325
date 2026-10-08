<?php
declare(strict_types=1);

session_save_path(__DIR__ . '/../storage/sessions');
if (!is_dir(__DIR__ . '/../storage/sessions')) {
    mkdir(__DIR__ . '/../storage/sessions', 0755, true);
}
session_start();

require __DIR__ . '/helpers.php';
require __DIR__ . '/Database.php';
require __DIR__ . '/Auth.php';
require __DIR__ . '/Installer.php';

$configFile = __DIR__ . '/../config.php';
$CONFIG = is_file($configFile) ? require $configFile : require __DIR__ . '/../config.sample.php';
date_default_timezone_set($CONFIG['timezone'] ?? 'Asia/Tehran');

ini_set('display_errors', '0');
error_reporting(E_ALL);
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    $msg = $e->getMessage();
    echo '<div style="font-family:tahoma;padding:2rem;direction:rtl">خطای سیستم: ' . htmlspecialchars($msg) . '</div>';
});
