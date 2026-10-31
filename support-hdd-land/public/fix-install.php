<?php

/**
 * One-time repair for shared-hosting installs after wizard finishes.
 * Open: https://dev.hdd-land.com/public/fix-install.php
 * Delete this file after success.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$base = dirname(__DIR__);
$envPath = $base.'/.env';
$lockPath = $base.'/storage/app/installed.lock';
$htaccessPath = $base.'/.htaccess';

$host = $_SERVER['HTTP_HOST'] ?? 'dev.hdd-land.com';
$host = preg_replace('/:\\d+$/', '', $host) ?? $host;
$https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$appUrl = ($https ? 'https' : 'http').'://'.$host;

$msgs = [];
$ok = true;

if (! is_file($envPath)) {
    $ok = false;
    $msgs[] = '.env پیدا نشد. اول ویزارد نصب را کامل کنید.';
} else {
    $env = file_get_contents($envPath);
    if ($env === false) {
        $ok = false;
        $msgs[] = 'خواندن .env ممکن نشد.';
    } else {
        $new = preg_replace('/^APP_URL=.*$/m', 'APP_URL="'.$appUrl.'"', $env, 1, $count);
        if ($count === 0) {
            $new = rtrim($env)."\nAPP_URL=\"".$appUrl."\"\n";
        }
        // Ensure debug off / production
        $new = preg_replace('/^APP_DEBUG=.*$/m', 'APP_DEBUG="false"', $new);
        if (file_put_contents($envPath, $new) === false) {
            $ok = false;
            $msgs[] = 'نوشتن APP_URL در .env ممکن نشد.';
        } else {
            $msgs[] = 'APP_URL روی '.$appUrl.' تنظیم شد.';
        }
    }
}

// Replace root .htaccess if it still forces support.hdd-land.ir
$goodHt = <<<'HT'
# Shared hosting: project root as Document Root → route into /public
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(\.env|\.env\..*|composer\.(json|lock)|artisan|phpunit\.xml)(/|$) - [F,L,NC]
    RewriteRule ^(storage|vendor|bootstrap|config|database|resources|routes|app|tests|install)/ - [F,L,NC]
    RewriteRule ^install\.php$ public/install.php [L]
    RewriteRule ^fix-install\.php$ public/fix-install.php [L]
    RewriteRule ^public/ - [L]
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
DirectoryIndex index.php
Options -Indexes
HT;

if (is_file($htaccessPath)) {
    $old = (string) file_get_contents($htaccessPath);
    if (str_contains($old, 'support.hdd-land.ir')) {
        if (file_put_contents($htaccessPath, $goodHt) !== false) {
            $msgs[] = 'فایل .htaccess ریشه اصلاح شد (ریدایرکت اشتباه به support حذف شد).';
        } else {
            $ok = false;
            $msgs[] = 'نشد .htaccess ریشه را بنویسم.';
        }
    } else {
        $msgs[] = '.htaccess ریشه ریدایرکت support ندارد.';
    }
} else {
    if (file_put_contents($htaccessPath, $goodHt) !== false) {
        $msgs[] = '.htaccess ریشه ساخته شد.';
    }
}

if (! is_file($base.'/index.php')) {
    file_put_contents($base.'/index.php', "<?php\nrequire __DIR__.'/public/index.php';\n");
    $msgs[] = 'index.php ریشه ساخته شد.';
}

if (! is_file($lockPath)) {
    @mkdir(dirname($lockPath), 0775, true);
    file_put_contents($lockPath, json_encode(['installed_at' => date('c'), 'repaired' => true], JSON_UNESCAPED_UNICODE));
    $msgs[] = 'installed.lock ساخته شد.';
} else {
    $msgs[] = 'installed.lock موجود است.';
}

// Clear bootstrap cache files if present
foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $f) {
    if (! str_ends_with($f, '.gitignore')) {
        @unlink($f);
    }
}
$msgs[] = 'کش bootstrap پاک شد.';

$loginCandidates = [
    $appUrl.'/login',
    $appUrl.'/public/index.php/login',
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تعمیر نصب</title>
    <style>
        body{font-family:Tahoma,sans-serif;background:#eef1f5;padding:24px;color:#1f2933}
        .box{max-width:640px;margin:0 auto;background:#fff;border:1px solid #c9d0da;border-radius:10px;padding:18px}
        .ok{background:#e8f8ef;border:1px solid #9dcfb0;color:#0f6b3a;padding:10px;border-radius:8px;margin:8px 0}
        .bad{background:#fde8e8;border:1px solid #f3b4b4;color:#b42318;padding:10px;border-radius:8px;margin:8px 0}
        a.btn{display:inline-block;margin-top:12px;background:#1d4f91;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:700}
        code{background:#f1f4f8;padding:1px 5px;border-radius:4px}
        li{margin:6px 0;line-height:1.7}
    </style>
</head>
<body>
<div class="box">
    <h1>تعمیر نصب — <?= htmlspecialchars($host) ?></h1>
    <?php foreach ($msgs as $m): ?>
        <div class="<?= $ok ? 'ok' : 'bad' ?>"><?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>

    <h3>کار بعدی در cPanel</h3>
    <ol>
        <li>Domains → دامنه <code>dev.hdd-land.com</code></li>
        <li>Document Root را بگذار روی: <code>public_html/dev/public</code></li>
        <li>بعد برو به صفحه ورود</li>
    </ol>

    <p>
        <a class="btn" href="<?= htmlspecialchars($appUrl) ?>/login">رفتن به ورود</a>
        <a class="btn" style="background:#445" href="<?= htmlspecialchars($appUrl) ?>/">صفحه اصلی</a>
    </p>
    <p style="font-size:12px;color:#667">بعد از ورود موفق، فایل <code>public/fix-install.php</code> را حذف کن.</p>
</div>
</body>
</html>
