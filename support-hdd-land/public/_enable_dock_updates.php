<?php
/**
 * One-shot: add ui_shortcuts columns + clear caches for dock + updates.
 * Delete this file after HTTP 200 OK.
 */
declare(strict_types=1);

$token = $_GET['t'] ?? '';
if (! hash_equals('akesht-dock-upd-20261017', (string) $token)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "forbidden\n";
    exit;
}

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');
$out = [];

try {
    $schema = Illuminate\Support\Facades\Schema::class;
    if (! Illuminate\Support\Facades\Schema::hasColumn('users', 'ui_shortcuts')) {
        Illuminate\Support\Facades\Schema::table('users', function ($table) {
            $table->json('ui_shortcuts')->nullable();
        });
        $out[] = 'added ui_shortcuts';
    } else {
        $out[] = 'ui_shortcuts exists';
    }
    if (! Illuminate\Support\Facades\Schema::hasColumn('users', 'ui_shortcuts_enabled')) {
        Illuminate\Support\Facades\Schema::table('users', function ($table) {
            $table->boolean('ui_shortcuts_enabled')->default(true);
        });
        $out[] = 'added ui_shortcuts_enabled';
    } else {
        $out[] = 'ui_shortcuts_enabled exists';
    }

    // Enable dock for all users by default
    try {
        Illuminate\Support\Facades\DB::table('users')->whereNull('ui_shortcuts_enabled')->update(['ui_shortcuts_enabled' => 1]);
        $out[] = 'enabled dock defaults';
    } catch (Throwable $e) {
        $out[] = 'default enable skip: '.$e->getMessage();
    }

    foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $cmd) {
        try {
            $kernel->call($cmd);
            $out[] = $cmd.' ok';
        } catch (Throwable $e) {
            $out[] = $cmd.' fail: '.$e->getMessage();
        }
    }

    // Record migration rows so artisan migrate later is clean
    $migrations = [
        '2026_09_24_080000_add_ui_shortcuts_to_users_table',
        '2026_09_24_083000_add_ui_shortcuts_enabled_to_users_table',
    ];
    if (Illuminate\Support\Facades\Schema::hasTable('migrations')) {
        foreach ($migrations as $m) {
            $exists = Illuminate\Support\Facades\DB::table('migrations')->where('migration', $m)->exists();
            if (! $exists) {
                $batch = (int) Illuminate\Support\Facades\DB::table('migrations')->max('batch') + 1;
                Illuminate\Support\Facades\DB::table('migrations')->insert(['migration' => $m, 'batch' => $batch]);
                $out[] = 'migration logged '.$m;
            }
        }
    }

    $out[] = 'DONE';
    echo implode("\n", $out)."\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n";
}
