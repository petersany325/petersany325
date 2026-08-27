<?php

/**
 * Fix white-label brand on an already-installed customer site.
 * Open: https://YOUR-DOMAIN/public/fix-brand.php
 * Optional: ?name=نام شرکت
 * DELETE after use.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$base = dirname(__DIR__);
$envPath = $base.'/.env';
$msgs = [];
$ok = true;

function env_read(string $path): array
{
    $out = [];
    if (! is_file($path)) {
        return $out;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        $out[trim($k)] = $v;
    }

    return $out;
}

$env = env_read($envPath);
$name = trim((string) ($_GET['name'] ?? ''));
if ($name === '') {
    $name = trim((string) ($env['APP_NAME'] ?? ''));
}
if ($name === '' || preg_match('/سرزمین\s*هارد|HDD\s*LAND|HDDLand/iu', $name)) {
    $name = 'تعمیرگاه';
}

$tagline = 'سیستم مدیریت تعمیرات';

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_DATABASE'] ?? ''
    );
    $pdo = new PDO($dsn, (string) ($env['DB_USERNAME'] ?? ''), (string) ($env['DB_PASSWORD'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pairs = [
        'invoice_shop_name' => $name,
        'shop_tagline' => $tagline,
        'brand_logo_version' => (string) time(),
    ];

    foreach ($pairs as $key => $value) {
        $st = $pdo->prepare('SELECT id FROM app_settings WHERE `key` = ? LIMIT 1');
        $st->execute([$key]);
        $id = $st->fetchColumn();
        if ($id) {
            $pdo->prepare('UPDATE app_settings SET `value` = ?, updated_at = NOW() WHERE id = ?')->execute([$value, $id]);
        } else {
            $pdo->prepare('INSERT INTO app_settings (`key`, `value`, created_at, updated_at) VALUES (?, ?, NOW(), NOW())')
                ->execute([$key, $value]);
        }
    }
    $msgs[] = 'نام برند روی «'.$name.'» تنظیم شد.';
    $msgs[] = 'شعار: '.$tagline;
    $msgs[] = 'کش لوگو تازه شد — صفحه را Hard Refresh کنید (Ctrl+F5).';
} catch (Throwable $e) {
    $ok = false;
    $msgs[] = 'خطا: '.$e->getMessage();
}

// Also normalize APP_NAME in .env if it still has seller brand
if (is_file($envPath)) {
    $raw = (string) file_get_contents($envPath);
    $new = preg_replace('/^APP_NAME=.*$/m', 'APP_NAME="'.$name.'"', $raw, 1, $count);
    if ($count === 0) {
        $new = rtrim($raw)."\nAPP_NAME=\"".$name."\"\n";
    }
    if (is_string($new) && file_put_contents($envPath, $new) !== false) {
        $msgs[] = 'APP_NAME در .env همسان شد.';
    }
    foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $f) {
        @unlink($f);
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>اصلاح برند</title>
    <style>
        body{font-family:Tahoma,sans-serif;background:#eef1f5;padding:24px}
        .box{max-width:640px;margin:0 auto;background:#fff;border:1px solid #c9d0da;border-radius:10px;padding:18px}
        .ok{background:#e8f8ef;border:1px solid #9dcfb0;color:#0f6b3a;padding:10px;border-radius:8px;margin:8px 0}
        .bad{background:#fde8e8;border:1px solid #f3b4b4;color:#b42318;padding:10px;border-radius:8px;margin:8px 0}
        code{background:#f1f4f8;padding:1px 5px;border-radius:4px}
        a.btn{display:inline-block;margin-top:12px;background:#1d4f91;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px}
    </style>
</head>
<body>
<div class="box">
    <h1>اصلاح برند وایت‌لیبل</h1>
    <?php foreach ($msgs as $m): ?>
        <div class="<?= $ok ? 'ok' : 'bad' ?>"><?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>
    <p>برای نام دلخواه: <code>?name=نام شرکت شما</code></p>
    <p>مثال: <code>fix-brand.php?name=تعمیرگاه نمونه</code></p>
    <a class="btn" href="/">بازگشت به صفحه ورود</a>
    <p style="font-size:12px;color:#667;margin-top:14px;">بعد از کار این فایل را حذف کنید.</p>
</div>
</body>
</html>
