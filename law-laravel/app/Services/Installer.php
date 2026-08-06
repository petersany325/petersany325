<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Setting;
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

    /**
     * @return array{ok: bool, message: string}
     */
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

            return ['ok' => true, 'message' => 'اتصال به دیتابیس برقرار شد.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'خطای دیتابیس: '.$e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, message: string, admin_email?: string, admin_password?: string}
     */
    public static function install(array $db, string $appUrl): array
    {
        $test = self::testDatabase($db);
        if (! $test['ok']) {
            return $test;
        }

        $appKey = 'base64:'.base64_encode(random_bytes(32));
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        if (filter_var($host, FILTER_VALIDATE_IP) || in_array($host, ['localhost', '127.0.0.1'], true)) {
            $adminEmail = 'admin@example.com';
        } else {
            $adminEmail = 'admin@'.$host;
        }
        $adminPassword = Str::password(12, symbols: false);

        self::writeEnv([
            'APP_NAME' => 'LawFirm',
            'APP_ENV' => 'production',
            'APP_KEY' => $appKey,
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim($appUrl, '/'),
            'APP_LOCALE' => 'fa',
            'APP_FALLBACK_LOCALE' => 'en',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $db['host'],
            'DB_PORT' => (string) $db['port'],
            'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
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
            return ['ok' => false, 'message' => 'خطا در اجرای مایگریشن: '.$e->getMessage()];
        }

        User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'مدیر سایت',
                'password' => Hash::make($adminPassword),
                'is_admin' => true,
            ]
        );

        Setting::many([
            'site_name' => 'مؤسسه حقوقی آریان',
            'site_tagline' => 'وکالت · مشاوره · دفاع',
            'phone' => '۰۲۱−۸۸۷۷۶۶۵۵',
            'address' => 'تهران، خیابان ولیعصر، برج حقوقی آریان',
            'hours' => 'شنبه تا چهارشنبه · ۹ تا ۱۸',
            'about_title' => 'دفتری که پرونده را تا نتیجه همراهی می‌کند',
            'about_text' => 'آریان ترکیبی از تجربه وکالت و مشاوره شفاف است. از همان جلسه اول، مسیر پرونده، ریسک‌ها و گزینه‌های واقعی را با شما مرور می‌کنیم.',
            'hero_lead' => 'همراهی دقیق و حرفه‌ای در پرونده‌های حقوقی، کیفری و تجاری — با زبانی ساده و دفاعی قوی.',
        ]);

        if (Service::query()->count() === 0) {
            $defaults = [
                ['sort_order' => 1, 'title' => 'حقوق خانواده', 'description' => 'طلاق، حضانت، مهریه، نفقه و توافقات خانوادگی با رویکرد حمایتی و واقع‌بینانه.'],
                ['sort_order' => 2, 'title' => 'کیفری و دفاع', 'description' => 'دفاع تخصصی در مراحل تحقیقات، دادگاه و تجدیدنظر با تمرکز بر حقوق متهم.'],
                ['sort_order' => 3, 'title' => 'قرارداد و تجارت', 'description' => 'تنظیم، بررسی و حل اختلاف قراردادهای تجاری، شرکتی و ملکی.'],
                ['sort_order' => 4, 'title' => 'ملک و ثبت', 'description' => 'دعاوی ملکی، خلع ید، الزام به تنظیم سند و پیگیری امور ثبتی.'],
            ];
            foreach ($defaults as $row) {
                Service::query()->create($row + ['is_active' => true]);
            }
        }

        self::markInstalled();

        return [
            'ok' => true,
            'message' => 'نصب با موفقیت انجام شد.',
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
            $escaped = self::envValue($value);
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
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }
}
