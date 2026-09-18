<?php

namespace Plugins\AboutDesk;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\View;

class Plugin extends BasePlugin
{
    protected static bool $booted = false;

    public function id(): string
    {
        return 'about-desk';
    }

    public function name(): string
    {
        return 'صفحه درباره ما';
    }

    public function description(): string
    {
        return 'صفحه رسمی درباره HDD Land با عنوان سه‌بعدی و متن تأییدشده';
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
            $base.'/Support/AboutCopy.php',
            $base.'/Http/Middleware/ServeAboutPage.php',
            $base.'/Http/Controllers/PageController.php',
            $base.'/Http/Controllers/Admin/AboutController.php',
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
            View::addNamespace('about-desk', $views);
            View::addNamespace('aboutdesk', $views);
        }
    }

    /** Take over /about even if an older route already exists. */
    public static function registerOverride(): void
    {
        try {
            $router = app('router');
            $mw = \Plugins\AboutDesk\src\Http\Middleware\ServeAboutPage::class;
            $web = $router->getMiddlewareGroups()['web'] ?? [];
            if (! in_array($mw, $web, true)) {
                $router->pushMiddlewareToGroup('web', $mw);
            }
        } catch (\Throwable) {
        }
    }
}
