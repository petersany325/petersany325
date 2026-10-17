<?php

namespace Plugins\ThemeBuilder\src;

/**
 * Stable bridge: ThemeBuilder / Revolution banner → storefront homepage.
 *
 * Production ThemeConfig may use different method names or omit bannerIsLive.
 * Views should prefer this helper so the banner builder always reaches `/`.
 */
class HomepageBanner
{
    /**
     * @return array{live: bool, banner: array<string, mixed>}
     */
    public static function resolve(): array
    {
        $banner = [];
        $live = false;

        try {
            self::ensureViewsRegistered();

            if (class_exists(ThemeConfig::class) && method_exists(ThemeConfig::class, 'get')) {
                $theme = ThemeConfig::get();
                $banner = self::extractBanner(is_array($theme) ? $theme : []);

                if (method_exists(ThemeConfig::class, 'normalizeBanner')) {
                    $banner = ThemeConfig::normalizeBanner($banner);
                }

                foreach (['bannerIsLive', 'isBannerLive'] as $method) {
                    if (method_exists(ThemeConfig::class, $method)) {
                        $live = (bool) ThemeConfig::$method($banner);
                        break;
                    }
                }

                if (! $live) {
                    $live = self::looksLive($banner);
                }

                // اگر ThemeConfig پروداکشن بنر خالی برگرداند، کلیدهای اختصاصی بنرساز را مستقیم بخوان.
                if (! $live) {
                    $fallback = self::readBannerFallback();
                    if (self::looksLive($fallback)) {
                        $banner = method_exists(ThemeConfig::class, 'normalizeBanner')
                            ? ThemeConfig::normalizeBanner($fallback)
                            : $fallback;
                        $live = true;
                    }
                }
            } else {
                $banner = self::readBannerFallback();
                $live = self::looksLive($banner);
            }
        } catch (\Throwable) {
            $banner = [];
            $live = false;
        }

        return [
            'live' => $live,
            'banner' => is_array($banner) ? $banner : [],
        ];
    }

    /** Register theme-builder view namespace even if plugin boot ran too late. */
    public static function ensureViewsRegistered(): void
    {
        try {
            if (class_exists(\Plugins\ThemeBuilder\Plugin::class)
                && method_exists(\Plugins\ThemeBuilder\Plugin::class, 'registerViews')) {
                \Plugins\ThemeBuilder\Plugin::registerViews();
            }
        } catch (\Throwable) {
            //
        }
    }

    public static function isLive(): bool
    {
        return self::resolve()['live'];
    }

    /** @return array<string, mixed> */
    public static function banner(): array
    {
        return self::resolve()['banner'];
    }

    /**
     * @param  array<string, mixed>  $theme
     * @return array<string, mixed>
     */
    public static function extractBanner(array $theme): array
    {
        $candidates = [
            $theme['banner'] ?? null,
            $theme['hero'] ?? null,
            $theme['slider'] ?? null,
            $theme['revolution'] ?? null,
            $theme['revolution_banner'] ?? null,
            $theme['homepage_banner'] ?? null,
            $theme['settings']['banner'] ?? null,
            $theme['homepage']['banner'] ?? null,
            $theme['data']['banner'] ?? null,
            $theme['config']['banner'] ?? null,
            $theme['theme']['banner'] ?? null,
            $theme['builder']['banner'] ?? null,
            $theme['canvas']['banner'] ?? null,
            $theme['canvas'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }
            // Nested canvas.banner inside canvas payloads.
            if (isset($candidate['banner']) && is_array($candidate['banner'])) {
                $nested = $candidate['banner'];
                if (self::bannerPayloadLooksUseful($nested)) {
                    return $nested;
                }
            }
            if (self::bannerPayloadLooksUseful($candidate)) {
                return $candidate;
            }
        }

        // Entire payload is a banner object.
        if (self::bannerPayloadLooksUseful($theme)) {
            return $theme;
        }

        return class_exists(ThemeConfig::class) && method_exists(ThemeConfig::class, 'defaultBanner')
            ? ThemeConfig::defaultBanner()
            : [];
    }

    /** @param  array<string, mixed>  $candidate */
    protected static function bannerPayloadLooksUseful(array $candidate): bool
    {
        return isset($candidate['image_url'])
            || isset($candidate['image'])
            || isset($candidate['image2_url'])
            || isset($candidate['image2'])
            || isset($candidate['src'])
            || isset($candidate['bg_image'])
            || isset($candidate['desktop_image'])
            || isset($candidate['background'])
            || isset($candidate['overlay_title'])
            || isset($candidate['overlay_text'])
            || isset($candidate['title'])
            || isset($candidate['subtitle'])
            || isset($candidate['layers'])
            || isset($candidate['slides'])
            || isset($candidate['elements'])
            || isset($candidate['items'])
            || array_key_exists('enabled', $candidate);
    }

    /** @param  array<string, mixed>  $banner */
    public static function looksLive(array $banner): bool
    {
        if ($banner === []) {
            return false;
        }

        $enabled = \App\Support\SettingsStore::toBool($banner['enabled'] ?? true, true);
        if (! $enabled) {
            return false;
        }

        $img = '';
        foreach (['image_url', 'image', 'src', 'image2_url', 'image2', 'bg_image', 'desktop_image', 'background'] as $key) {
            $val = trim((string) ($banner[$key] ?? ''));
            if ($val !== '') {
                $img = $val;
                break;
            }
        }

        if ($img === '' && class_exists(ThemeConfig::class) && method_exists(ThemeConfig::class, 'bannerUrl')) {
            $img = (string) ThemeConfig::bannerUrl($banner, 1);
            if ($img === '') {
                $img = (string) ThemeConfig::bannerUrl($banner, 2);
            }
        }

        $layers = $banner['layers'] ?? $banner['slides'] ?? $banner['elements'] ?? $banner['items'] ?? [];
        $hasLayer = false;
        if (is_array($layers)) {
            foreach ($layers as $layer) {
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
        }

        foreach (['overlay_title', 'overlay_text', 'title', 'subtitle', 'heading', 'text'] as $key) {
            if (trim((string) ($banner[$key] ?? '')) !== '') {
                return true;
            }
        }

        return $img !== '' || $hasLayer;
    }

    /** @return array<string, mixed> */
    protected static function readBannerFallback(): array
    {
        $keys = [
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
            'tb_banner',
            'builder_banner',
        ];

        foreach ($keys as $key) {
            $raw = \App\Support\SettingsStore::get($key, null);
            if ($raw === null || $raw === '' || $raw === []) {
                continue;
            }
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($raw)) {
                continue;
            }
            $banner = self::extractBanner($raw);
            if (self::looksLive($banner) || $banner !== []) {
                return $banner;
            }
        }

        return [];
    }
}
