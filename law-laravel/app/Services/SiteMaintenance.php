<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

class SiteMaintenance
{
    /**
     * @return array{ok: bool, lines: list<string>}
     */
    public function refreshCache(): array
    {
        $lines = [];
        $ok = true;

        $commands = [
            'optimize:clear',
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
            'event:clear',
        ];

        foreach ($commands as $command) {
            try {
                Artisan::call($command);
                $out = trim(Artisan::output());
                $lines[] = $command.($out !== '' ? ' → '.$out : ' → OK');
            } catch (Throwable $e) {
                $ok = false;
                $lines[] = $command.' → خطا: '.$e->getMessage();
            }
        }

        try {
            \Illuminate\Support\Facades\Cache::forget('settings.all');
            $lines[] = 'settings.cache → پاک شد';
        } catch (Throwable $e) {
            $ok = false;
            $lines[] = 'settings.cache → '.$e->getMessage();
        }

        $version = (string) max(1, Setting::int('asset_version', 12) + 1);
        Setting::put('asset_version', $version);
        $lines[] = 'asset_version → '.$version.' (کش مرورگر/وب‌اپ تازه می‌شود)';

        return ['ok' => $ok, 'lines' => $lines];
    }

    /**
     * @return array{ok: bool, lines: list<string>}
     */
    public function repairSite(): array
    {
        $lines = [];
        $ok = true;

        // Ensure critical directories exist and are writable
        foreach ([
            storage_path('app'),
            storage_path('app/public'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ] as $dir) {
            try {
                if (! is_dir($dir)) {
                    File::makeDirectory($dir, 0755, true);
                    $lines[] = 'mkdir '.$this->relative($dir).' → ساخته شد';
                }
                if (! is_writable($dir)) {
                    @chmod($dir, 0755);
                    $lines[] = 'chmod '.$this->relative($dir).' → 0755';
                } else {
                    $lines[] = $this->relative($dir).' → قابل نوشتن';
                }
            } catch (Throwable $e) {
                $ok = false;
                $lines[] = $this->relative($dir).' → '.$e->getMessage();
            }
        }

        try {
            Artisan::call('storage:link');
            $lines[] = 'storage:link → '.trim(Artisan::output() ?: 'OK');
        } catch (Throwable $e) {
            $lines[] = 'storage:link → '.$e->getMessage();
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $lines[] = 'migrate → '.trim(Artisan::output() ?: 'OK');
        } catch (Throwable $e) {
            $ok = false;
            $lines[] = 'migrate → '.$e->getMessage();
        }

        $cacheResult = $this->refreshCache();
        $lines = array_merge($lines, $cacheResult['lines']);
        $ok = $ok && $cacheResult['ok'];

        return ['ok' => $ok, 'lines' => $lines];
    }

    protected function relative(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
