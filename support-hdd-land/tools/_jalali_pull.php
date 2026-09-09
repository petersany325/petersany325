<?php
/**
 * One-shot: pull Jalali calendar settings files from GitHub onto live app root.
 * Place in public_html/tmr/public/_jalali_pull.php and open with ?t=jalali-cb9c
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(180);

if (($_GET['t'] ?? '') !== 'jalali-cb9c') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$root = dirname(__DIR__);
$branch = 'cursor/jalali-dates-settings-cb9c';
$base = "https://raw.githubusercontent.com/petersany325/petersany325/{$branch}/support-hdd-land/";

$files = [
    'app/Support/CalendarSettings.php',
    'app/helpers.php',
    'app/Http/Controllers/SettingController.php',
    'app/Http/Controllers/LicenseAdminController.php',
    'routes/web.php',
    'resources/views/settings/index.blade.php',
    'resources/views/partials/jalali-date.blade.php',
    'resources/views/reports/_settings.blade.php',
    'resources/views/daily-logs/index.blade.php',
    'resources/views/licenses/index.blade.php',
    'resources/views/licenses/edit.blade.php',
    'resources/views/licenses/renew.blade.php',
    'resources/views/licenses/releases.blade.php',
];

function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => 'HDD-Land-Jalali-Pull',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [$code, is_string($body) ? $body : '', $err];
}

echo "ROOT={$root}\nBRANCH={$branch}\n";

$ok = 0;
$fail = 0;
foreach ($files as $rel) {
    [$code, $body, $err] = fetch($base.$rel);
    if ($code >= 400 || $body === '' || strlen($body) < 20) {
        echo "FAIL {$rel} http={$code} err={$err}\n";
        $fail++;
        continue;
    }
    $dest = $root.'/'.$rel;
    $dir = dirname($dest);
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $n = file_put_contents($dest, $body);
    if ($n === false) {
        echo "FAIL write {$rel}\n";
        $fail++;
        continue;
    }
    echo "OK {$rel} {$n}\n";
    $ok++;
}

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "view:clear OK\n";
    Illuminate\Support\Facades\Artisan::call('route:clear');
    echo "route:clear OK\n";
    // Smoke: helpers
    echo 'sample='.jalali_like(now())."\n";
    echo 'calendar='.\App\Support\CalendarSettings::type().'/'.\App\Support\CalendarSettings::digits()."\n";
} catch (Throwable $e) {
    echo 'BOOT ERR: '.$e->getMessage()."\n";
}

echo "DONE ok={$ok} fail={$fail}\n";
@unlink(__FILE__);
