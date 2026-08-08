<?php

/**
 * Show the real reason for Laravel 500 on shared hosting.
 * Open: https://YOUR-DOMAIN/public/debug-500.php
 * DELETE after fixing.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', '1');
error_reporting(E_ALL);

$base = dirname(__DIR__);
$msgs = [];
$fatal = null;

function row(string $label, bool $ok, string $detail = ''): array
{
    return compact('label', 'ok', 'detail');
}

$checks = [];
$checks[] = row('PHP ≥ 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION);
$checks[] = row('vendor/autoload.php', is_file($base.'/vendor/autoload.php'));
$checks[] = row('.env', is_file($base.'/.env'));
$checks[] = row('bootstrap/app.php', is_file($base.'/bootstrap/app.php'));
$checks[] = row('storage writable', is_dir($base.'/storage') && is_writable($base.'/storage'));
$checks[] = row('bootstrap/cache writable', is_dir($base.'/bootstrap/cache') && is_writable($base.'/bootstrap/cache'));

$env = [];
if (is_file($base.'/.env')) {
    foreach (file($base.'/.env', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        $env[trim($k)] = $v;
    }
}

$appKey = (string) ($env['APP_KEY'] ?? '');
$checks[] = row('APP_KEY set', $appKey !== '' && str_contains($appKey, 'base64:'), $appKey === '' ? 'خالی' : 'OK (مخفی)');
$checks[] = row('APP_URL', ! empty($env['APP_URL']), (string) ($env['APP_URL'] ?? '—'));
$checks[] = row('DB_DATABASE', ! empty($env['DB_DATABASE']), (string) ($env['DB_DATABASE'] ?? '—'));
$checks[] = row('DB_USERNAME', ! empty($env['DB_USERNAME']), (string) ($env['DB_USERNAME'] ?? '—'));
$checks[] = row('LICENSE_KEY', isset($env['LICENSE_KEY']), (($env['LICENSE_KEY'] ?? '') !== '' ? 'ست شده' : 'خالی'));

// Wipe stale config cache again
foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $f) {
    @unlink($f);
}

// DB ping without Laravel
$dbDetail = '';
$dbOk = false;
try {
    $host = (string) ($env['DB_HOST'] ?? '127.0.0.1');
    $port = (string) ($env['DB_PORT'] ?? '3306');
    $database = (string) ($env['DB_DATABASE'] ?? '');
    $username = (string) ($env['DB_USERNAME'] ?? '');
    $password = (string) ($env['DB_PASSWORD'] ?? '');
    if ($database === '' || $username === '') {
        $dbDetail = 'DB در .env ناقص است';
    } else {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $dbOk = true;
        $dbDetail = 'متصل — '.count($tables).' جدول — host='.$host;
        if (! in_array('users', $tables, true)) {
            $dbDetail .= ' | جدول users نیست (نصب کامل نشده)';
            $dbOk = false;
        }
    }
} catch (Throwable $e) {
    $dbDetail = $e->getMessage();
}
$checks[] = row('اتصال MySQL', $dbOk, $dbDetail);

// Bootstrap Laravel and catch real exception
$bootDetail = '';
$bootOk = false;
$routeOk = false;
$routeDetail = '';
try {
    if (! is_file($base.'/vendor/autoload.php')) {
        throw new RuntimeException('vendor نیست');
    }
    require $base.'/vendor/autoload.php';
    $app = require $base.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/login', 'GET');
    $response = $kernel->handle($request);
    $bootOk = true;
    $bootDetail = 'Laravel boot OK — /login status '.$response->getStatusCode();
    $routeOk = $response->getStatusCode() < 500;
    $routeDetail = 'status='.$response->getStatusCode();
    if ($response->getStatusCode() >= 500) {
        $content = method_exists($response, 'getContent') ? (string) $response->getContent() : '';
        if (preg_match('/class="exception-message[^"]*"[^>]*>(.*?)<\\/span>/s', $content, $m)) {
            $routeDetail .= ' | '.trim(html_entity_decode(strip_tags($m[1])));
        } elseif (preg_match('/<div class="exception-message[^"]*"[^>]*>(.*?)<\\/div>/s', $content, $m)) {
            $routeDetail .= ' | '.trim(html_entity_decode(strip_tags($m[1])));
        } else {
            // Symfony error page title
            if (preg_match('/<title>(.*?)<\\/title>/', $content, $m)) {
                $routeDetail .= ' | '.trim(html_entity_decode(strip_tags($m[1])));
            }
            $routeDetail .= ' | body='.substr(trim(strip_tags($content)), 0, 240);
        }
    }
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    $fatal = $e;
    $bootDetail = $e->getMessage();
    $routeDetail = $e->getFile().':'.$e->getLine();
}
$checks[] = row('Laravel boot + /login', $bootOk && $routeOk, $bootDetail.($routeDetail ? ' — '.$routeDetail : ''));

// Latest log tail
$logDetail = '—';
$logFiles = glob($base.'/storage/logs/*.log') ?: [];
rsort($logFiles);
if ($logFiles) {
    $tail = @file_get_contents($logFiles[0]);
    if (is_string($tail) && $tail !== '') {
        $logDetail = basename($logFiles[0])."\n".substr($tail, -2500);
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تشخیص خطای ۵۰۰</title>
    <style>
        body{font-family:Tahoma,sans-serif;background:#eef1f5;padding:20px;color:#1f2933}
        .box{max-width:860px;margin:0 auto;background:#fff;border:1px solid #c9d0da;border-radius:10px;padding:18px}
        .ok{color:#0f6b3a}.bad{color:#b42318}
        table{width:100%;border-collapse:collapse;margin:12px 0}
        td,th{border:1px solid #dbe2ea;padding:8px;text-align:right;font-size:13px;vertical-align:top}
        pre{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;font-size:12px;direction:ltr;text-align:left}
        code{background:#f1f4f8;padding:1px 5px;border-radius:4px}
    </style>
</head>
<body>
<div class="box">
    <h1>تشخیص خطای ۵۰۰ — <?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?></h1>
    <table>
        <tr><th>بررسی</th><th>وضعیت</th><th>جزئیات</th></tr>
        <?php foreach ($checks as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['label']) ?></td>
                <td class="<?= $c['ok'] ? 'ok' : 'bad' ?>"><?= $c['ok'] ? 'OK' : 'FAIL' ?></td>
                <td dir="ltr" style="text-align:left"><?= htmlspecialchars($c['detail']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if ($fatal): ?>
        <h3 class="bad">Exception</h3>
        <pre><?= htmlspecialchars($fatal->getMessage()."\n".$fatal->getFile().':'.$fatal->getLine()."\n\n".$fatal->getTraceAsString()) ?></pre>
    <?php endif; ?>

    <h3>انتهای لاگ Laravel</h3>
    <pre><?= htmlspecialchars($logDetail) ?></pre>

    <p style="font-size:12px;color:#667">بعد از رفع مشکل این فایل را حذف کنید: <code>public/debug-500.php</code></p>
</div>
</body>
</html>
