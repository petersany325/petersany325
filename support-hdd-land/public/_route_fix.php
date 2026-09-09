<?php
/**
 * EMERGENCY: fix Laravel 404 on ALL routes while /up still works.
 *
 * MUST be uploaded next to install.php:
 *   public_html/tmr/public/_route_fix.php
 *
 * Open:
 *   https://support.hdd-land.ir/_route_fix.php?token=cb9c-route-fix-2026
 *   https://support.hdd-land.ir/_route_fix.php?token=cb9c-route-fix-2026&repair=1
 *   https://support.hdd-land.ir/_route_fix.php?token=cb9c-route-fix-2026&repair=1&cleanup=1
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
@ini_set('display_errors', '1');
@error_reporting(E_ALL);
@set_time_limit(300);

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

function http_get(string $url): string|false
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 120,
            'header' => "User-Agent: HDD-Land-RouteFix\r\nAccept: */*\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data !== false && strlen($data) > 0) {
        return $data;
    }
    if (! function_exists('curl_init')) {
        return false;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'HDD-Land-RouteFix',
    ]);
    $data = curl_exec($ch);
    curl_close($ch);

    return ($data !== false && strlen((string) $data) > 0) ? (string) $data : false;
}

// public/_route_fix.php → app root is parent
$public = __DIR__;
$root = dirname($public);
if (! is_file($root.'/artisan') && is_file($public.'/artisan')) {
    // script wrongly placed in app root
    $root = $public;
}
if (! is_file($root.'/artisan') && is_file($root.'/support-hdd-land/artisan')) {
    $root = $root.'/support-hdd-land';
}

out('PUBLIC='.$public);
out('ROOT='.$root);
out('PHP='.PHP_VERSION);
out('sapi='.PHP_SAPI);

if (! is_file($root.'/artisan')) {
    out('FAIL: artisan not found. Upload this file into public_html/tmr/public/ (same folder as install.php)');
    http_response_code(500);
    exit(1);
}

$web = $root.'/routes/web.php';
$bootstrap = $root.'/bootstrap/app.php';
$cacheDir = $root.'/bootstrap/cache';

out('web.php='.(is_file($web) ? ('yes bytes='.filesize($web)) : 'MISSING'));
out('bootstrap/app.php='.(is_file($bootstrap) ? ('yes bytes='.filesize($bootstrap)) : 'MISSING'));

// Always clear route/config caches first
$removed = [];
foreach (['routes-v7.php', 'routes.php', 'config.php', 'events.php'] as $f) {
    $p = $cacheDir.'/'.$f;
    if (is_file($p)) {
        $ok = @unlink($p);
        $removed[] = $f.($ok ? '' : '(unlink_fail)');
    }
}
out('cache_removed='.($removed ? implode(',', $removed) : '(none)'));

$branch = 'cursor/restore-remote-portal-cb9c';
$baseRaw = 'https://raw.githubusercontent.com/petersany325/petersany325/'.$branch.'/support-hdd-land';

if (isset($_GET['repair'])) {
    out('--- repair from GitHub branch '.$branch.' ---');

    $files = [
        'routes/web.php' => $web,
        'bootstrap/app.php' => $bootstrap,
    ];

    foreach ($files as $rel => $dest) {
        $url = $baseRaw.'/'.$rel;
        out('GET '.$url);
        $data = http_get($url);
        if ($data === false || strlen($data) < 200) {
            out('FAIL download '.$rel.' (len='.strlen((string) $data).')');
            http_response_code(500);
            exit(1);
        }
        // safety: web.php must define gate /
        if ($rel === 'routes/web.php' && ! str_contains($data, "Route::get('/')")) {
            out('FAIL downloaded web.php has no Route::get(/)');
            http_response_code(500);
            exit(1);
        }
        $dir = dirname($dest);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // backup
        if (is_file($dest)) {
            @copy($dest, $dest.'.bak-404-'.date('YmdHis'));
        }
        $w = file_put_contents($dest, $data);
        out('WROTE '.$rel.' bytes='.$w);
    }

    // clear caches again after write
    foreach (['routes-v7.php', 'routes.php', 'config.php'] as $f) {
        @unlink($cacheDir.'/'.$f);
    }
}

// Syntax check web.php
if (is_file($web)) {
    $lint = [];
    $code = 0;
    @exec('php -l '.escapeshellarg($web).' 2>&1', $lint, $code);
    out('php_lint_web='.($lint[0] ?? 'n/a').' code='.$code);
}

// Bootstrap Laravel and count routes
try {
    require $root.'/vendor/autoload.php';
    /** @var \Illuminate\Foundation\Application $app */
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
    $hit = [];
    foreach ($routes as $route) {
        $uri = $route->uri();
        if ($uri === '/' || $uri === 'login' || str_starts_with((string) $uri, 'license')) {
            $hit[] = $uri;
        }
    }
    $hit = array_values(array_unique($hit));
    sort($hit);
    out('total_routes='.count($routes));
    out('key_routes='.implode(',', $hit ?: ['(none)']));

    if (! in_array('/', $hit, true) || ! in_array('login', $hit, true)) {
        out('STATUS=STILL_BROKEN');
        out('HINT: open again with &repair=1  OR re-upload full routes/web.php via File Manager');
        // dump first 40 route uris for debug
        $i = 0;
        foreach ($routes as $route) {
            out('route: '.$route->uri());
            if (++$i >= 40) {
                break;
            }
        }
        http_response_code(500);
        exit(1);
    }

    out('STATUS=OK');
    out('Open https://support.hdd-land.ir/  (hard refresh)');
} catch (Throwable $e) {
    out('FAIL bootstrap: '.$e->getMessage());
    out('file: '.$e->getFile().':'.$e->getLine());
    http_response_code(500);
    exit(1);
}

if (isset($_GET['cleanup'])) {
    @unlink(__FILE__);
    out('self_deleted=1');
}
