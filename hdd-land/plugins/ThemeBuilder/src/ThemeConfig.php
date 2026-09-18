<?php

namespace Plugins\ThemeBuilder\src;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ThemeConfig reader for Revolution / ThemeBuilder banner on the storefront.
 *
 * Production may already ship a richer ThemeConfig. This class is used when
 * that file is missing from the sparse tree, and is defensive about Setting
 * API shape and storage key names observed on live HDD Land.
 */
class ThemeConfig
{
    public const SETTING_KEYS = [
        // Live admin saves Revolution/ThemeBuilder homepage banner here first.
        'theme_homepage',
        'theme_home',
        'homepage_theme',
        'theme_builder',
        'theme_builder_config',
        'theme_config',
        'site_theme',
        'theme',
        'theme.banner',
        'theme_banner',
        'revolution_banner',
        'revolution_slider',
        'homepage_banner',
        'themebuilder',
        'themebuilder_settings',
        'theme_builder_settings',
        'tb_banner',
        'builder_banner',
        'homepage_revolution_banner',
    ];

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $bestTheme = [];
        $bestBanner = [];
        $bestScore = -1;

        foreach (self::SETTING_KEYS as $key) {
            $raw = self::settingGet($key);
            if ($raw === null || $raw === '' || $raw === []) {
                continue;
            }
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($raw) || $raw === []) {
                continue;
            }

