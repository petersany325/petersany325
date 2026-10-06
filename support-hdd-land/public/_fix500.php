<?php
/**
 * Emergency seller 500 fix: restore profile.shortcuts + harden shortcut dock blades.
 * Upload to public_html/tmr/public/_fix500.php then open:
 *   https://support.hdd-land.ir/_fix500.php?t=fix500-cb9c
 *
 * Modes:
 *   ?t=fix500-cb9c          — local surgical patch + optional github refresh
 *   ?t=fix500-cb9c&pull=1   — also pull key files from github branch
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
$pull = isset($_GET['pull']);

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

function backupOnce(string $path): void
{
    if (is_file($path) && ! is_file($path.'.bak-fix500')) {
        @copy($path, $path.'.bak-fix500');
    }
}

out('ROOT='.$root);
out('BRANCH='.$branch);
out('PULL='.($pull ? 'Y' : 'N'));

// 1) Ensure profile.shortcuts route exists in routes/web.php
$web = $root.'/routes/web.php';
if (is_file($web)) {
    $src = (string) file_get_contents($web);
    if (! str_contains($src, "->name('profile.shortcuts')") && ! str_contains($src, '->name("profile.shortcuts")')) {
        backupOnce($web);
        $needle = "Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');";
        $inject = $needle."\n    });\n    // میانبرهای شخصی — همه نقش‌های واردشده\n    Route::post('/profile/shortcuts', [ProfileController::class, 'updateShortcuts'])->name('profile.shortcuts');\n\n    // __FIX500_MARKER__";
        if (str_contains($src, $needle)) {
            // Avoid double-closing group: find profile middleware group ending
            $patched = preg_replace(
                '/Route::middleware\(EnsurePermission::class\.\':profile\'\)->group\(function \(\) \{\s*'
                .'Route::get\(\'\/profile\'.*?->name\(\'profile\.password\'\);\s*'
                .'\}\);/s',
                "Route::middleware(EnsurePermission::class.':profile')->group(function () {\n"
                ."        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');\n"
                ."        Route::post('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');\n"
                ."        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');\n"
                ."    });\n"
                ."    // میانبرهای شخصی — همه نقش‌های واردشده\n"
                ."    Route::post('/profile/shortcuts', [ProfileController::class, 'updateShortcuts'])->name('profile.shortcuts');",
                $src,
                1,
                $count
            );
            if ($count > 0 && is_string($patched)) {
                file_put_contents($web, $patched);
                out('INJECTED profile.shortcuts into routes/web.php');
            } else {
                // Fallback: append near ProfileController use if group pattern mismatched
                $append = "\n// FIX500: shortcuts route\n"
                    ."Route::middleware('auth')->post('/profile/shortcuts', [\\App\\Http\\Controllers\\ProfileController::class, 'updateShortcuts'])->name('profile.shortcuts');\n";
                if (! str_contains($src, 'FIX500: shortcuts route')) {
                    file_put_contents($web, $src.$append);
                    out('APPENDED profile.shortcuts fallback route');
                } else {
                    out('SKIP routes already marked');
                }
            }
        } else {
            $append = "\n// FIX500: shortcuts route\n"
                ."Route::middleware('auth')->post('/profile/shortcuts', [\\App\\Http\\Controllers\\ProfileController::class, 'updateShortcuts'])->name('profile.shortcuts');\n";
            file_put_contents($web, $src.$append);
            out('APPENDED profile.shortcuts (no profile.password match)');
        }
    } else {
        out('OK routes already has profile.shortcuts');
    }
} else {
    out('MISS routes/web.php');
}

// 2) Harden layout blades that call route('profile.shortcuts') naked
$bladeTargets = [
    $root.'/resources/views/layouts/app.blade.php',
    $root.'/resources/views/partials/staff-shortcut-dock.blade.php',
];
foreach ($bladeTargets as $blade) {
    if (! is_file($blade)) {
        out('MISS '.$blade);
        continue;
    }
    $src = (string) file_get_contents($blade);
    $orig = $src;
    // Replace bare route('profile.shortcuts') with Route::has-safe expression where still bare in attributes
    if (str_contains($src, "route('profile.shortcuts')") && ! str_contains($src, "Route::has('profile.shortcuts')")) {
        backupOnce($blade);
        if (str_contains($blade, 'staff-shortcut-dock')) {
            $src = preg_replace(
                '/\{\{-- نوار میانبر شخصی.*?@endphp/s',
                "{{-- نوار میانبر شخصی (آیکونی) — سمت راست صفحه --}}\n"
                ."@php\n"
                ."    \$dockUser = auth()->user();\n"
                ."    \$dockEnabled = \$dockUser->ui_shortcuts_enabled !== false;\n"
                ."    \$dockHasSave = \\Illuminate\\Support\\Facades\\Route::has('profile.shortcuts');\n"
                ."    \$dockItems = \$dockHasSave ? \\App\\Support\\StaffShortcutDock::forUser(\$dockUser) : [];\n"
                ."    \$dockCatalog = \$dockHasSave ? \\App\\Support\\StaffShortcutDock::catalog(\$dockUser) : [];\n"
                ."    \$dockIds = collect(\$dockItems)->pluck('id')->all();\n"
                ."    \$dockSaveUrl = \$dockHasSave ? route('profile.shortcuts') : '';\n"
                ."@endphp\n"
                ."@if(\$dockHasSave)",
                $src,
                1
            );
            if (is_string($src) && ! str_ends_with(trim($src), '@endif')) {
                $src = rtrim($src)."\n@endif\n";
            }
        } else {
            // layouts/app.blade.php: wrap dockSaveUrl / include
            $src = str_replace(
                "data-save-url=\"{{ route('profile.shortcuts') }}\"",
                "data-save-url=\"{{ \\Illuminate\\Support\\Facades\\Route::has('profile.shortcuts') ? route('profile.shortcuts') : '' }}\"",
                $src
            );
            if (str_contains($src, "@include('partials.staff-shortcut-dock')")
                && ! str_contains($src, '@if($dockSaveUrl)')) {
                $src = str_replace(
                    "@include('partials.staff-shortcut-dock')",
                    "@if(\\Illuminate\\Support\\Facades\\Route::has('profile.shortcuts'))\n        @include('partials.staff-shortcut-dock')\n    @endif",
                    $src
                );
            }
        }
        if ($src !== $orig && is_string($src)) {
            file_put_contents($blade, $src);
            out('PATCHED '.str_replace($root.'/', '', $blade));
        } else {
            out('NOCHANGE '.str_replace($root.'/', '', $blade));
        }
    } else {
        out('OK guarded '.str_replace($root.'/', '', $blade));
    }
}

// 3) Optional github pull of hardened files
if ($pull) {
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
        backupOnce($dest);
        file_put_contents($dest, $body);
        out('OK '.$rel.' '.strlen($body));
        $ok++;
    }
    out("copied={$ok} fail={$fail}");
}

// 4) Clear caches + verify route
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

// Clear compiled views that may still call missing route
$viewCache = $root.'/storage/framework/views';
if (is_dir($viewCache)) {
    foreach (glob($viewCache.'/*.php') ?: [] as $vf) {
        @unlink($vf);
    }
    out('cleared compiled views');
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

// 5) Static public/robots.txt blocks Laravel SEO route on many hosts
$staticRobots = $root.'/public/robots.txt';
if (is_file($staticRobots)) {
    if (! is_file($staticRobots.'.bak-fix500')) {
        @copy($staticRobots, $staticRobots.'.bak-fix500');
    }
    @unlink($staticRobots);
    out('removed public/robots.txt (static blocker)');
}

out('DONE');
