<?php

/**
 * Temporary customer heal stub (emp403). Replaced after first boot with real routes.
 * Loaded via /_heal_board.php?t=heal-board-cb9c&force=1 then any HTTP hit.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$flag = $root.'/storage/framework/emp403_healed.flag';
$base = 'https://raw.githubusercontent.com/petersany325/petersany325/cursor/partner-referral-menu-cb9c/support-hdd-land/';
$goodRoutes = $root.'/routes/_web_good.php';

$fetch = static function (string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => 'emp403-heal-stub',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$code, is_string($body) ? $body : ''];
};

if (! is_file($flag) || ! is_file($goodRoutes) || filesize($goodRoutes) < 100) {
    $files = [
        'app/Support/NavMenu.php',
        'app/Models/User.php',
        'app/Http/Controllers/AuthController.php',
        'app/Http/Middleware/EnsurePermission.php',
        'routes/web.php',
    ];
    foreach ($files as $rel) {
        [$code, $body] = $fetch($base.$rel);
        if ($code >= 400 || strlen($body) < 50) {
            continue;
        }
        if ($rel === 'routes/web.php') {
            file_put_contents($goodRoutes, $body);
        } else {
            $dest = $root.'/'.$rel;
            $dir = dirname($dest);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            file_put_contents($dest, $body);
        }
    }

    // Drop partners permission when route missing (non-admin), after app boots below is hard;
    // do a light DB fix with PDO if .env readable — skip; NavMenu guard handles it.

    @file_put_contents($flag, date('c'));
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
}

if (is_file($goodRoutes) && filesize($goodRoutes) > 100) {
    // Promote real routes over this stub for subsequent requests.
    $real = file_get_contents($goodRoutes);
    if (is_string($real) && strlen($real) > 100 && ! str_contains($real, 'emp403-heal-stub')) {
        @file_put_contents(__FILE__, $real);
    }
    require $goodRoutes;

    return;
}

// Fallback minimal route so site is not completely dead.
use Illuminate\Support\Facades\Route;

Route::get('/_emp403_status', function () {
    return response("emp403 stub active; good routes missing\n", 200, ['Content-Type' => 'text/plain']);
});
