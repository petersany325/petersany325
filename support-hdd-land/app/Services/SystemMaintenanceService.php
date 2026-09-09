<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SystemMaintenanceService
{
    /** @return array{ok:bool,message:string,details?:list<string>} */
    public function clearCaches(): array
    {
        @set_time_limit(120);
        $steps = [];
        $commands = [
            'cache:clear' => 'کش برنامه',
            'config:clear' => 'کش تنظیمات',
            'route:clear' => 'کش مسیرها',
            'view:clear' => 'کش ویوها',
            'event:clear' => 'کش رویدادها',
        ];

        foreach ($commands as $cmd => $label) {
            try {
                Artisan::call($cmd);
                $steps[] = "✓ {$label}";
            } catch (Throwable $e) {
                $steps[] = "✗ {$label}: ".$e->getMessage();
            }
        }

        $this->clearBootstrapCaches($steps);

        return [
            'ok' => true,
            'message' => 'کش سایت پاک شد.',
            'details' => $steps,
        ];
    }

    /** @return array{ok:bool,message:string,details?:list<string>} */
    public function rebuildCaches(): array
    {
        @set_time_limit(180);
        $clear = $this->clearCaches();
        $steps = $clear['details'] ?? [];
        $steps[] = '— بازسازی —';

        $commands = [
            'config:cache' => 'کش تنظیمات',
            'route:cache' => 'کش مسیرها',
            'view:cache' => 'کش ویوها',
        ];

        $ok = true;
        foreach ($commands as $cmd => $label) {
            try {
                Artisan::call($cmd);
                $steps[] = "✓ {$label} ساخته شد";
            } catch (Throwable $e) {
                $ok = false;
                $steps[] = "✗ {$label}: ".$e->getMessage();
            }
        }

        return [
            'ok' => $ok,
            'message' => $ok ? 'کش سایت بازسازی و بهینه‌سازی شد.' : 'بازسازی کش با خطا همراه بود.',
            'details' => $steps,
        ];
    }

    /** @return array{ok:bool,message:string,details?:list<string>,health?:array} */
    public function databaseHealth(): array
    {
        @set_time_limit(180);
        $details = [];
        $health = [
            'connected' => false,
            'driver' => config('database.default'),
            'database' => null,
            'tables' => 0,
            'pending_migrations' => 0,
            'problems' => [],
        ];

        try {
            DB::connection()->getPdo();
            $health['connected'] = true;
            $health['database'] = DB::connection()->getDatabaseName();
            $details[] = '✓ اتصال به دیتابیس برقرار است';
            $details[] = 'دیتابیس: '.$health['database'];
            $details[] = 'درایور: '.$health['driver'];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'اتصال به دیتابیس برقرار نشد.',
                'details' => ['✗ '.$e->getMessage()],
                'health' => $health,
            ];
        }

        $tables = $this->listTables();
        $health['tables'] = count($tables);
        $details[] = 'تعداد جداول: '.$health['tables'];

        if ($this->isMysql()) {
            foreach ($tables as $table) {
                try {
                    $rows = DB::select('CHECK TABLE `'.$this->quoteIdent($table).'`');
                    foreach ($rows as $row) {
                        $msgType = strtolower((string) ($row->Msg_type ?? ''));
                        $msgText = (string) ($row->Msg_text ?? '');
                        $lower = strtolower($msgText);
                        if ($msgType === 'error' || str_contains($lower, 'corrupt') || str_contains($lower, 'crashed')) {
                            $health['problems'][] = $table.': '.$msgText;
                            $details[] = "✗ جدول {$table}: {$msgText}";
                        }
                    }
                } catch (Throwable $e) {
                    $health['problems'][] = $table.': '.$e->getMessage();
                    $details[] = "✗ بررسی {$table}: ".$e->getMessage();
                }
            }
            if ($health['problems'] === []) {
                $details[] = '✓ همه جداول سالم هستند';
            }
        } else {
            $details[] = 'بررسی عمیق جدول فقط برای MySQL/MariaDB فعال است.';
        }

        try {
            $pending = $this->pendingMigrationNames();
            $health['pending_migrations'] = count($pending);
            if ($pending) {
                $details[] = 'مایگریشن معوق: '.count($pending);
                foreach (array_slice($pending, 0, 8) as $name) {
                    $details[] = '· '.$name;
                }
            } else {
                $details[] = '✓ مایگریشن معوقی نیست';
            }
        } catch (Throwable $e) {
            $details[] = 'بررسی مایگریشن: '.$e->getMessage();
        }

        $ok = $health['connected'] && $health['problems'] === [];

        return [
            'ok' => $ok,
            'message' => $ok ? 'وضعیت دیتابیس سالم است.' : 'مشکلاتی در دیتابیس یافت شد.',
            'details' => $details,
            'health' => $health,
        ];
    }

    /** @return array{ok:bool,message:string,details?:list<string>} */
    public function repairDatabase(): array
    {
        @set_time_limit(300);
        if (! $this->isMysql()) {
            return [
                'ok' => false,
                'message' => 'تعمیر جدول فقط برای MySQL/MariaDB پشتیبانی می‌شود.',
                'details' => [],
            ];
        }

        $details = [];
        $fixed = 0;
        $skipped = 0;
        $failed = 0;
        $engines = $this->tableEngines();

        foreach ($this->listTables() as $table) {
            $engine = strtolower((string) ($engines[$table] ?? 'innodb'));
            try {
                if (in_array($engine, ['myisam', 'aria', 'archive'], true)) {
                    $rows = DB::select('REPAIR TABLE `'.$this->quoteIdent($table).'`');
                    $text = collect($rows)->map(fn ($r) => (string) ($r->Msg_text ?? ''))->implode(' / ');
                    $details[] = "تعمیر {$table} ({$engine}): ".$text;
                    $fixed++;
                } else {
                    // InnoDB: REPAIR is unsupported — ANALYZE rebuilds stats safely
                    DB::statement('ANALYZE TABLE `'.$this->quoteIdent($table).'`');
                    $details[] = "✓ بررسی/ANALYZE {$table} (InnoDB — REPAIR لازم نیست)";
                    $skipped++;
                }
            } catch (Throwable $e) {
                $failed++;
                $details[] = "✗ {$table}: ".$e->getMessage();
            }
        }

        return [
            'ok' => $failed === 0,
            'message' => "تعمیر/تحلیل انجام شد (تعمیر: {$fixed}، InnoDB: {$skipped}".($failed ? "، خطا: {$failed}" : '').').',
            'details' => $this->trimDetails($details),
        ];
    }

    /** @return array{ok:bool,message:string,details?:list<string>} */
    public function optimizeDatabase(): array
    {
        @set_time_limit(300);
        if (! $this->isMysql()) {
            return [
                'ok' => false,
                'message' => 'بهینه‌سازی جدول فقط برای MySQL/MariaDB پشتیبانی می‌شود.',
                'details' => [],
            ];
        }

        $details = [];
        $okCount = 0;
        $fail = 0;
        foreach ($this->listTables() as $table) {
            try {
                DB::statement('OPTIMIZE TABLE `'.$this->quoteIdent($table).'`');
                $details[] = "✓ بهینه‌سازی {$table}";
                $okCount++;
            } catch (Throwable $e) {
                $fail++;
                $details[] = "✗ {$table}: ".$e->getMessage();
            }
        }

        return [
            'ok' => $fail === 0,
            'message' => "{$okCount} جدول بهینه‌سازی شد".($fail ? " ({$fail} خطا)" : '').'.',
            'details' => $this->trimDetails($details),
        ];
    }

    /** @return array{ok:bool,message:string,details?:list<string>} */
    public function rebuildDamagedDatabase(): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $details = [];
        $criticalFail = false;

        try {
            $health = $this->databaseHealth();
            $details = array_merge($details, ['— بررسی اولیه —'], $this->trimDetails($health['details'] ?? [], 40));
            if (! ($health['health']['connected'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => 'اتصال دیتابیس برقرار نیست؛ بازسازی متوقف شد.',
                    'details' => $details,
                ];
            }

            if ($this->isMysql()) {
                $repair = $this->repairDatabase();
                $details = array_merge($details, ['— تعمیر / ANALYZE —'], $repair['details'] ?? []);
                if (! ($repair['ok'] ?? false)) {
                    $criticalFail = true;
                }

                $optimize = $this->optimizeDatabase();
                $details = array_merge($details, ['— بهینه‌سازی —'], $optimize['details'] ?? []);
            }

            $migrate = $this->runPendingMigrations();
            $details = array_merge($details, ['— مایگریشن —'], $migrate['details'] ?? []);
            if (! ($migrate['ok'] ?? false)) {
                $criticalFail = true;
            }

            $cache = $this->clearCaches();
            $details = array_merge($details, ['— پاک‌سازی کش —'], $cache['details'] ?? []);

            $after = $this->databaseHealth();
            $details = array_merge($details, ['— بررسی نهایی —'], $this->trimDetails($after['details'] ?? [], 40));
            $finalOk = ($after['ok'] ?? false) && ! $criticalFail;

            return [
                'ok' => $finalOk,
                'message' => $finalOk
                    ? 'بازسازی دیتابیس با موفقیت انجام شد.'
                    : 'بازسازی اجرا شد؛ برخی مراحل هشدار/خطا داشتند — جزئیات را ببینید.',
                'details' => $this->trimDetails($details, 120),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'بازسازی متوقف شد: '.$e->getMessage(),
                'details' => array_merge($details, ['✗ '.$e->getMessage()]),
            ];
        }
    }

    /** @return array{ok:bool,message:string,details?:list<string>} */
    public function runPendingMigrations(): array
    {
        try {
            $pending = $this->pendingMigrationNames();
            if ($pending === []) {
                return [
                    'ok' => true,
                    'message' => 'مایگریشن معوقی وجود ندارد.',
                    'details' => ['✓ همه مایگریشن‌ها اعمال شده‌اند'],
                ];
            }

            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());

            return [
                'ok' => true,
                'message' => count($pending).' مایگریشن اجرا شد.',
                'details' => array_merge(
                    array_map(fn ($n) => '· '.$n, $pending),
                    $output !== '' ? [mb_substr($output, 0, 800)] : []
                ),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'اجرای مایگریشن ناموفق بود.',
                'details' => ['✗ '.$e->getMessage()],
            ];
        }
    }

    /** @return list<string> */
    public function statusSnapshot(): array
    {
        $lines = [];
        try {
            DB::connection()->getPdo();
            $lines[] = 'اتصال دیتابیس: برقرار';
            $lines[] = 'نام دیتابیس: '.DB::connection()->getDatabaseName();
            $lines[] = 'جداول: '.count($this->listTables());
            $pending = $this->pendingMigrationNames();
            $lines[] = 'مایگریشن معوق: '.count($pending);
        } catch (Throwable $e) {
            $lines[] = 'اتصال دیتابیس: قطع — '.$e->getMessage();
        }

        $cachePath = storage_path('framework/cache/data');
        $viewPath = storage_path('framework/views');
        $lines[] = 'فایل‌های کش ویو: '.(is_dir($viewPath) ? count(File::files($viewPath)) : 0);
        $lines[] = 'پوشه کش برنامه: '.(is_dir($cachePath) ? 'موجود' : 'نیست');
        $lines[] = 'محیط: '.app()->environment();
        $lines[] = 'نسخه Laravel: '.app()->version();

        return $lines;
    }

    private function isMysql(): bool
    {
        $driver = DB::connection()->getDriverName();

        return in_array($driver, ['mysql', 'mariadb'], true);
    }

    /** @return array<string,string> table => engine */
    private function tableEngines(): array
    {
        try {
            $rows = DB::select('SHOW TABLE STATUS');
            $map = [];
            foreach ($rows as $row) {
                $name = (string) ($row->Name ?? '');
                if ($name !== '') {
                    $map[$name] = (string) ($row->Engine ?? 'InnoDB');
                }
            }

            return $map;
        } catch (Throwable) {
            return [];
        }
    }

    /** @return list<string> */
    private function listTables(): array
    {
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return collect(DB::select('SHOW TABLES'))
                ->map(fn ($row) => array_values((array) $row)[0] ?? null)
                ->filter()
                ->values()
                ->all();
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('name')
                ->all();
        }

        return [];
    }

    /** @return list<string> */
    private function pendingMigrationNames(): array
    {
        $ran = [];
        try {
            if (DB::getSchemaBuilder()->hasTable('migrations')) {
                $ran = DB::table('migrations')->pluck('migration')->all();
            }
        } catch (Throwable) {
            $ran = [];
        }

        $files = collect(File::files(database_path('migrations')))
            ->map(fn ($f) => pathinfo($f->getFilename(), PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();

        return array_values(array_diff($files, $ran));
    }

    private function quoteIdent(string $name): string
    {
        return str_replace('`', '``', $name);
    }

    /** @param list<string> $steps */
    private function clearBootstrapCaches(array &$steps): void
    {
        foreach ([
            base_path('bootstrap/cache/config.php'),
            base_path('bootstrap/cache/routes-v7.php'),
            base_path('bootstrap/cache/routes.php'),
            base_path('bootstrap/cache/events.php'),
        ] as $file) {
            if (is_file($file)) {
                @unlink($file);
                $steps[] = '✓ حذف '.basename($file);
            }
        }
    }

    /** @param list<string> $details @return list<string> */
    private function trimDetails(array $details, int $max = 80): array
    {
        if (count($details) <= $max) {
            return $details;
        }

        return array_merge(
            array_slice($details, 0, $max - 1),
            ['… و '.(count($details) - ($max - 1)).' مورد دیگر']
        );
    }
}
