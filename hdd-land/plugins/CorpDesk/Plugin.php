<?php

namespace Plugins\CorpDesk;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\View;

class Plugin extends BasePlugin
{
    protected static bool $booted = false;

    public function id(): string
    {
        return 'corp-desk';
    }

    public function name(): string
    {
        return 'صفحات سازمانی و CCTV';
    }

    public function description(): string
    {
        return 'صفحه‌ساز تأمین هارد سازمانی و پروژه‌های نظارتی با متن و تصویر قابل ویرایش';
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
            $base.'/Support/CorpCopy.php',
            $base.'/Http/Controllers/PageController.php',
            $base.'/Http/Controllers/Admin/CorpController.php',
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
            View::addNamespace('corp-desk', $views);
            View::addNamespace('corpdesk', $views);
        }
    }

    /** @return list<array{label:string,href:string,icon?:string,group?:string}> */
    public function adminMenu(): array
    {
        return [
            ['label' => 'صفحه تأمین سازمانی', 'href' => '/admin/corp-pages/enterprise', 'icon' => '▣', 'group' => 'theme'],
            ['label' => 'صفحه پروژه‌های CCTV', 'href' => '/admin/corp-pages/cctv', 'icon' => '◎', 'group' => 'theme'],
        ];
    }
}
