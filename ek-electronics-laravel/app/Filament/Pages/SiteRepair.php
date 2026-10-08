<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;
use UnitEnum;

class SiteRepair extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Site repair';

    protected static ?string $title = 'Site repair';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.site-repair';

    public string $log = 'Ready. Run a repair action below.';

    public function clearCache(): void
    {
        $this->run('Clear cache', function (): string {
            Artisan::call('optimize:clear');
            $out = Artisan::output();
            Cache::flush();

            return trim($out)."\nApplication cache flushed.";
        });
    }

    public function fixDatabase(): void
    {
        $this->run('Fix database', function (): string {
            Artisan::call('migrate', ['--force' => true]);
            $out = trim(Artisan::output());

            $fixed = 0;
            if (Schema::hasColumn('menus', 'parent_id')) {
                $ids = Menu::query()->pluck('id');
                $fixed += Menu::query()
                    ->whereNotNull('parent_id')
                    ->where(function ($q) use ($ids): void {
                        $q->whereNotIn('parent_id', $ids)->orWhereColumn('parent_id', 'id');
                    })
                    ->update(['parent_id' => null]);
            }

            $sessions = 0;
            if (Schema::hasTable('sessions')) {
                $sessions = DB::table('sessions')->where('last_activity', '<', now()->subDays(14)->getTimestamp())->delete();
            }

            return $out."\nOrphan menu parents fixed: {$fixed}\nExpired sessions removed: {$sessions}";
        });
    }

    public function optimizeSite(): void
    {
        $this->run('Optimize site', function (): string {
            $lines = [];
            foreach (['view:clear', 'cache:clear', 'route:clear', 'config:clear'] as $cmd) {
                Artisan::call($cmd);
                $lines[] = trim($cmd.' '.Artisan::output());
            }
            if (function_exists('opcache_reset')) {
                $lines[] = opcache_reset() ? 'OPcache reset.' : 'OPcache reset skipped.';
            }
            Cache::flush();
            $lines[] = 'Setting cache flushed.';

            return implode("\n", array_filter($lines));
        });
    }

    public function fixAll(): void
    {
        $chunks = [];
        foreach (['clearCache', 'fixDatabase', 'optimizeSite'] as $method) {
            $before = $this->log;
            $this->{$method}();
            $chunks[] = $this->log;
            $this->log = $before;
        }
        $this->log = implode("\n\n", $chunks);
        Notification::make()->title('Site repair finished')->success()->send();
    }

    private function run(string $title, callable $work): void
    {
        try {
            $body = (string) $work();
            $this->log = '['.now()->format('H:i:s')."] {$title}\n".$body;
            Notification::make()->title($title.' complete')->success()->send();
        } catch (Throwable $e) {
            $this->log = '['.now()->format('H:i:s')."] {$title} failed\n".$e->getMessage();
            Notification::make()->title($title.' failed')->body($e->getMessage())->danger()->send();
        }
    }
}
