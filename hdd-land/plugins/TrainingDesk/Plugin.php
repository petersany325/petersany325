<?php

namespace Plugins\TrainingDesk;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\View;

class Plugin extends BasePlugin
{
    protected static bool $booted = false;

    public function id(): string
    {
        return 'training-desk';
    }

    public function name(): string
    {
        return 'آکادمی آموزش HDD Land';
    }

    public function description(): string
    {
        return 'منو و صفحات آموزش بازیابی، تعمیر هارد، SSD/NVMe و سرور با تنظیمات قابل ویرایش از پنل';
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
            $base.'/Support/TrainingCopy.php',
            $base.'/Http/Middleware/ServeTrainingPage.php',
            $base.'/Http/Controllers/PageController.php',
            $base.'/Http/Controllers/Admin/TrainingController.php',
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
            View::addNamespace('training-desk', $views);
            View::addNamespace('trainingdesk', $views);
        }
    }

    public static function registerOverride(): void
    {
        try {
            $router = app('router');
            $mw = \Plugins\TrainingDesk\src\Http\Middleware\ServeTrainingPage::class;
            $web = $router->getMiddlewareGroups()['web'] ?? [];
            if (! in_array($mw, $web, true)) {
                $router->pushMiddlewareToGroup('web', $mw);
            }
        } catch (\Throwable) {
        }
    }
}
