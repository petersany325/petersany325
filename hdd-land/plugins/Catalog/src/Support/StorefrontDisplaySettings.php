<?php

namespace Plugins\Catalog\src\Support;

use App\Models\Setting;
use App\Support\JsonSettings;

class StorefrontDisplaySettings
{
    public const SETTINGS_KEY = 'catalog_storefront_display';

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return [
            // Brand / header
            'pdp_show_brand' => true,
            'pdp_brand_label' => '', // empty → shop_name
            'pdp_show_category' => true,
            'pdp_show_lead' => true,
            'pdp_show_chips' => true,
            'pdp_show_badges' => true,
            'pdp_show_stock_count' => true,
            'pdp_show_display_serial' => true,

            // Buy bar / CTAs
            'pdp_show_add_cart' => true,
            'pdp_show_buy_now' => true,
            'pdp_show_preorder' => true,
            'pdp_show_warranty_link' => true,
            'pdp_custom_serial_dropdown' => true,

            // Layout / media
            'pdp_compact_layout' => true,
            'pdp_image_fit' => 'contain', // contain|cover
            'pdp_media_width' => 280,

            // Below fold
            'pdp_show_specs' => true,
            'pdp_show_description' => true,
            'pdp_render_html_description' => true,
            'pdp_show_related' => true,

            // WebApp product page parity toggles
            'wa_show_brand' => true,
            'wa_custom_serial_dropdown' => true,
            'wa_render_html_description' => false,
            'wa_show_specs_chips' => true,
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
            'pdp_show_brand', 'pdp_show_category', 'pdp_show_lead', 'pdp_show_chips', 'pdp_show_badges',
            'pdp_show_stock_count', 'pdp_show_display_serial',
            'pdp_show_add_cart', 'pdp_show_buy_now', 'pdp_show_preorder', 'pdp_show_warranty_link',
            'pdp_custom_serial_dropdown', 'pdp_compact_layout',
            'pdp_show_specs', 'pdp_show_description', 'pdp_render_html_description', 'pdp_show_related',
            'wa_show_brand', 'wa_custom_serial_dropdown', 'wa_render_html_description', 'wa_show_specs_chips',
        ], [
            'pdp_brand_label' => fn ($v) => mb_substr(trim((string) $v), 0, 80),
            'pdp_image_fit' => fn ($v) => in_array((string) $v, ['contain', 'cover'], true) ? (string) $v : 'contain',
            'pdp_media_width' => fn ($v) => max(180, min(420, (int) $v)),
        ]);
    }

    public static function brandLabel(?array $s = null): string
    {
        $s = $s ?? static::get();
        $custom = trim((string) ($s['pdp_brand_label'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return (string) Setting::getValue('shop_name', 'سرزمین هارد');
    }
}
