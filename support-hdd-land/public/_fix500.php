<?php
/**
 * Emergency fix for seller 500 after routes/web.php lost profile.shortcuts.
 * Upload to public_html/tmr/public/_fix500.php then open:
 *   https://support.hdd-land.ir/_fix500.php?t=fix500-cb9c
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(300);
@ini_set('memory_limit', '512M');

if (($_GET['t'] ?? '') !== 'fix500-cb9c') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$root = dirname(__DIR__);
$branch = 'cursor/site-seo-engine-cb9c';
$base = "https://raw.githubusercontent.com/petersany325/petersany325/{$branch}/support-hdd-land/";

function out(string $m): void
{
    echo $m."\n";
    @ob_flush();
    @flush();
}

function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 180,
        CURLOPT_USERAGENT => 'HDD-Fix500',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [$code, is_string($body) ? $body : '', $err];
}

out('ROOT='.$root);
out('BRANCH='.$branch);

$files = [
    'routes/web.php',
    'app/Http/Controllers/ProfileController.php',
    'app/Support/StaffShortcutDock.php',
    'app/Support/NavMenu.php',
    'app/Support/SeoSettings.php',
    'app/Http/Controllers/SeoController.php',
    'app/Http/Controllers/SettingController.php',
    'resources/views/layouts/app.blade.php',
    'resources/views/layouts/portal.blade.php',
    'resources/views/gate.blade.php',
    'resources/views/partials/seo-meta.blade.php',
    'resources/views/partials/staff-shortcut-dock.blade.php',
    'resources/views/settings/index.blade.php',
    'config/updates.php',
];

$ok = 0;
$fail = 0;
foreach ($files as $rel) {
    [$code, $body, $err] = fetch($base.$rel);
    if ($code >= 400 || $body === '' || strlen($body) < 20) {
        out("FAIL {$rel} http={$code} err={$err}");
        $fail++;
        continue;
    }
    $dest = $root.'/'.$rel;
    $dir = dirname($dest);
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    // backup once
    if (is_file($dest) && ! is_file($dest.'.bak-fix500')) {
        @copy($dest, $dest.'.bak-fix500');
    }
    file_put_contents($dest, $body);
    out('OK '.$rel.' '.strlen($body));
    $ok++;
}

foreach ([
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/routes.php',
    'bootstrap/cache/services.php',
    'bootstrap/cache/packages.php',
] as $c) {
    $p = $root.'/'.$c;
    if (is_file($p)) {
        @unlink($p);
        out('cleared '.$c);
    }
}

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    Illuminate\Support\Facades\Artisan::call('route:clear');
    out('route:clear OK');
    Illuminate\Support\Facades\Artisan::call('view:clear');
    out('view:clear OK');
    Illuminate\Support\Facades\Artisan::call('config:clear');
    out('config:clear OK');
    out('HAS profile.shortcuts='.(Illuminate\Support\Facades\Route::has('profile.shortcuts') ? 'Y' : 'N'));
} catch (Throwable $e) {
    out('boot warn '.$e->getMessage());
}

out("copied={$ok} fail={$fail}");
out('DONE');
@unlink(__FILE__);
