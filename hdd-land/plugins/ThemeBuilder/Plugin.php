<?php

namespace Plugins\ThemeBuilder;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\View;

/**
 * Registers ThemeBuilder / Revolution storefront views so homepage can
 * include theme-builder::storefront.* (banner + homepage layout).
 */
class Plugin extends BasePlugin
{
    public function id(): string
    {
        return 'theme-builder';
    }

    public function name(): string
    {
        return 'استودیو قالب و بنرساز';
    }

    public function description(): string
    {
        return 'بنرساز Revolution و چیدمان صفحه اول فروشگاه';
    }

    public function version(): string
    {
        return '1.4.0';
    }

    public function isCore(): bool
    {
        return true;
    }

    public function boot(): void
    {
        $views = base_path('plugins/ThemeBuilder/resources/views');
        if (is_dir($views)) {
            View::addNamespace('theme-builder', $views);
            // Alias used by some older includes / production blades.
            View::addNamespace('themebuilder', $views);
        }

        parent::boot();
    }
}
