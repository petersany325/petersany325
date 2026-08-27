<?php

namespace Plugins\Catalog\src\Support;

use App\Support\JsonSettings;

class CategorySettings
{
    public const SETTINGS_KEY = 'category_manager_settings';

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return [
            'allow_delete' => true,
            'allow_duplicate' => true,
            'allow_move' => true,
            'allow_drag' => true,
            'allow_image' => true,
            'show_slug' => true,
            'show_seo' => true,
            'show_banner' => true,
            'show_product_count' => true,
            'max_depth' => 5,
            'default_display' => 'default',
        ];
    }

    /** @return array<string,mixed> */
    public static function get(): array
    {
        return JsonSettings::get(self::SETTINGS_KEY, static::defaults());
    }

    /** @param  array<string,mixed>  $data */
    public static function save(array $data): array
    {
        return JsonSettings::save(self::SETTINGS_KEY, static::defaults(), $data, [
            'allow_delete', 'allow_duplicate', 'allow_move', 'allow_drag', 'allow_image',
            'show_slug', 'show_seo', 'show_banner', 'show_product_count',
        ], [
            'max_depth' => fn ($v) => max(1, min(10, (int) $v)),
            'default_display' => fn ($v) => in_array((string) $v, ['default', 'products', 'subcategories', 'both'], true)
                ? (string) $v
                : 'default',
        ]);
    }
}
