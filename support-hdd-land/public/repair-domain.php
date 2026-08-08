<?php

/**
 * Emergency repair when customer domain redirects to support.hdd-land.ir
 * or /login returns Apache 404.
 *
 * Open: https://YOUR-DOMAIN/repair-domain.php
 * or:   https://YOUR-DOMAIN/public/repair-domain.php
 * Delete after success.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$base = dirname(__DIR__);
// If this file was copied to project root by mistake
if (! is_dir($base.'/app') && is_dir(__DIR__.'/app')) {
    $base = __DIR__;
}

$envPath = $base.'/.env';
$htRoot = $base.'/.htaccess';
$htPublic = $base.'/public/.htaccess';
$cacheDir = $base.'/bootstrap/cache';

$host = $_SERVER['HTTP_HOST'] ?? '';
$host = preg_replace('/:\\d+$/', '', (string) $host) ?: '';
$https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$appUrl = $host !== '' ? (($https ? 'https' : 'http').'://'.$host) : '';

$msgs = [];
$ok = true;

// 1) Kill stale config cache (this is what pointed customers to support.hdd-land.ir)
$removed = 0;
foreach (glob($cacheDir.'/*.php') ?: [] as $f) {
    if (is_file($f) && @unlink($f)) {
        $removed++;
    }
}
$msgs[] = $removed > 0
    ? "کش bootstrap پاک شد ({$removed} فایل) — شامل config قفل‌شده روی support."
    : 'کش bootstrap از قبل خالی بود.';

// 2) Fix APP_URL in .env to THIS host
if ($appUrl === '') {
    $ok = false;
    $msgs[] = 'هاست درخواست تشخیص داده نشد.';
} elseif (! is_file($envPath)) {
    $ok = false;
    $msgs[] = '.env پیدا نشد — اول نصب را کامل کنید.';
} else {
    $env = (string) file_get_contents($envPath);
    $new = preg_replace('/^APP_URL=.*$/m', 'APP_URL="'.$appUrl.'"', $env, 1, $count);
    if ($count === 0) {
        $new = rtrim($env)."\nAPP_URL=\"".$appUrl."\"\n";
    }
    if (! preg_match('/^FORCE_CANONICAL_HOST=/m', (string) $new)) {
        $new = rtrim((string) $new)."\nFORCE_CANONICAL_HOST=false\n";
    } else {
        $new = preg_replace('/^FORCE_CANONICAL_HOST=.*$/m', 'FORCE_CANONICAL_HOST=false', (string) $new);
    }
    if (file_put_contents($envPath, $new) === false) {
        $ok = false;
        $msgs[] = 'نتوانستم APP_URL را در .env بنویسم.';
    } else {
        $msgs[] = 'APP_URL روی '.$appUrl.' تنظیم شد (دیگر به support نمی‌رود).';
    }
}

// 3) Root .htaccess for shared hosting (docroot = project root)
$goodRoot = <<<'HT'
# Shared hosting: project root as Document Root → route into /public
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(\.env|\.env\..*|composer\.(json|lock)|artisan|phpunit\.xml)(/|$) - [F,L,NC]
    RewriteRule ^(storage|vendor|bootstrap|config|database|resources|routes|app|tests|install)/ - [F,L,NC]
    RewriteRule ^install\.php$ public/install.php [L]
    RewriteRule ^fix-install\.php$ public/fix-install.php [L]
    RewriteRule ^repair-domain\.php$ public/repair-domain.php [L]
    RewriteRule ^public/ - [L]
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
DirectoryIndex index.php
Options -Indexes
HT;

if (file_put_contents($htRoot, $goodRoot) !== false) {
    $msgs[] = '.htaccess ریشه برای مسیر /login نوشته شد.';
} else {
    $ok = false;
    $msgs[] = 'نوشتن .htaccess ریشه ناموفق بود.';
}

// 4) Ensure public/.htaccess front controller exists
$goodPublic = <<<'HT'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HT;

if (! is_dir(dirname($htPublic))) {
    @mkdir(dirname($htPublic), 0775, true);
}
if (file_put_contents($htPublic, $goodPublic) !== false) {
    $msgs[] = 'public/.htaccess برای /login نوشته شد.';
}

if (! is_file($base.'/index.php')) {
    file_put_contents($base.'/index.php', "<?php\nrequire __DIR__.'/public/index.php';\n");
    $msgs[] = 'index.php ریشه ساخته شد.';
}

// Ensure storage dirs exist/writable
foreach (['storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/app/public', 'bootstrap/cache'] as $rel) {
    $p = $base.'/'.$rel;
    if (! is_dir($p)) {
        @mkdir($p, 0775, true);
    }
}

// Generate APP_KEY if missing (common cause of opaque 500)
if (is_file($envPath)) {
    $envNow = (string) file_get_contents($envPath);
    if (! preg_match('/^APP_KEY=base64:.+/m', $envNow)) {
        $key = 'base64:'.base64_encode(random_bytes(32));
        if (preg_match('/^APP_KEY=.*$/m', $envNow)) {
            $envNow = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY="'.$key.'"', $envNow);
        } else {
            $envNow = rtrim($envNow)."\nAPP_KEY=\"".$key."\"\n";
        }
        if (file_put_contents($envPath, $envNow) !== false) {
            $msgs[] = 'APP_KEY خالی بود و ساخته شد.';
        }
    }
}

// Probe Laravel boot error (so 500 reason is visible here)
$bootError = null;
try {
    if (is_file($base.'/vendor/autoload.php') && is_file($base.'/bootstrap/app.php')) {
        require $base.'/vendor/autoload.php';
        $app = require $base.'/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $request = Illuminate\Http\Request::create('/', 'GET');
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        if ($status >= 500) {
            $bootError = 'Laravel پاسخ '.$status.' داد. فایل public/debug-500.php را باز کنید یا لاگ storage/logs را ببینید.';
            $ok = false;
        } else {
            $msgs[] = 'تست Laravel روی / وضعیت '.$status.' گرفت.';
        }
        $kernel->terminate($request, $response);
    }
} catch (Throwable $e) {
    $bootError = $e->getMessage().' @ '.$e->getFile().':'.$e->getLine();
    $ok = false;
}
if ($bootError) {
    $msgs[] = 'خطای فعلی برنامه: '.$bootError;
}

$login = $appUrl !== '' ? $appUrl.'/login' : '/login';
$loginAlt = $appUrl !== '' ? $appUrl.'/index.php/login' : '/index.php/login';
$debugUrl = ($appUrl !== '' ? $appUrl : '').'/public/debug-500.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تعمیر دامنه</title>
    <style>
        body{font-family:Tahoma,sans-serif;background:#eef1f5;padding:24px;color:#1f2933}
        .box{max-width:640px;margin:0 auto;background:#fff;border:1px solid #c9d0da;border-radius:10px;padding:18px}
        .ok{background:#e8f8ef;border:1px solid #9dcfb0;color:#0f6b3a;padding:10px;border-radius:8px;margin:8px 0}
        .bad{background:#fde8e8;border:1px solid #f3b4b4;color:#b42318;padding:10px;border-radius:8px;margin:8px 0}
        a.btn{display:inline-block;margin:8px 6px 0 0;background:#1d4f91;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:700}
        code{background:#f1f4f8;padding:1px 5px;border-radius:4px}
    </style>
</head>
<body>
<div class="box">
    <h1>تعمیر دامنه — <?= htmlspecialchars($host) ?></h1>
    <?php foreach ($msgs as $m): ?>
        <div class="<?= $ok ? 'ok' : 'bad' ?>"><?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>
    <p>اگر هنوز <code>/login</code> کار نکرد، در cPanel برای این دامنه Document Root را روی پوشه <code>public</code> بگذارید.</p>
    <p>
        <a class="btn" href="<?= htmlspecialchars($login) ?>">ورود</a>
        <a class="btn" style="background:#445" href="<?= htmlspecialchars($loginAlt) ?>">ورود (مسیر جایگزین)</a>
    </p>
    <p style="font-size:12px;color:#667">بعد از ورود موفق این فایل را حذف کنید: <code>public/repair-domain.php</code></p>
</div>
</body>
</html>
