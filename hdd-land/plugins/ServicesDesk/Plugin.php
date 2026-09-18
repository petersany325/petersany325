<?php

namespace Plugins\ServicesDesk;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\View;

class Plugin extends BasePlugin
{
    protected static bool $booted = false;

    public function id(): string
    {
        return 'services-desk';
    }

    public function name(): string
    {
        return 'صفحه خدمات سازمانی';
    }

    public function description(): string
    {
        return 'صفحه خدمات سازمانی HDD Land با متن و لینک قابل ویرایش از پنل';
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
        static::registerOverride();
    }

    public static function ensureBooted(): void
    {
        try {
            (new static())->boot();
        } catch (\Throwable) {
        }
    }

    public static function loadClasses(): void
    {
        $base = __DIR__.DIRECTORY_SEPARATOR.'src';
        foreach ([
            $base.'/Support/ServicesCopy.php',
            $base.'/Http/Middleware/ServeServicesPage.php',
            $base.'/Http/Controllers/PageController.php',
            $base.'/Http/Controllers/Admin/ServicesController.php',
        ] as $file) {
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
        if (is_dir($views)) {
            View::addNamespace('services-desk', $views);
            View::addNamespace('servicesdesk', $views);
        }
    }

    public static function registerOverride(): void
    {
        try {
            $router = app('router');
            $mw = \Plugins\ServicesDesk\src\Http\Middleware\ServeServicesPage::class;
            $web = $router->getMiddlewareGroups()['web'] ?? [];
            if (! in_array($mw, $web, true)) {
                $router->pushMiddlewareToGroup('web', $mw);
            }
        } catch (\Throwable) {
        }
    }
}
