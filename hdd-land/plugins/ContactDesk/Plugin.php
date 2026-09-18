<?php

namespace Plugins\ContactDesk;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\View;

class Plugin extends BasePlugin
{
    protected static bool $booted = false;

    public function id(): string
    {
        return 'contact-desk';
    }

    public function name(): string
    {
        return 'صفحه تماس با واحد فروش';
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
            $base.'/Support/ContactCopy.php',
            $base.'/Http/Middleware/ServeContactPage.php',
            $base.'/Http/Controllers/PageController.php',
            $base.'/Http/Controllers/Admin/ContactController.php',
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
            View::addNamespace('contact-desk', $views);
        }
    }

    public static function registerOverride(): void
    {
        try {
            $router = app('router');
            $mw = \Plugins\ContactDesk\src\Http\Middleware\ServeContactPage::class;
            $web = $router->getMiddlewareGroups()['web'] ?? [];
            if (! in_array($mw, $web, true)) {
                $router->pushMiddlewareToGroup('web', $mw);
            }
        } catch (\Throwable) {
        }
    }
}
