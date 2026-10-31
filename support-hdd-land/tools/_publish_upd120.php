<?php
/**
 * Seller one-shot: deploy update API routes + publish release 1.2.0 from GitHub-built payload.
 * Place in public_html/tmr/public/_publish_upd120.php then open ?t=upd120-cb9c
 *
 * Flow:
 * 1) Pull AppUpdate* controllers/service + routes/web.php from GitHub branch
 * 2) Download release zip from ARTIFACT_URL (or embedded GitHub release path)
 * 3) Write storage/app/releases/hddland-1.2.0.zip + manifest.json
 * 4) Clear caches
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(600);
@ini_set('memory_limit', '512M');

if (($_GET['t'] ?? '') !== 'upd120-cb9c') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$root = dirname(__DIR__);
$branch = 'cursor/jalali-dates-settings-cb9c';
$base = "https://raw.githubusercontent.com/petersany325/petersany325/{$branch}/support-hdd-land/";
$version = '1.2.0';
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
        CURLOPT_USERAGENT => 'HDD-Land-Publish-Upd',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [$code, is_string($body) ? $body : '', $err];
}

out('ROOT='.$root);
out('BRANCH='.$branch);

$codeFiles = [
    'app/Http/Controllers/AppUpdateController.php',
    'app/Http/Controllers/AppUpdateApiController.php',
    'app/Http/Controllers/AppReleaseAdminController.php',
    'app/Services/AppUpdateService.php',
    'config/updates.php',
    'routes/web.php',
    'resources/views/system-tools/updates.blade.php',
    'resources/views/partials/update-banner.blade.php',
    'app/Support/NavMenu.php',
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
    $dir = dirname($dest);
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
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

// Build zip from GitHub branch codeload (full tree), then strip to support-hdd-land package
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
            out('FAIL support-hdd-land not in branch zip');
        } else {
            // Pack release zip with exclusions
            $releaseDir = $root.'/storage/app/releases';
            if (! is_dir($releaseDir)) {
                @mkdir($releaseDir, 0755, true);
            }
            $releaseZip = $releaseDir.'/'.$zipName;
            @unlink($releaseZip);

            $packRoot = $tmp.'/pack_'.$version;
            @mkdir($packRoot.'/support-hdd-land', 0755, true);

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            $skipNames = ['.env', 'vendor', 'node_modules', '.git'];
            foreach ($iterator as $file) {
                $path = $file->getPathname();
                $rel = substr($path, strlen($src) + 1);
                $parts = explode('/', str_replace('\\', '/', $rel));
                if ($parts && in_array($parts[0], $skipNames, true)) {
                    continue;
                }
                if (str_starts_with($rel, '.env')) {
                    continue;
                }
                if (preg_match('#(^|/)storage/(logs|framework)/(.*)$#', $rel)) {
                    continue;
                }
                if (preg_match('#\.sqlite$#i', $rel)) {
                    continue;
                }
                $target = $packRoot.'/support-hdd-land/'.$rel;
                if ($file->isDir()) {
                    if (! is_dir($target)) {
                        @mkdir($target, 0755, true);
                    }
                } else {
                    $td = dirname($target);
                    if (! is_dir($td)) {
                        @mkdir($td, 0755, true);
                    }
                    @copy($path, $target);
                }
            }

            $pack = new ZipArchive();
            if ($pack->open($releaseZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $packIt = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($packRoot, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($packIt as $f) {
                    if ($f->isDir()) {
                        continue;
                    }
                    $local = substr($f->getPathname(), strlen($packRoot) + 1);
                    $pack->addFile($f->getPathname(), $local);
                }
                $pack->close();
                $sha = hash_file('sha256', $releaseZip);
                $size = filesize($releaseZip);
                out("RELEASE_ZIP={$releaseZip} size={$size} sha256={$sha}");

                $manifest = [
                    'channel' => 'stable',
                    'latest' => $version,
                    'updated_at' => date('c'),
                    'releases' => [[
                        'version' => $version,
                        'released_at' => date('Y-m-d'),
                        'min_php' => '8.2',
                        'changelog' => [
                            'منوی آپدیت نرم‌افزار در ابزارهای سیستم',
                            'API بررسی/دانلود آپدیت برای مشتریان لایسنس‌دار',
                            'تقویم شمسی سراسری + تنظیمات ادمین',
                            'رفع‌های پذیرش، پورتال و کارتابل',
                        ],
                        'file' => $zipName,
                        'sha256' => $sha,
                    ]],
                ];
                file_put_contents(
                    $releaseDir.'/manifest.json',
                    json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                );
                out('manifest.json written latest='.$version);
            } else {
                out('FAIL create release zip');
            }
        }
    } else {
        out('FAIL open branch zip');
    }
}

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

    // Smoke: routes registered?
    $routes = app('router')->getRoutes();
    $need = ['license.updates.latest', 'license.updates.download', 'system-tools.updates'];
    foreach ($need as $name) {
        try {
            $url = route($name, [], false);
            out("ROUTE {$name} => {$url}");
        } catch (Throwable $e) {
            out("ROUTE MISSING {$name}");
        }
    }
} catch (Throwable $e) {
    out('BOOT ERR: '.$e->getMessage());
}

out("DONE code_ok={$ok} code_fail={$fail}");
@unlink(__FILE__);
