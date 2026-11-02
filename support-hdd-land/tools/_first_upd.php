<?php
/**
 * Customer first-time bootstrap: install update menu + latest code from GitHub branch.
 * Upload to public_html/.../public/_first_upd.php then open:
 *   https://support.hddsoftware.ir/_first_upd.php?t=first-upd-cb9c
 *
 * Safe: skips .env, storage user data, vendor.
 * After success: منوی «آپدیت نرم‌افزار» در ابزارهای سیستم ظاهر می‌شود.
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(600);
@ini_set('memory_limit', '512M');

if (($_GET['t'] ?? '') !== 'first-upd-cb9c') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$root = dirname(__DIR__);
$branch = 'cursor/jalali-dates-settings-cb9c';
$zipUrl = "https://codeload.github.com/petersany325/petersany325/zip/refs/heads/{$branch}";

function out(string $m): void
{
    echo $m."\n";
    @ob_flush();
    @flush();
}

out('ROOT='.$root);
out('PHP='.PHP_VERSION);
out('BRANCH='.$branch);

$tmp = $root.'/storage/app/tmp';
if (! is_dir($tmp)) {
    @mkdir($tmp, 0755, true);
}
$zipFile = $tmp.'/first_upd_branch.zip';
$extract = $tmp.'/first_upd_extract';

out('Downloading...');
$ch = curl_init($zipUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 300,
    CURLOPT_USERAGENT => 'HDD-Land-First-Update',
]);
$data = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
if ($code >= 400 || ! is_string($data) || strlen($data) < 1000) {
    out("FAIL download http={$code} err={$err}");
    exit(1);
}
file_put_contents($zipFile, $data);
out('zip bytes='.strlen($data));

if (is_dir($extract)) {
    // best-effort cleanup
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extract, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
}
@mkdir($extract, 0755, true);
$zip = new ZipArchive();
if ($zip->open($zipFile) !== true) {
    out('FAIL open zip');
    exit(1);
}
$zip->extractTo($extract);
$zip->close();
out('extracted');

$src = null;
foreach (scandir($extract) ?: [] as $d) {
    if ($d === '.' || $d === '..') {
        continue;
    }
    $cand = $extract.'/'.$d.'/support-hdd-land';
    if (is_dir($cand) && is_file($cand.'/artisan') && is_dir($cand.'/app')) {
        $src = $cand;
        break;
    }
}
if (! $src) {
    out('FAIL app root not found');
    exit(1);
}
out('SRC='.$src);

$skipExact = ['.env', '.env.backup', '.env.bak', '.env.bak-inst', '.env.bak-phase1', 'vendor', 'node_modules', '.git'];
$copied = 0;
$skipped = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($iterator as $file) {
    $rel = substr($file->getPathname(), strlen($src) + 1);
    $relUnix = str_replace('\\', '/', $rel);
    $top = explode('/', $relUnix)[0] ?? '';
    if (in_array($top, $skipExact, true) || str_starts_with(basename($relUnix), '.env')) {
        $skipped++;
        continue;
    }
    if (str_starts_with($relUnix, 'storage/')) {
        // keep customer storage; only allow empty placeholder dirs creation lightly
        if ($file->isDir()) {
            $dest = $root.'/'.$relUnix;
            if (! is_dir($dest)) {
                @mkdir($dest, 0755, true);
            }
        }
        $skipped++;
        continue;
    }
    if (str_starts_with($relUnix, 'vendor/') || str_starts_with($relUnix, 'node_modules/') || str_starts_with($relUnix, '.git/')) {
        $skipped++;
        continue;
    }
    if (preg_match('#\.sqlite$#i', $relUnix)) {
        $skipped++;
        continue;
    }
    $dest = $root.'/'.$relUnix;
    if ($file->isDir()) {
        if (! is_dir($dest)) {
            @mkdir($dest, 0755, true);
        }
        continue;
    }
    $dir = dirname($dest);
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (@copy($file->getPathname(), $dest)) {
        $copied++;
    } else {
        out('FAIL copy '.$relUnix);
    }
}
out("copied={$copied} skipped={$skipped}");

// Mark installed version
$verFile = $root.'/storage/app/installed_version.json';
file_put_contents($verFile, json_encode([
    'version' => '1.2.0',
    'channel' => 'stable',
    'updated_at' => date('c'),
    'meta' => ['source' => 'first_upd_bootstrap', 'branch' => $branch],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
out('installed_version.json => 1.2.0');

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Illuminate\Support\Facades\Artisan::call('route:clear');
    out('route:clear OK');
    Illuminate\Support\Facades\Artisan::call('view:clear');
    out('view:clear OK');
    Illuminate\Support\Facades\Artisan::call('config:clear');
    out('config:clear OK');
    try {
        Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        out('migrate OK: '.trim(Illuminate\Support\Facades\Artisan::output()));
    } catch (Throwable $e) {
        out('migrate WARN: '.$e->getMessage());
    }
    foreach (['system-tools.updates', 'system-tools.updates.check', 'system-tools.updates.apply'] as $name) {
        try {
            out('ROUTE '.$name.' => '.route($name, [], false));
        } catch (Throwable $e) {
            out('ROUTE MISSING '.$name);
        }
    }
} catch (Throwable $e) {
    out('BOOT ERR: '.$e->getMessage());
}

out('DONE');
@unlink(__FILE__);
