<?php
/**
 * Seller one-shot: publish a FULL customer update package 1.2.3.
 * Place in public_html/tmr/public/_publish_upd123.php then open:
 *   https://support.hdd-land.ir/_publish_upd123.php?t=upd123-cb9c
 *
 * Fixes invalid board-only 1.2.2 zip (missing artisan/app) that customers cannot install.
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(600);
@ini_set('memory_limit', '512M');

if (($_GET['t'] ?? '') !== 'upd123-cb9c') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$root = dirname(__DIR__);
$branch = 'cursor/release-change-board-cb9c';
$version = '1.2.3';
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
        CURLOPT_USERAGENT => 'HDD-Land-Publish-123',
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

$zipUrl = "https://codeload.github.com/petersany325/petersany325/zip/refs/heads/{$branch}";
out('Downloading branch zip...');
[$zcode, $zbody, $zerr] = fetch($zipUrl);
if ($zcode >= 400 || strlen($zbody) < 1000) {
    out("FAIL branch zip http={$zcode} err={$zerr} bytes=".strlen($zbody));
    exit(1);
}

$tmp = $root.'/storage/app/tmp';
if (! is_dir($tmp)) {
    @mkdir($tmp, 0755, true);
}
$srcZip = $tmp.'/branch-'.$version.'.zip';
file_put_contents($srcZip, $zbody);
out('branch zip bytes='.strlen($zbody));

$extract = $tmp.'/extract_'.$version;
rrmdir($extract);
@mkdir($extract, 0755, true);
$zip = new ZipArchive();
if ($zip->open($srcZip) !== true) {
    out('FAIL open branch zip');
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
    out('FAIL support-hdd-land app root not found');
    exit(1);
}
out('SRC='.$src);

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
out("packed files={$copied}");

if (! is_file($packRoot.'/artisan') || ! is_dir($packRoot.'/app')) {
    out('FAIL pack missing artisan/app');
    exit(1);
}

$releaseDir = $root.'/storage/app/releases';
if (! is_dir($releaseDir)) {
    @mkdir($releaseDir, 0755, true);
}
$outZip = $releaseDir.'/'.$zipName;
@unlink($outZip);
$z = new ZipArchive();
if ($z->open($outZip, ZipArchive::CREATE) !== true) {
    out('FAIL create release zip');
    exit(1);
}
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($packRoot, FilesystemIterator::SKIP_DOTS)
);
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
out("RELEASE_ZIP={$outZip} size={$size} sha256={$sha}");

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
        'بسته کامل نصب‌پذیر برای مشتری (جایگزین ZIP ناقص 1.2.2)',
        'منوی تخصص، سود و حقوق کارمند',
        'شماره قبض روی ردیف پذیرش گروهی',
        'معافیت CSRF برای API آپدیت مشتری',
        'تابلو تغییرات و انتشار انتخابی آپدیت',
    ],
    'file' => $zipName,
    'sha256' => $sha,
    'size' => $size,
    'source' => 'full',
]);
$manifest = [
    'channel' => (string) ($prev['channel'] ?? 'stable'),
    'latest' => $version,
    'product' => (string) ($prev['product'] ?? 'hddland-repair'),
    'releases' => $releases,
    'updated_at' => date('c'),
];
file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
out('manifest latest='.$version);

// Clean bulky tmp leftovers that were slowing the host
foreach ([
    $tmp.'/branch-1.2.0.zip',
    $tmp.'/branch-1.2.1.zip',
    $tmp.'/first_upd_branch.zip',
    $root.'/_deploy_tmp_da045be9.zip',
] as $junk) {
    if (is_file($junk)) {
        @unlink($junk);
        out('deleted '.basename($junk));
    }
}
foreach ([$tmp.'/extract_1.2.0', $tmp.'/extract_1.2.1', $tmp.'/first_upd_extract', $tmp.'/pack_1.2.0', $root.'/_deploy_tmp_da045be9'] as $dir) {
    if (is_dir($dir)) {
        rrmdir($dir);
        out('rmdir '.basename($dir));
    }
}
// keep current extract/pack briefly then remove
rrmdir($extract);
rrmdir($packRoot);
@unlink($srcZip);
out('cleaned tmp for '.$version);

try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    foreach (['route:clear', 'view:clear', 'config:clear', 'cache:clear'] as $cmd) {
        Illuminate\Support\Facades\Artisan::call($cmd);
        out($cmd.' OK');
    }
} catch (Throwable $e) {
    out('cache clear WARN '.$e->getMessage());
}

out('DONE');
@unlink(__FILE__);
