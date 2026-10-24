<?php

namespace Plugins\ThemeBuilder;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\View;
use Plugins\ThemeBuilder\src\PageBuilder;

/**
 * بنرساز Revolution + صفحه‌ساز هر منو و آپشن استودیو.
 */
class Plugin extends BasePlugin
{
    public function id(): string
    {
        return 'theme-builder';
    }

    public function name(): string
    {
        return 'صفحه‌ساز و بنرساز';
    }

    public function description(): string
    {
        return 'صفحه‌ساز پیشرفته هر منو، به‌همراه بنر Revolution فروشگاه';
    }

    public function version(): string
    {
        return '1.6.0';
    }

    public function isCore(): bool
    {
        return true;
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'صفحه‌ساز', 'url' => '/admin/page-builder'],
        ];
    }

    public function boot(): void
    {
        self::registerViews();
        self::loadClasses();
        parent::boot();
        try {
            if (is_file(base_path('plugins/Studio/Plugin.php'))) {
                require_once base_path('plugins/Studio/Plugin.php');
                \Plugins\Studio\Plugin::ensureBooted();
            }
            PageBuilder::ensureReady();
        } catch (\Throwable) {
        }
    }

    public static function loadClasses(): void
    {
        $files = [
            __DIR__.'/src/PageBuilder.php',
            __DIR__.'/src/StudioPageCatalog.php',
            __DIR__.'/src/Models/BuilderPage.php',
            __DIR__.'/src/Http/Controllers/Admin/PageBuilderController.php',
            __DIR__.'/src/Http/Controllers/Storefront/PageController.php',
        ];
        foreach ($files as $file) {
            if (is_file($file)) {
                try {
                    require_once $file;
                } catch (\Throwable) {
                }
            }
        }
        self::registerViews();
    }

    /** Safe to call from blades/helpers even if plugin boot order is late. */
    public static function registerViews(): void
    {
        $views = __DIR__.'/resources/views';
        if (! is_dir($views)) {
            $views = base_path('plugins/ThemeBuilder/resources/views');
        }
        if (! is_dir($views)) {
            return;
        }

        View::addNamespace('theme-builder', $views);
        View::addNamespace('themebuilder', $views);
    }
}
