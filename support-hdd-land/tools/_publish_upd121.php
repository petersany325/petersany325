<?php
/**
 * Seller one-shot: apply group-receipt + employee-cartable pay merge, publish release 1.2.1.
 * Upload to public_html/tmr/public/_publish_upd121.php then open:
 *   https://support.hdd-land.ir/_publish_upd121.php?t=upd121-cb9c
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(600);
@ini_set('memory_limit', '512M');

if (($_GET['t'] ?? '') !== 'upd121-cb9c') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$root = dirname(__DIR__);
$branch = 'cursor/group-receipt-emp-cartable-cb9c';
$base = "https://raw.githubusercontent.com/petersany325/petersany325/{$branch}/support-hdd-land/";
$version = '1.2.1';
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
        CURLOPT_TIMEOUT => 180,
        CURLOPT_USERAGENT => 'HDD-Land-Publish-121',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [$code, is_string($body) ? $body : '', $err];
}

out('ROOT='.$root);
out('BRANCH='.$branch);
out('VERSION='.$version);

$codeFiles = [
    'app/Http/Controllers/EmployeeController.php',
    'app/Http/Controllers/TechnicianController.php',
    'app/Http/Controllers/SettingController.php',
    'app/Models/Technician.php',
    'app/Support/NavMenu.php',
    'database/migrations/2026_09_12_060000_add_monthly_salary_to_technicians_table.php',
    'public/js/app.js',
    'public/css/app.css',
    'resources/views/layouts/app.blade.php',
    'resources/views/receptions/create.blade.php',
    'resources/views/employees/_form.blade.php',
    'resources/views/employees/index.blade.php',
    'resources/views/settings/index.blade.php',
];

$ok = 0;
$fail = 0;
foreach ($codeFiles as $rel) {
    [$code, $body, $err] = fetch($base.$rel);
    if ($code >= 400 || $body === '' || strlen($body) < 20) {
        out("FAIL {$rel} http={$code} err={$err}");
        $fail++;
        continue;
    }
    $dest = $root.'/'.$rel;
    if (! is_dir(dirname($dest))) {
        @mkdir(dirname($dest), 0755, true);
    }
    $n = file_put_contents($dest, $body);
    if ($n === false) {
        out("FAIL write {$rel}");
        $fail++;
        continue;
    }
    out("OK {$rel} {$n}");
    $ok++;
}

// Migrate + clear
try {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    out('migrate: '.trim(Illuminate\Support\Facades\Artisan::output()));
    foreach (['route:clear', 'view:clear', 'config:clear', 'cache:clear'] as $cmd) {
        Illuminate\Support\Facades\Artisan::call($cmd);
        out($cmd.' OK');
    }
} catch (Throwable $e) {
    out('BOOT/MIGRATE ERR '.$e->getMessage());
}

// Build release zip from branch
$zipUrl = "https://codeload.github.com/petersany325/petersany325/zip/refs/heads/{$branch}";
out('Downloading branch zip for release package...');
[$zcode, $zbody, $zerr] = fetch($zipUrl);
if ($zcode >= 400 || strlen($zbody) < 1000) {
    out("FAIL branch zip http={$zcode} err={$zerr} bytes=".strlen($zbody));
} else {
    $tmp = $root.'/storage/app/tmp';
    if (! is_dir($tmp)) {
        @mkdir($tmp, 0755, true);
    }
    $srcZip = $tmp.'/branch-'.$version.'.zip';
    file_put_contents($srcZip, $zbody);
    out('branch zip bytes='.strlen($zbody));

    $extract = $tmp.'/extract_'.$version;
    @mkdir($extract, 0755, true);
    $zip = new ZipArchive();
    if ($zip->open($srcZip) === true) {
        $zip->extractTo($extract);
        $zip->close();
        out('extracted');
        $src = null;
        foreach (scandir($extract) ?: [] as $d) {
            if ($d === '.' || $d === '..') {
                continue;
            }
            $cand = $extract.'/'.$d.'/support-hdd-land';
            if (is_dir($cand)) {
                $src = $cand;
                break;
            }
        }
        if (! $src) {
            out('FAIL support-hdd-land not found in branch zip');
        } else {
            $packRoot = $tmp.'/pack_'.$version;
            @mkdir($packRoot, 0755, true);
            $skip = ['.env', '.env.example', 'vendor', 'node_modules', 'storage', 'tests', '.git'];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $file) {
                $rel = substr($file->getPathname(), strlen($src) + 1);
                $top = explode('/', str_replace('\\', '/', $rel))[0];
                if (in_array($top, $skip, true)) {
                    continue;
                }
                $target = $packRoot.'/'.$rel;
                if ($file->isDir()) {
                    if (! is_dir($target)) {
                        @mkdir($target, 0755, true);
                    }
                } else {
                    if (! is_dir(dirname($target))) {
                        @mkdir(dirname($target), 0755, true);
                    }
                    @copy($file->getPathname(), $target);
                }
            }
            $releaseDir = $root.'/storage/app/releases';
            if (! is_dir($releaseDir)) {
                @mkdir($releaseDir, 0755, true);
            }
            $outZip = $releaseDir.'/'.$zipName;
            @unlink($outZip);
            $z = new ZipArchive();
            if ($z->open($outZip, ZipArchive::CREATE) === true) {
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
                $sha = hash_file('sha256', $outZip);
                $size = filesize($outZip);
                out('RELEASE_ZIP='.$outZip.' size='.$size.' sha256='.$sha);

                $manifestPath = $releaseDir.'/manifest.json';
                $prev = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : [];
                $releases = is_array($prev['releases'] ?? null) ? $prev['releases'] : [];
                $releases = array_values(array_filter($releases, fn ($r) => ($r['version'] ?? '') !== $version));
                array_unshift($releases, [
                    'version' => $version,
                    'file' => $zipName,
                    'sha256' => $sha,
                    'size' => $size,
                    'notes' => 'شماره قبض روی ردیف پذیرش گروهی + ادغام تخصص/سود/حقوق تعمیرکار در کارتابل کارمند',
                    'min_php' => '8.2',
                    'released_at' => date('c'),
                ]);
                $manifest = [
                    'channel' => 'stable',
                    'latest' => $version,
                    'product' => 'hddland-repair',
                    'releases' => $releases,
                    'updated_at' => date('c'),
                ];
                file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                out('manifest.json written latest='.$version);
            } else {
                out('FAIL create release zip');
            }
        }
    } else {
        out('FAIL open branch zip');
    }
}

out("DONE code_ok={$ok} code_fail={$fail}");
@unlink(__FILE__);
