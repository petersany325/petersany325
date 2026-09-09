<?php
/**
 * Emergency fix when support.hdd-land.ir shows Laravel 404 for ALL pages
 * (/up and /css work, but / and /login 404 → usually broken route cache
 * or missing/corrupt routes/web.php after a partial deploy).
 *
 * Upload to public_html/tmr/_route_fix.php (app root, next to artisan)
 * then open: https://support.hdd-land.ir/_route_fix.php?token=cb9c-route-fix-2026
 * Optional: &repair_web=1  to restore routes/web.php from GitHub branch
 * Optional: &cleanup=1     to delete this script after success
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(120);

$token = 'cb9c-route-fix-2026';
if (! isset($_GET['token']) || ! hash_equals($token, (string) $_GET['token'])) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

function out(string $m): void
{
    echo $m."\n";
    @ob_flush();
    @flush();
}

$root = __DIR__;
out('ROOT='.$root);
out('PHP='.PHP_VERSION);

$cacheDir = $root.'/bootstrap/cache';
$removed = [];
foreach (['routes-v7.php', 'routes.php', 'config.php', 'services.php', 'packages.php', 'events.php'] as $f) {
    $p = $cacheDir.'/'.$f;
    if (is_file($p)) {
        @unlink($p);
        $removed[] = $f;
    }
}
out('cache_cleared='.( $removed ? implode(',', $removed) : '(none present)' ));

$web = $root.'/routes/web.php';
out('web.php_exists='.(is_file($web) ? 'yes' : 'NO'));
out('web.php_bytes='.(is_file($web) ? (string) filesize($web) : '0'));

if ((! is_file($web) || filesize($web) < 500) && isset($_GET['repair_web'])) {
    out('Downloading routes/web.php from GitHub…');
    $url = 'https://raw.githubusercontent.com/petersany325/petersany325/cursor/customer-live-update-cb9c/support-hdd-land/routes/web.php';
    $ctx = stream_context_create([
        'http' => ['timeout' => 60, 'header' => "User-Agent: HDD-Land-RouteFix\r\n"],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 500) {
        out('FAIL download web.php');
        exit(1);
    }
    if (! is_dir(dirname($web))) {
        @mkdir(dirname($web), 0755, true);
    }
    file_put_contents($web, $data);
    out('web.php restored bytes='.strlen($data));
}

if (! is_file($root.'/artisan')) {
    out('FAIL artisan missing — wrong upload path (need tmr app root)');
    exit(1);
}

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    foreach (['route:clear', 'config:clear', 'cache:clear', 'view:clear'] as $cmd) {
        try {
            Illuminate\Support\Facades\Artisan::call($cmd);
            out('OK '.$cmd);
        } catch (Throwable $e) {
            out('WARN '.$cmd.': '.$e->getMessage());
        }
    }

    $routes = Illuminate\Support\Facades\Route::getRoutes();
    $names = [];
    foreach ($routes as $route) {
        $uri = $route->uri();
        if ($uri === '/' || $uri === 'login' || str_starts_with($uri, 'license/')) {
            $names[] = $uri;
        }
    }
    sort($names);
    out('key_routes='.implode(',', $names ?: ['(none)']));
    out('total_routes='.count($routes));

    if (! in_array('/', $names, true) || ! in_array('login', $names, true)) {
        out('STATUS=STILL_BROKEN — routes/web.php still not loaded. Re-upload full app or open with &repair_web=1');
        http_response_code(500);
        exit(1);
    }

    out('STATUS=OK — open https://support.hdd-land.ir/');
} catch (Throwable $e) {
    out('FAIL bootstrap: '.$e->getMessage());
    http_response_code(500);
    exit(1);
}

if (isset($_GET['cleanup'])) {
    @unlink(__FILE__);
    out('self_deleted=1');
}