            $candidate = HomepageBanner::extractBanner($raw);
            $score = self::bannerScore($candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestBanner = $candidate;
                $bestTheme = $raw;
            }
        }

        // Fallback: scan any settings row whose key looks theme/banner related.
        if ($bestScore < 10) {
            try {
                if (class_exists(Schema::class) && Schema::hasTable('settings')) {
                    $rows = DB::table('settings')->get(['key', 'value']);
                    foreach ($rows as $row) {
                        $key = (string) ($row->key ?? '');
                        if ($key === '' || ! preg_match('/theme|banner|revolution|homepage|slider|builder/i', $key)) {
                            continue;
                        }
                        $raw = $row->value;
                        if (is_string($raw) && $raw !== '') {
                            $decoded = json_decode($raw, true);
                            $raw = is_array($decoded) ? $decoded : [];
                        }
                        if (! is_array($raw) || $raw === []) {
                            continue;
                        }
                        $candidate = HomepageBanner::extractBanner($raw);
                        $score = self::bannerScore($candidate);
                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $bestBanner = $candidate;
                            $bestTheme = $raw;
                        }
                    }
                }
            } catch (\Throwable) {
                //
            }
        }

        $theme = $bestTheme;
        $theme['banner'] = self::normalizeBanner($bestBanner !== [] ? $bestBanner : self::defaultBanner());

        $order = $theme['layout_order'] ?? $theme['sections_order'] ?? null;
        if (! is_array($order)) {
            $order = ['banner', 'categories', 'featured'];
        }
        $order = array_values($order);
        $theme['layout_order'] = $order;

        return $theme;
    }

    /** Prefer banners that actually have image/layers/overlay copy. */
    protected static function bannerScore(array $banner): int
    {
        if ($banner === []) {
            return 0;
        }
        $score = 1;
        foreach (['image_url', 'image', 'src', 'image2_url', 'image2', 'bg_image', 'desktop_image'] as $k) {
            if (trim((string) ($banner[$k] ?? '')) !== '') {
                $score += 50;
                break;
            }
        }
        foreach (['overlay_title', 'overlay_text', 'title', 'subtitle', 'heading', 'text'] as $k) {
            if (trim((string) ($banner[$k] ?? '')) !== '') {
                $score += 20;
            }
        }
        $layers = $banner['layers'] ?? $banner['slides'] ?? $banner['elements'] ?? [];
        if (is_array($layers)) {
            $score += min(40, count($layers) * 8);
        }
        if (HomepageBanner::looksLive($banner)) {
            $score += 25;
        }

        return $score;
    }

    /** @return mixed */
    protected static function readRawSetting(): mixed
    {
        foreach (self::SETTING_KEYS as $key) {
            $candidate = self::settingGet($key);
            if ($candidate !== null && $candidate !== '' && $candidate !== []) {
                return $candidate;
            }
        }

        // Last-resort direct DB read when Setting helpers differ on production.
        try {
            if (class_exists(Schema::class) && Schema::hasTable('settings')) {
                foreach (self::SETTING_KEYS as $key) {
                    $row = DB::table('settings')->where('key', $key)->value('value');
                    if ($row !== null && $row !== '') {
                        return $row;
                    }
                }
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }

    /** @return mixed */
    protected static function settingGet(string $key): mixed
    {
        return \App\Support\SettingsStore::get($key, null);
    }

    /** @param  array<string, mixed>  $b
     *  @return array<string, mixed>
     */
    public static function normalizeBanner(array $b): array
    {
        $defaults = self::defaultBanner();
        // Accept both image_url / image and image2_url / image2 from admin payloads.
        if (! isset($b['image_url']) && isset($b['image'])) {
            $b['image_url'] = $b['image'];
        }
        if (! isset($b['image2_url']) && isset($b['image2'])) {
            $b['image2_url'] = $b['image2'];
        }
        if (! isset($b['image_url']) && isset($b['src'])) {
            $b['image_url'] = $b['src'];
        }
        if (! isset($b['slider_enabled']) && isset($b['carousel'])) {
            $b['slider_enabled'] = (bool) $b['carousel'];
        }

        $out = array_merge($defaults, $b);

        // "0"/"false" must stay disabled — bare (bool)"0" is true in PHP.
        $out['enabled'] = array_key_exists('enabled', $b)
            ? \App\Support\SettingsStore::toBool($b['enabled'], false)
            : true;
        $layout = (string) ($out['layout'] ?? 'full');
        // Map admin labels / legacy values onto storefront CSS classes.
        $layoutMap = [
            'full' => 'full',
            'wide' => 'full',
            'کامل' => 'full',
            'عریض' => 'full',
            'card' => 'card',
            'split' => 'split',
            'slider-duo' => 'slider-duo',
            'overlay-box' => 'overlay-box',
        ];
        $out['layout'] = $layoutMap[$layout] ?? (in_array($layout, ['full', 'card', 'split', 'slider-duo', 'overlay-box'], true) ? $layout : 'full');
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
        if (! \App\Support\SettingsStore::toBool($b['enabled'] ?? true, true)) {
            return false;
        }
        $hasImage = self::bannerUrl($b, 1) !== '' || self::bannerUrl($b, 2) !== '';
        $hasLayer = false;
        foreach ($b['layers'] as $layer) {
            if (! is_array($layer)) {
                continue;
            }
            $on = \App\Support\SettingsStore::toBool($layer['enabled'] ?? true, true);
            $deleted = \App\Support\SettingsStore::toBool($layer['deleted'] ?? false, false)
                || \App\Support\SettingsStore::toBool($layer['is_deleted'] ?? false, false);
            $content = trim((string) ($layer['content'] ?? $layer['text'] ?? $layer['html'] ?? $layer['title'] ?? ''));
            $layerImg = trim((string) ($layer['image'] ?? $layer['image_url'] ?? $layer['src'] ?? ''));
            if ($on && ! $deleted && ($content !== '' || $layerImg !== '')) {
                $hasLayer = true;
                break;
            }
        }

        return $hasImage || $hasLayer;
    }

    /** Alias for older ThemeBuilder call sites. */
    public static function isBannerLive(?array $banner = null): bool
    {
        return self::bannerIsLive($banner);
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
    /** @param  array<string, mixed>  $b */
    protected static function bannerLooksEmpty(array $b): bool
    {
        $img = trim((string) ($b['image_url'] ?? $b['image'] ?? $b['src'] ?? ''));
        $img2 = trim((string) ($b['image2_url'] ?? $b['image2'] ?? ''));
        $layers = $b['layers'] ?? [];

        return $img === '' && $img2 === '' && (! is_array($layers) || $layers === []);
    }

    /**
     * Admin banner presets (ThemeBuilderController / theme-studio).
     *
     * @var array<string, array<string, mixed>>
     */
    public const BANNER_PRESETS = [
        'hero_wide' => [
            'label' => 'هیرو عریض',
            'layout' => 'full',
            'align' => 'right',
            'valign' => 'center',
            'height' => 560,
            'effect' => 'kenburns',
            'overlay_opacity' => 12,
        ],
        'hero_split' => [
            'label' => 'هیرو دو ستونه',
            'layout' => 'split',
            'align' => 'right',
            'valign' => 'center',
            'height' => 520,
            'effect' => 'none',
            'overlay_opacity' => 18,
        ],
        'promo_card' => [
            'label' => 'کارت پرومو',
            'layout' => 'card',
            'align' => 'center',
            'valign' => 'center',
            'height' => 420,
            'effect' => 'none',
            'overlay_opacity' => 24,
            'radius' => 24,
        ],
        'slider_duo' => [
            'label' => 'اسلایدر دو تصویر',
            'layout' => 'slider-duo',
            'align' => 'right',
            'valign' => 'center',
            'height' => 520,
            'slider_enabled' => true,
            'effect' => 'none',
            'overlay_opacity' => 16,
        ],
        'minimal' => [
            'label' => 'مینیمال',
            'layout' => 'full',
            'align' => 'right',
            'valign' => 'center',
            'height' => 400,
            'effect' => 'none',
            'overlay_opacity' => 8,
            'text_display' => 'simple',
        ],
    ];

    /** @var array<string, string> */
    public const BANNER_PLACEMENTS = [
        'homepage' => 'صفحه اول — هیرو اصلی',
        'shop' => 'فروشگاه',
        'category' => 'صفحه دسته‌بندی',
        'product' => 'صفحه محصول',
        'hidden' => 'پنهان',
    ];

    /**
     * Homepage section types for the studio builder.
     *
     * @var array<string, array{label: string}>
     */
    public const SECTION_TYPES = [
        'banner' => ['label' => 'بنر / هیرو'],
        'categories' => ['label' => 'دسته‌بندی‌ها'],
        'featured' => ['label' => 'محصولات ویژه'],
        'latest' => ['label' => 'جدیدترین‌ها'],
        'brands' => ['label' => 'برندها'],
        'features' => ['label' => 'ویژگی‌ها'],
        'cta' => ['label' => 'فراخوان اقدام'],
        'html' => ['label' => 'بلوک HTML'],
        'online' => ['label' => 'وضعیت آنلاین'],
        'hero' => ['label' => 'هیرو متنی'],
    ];

    /** Human-readable status line under the admin banner editor. */
    public static function bannerHint(?array $banner = null): string
    {
        $b = self::normalizeBanner($banner ?? []);
        if (! self::softBool($b['enabled'] ?? true, true)) {
            return 'بنر خاموش است — در فروشگاه نمایش داده نمی‌شود.';
        }
        if (self::bannerIsLive($b)) {
            $placement = (string) ($b['placement'] ?? 'homepage');
            $label = self::BANNER_PLACEMENTS[$placement] ?? $placement;

            return 'بنر زنده است — محل نمایش: '.$label;
        }

        return 'بنر فعال است ولی تصویر/لایه ندارد — یک تصویر یا لایه متن اضافه کنید.';
    }

    /**
     * Merge an admin-submitted theme payload onto defaults / current theme.
     *
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function mergePublic(array $incoming): array
    {
        $current = [];
        try {
            $current = self::get();
        } catch (\Throwable) {
            $current = [];
        }

        $theme = array_replace_recursive([
            'banner' => self::defaultBanner(),
            'layout_order' => ['banner', 'categories', 'featured'],
            'blocks' => [],
        ], is_array($current) ? $current : [], $incoming);

        if (isset($incoming['banner']) && is_array($incoming['banner'])) {
            $theme['banner'] = self::normalizeBanner(array_replace_recursive(
                self::defaultBanner(),
                is_array($current['banner'] ?? null) ? $current['banner'] : [],
                $incoming['banner']
            ));
        } else {
            $theme['banner'] = self::normalizeBanner(is_array($theme['banner'] ?? null) ? $theme['banner'] : []);
        }

        if (isset($incoming['layout_order']) && is_array($incoming['layout_order'])) {
            $theme['layout_order'] = array_values($incoming['layout_order']);
        }
        if (isset($incoming['blocks']) && is_array($incoming['blocks'])) {
            $theme['blocks'] = $incoming['blocks'];
        }

        return $theme;
    }

    /**
     * Persist theme (writes theme_homepage first so storefront readers find it).
     *
     * @param  array<string, mixed>  $theme
     */
    public static function save(array $theme): void
    {
        if (isset($theme['banner']) && is_array($theme['banner'])) {
            $theme['banner'] = self::normalizeBanner($theme['banner']);
        }

        $json = json_encode($theme, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Theme JSON encode failed.');
        }

        $keys = ['theme_homepage', 'theme_builder', 'theme_config'];
        $written = false;
        foreach ($keys as $key) {
            try {
                if (class_exists(\App\Support\SettingsStore::class) && method_exists(\App\Support\SettingsStore::class, 'set')) {
                    \App\Support\SettingsStore::set($key, $theme);
                    $written = true;
                    break;
                }
            } catch (\Throwable) {
                //
            }
            try {
                if (class_exists(\App\Support\SettingsStore::class) && method_exists(\App\Support\SettingsStore::class, 'put')) {
                    \App\Support\SettingsStore::put($key, $theme);
                    $written = true;
                    break;
                }
            } catch (\Throwable) {
                //
            }
            try {
                if (class_exists(\App\Models\Setting::class)) {
                    if (method_exists(\App\Models\Setting::class, 'setValue')) {
                        \App\Models\Setting::setValue($key, $json);
                        $written = true;
                        break;
                    }
                    if (method_exists(\App\Models\Setting::class, 'set')) {
                        \App\Models\Setting::set($key, $json);
                        $written = true;
                        break;
                    }
                }
            } catch (\Throwable) {
                //
            }
        }

        if (! $written) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'theme_homepage'],
                ['value' => $json, 'updated_at' => now()]
            );
        }
    }

    /** Public URL for a stored theme media path. */
    public static function publicUrlForPath(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return url($path);
        }
        // Common storage prefixes used by ThemeBuilder uploads.
        foreach (['storage/', 'uploads/', 'theme/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return asset($path);
            }
        }

        return asset('storage/'.$path);
    }

    /** Soft bool helper tolerant of "0"/"false"/0. */
    protected static function softBool(mixed $value, bool $default = false): bool
    {
        foreach (['\\App\\Support\\SettingsStore', '\\App\\Support\\SettingsStore'] as $cls) {
            if (class_exists($cls) && method_exists($cls, 'toBool')) {
                return (bool) $cls::toBool($value, $default);
            }
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value !== 0;
        }
        $v = strtolower(trim((string) $value));
        if ($v === '') {
            return $default;
        }
        if (in_array($v, ['1', 'true', 'yes', 'on', 'enabled'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'no', 'off', 'disabled'], true)) {
            return false;
        }

        return $default;
    }
}
