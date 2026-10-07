<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class Installer
{
    public static function isInstalled(): bool
    {
        return File::exists(storage_path('app/installed'));
    }

    public static function markInstalled(): void
    {
        File::ensureDirectoryExists(storage_path('app'));
        File::put(storage_path('app/installed'), now()->toIso8601String());
    }

    public static function testDatabase(array $db): array
    {
        try {
            config([
                'database.default' => 'mysql',
                'database.connections.mysql.host' => $db['host'],
                'database.connections.mysql.port' => $db['port'],
                'database.connections.mysql.database' => $db['database'],
                'database.connections.mysql.username' => $db['username'],
                'database.connections.mysql.password' => $db['password'],
            ]);
            DB::purge('mysql');
            DB::connection('mysql')->getPdo();
            DB::connection('mysql')->select('select 1');

            return ['ok' => true, 'message' => 'Database connection OK.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Database error: '.$e->getMessage()];
        }
    }

    public static function install(array $db, string $appUrl): array
    {
        $test = self::testDatabase($db);
        if (! $test['ok']) {
            return $test;
        }

        $appKey = 'base64:'.base64_encode(random_bytes(32));
        $adminEmail = 'admin@ekelectronics.co.za';
        $adminPassword = Str::password(12, symbols: false);

        self::writeEnv([
            'APP_NAME' => 'EK Electronics',
            'APP_ENV' => 'production',
            'APP_KEY' => $appKey,
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim($appUrl, '/'),
            'APP_LOCALE' => 'en',
            'APP_FALLBACK_LOCALE' => 'en',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $db['host'],
            'DB_PORT' => (string) $db['port'],
            'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
        ]);

        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        config([
            'app.key' => $appKey,
            'app.url' => rtrim($appUrl, '/'),
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $db['host'],
            'database.connections.mysql.port' => $db['port'],
            'database.connections.mysql.database' => $db['database'],
            'database.connections.mysql.username' => $db['username'],
            'database.connections.mysql.password' => $db['password'],
        ]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Migration failed: '.$e->getMessage()];
        }

        User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'EK Admin',
                'password' => Hash::make($adminPassword),
                'is_admin' => true,
            ]
        );

        try {
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\EkStoreSeeder::class,
                '--force' => true,
            ]);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Seeding failed: '.$e->getMessage()];
        }

        self::markInstalled();

        return [
            'ok' => true,
            'message' => 'Installation complete.',
            'admin_email' => $adminEmail,
            'admin_password' => $adminPassword,
        ];
    }

    public static function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $example = base_path('.env.example');
        if (! File::exists($path) && File::exists($example)) {
            File::copy($example, $path);
        }
        if (! File::exists($path)) {
            File::put($path, '');
        }

        $content = File::get($path);
        foreach ($values as $key => $value) {
            $escaped = self::envValue((string) $value);
            $line = $key.'='.$escaped;
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", $line, $content);
            } else {
                $content = rtrim($content)."\n{$line}\n";
            }
        }
        File::put($path, $content);
    }

    private static function envValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }
        if (preg_match('/\s|#|"|\'/', $value)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }
}
