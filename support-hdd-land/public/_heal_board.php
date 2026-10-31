<?php
/**
 * Seller self-heal: restore release-board service/controller if truncated.
 * Keep in public; open: /_heal_board.php?t=heal-board-cb9c
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(180);

if (($_GET['t'] ?? '') !== 'heal-board-cb9c') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$root = dirname(__DIR__);
$branch = 'cursor/release-change-board-cb9c';
$base = "https://raw.githubusercontent.com/petersany325/petersany325/{$branch}/support-hdd-land/";

$files = [
    'app/Services/ReleaseChangeBoardService.php',
    'app/Http/Controllers/AppReleaseAdminController.php',
    'resources/views/licenses/releases.blade.php',
    'routes/web.php',
];

function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'HDD-Heal-Board',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [$code, is_string($body) ? $body : '', $err];
}

echo "ROOT={$root}\n";
$ok = 0;
$fail = 0;
foreach ($files as $rel) {
    $dest = $root.'/'.$rel;
    $size = is_file($dest) ? (int) filesize($dest) : 0;
    $force = isset($_GET['force']);
    if ($size > 100 && ! $force) {
        echo "SKIP {$rel} size={$size}\n";
        continue;
    }
    [$code, $body, $err] = fetch($base.$rel);
    if ($code >= 400 || strlen($body) < 50) {
        echo "FAIL {$rel} http={$code} err={$err}\n";
        $fail++;
        continue;
    }
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
    foreach (['view:clear', 'route:clear', 'config:clear'] as $cmd) {
        try {
            Illuminate\Support\Facades\Artisan::call($cmd);
            echo "artisan {$cmd} ok\n";
        } catch (Throwable $e) {
            echo "artisan {$cmd} ".$e->getMessage()."\n";
        }
    }
} catch (Throwable $e) {
    echo 'bootstrap '.$e->getMessage()."\n";
}

echo "DONE ok={$ok} fail={$fail}\n";
