<?php

namespace Plugins\ThemeBuilder\src;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal ThemeConfig so Revolution banner settings (theme.banner)
 * can be read on the storefront even when the full ThemeBuilder
 * package class is not shipped in this tree.
 *
 * Production may already provide a richer ThemeConfig; class_exists
 * checks elsewhere prefer that implementation when present.
 */
class ThemeConfig
{
    public const SETTING_KEYS = [
        'theme_builder',
        'theme_builder_config',
        'theme_config',
        'site_theme',
        'theme',
    ];

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $raw = null;
        foreach (self::SETTING_KEYS as $key) {
            try {
                if (class_exists(Setting::class)) {
                    $candidate = Setting::getValue($key, null);
                    if ($candidate !== null && $candidate !== '' && $candidate !== []) {
                        $raw = $candidate;
                        break;
                    }
                }
            } catch (\Throwable) {
                //
            }
        }

        $theme = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $theme = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $theme = $raw;
        }

        if (! isset($theme['banner']) || ! is_array($theme['banner'])) {
            $theme['banner'] = self::defaultBanner();
        } else {
            $theme['banner'] = self::normalizeBanner($theme['banner']);
        }

        if (! isset($theme['layout_order']) || ! is_array($theme['layout_order'])) {
            $theme['layout_order'] = ['banner', 'categories', 'featured'];
        }

        return $theme;
    }

    /** @param  array<string, mixed>  $b
     *  @return array<string, mixed>
     */
    public static function normalizeBanner(array $b): array
    {
        $defaults = self::defaultBanner();
        $out = array_merge($defaults, $b);

        $out['enabled'] = array_key_exists('enabled', $b) ? (bool) $b['enabled'] : true;
        $out['layout'] = in_array((string) ($out['layout'] ?? ''), ['full', 'card', 'split', 'slider-duo', 'overlay-box'], true)
            ? $out['layout'] : 'full';
        $out['align'] = in_array((string) ($out['align'] ?? ''), ['right', 'left', 'center'], true) ? $out['align'] : 'right';
        $out['valign'] = in_array((string) ($out['valign'] ?? ''), ['top', 'center', 'bottom', 'middle'], true)
            ? str_replace('middle', 'center', (string) $out['valign']) : 'center';
        if (($out['valign'] ?? '') === 'middle') {
            $out['valign'] = 'center';
        }
        $out['height'] = max(180, min(900, (int) ($out['height'] ?? 520)));
        $out['width'] = max(320, min(2560, (int) ($out['width'] ?? 1920)));
        $out['content_width'] = max(240, min(900, (int) ($out['content_width'] ?? 560)));
        $out['radius'] = max(0, min(40, (int) ($out['radius'] ?? 0)));
        $out['overlay_opacity'] = max(0, min(90, (int) ($out['overlay_opacity'] ?? 18)));
        $out['text_display'] = in_array((string) ($out['text_display'] ?? ''), ['stacked', 'boxed', 'glass', 'simple'], true)
            ? $out['text_display'] : 'stacked';
        // Map UI label "نمایش متن ساده" → simple/stacked
        if (($out['text_display'] ?? '') === 'simple') {
            $out['text_display'] = 'stacked';
        }

        $layers = $out['layers'] ?? [];
        if (! is_array($layers)) {
            $layers = [];
        }
        $normalizedLayers = [];
        foreach ($layers as $layer) {
            if (! is_array($layer)) {
                continue;
            }
            $normalizedLayers[] = array_merge([
                'id' => uniqid('layer_', false),
                'type' => 'text',
                'enabled' => true,
                'deleted' => false,
                'content' => '',
                'font' => 'vazirmatn',
                'size' => 16,
                'weight' => '700',
                'color' => '#1a1d23',
                'x' => 8,
                'y' => 20,
                'width' => 0,
                'url' => '',
                'animation' => 'none',
                'anim_speed' => 'normal',
                'delay' => 0,
                'letter' => 0,
                'shadow' => false,
                'bg' => '',
            ], $layer);
        }
        $out['layers'] = $normalizedLayers;

        return $out;
    }

    public static function bannerUrl(array $b, int $index = 1): string
    {
        $b = self::normalizeBanner($b);
        $key = $index === 2 ? 'image2_url' : 'image_url';
        $altKey = $index === 2 ? 'image2' : 'image';
        $src = trim((string) ($b[$key] ?? $b[$altKey] ?? ''));
        if ($src === '') {
            return '';
        }
        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://') || str_starts_with($src, '//')) {
            return $src;
        }
        if (str_starts_with($src, '/')) {
            return url($src);
        }

        return asset(ltrim($src, '/'));
    }

    /** @param  array<string, mixed>  $theme */
    public static function findBlock(array $theme, string $id): ?array
    {
        $blocks = $theme['blocks'] ?? [];
        if (! is_array($blocks)) {
            return null;
        }
        foreach ($blocks as $block) {
            if (is_array($block) && (string) ($block['id'] ?? '') === $id) {
                return $block;
            }
        }

        return null;
    }

    /** Whether the Revolution banner should be the live homepage hero. */
    public static function bannerIsLive(?array $banner = null): bool
    {
        $b = self::normalizeBanner($banner ?? (self::get()['banner'] ?? []));
        if (array_key_exists('enabled', $b) && empty($b['enabled'])) {
            return false;
        }
        $hasImage = self::bannerUrl($b, 1) !== '' || self::bannerUrl($b, 2) !== '';
        $hasLayer = false;
        foreach ($b['layers'] as $layer) {
            if (! empty($layer['enabled']) && empty($layer['deleted']) && trim((string) ($layer['content'] ?? '')) !== '') {
                $hasLayer = true;
                break;
            }
        }

        return $hasImage || $hasLayer;
    }

    /** @return array<string, mixed> */
    public static function defaultBanner(): array
    {
        return [
            'enabled' => true,
            'placement' => 'homepage',
            'placement_label' => 'صفحه اول - هیرو اصلی',
            'layout' => 'full',
            'align' => 'right',
            'valign' => 'center',
            'text_display' => 'stacked',
            'effect' => 'none',
            'effect_speed' => 'normal',
            'hover_effect' => 'none',
            'height' => 520,
            'width' => 1920,
            'content_width' => 560,
            'radius' => 0,
            'overlay_opacity' => 18,
            'dark_overlay' => false,
            'image_url' => '',
            'image2_url' => '',
            'image_alt' => '',
            'link' => '',
            'open_new' => false,
            'slider_enabled' => false,
            'slider_interval' => 6000,
            'slider_navigation' => true,
            'slider_autoplay' => true,
            'slider_pause_hover' => true,
            'layers' => [],
        ];
    }
}
