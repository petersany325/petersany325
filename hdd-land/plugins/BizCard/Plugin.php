<?php

namespace Plugins\BizCard;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\View;
use Plugins\BizCard\src\CardConfig;
use Plugins\BizCard\src\ClubStore;

class Plugin extends BasePlugin
{
    public const SETTINGS_KEY = CardConfig::KEY;

    protected static bool $booted = false;

    public function id(): string
    {
        return 'biz-card';
    }

    public function name(): string
    {
        return 'کارت ویزیت دیجیتال';
    }

    public function description(): string
    {
        return 'کارت ویزیت دیجیتال، ذخیره در گوشی، ارسال لینک با پیامک و باشگاه مشتری سرزمین هارد';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function isCore(): bool
    {
        return true;
    }

    /** @return list<array{label:string,url:string,icon?:string,permission?:string}> */
    public function adminMenu(): array
    {
        return [
            [
                'label' => 'کارت ویزیت دیجیتال',
                'url' => '/admin/biz-card',
                'icon' => '📇',
                'permission' => 'site.biz_card',
            ],
        ];
    }

    public function boot(): void
    {
        if (static::$booted) {
            return;
        }
        static::loadClasses();
        ClubStore::ensureSchema();
        parent::boot();
        static::$booted = true;
    }

    /** Safe to call from MegaMenu / WebApp if the plugin manager boots late. */
    public static function ensureBooted(): void
    {
        if (static::$booted) {
            return;
        }
        static::loadClasses();
        ClubStore::ensureSchema();
        $self = new static();
        $self->bootPluginViews();
        $self->bootPluginRoutes();
        static::$booted = true;
    }

    public static function loadClasses(): void
    {
        $base = __DIR__.DIRECTORY_SEPARATOR.'src';
        foreach ([
            $base.DIRECTORY_SEPARATOR.'CardConfig.php',
            $base.DIRECTORY_SEPARATOR.'SmsSender.php',
            $base.DIRECTORY_SEPARATOR.'ClubStore.php',
            $base.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'PageController.php',
            $base.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'CardController.php',
        ] as $file) {
            if (is_file($file)) {
                try {
                    require_once $file;
                } catch (\Throwable) {
                    //
                }
            }
        }

        $views = __DIR__.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';
        if (is_dir($views) && class_exists(View::class)) {
            try {
                View::addNamespace('biz-card', $views);
                View::addNamespace('bizcard', $views);
            } catch (\Throwable) {
                //
            }
        }
    }

    /** @return array<string,mixed> */
    public static function settings(): array
    {
        return CardConfig::get();
    }

    public static function isEnabled(): bool
    {
        return CardConfig::isEnabled();
    }
}
