<?php
/**
 * One-shot live deploy for menus + remote preorder + portal cartable options.
 * Upload to public_html/tmr/_menus_deploy.php then open in browser.
 * Deletes itself after success when ?cleanup=1
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(600);
@ini_set('memory_limit', '512M');

$root = __DIR__;
$token = 'cb9c-menus-deploy-2026';
if (!isset($_GET['token']) || !hash_equals($token, (string)$_GET['token'])) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

function out(string $m): void { echo $m."\n"; @ob_flush(); @flush(); }

out('ROOT='.$root);
out('PHP='.PHP_VERSION);

$zipUrl = 'https://codeload.github.com/petersany325/petersany325/zip/refs/heads/cursor/restore-remote-portal-cb9c';
$tmpDir = $root.'/_deploy_tmp_'.bin2hex(random_bytes(4));
$zipFile = $tmpDir.'.zip';
@mkdir($tmpDir, 0755, true);

out('Downloading branch zip...');
$ctx = stream_context_create([
    'http' => ['timeout' => 300, 'header' => "User-Agent: HDD-Land-Deploy\r\n"],
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
]);
$data = @file_get_contents($zipUrl, false, $ctx);
if ($data === false || strlen($data) < 1000) {
    // fallback curl
    if (function_exists('curl_init')) {
        $ch = curl_init($zipUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_USERAGENT => 'HDD-Land-Deploy',
        ]);
        $data = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        out("curl code=$code err=$err bytes=".strlen((string)$data));
    }
}
if ($data === false || strlen((string)$data) < 1000) {
    out('FAIL download zip');
    exit(1);
}
file_put_contents($zipFile, $data);
out('zip bytes='.strlen($data));

$zip = new ZipArchive();
if ($zip->open($zipFile) !== true) {
    out('FAIL open zip');
    exit(1);
}
$zip->extractTo($tmpDir);
$zip->close();
out('extracted');

// find support-hdd-land folder inside extract
$src = null;
foreach (scandir($tmpDir) as $d) {
    if ($d === '.' || $d === '..') continue;
    $cand = $tmpDir.'/'.$d.'/support-hdd-land';
    if (is_dir($cand)) { $src = $cand; break; }
}
if (!$src) {
    out('FAIL support-hdd-land not in zip');
    exit(1);
}
out('SRC='.$src);

$skipNames = ['.env', '.env.backup', '.env.bak', 'storage', 'vendor', 'node_modules', '.git', '_menus_deploy.php', '_pr_fix.php', '_deploy_probe.php'];
$skipPrefixes = ['storage/', 'vendor/', 'bootstrap/cache/', 'node_modules/'];
// DATA SAFETY: never touch storage/ (customer photos, uploads) or .env / DB dumps.
out('DATA_SAFETY=preserve .env storage/ vendor/ database/*.sqlite');

$copied = 0; $failed = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($iterator as $item) {
    $rel = substr($item->getPathname(), strlen($src) + 1);
    $rel = str_replace('\\', '/', $rel);
    $base = basename($rel);
    if (in_array($base, $skipNames, true) && !str_contains($rel, '/')) {
        // skip root-level protected
        if (in_array($base, ['.env', 'storage', 'vendor', 'node_modules', '.git'], true)) continue;
    }
    $skip = false;
    foreach ($skipPrefixes as $p) {
        if (str_starts_with($rel, $p)) { $skip = true; break; }
    }
    if ($skip || $base === '.env') continue;
    // never overwrite live env
    if ($rel === '.env' || str_starts_with($rel, '.env.')) continue;
    // never overwrite local DB dumps
    if (str_starts_with($rel, 'database/') && preg_match('/\.(sqlite|sql|sql\.gz)$/i', $base)) continue;

    $dest = $root.'/'.$rel;
    if ($item->isDir()) {
        if (!is_dir($dest)) @mkdir($dest, 0755, true);
        continue;
    }
    $dir = dirname($dest);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (@copy($item->getPathname(), $dest)) {
        $copied++;
    } else {
        $failed++;
        out('copy fail '.$rel);
    }
}
out("copied=$copied failed=$failed");

// clear caches
foreach (['bootstrap/cache/config.php','bootstrap/cache/routes-v7.php','bootstrap/cache/services.php','bootstrap/cache/packages.php'] as $c) {
    $p = $root.'/'.$c;
    if (is_file($p)) { @unlink($p); out('cleared '.$c); }
}
// clear view cache files
$viewCache = $root.'/storage/framework/views';
if (is_dir($viewCache)) {
    foreach (glob($viewCache.'/*.php') ?: [] as $f) @unlink($f);
    out('cleared view cache');
}

// run migrate via artisan if possible
$artisan = $root.'/artisan';
if (is_file($artisan)) {
    out('running migrate...');
    $cmd = 'cd '.escapeshellarg($root).' && php artisan migrate --force 2>&1';
    $out = [];
    $code = 0;
    exec($cmd, $out, $code);
    out('migrate exit='.$code);
    foreach ($out as $line) out('  '.$line);
    exec('cd '.escapeshellarg($root).' && php artisan route:clear 2>&1 && php artisan view:clear 2>&1 && php artisan cache:clear 2>&1', $out2, $c2);
    foreach ($out2 as $line) out('  '.$line);
}

// verify NavMenu markers
$nav = @file_get_contents($root.'/app/Support/NavMenu.php') ?: '';
foreach (['اقساط','انبارگردانی','شرح کار','payment-receipts','installments','device-blacklist','labels.preview','remote-preorders','portal-invites','ورود قطعه از راه دور','ارسال لینک کارتابل'] as $w) {
    out((str_contains($nav, $w) ? 'NAV_OK ' : 'NAV_MISS ').$w);
}

// cleanup temp
@unlink($zipFile);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
@rmdir($tmpDir);

if (isset($_GET['cleanup']) && $_GET['cleanup'] === '1') {
    @unlink(__FILE__);
    out('self-deleted');
}
out('DONE');
