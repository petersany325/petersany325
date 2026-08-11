<?php
/**
 * Admin diagnostic — open this if login / menus return HTTP 500
 * https://YOUR-DOMAIN/telegram_bot/php_bot/admin/check.php
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

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
    bad('PHP is too old. Need 7.4+ (preferably 8.0+). Change version in cPanel → MultiPHP Manager.');
} else {
    ok('PHP version is OK');
}
info('mbstring: ' . (extension_loaded('mbstring') ? 'yes' : 'NO (fallback active)'));

$adminDir = __DIR__;
$botDir = dirname($adminDir);
info('Admin path: ' . $adminDir);
info('Bot path: ' . $botDir);

$needed = array(
    $adminDir . '/login.php',
    $adminDir . '/auth.php',
    $adminDir . '/index.php',
    $adminDir . '/menus.php',
    $adminDir . '/assets/admin.css',
    $botDir . '/bootstrap.php',
    $botDir . '/menu_faq.php',
    $botDir . '/i18n_world.php',
    $botDir . '/admins_schema.php',
    $botDir . '/requests.php',
    $botDir . '/config.local.php',
    $botDir . '/plugins/loader.php',
    $botDir . '/plugins/SmartI18n/plugin.php',
    $botDir . '/plugins/HealthRepair/plugin.php',
);
foreach ($needed as $f) {
    if (is_file($f)) {
        ok('Found: ' . str_replace($botDir . '/', '', str_replace('\\', '/', $f)));
    } else {
        bad('MISSING: ' . $f);
    }
}

$configFile = $botDir . '/config.local.php';
if (is_file($configFile)) {
    try {
        $cfg = require $configFile;
        if (!is_array($cfg)) {
            bad('config.local.php did not return an array');
        } else {
            ok('config.local.php loaded');
            if (empty($cfg['bot_token'])) bad('bot_token is empty'); else ok('bot_token is set');
            if (empty($cfg['db']['name'])) bad('DB name missing'); else ok('DB name: ' . $cfg['db']['name']);
            if (empty($cfg['admin_password_hash'])) {
                bad('Admin password NOT set. Open setup_admin.php first.');
            } else {
                ok('Admin password hash exists');
            }
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $cfg['db']['host'],
                    $cfg['db']['port'],
                    $cfg['db']['name']
                );
                $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ));
                ok('MySQL connection OK');
                try {
                    $pdo->exec('ALTER TABLE languages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                    ok('languages table utf8mb4 OK (or already)');
                } catch (Throwable $e) {
                    info('utf8mb4 convert note: ' . $e->getMessage());
                }
            } catch (Throwable $e) {
                bad('MySQL error: ' . $e->getMessage());
            }
        }
    } catch (Throwable $e) {
        bad('Cannot load config: ' . $e->getMessage());
    }
}

echo '</ul><h3>Bootstrap load test</h3><ul>';
try {
    require_once $botDir . '/bootstrap.php';
    ok('bootstrap.php loaded without fatal error');
    if (function_exists('ensure_schema')) {
        try {
            ensure_schema();
            ok('ensure_schema() OK');
        } catch (Throwable $e) {
            bad('ensure_schema failed: ' . $e->getMessage());
        }
    }
    if (function_exists('ensure_world_languages')) {
        try {
            ensure_world_languages();
            ok('ensure_world_languages() OK');
        } catch (Throwable $e) {
            bad('ensure_world_languages failed: ' . $e->getMessage());
        }
    }
} catch (Throwable $e) {
    bad('bootstrap FAILED: ' . $e->getMessage());
    bad('File: ' . $e->getFile() . ' line ' . $e->getLine());
}

echo '</ul><h3>Telegram API (outbound)</h3><ul>';
info('allow_url_fopen: ' . (ini_get('allow_url_fopen') ? 'On' : 'Off'));
info('curl extension: ' . (function_exists('curl_init') ? 'yes' : 'NO'));
info('openssl: ' . (extension_loaded('openssl') ? 'yes' : 'NO'));
if (function_exists('cfg')) {
    info('feature_language_gate: ' . (function_exists('feature_on') && feature_on('language_gate') ? 'ON' : 'off'));
    info('start_with_menu: ' . ((int)cfg('start_with_menu', 0) === 1 ? 'ON (skips language!)' : 'off (good)'));
    $sec = (string)cfg('webhook_secret', '');
    info('webhook_secret: ' . ($sec !== '' ? ('set, len=' . strlen($sec)) : 'empty'));
}
if (function_exists('tg_api')) {
    $me = tg_api('getMe', array());
    if (!empty($me['ok'])) {
        $u = $me['result']['username'] ?? '?';
        ok('getMe OK — @' . $u);
    } else {
        bad('getMe FAILED: ' . json_encode($me, JSON_UNESCAPED_UNICODE));
        bad('Without a working Telegram token the bot cannot show language picker or menus.');
        $code = (int)($me['error_code'] ?? 0);
        $desc = (string)($me['description'] ?? '');
        if ($code === 401 || stripos($desc, 'Unauthorized') !== false) {
            bad('Token is invalid/revoked. Open @BotFather → API Token → Revoke → paste new token in Settings → API, then Set Webhook.');
            bad('توکن باطل است. از BotFather توکن جدید بگیرید و در تنظیمات API ذخیره کنید، بعد Webhook را دوباره Set کنید.');
        } elseif ($code === 502 || stripos($desc, 'Bad Gateway') !== false) {
            bad('Telegram returns 502 for this bot token (often means token/bot is broken). Regenerate token in @BotFather and update Settings → API.');
            bad('تلگرام برای این توکن 502 می‌دهد — معمولاً توکن خراب/باطل است. از BotFather توکن تازه بگیرید و جایگزین کنید.');
        }
    }
    $wh = tg_api('getWebhookInfo', array());
    if (!empty($wh['ok'])) {
        $r = $wh['result'] ?? array();
        ok('Webhook URL: ' . (string)($r['url'] ?? ''));
        info('pending_update_count: ' . (string)($r['pending_update_count'] ?? '?'));
        if (!empty($r['last_error_message'])) {
            bad('last_error_message: ' . (string)$r['last_error_message'] . ' @ ' . (string)($r['last_error_date'] ?? ''));
        } else {
            ok('No last_error_message on webhook');
        }
    } else {
        bad('getWebhookInfo FAILED: ' . json_encode($wh, JSON_UNESCAPED_UNICODE));
    }
} else {
    bad('tg_api() not available');
}

$log = $botDir . '/error.log';
if (is_file($log)) {
    $tail = @file_get_contents($log);
    if ($tail !== false && trim($tail) !== '') {
        info('error.log (last 2KB):');
        echo '</ul><pre style="max-width:820px;overflow:auto;background:#020617;padding:12px;border-radius:8px">'
            . htmlspecialchars(substr($tail, -2000)) . '</pre><ul>';
    } else {
        ok('error.log empty');
    }
} else {
    info('No error.log yet');
}

echo '</ul>';
echo '<p><a href="login.php">Try Admin Login</a> &nbsp; | &nbsp; ';
echo '<a href="menus.php">Menus</a> &nbsp; | &nbsp; ';
echo '<a href="settings.php?tab=webhook">Webhook settings</a> &nbsp; | &nbsp; ';
echo '<a href="../setup_admin.php">Setup Admin Password</a></p>';
echo '<p>Correct login URL:<br><code>https://hdd-land.com/telegram_bot/php_bot/admin/login.php</code></p>';
echo '</body></html>';
