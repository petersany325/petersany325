<?php
/**
 * Authenticated admin diagnostics.
 * https://YOUR-DOMAIN/telegram_bot/php_bot/admin/check.php
 */
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

header('Content-Type: text/html; charset=utf-8');

function ok($t) { echo '<li style="color:#166534">✔ ' . htmlspecialchars($t) . '</li>'; }
function bad($t) { echo '<li style="color:#991b1b">✘ ' . htmlspecialchars($t) . '</li>'; }
function info($t) { echo '<li style="color:#1e3a8a">• ' . htmlspecialchars($t) . '</li>'; }

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Check</title>';
echo '<style>body{font-family:Tahoma,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px}';
echo 'ul{line-height:1.9;background:#1e293b;padding:20px 20px 20px 40px;border-radius:12px;max-width:820px}';
echo 'a{color:#38bdf8} code{background:#0f172a;padding:2px 6px;border-radius:6px}</style></head><body>';
echo '<h2>HDD-Land Admin Diagnostics</h2><ul>';

info('PHP version: ' . PHP_VERSION);
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    bad('PHP is too old. Need 7.4+.');
} else {
    ok('PHP version is OK');
}
info('mbstring: ' . (extension_loaded('mbstring') ? 'yes' : 'NO'));
info('curl: ' . (function_exists('curl_init') ? 'yes' : 'NO'));
info('allow_url_fopen: ' . (ini_get('allow_url_fopen') ? 'On' : 'Off'));

$botDir = dirname(__DIR__);
$configFile = $botDir . '/config.local.php';
if (!is_file($configFile)) {
    bad('Missing config.local.php');
} else {
    ok('config.local.php present');
    try {
        $cfg = bot_config();
        if (empty($cfg['bot_token'])) {
            bad('bot_token is empty');
        } else {
            ok('bot_token is set');
        }
        try {
            db()->query('SELECT 1');
            ok('MySQL connection OK');
        } catch (Throwable $e) {
            bad('MySQL error: ' . $e->getMessage());
        }
    } catch (Throwable $e) {
        bad('config load failed: ' . $e->getMessage());
    }
}

info('feature_language_gate: ' . (feature_on('language_gate') ? 'ON' : 'off'));
info('start_with_menu: ' . ((int)cfg('start_with_menu', 0) === 1 ? 'ON (skips language)' : 'off'));
$sec = (string)cfg('webhook_secret', '');
info('webhook_secret: ' . ($sec !== '' ? ('set, len=' . strlen($sec)) : 'empty'));

$me = tg_api('getMe', array());
if (!empty($me['ok'])) {
    ok('getMe OK — @' . ($me['result']['username'] ?? '?'));
} else {
    bad('getMe FAILED: ' . json_encode($me, JSON_UNESCAPED_UNICODE));
}

$wh = tg_api('getWebhookInfo', array());
if (!empty($wh['ok'])) {
    $r = $wh['result'] ?? array();
    ok('Webhook URL: ' . (string)($r['url'] ?? ''));
    info('pending_update_count: ' . (string)($r['pending_update_count'] ?? '?'));
    if (!empty($r['last_error_message'])) {
        bad('last_error_message: ' . (string)$r['last_error_message']);
    } else {
        ok('No last_error_message on webhook');
    }
} else {
    bad('getWebhookInfo FAILED: ' . json_encode($wh, JSON_UNESCAPED_UNICODE));
}

$log = $botDir . '/error.log';
if (is_file($log)) {
    $tail = @file_get_contents($log);
    if ($tail !== false && trim($tail) !== '') {
        info('error.log (last 1.5KB):');
        echo '</ul><pre style="max-width:820px;overflow:auto;background:#020617;padding:12px;border-radius:8px">'
            . htmlspecialchars(substr($tail, -1500)) . '</pre><ul>';
    }
}

echo '</ul>';
echo '<p><a href="index.php">Dashboard</a> · <a href="settings.php?tab=webhook">Webhook</a> · <a href="settings.php?tab=security">Security</a></p>';
echo '</body></html>';
