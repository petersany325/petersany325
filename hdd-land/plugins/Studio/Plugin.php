<?php

namespace Plugins\Studio;

use App\Http\Middleware\SiteHostSplit;
use App\Support\BasePlugin;
use Illuminate\Support\Facades\View;

class Plugin extends BasePlugin
{
    protected static bool $booted = false;

    public function id(): string
    {
        return 'studio';
    }

    public function name(): string
    {
        return 'استودیو سایت و نرم‌افزار';
    }

    public function description(): string
    {
        return 'صفحه و منوی سبک دامنه اصلی: طراحی سایت، حسابداری، AI و نرم‌افزار قبض';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function isCore(): bool
    {
        return true;
    }

    public function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        static::loadClasses();
        static::registerViews();
        parent::boot();
        try {
            app('router')->pushMiddlewareToGroup('web', SiteHostSplit::class);
        } catch (\Throwable) {
        }
    }

    public static function ensureBooted(): void
    {
        if (self::$booted) {
            static::registerViews();

            return;
        }
        (new self())->boot();
    }

    public static function loadClasses(): void
    {
        $files = [
            base_path('app/Support/SiteHost.php'),
            base_path('app/Http/Middleware/SiteHostSplit.php'),
            __DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'StudioController.php',
        ];
        foreach ($files as $file) {
            if (is_file($file)) {
                try {
                    require_once $file;
                } catch (\Throwable) {
                }
            }
        }
    }

    public static function registerViews(): void
    {
        $views = __DIR__.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';
        if (! is_dir($views)) {
            $views = base_path('plugins/Studio/resources/views');
        }
        if (is_dir($views)) {
            View::addNamespace('studio', $views);
        }
    }
}
