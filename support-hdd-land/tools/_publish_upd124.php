<?php
/**
 * Seller one-shot: publish full customer update 1.2.4 (configurable receipt prefix).
 * Upload/rename to public/_publish_upd124.php then open:
 *   https://support.hdd-land.ir/_publish_upd124.php?t=upd124-cb9c
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(600);
@ini_set('memory_limit', '512M');

if (($_GET['t'] ?? '') !== 'upd124-cb9c') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$root = dirname(__DIR__);
$branch = 'cursor/receipt-prefix-setting-cb9c';
$version = '1.2.4';
$zipName = 'hddland-'.$version.'.zip';

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
        CURLOPT_TIMEOUT => 300,
        CURLOPT_USERAGENT => 'HDD-Land-Publish-124',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [$code, is_string($body) ? $body : '', $err];
}

function rrmdir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($dir);
}

out('ROOT='.$root);
out('BRANCH='.$branch);
out('VERSION='.$version);

// Apply code files for seller immediately
$codeFiles = [
    'app/Models/Reception.php',
    'app/Http/Controllers/SettingController.php',
    'resources/views/settings/index.blade.php',
    'resources/views/receptions/create.blade.php',
    'resources/views/handoffs/index.blade.php',
];
$base = "https://raw.githubusercontent.com/petersany325/petersany325/{$branch}/support-hdd-land/";
foreach ($codeFiles as $rel) {
    [$code, $body, $err] = fetch($base.$rel);
    if ($code >= 400 || strlen($body) < 50) {
        out("FAIL pull {$rel} http={$code} err={$err}");
        continue;
    }
    $dest = $root.'/'.$rel;
    if (! is_dir(dirname($dest))) {
        @mkdir(dirname($dest), 0755, true);
    }
    file_put_contents($dest, $body);
    out("OK pull {$rel} ".strlen($body));
}

// Keep Peter's shop on T-20N explicitly
try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    App\Models\AppSetting::setValue('receipt_prefix', 'T-20N');
    out('seller receipt_prefix=T-20N');
} catch (Throwable $e) {
    out('prefix set WARN '.$e->getMessage());
}

$zipUrl = "https://codeload.github.com/petersany325/petersany325/zip/refs/heads/{$branch}";
out('Downloading branch zip...');
[$zcode, $zbody, $zerr] = fetch($zipUrl);
if ($zcode >= 400 || strlen($zbody) < 1000) {
    out("FAIL branch zip http={$zcode} err={$zerr}");
    exit(1);
}

$tmp = $root.'/storage/app/tmp';
@mkdir($tmp, 0755, true);
$srcZip = $tmp.'/branch-'.$version.'.zip';
file_put_contents($srcZip, $zbody);
out('branch zip bytes='.strlen($zbody));

$extract = $tmp.'/extract_'.$version;
rrmdir($extract);
@mkdir($extract, 0755, true);
$zip = new ZipArchive();
if ($zip->open($srcZip) !== true) {
    out('FAIL open zip');
    exit(1);
}
$zip->extractTo($extract);
$zip->close();

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
    out('FAIL app root');
    exit(1);
}

$packRoot = $tmp.'/pack_'.$version;
rrmdir($packRoot);
@mkdir($packRoot, 0755, true);
$skip = ['.env', '.env.example', 'vendor', 'node_modules', 'storage', 'tests', '.git', 'tools'];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
$copied = 0;
foreach ($iterator as $file) {
    $rel = substr($file->getPathname(), strlen($src) + 1);
    $relUnix = str_replace('\\', '/', $rel);
    $top = explode('/', $relUnix)[0] ?? '';
    if (in_array($top, $skip, true) || str_starts_with(basename($relUnix), '.env')) {
        continue;
    }
    if (preg_match('#^public/_#', $relUnix) || preg_match('#\.sqlite$#i', $relUnix)) {
        continue;
    }
    $target = $packRoot.'/'.$relUnix;
    if ($file->isDir()) {
        if (! is_dir($target)) {
            @mkdir($target, 0755, true);
        }
        continue;
    }
    if (! is_dir(dirname($target))) {
        @mkdir(dirname($target), 0755, true);
    }
    if (@copy($file->getPathname(), $target)) {
        $copied++;
    }
}
out("packed={$copied}");

$releaseDir = $root.'/storage/app/releases';
@mkdir($releaseDir, 0755, true);
$outZip = $releaseDir.'/'.$zipName;
@unlink($outZip);
$z = new ZipArchive();
$z->open($outZip, ZipArchive::CREATE);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($packRoot, FilesystemIterator::SKIP_DOTS));
foreach ($files as $f) {
    if (! $f->isFile()) {
        continue;
    }
    $rel = substr($f->getPathname(), strlen($packRoot) + 1);
    $z->addFile($f->getPathname(), str_replace('\\', '/', $rel));
}
$z->close();
$sha = hash_file('sha256', $outZip) ?: '';
$size = (int) filesize($outZip);
out("RELEASE size={$size} sha={$sha}");

$manifestPath = $releaseDir.'/manifest.json';
$prev = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : [];
if (! is_array($prev)) {
    $prev = [];
}
$releases = is_array($prev['releases'] ?? null) ? $prev['releases'] : [];
$releases = array_values(array_filter($releases, fn ($r) => (string) ($r['version'] ?? '') !== $version));
array_unshift($releases, [
    'version' => $version,
    'released_at' => date('Y-m-d'),
    'min_php' => '8.2',
    'changelog' => [
        'پیشوند شماره قبض قابل تنظیم برای هر تعمیرگاه (دیگر همه T-20N نیستند)',
        'تنظیمات → عمومی: فیلد پیشوند قبض',
        'نصب‌های جدید پیش‌فرض R؛ فروشگاه‌هایی که قبلاً T-20N داشتند حفظ می‌شود',
    ],
    'file' => $zipName,
    'sha256' => $sha,
    'size' => $size,
    'source' => 'full',
]);
file_put_contents($manifestPath, json_encode([
    'channel' => (string) ($prev['channel'] ?? 'stable'),
    'latest' => $version,
    'product' => (string) ($prev['product'] ?? 'hddland-repair'),
    'releases' => $releases,
    'updated_at' => date('c'),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
out('manifest latest='.$version);

rrmdir($extract);
rrmdir($packRoot);
@unlink($srcZip);

try {
    foreach (['route:clear', 'view:clear', 'config:clear', 'cache:clear'] as $cmd) {
        Illuminate\Support\Facades\Artisan::call($cmd);
        out($cmd.' OK');
    }
} catch (Throwable $e) {
    out('cache WARN '.$e->getMessage());
}

out('DONE');
@unlink(__FILE__);
