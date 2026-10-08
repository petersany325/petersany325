<?php

namespace App\Support;

use App\Models\Setting;

class MobileWeb
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'mobile_enabled' => true,
            'mobile_breakpoint' => 900,
            'mobile_show_topbar' => true,
            'mobile_show_search' => true,
            'mobile_hide_tagline' => true,
            'mobile_sticky_header' => true,
            'mobile_menu_label' => 'Menu',
            'mobile_bottom_nav' => true,
            'mobile_bar_home' => true,
            'mobile_bar_home_label' => 'Home',
            'mobile_bar_shop' => true,
            'mobile_bar_shop_label' => 'Shop',
            'mobile_bar_services' => true,
            'mobile_bar_services_label' => 'Services',
            'mobile_bar_agents' => true,
            'mobile_bar_agents_label' => 'Agents',
            'mobile_bar_cart' => true,
            'mobile_bar_cart_label' => 'Cart',
            'mobile_lift_whatsapp' => true,
        ];
    }

    /** @return array<string, mixed> */
    public static function formState(): array
    {
        $state = [];
        foreach (self::defaults() as $key => $default) {
            if (is_bool($default)) {
                $state[$key] = Setting::bool($key, $default);
            } elseif (is_int($default)) {
                $state[$key] = self::breakpoint(Setting::getValue($key, (string) $default));
            } else {
                $value = Setting::getValue($key, (string) $default);
                $state[$key] = ($value === null || $value === '') ? $default : $value;
            }
        }

        return $state;
    }

    public static function breakpoint(mixed $value): int
    {
        $width = (int) $value;

        return max(480, min(1200, $width > 0 ? $width : 900));
    }

    /** @return array<string, mixed> */
    public static function resolved(): array
    {
        $state = self::formState();
        $bars = array_values(array_filter([
            self::bar($state, 'home', route('home'), request()->routeIs('home')),
            self::bar($state, 'shop', route('shop'), request()->routeIs('shop')),
            self::bar($state, 'services', route('services'), request()->routeIs('services')),
            self::bar($state, 'agents', route('agents'), request()->routeIs('agents', 'agents.show')),
            self::bar($state, 'cart', route('cart'), request()->routeIs('cart')),
        ]));

        return [
            'enabled' => (bool) $state['mobile_enabled'],
            'breakpoint' => (int) $state['mobile_breakpoint'],
            'show_topbar' => (bool) $state['mobile_show_topbar'],
            'show_search' => (bool) $state['mobile_show_search'],
            'hide_tagline' => (bool) $state['mobile_hide_tagline'],
            'sticky_header' => (bool) $state['mobile_sticky_header'],
            'menu_label' => (string) $state['mobile_menu_label'],
            'bottom_nav' => (bool) $state['mobile_bottom_nav'],
            'lift_whatsapp' => (bool) $state['mobile_lift_whatsapp'],
            'bars' => $bars,
        ];
    }

    /** @param  array<string, mixed>  $mobile */
    public static function htmlClass(array $mobile): string
    {
        return trim(implode(' ', array_filter([
            ! empty($mobile['hide_tagline']) ? 'hide-tagline' : null,
            ! empty($mobile['show_search']) ? 'show-search' : null,
            ! empty($mobile['show_topbar']) ? 'show-topbar' : null,
            ! empty($mobile['sticky_header']) ? 'sticky-header' : null,
            ! empty($mobile['bottom_nav']) ? 'has-mobile-bar' : null,
            ! empty($mobile['lift_whatsapp']) ? 'lift-wa' : null,
        ])));
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{key: string, label: string, url: string, active: bool}|null
     */
    private static function bar(array $state, string $key, string $url, bool $active): ?array
    {
        if (empty($state['mobile_bar_'.$key])) {
            return null;
        }

        $label = trim((string) ($state['mobile_bar_'.$key.'_label'] ?? ''));

        return [
            'key' => $key,
            'label' => $label !== '' ? $label : ucfirst($key),
            'url' => $url,
            'active' => $active,
        ];
    }
}
