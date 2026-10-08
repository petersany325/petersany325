<?php

namespace App\Support;

use App\Models\Setting;

class HomepageContent
{
    /** @return array<string, string> */
    public static function defaults(): array
    {
        return [
            'home_show_hero' => '1',
            'hero_kicker' => 'Innovation. Integrity. Impact.',
            'hero_headline' => 'Hard drives & components, refurbished with integrity.',
            'hero_sub' => 'Enterprise storage, memory, boards, and professional data recovery from Midrand.',
            'hero_image' => 'assets/img/hero.jpg',
            'hero_cta_label' => 'Shop catalogue',
            'hero_cta_url' => '/shop',
            'hero_cta2_label' => 'Book data recovery',
            'hero_cta2_url' => '/services',
            'hero_show_whatsapp' => '1',
            'home_show_stats' => '1',
            'stat_1_value' => '885+',
            'stat_1_label' => 'SKU in the live product feed',
            'stat_2_value' => 'Grade A / A+',
            'stat_2_label' => 'Tested, certified refurbished drives',
            'stat_3_value' => '24–48h',
            'stat_3_label' => 'Courier dispatch from Midrand',
            'stat_4_value' => 'WhatsApp-first',
            'stat_4_label' => 'Orders, invoices & recovery updates',
            'home_show_bestsellers' => '1',
            'bestsellers_title' => 'Shop bestsellers',
            'bestsellers_lede' => 'Direct cart checkout in ZAR. Pay on invoice or confirm stock on WhatsApp — both paths land in the same order desk.',
            'bestsellers_count' => '8',
            'home_show_lab' => '1',
            'lab_kicker' => 'Lab in Midrand',
            'lab_heading' => 'Recover the unrecoverable. Supply drives that last.',
            'lab_body' => 'Every refurbished unit is health-tested, surface-scanned, and graded. Data recovery cases are handled confidentially with professional tools for HDD, SSD, flash, and RAID.',
            'lab_image' => 'assets/img/lab.jpg',
            'lab_cta_label' => 'Our story',
            'lab_cta_url' => '/about',
            'lab_cta2_label' => 'Visit the office',
            'lab_cta2_url' => '/contact',
            'home_meta_title' => 'EK Electronics | Hard Drives, Data Recovery & Reliable Tech',
        ];
    }

    /** @return array<string, string> */
    public static function resolved(): array
    {
        $out = [];
        foreach (self::defaults() as $key => $default) {
            $out[$key] = (string) (Setting::getValue($key, $default) ?? $default);
        }

        return $out;
    }

    /** @return array<string, string|bool|int> */
    public static function forView(): array
    {
        $out = self::resolved();
        foreach (['home_show_hero', 'home_show_stats', 'home_show_bestsellers', 'home_show_lab', 'hero_show_whatsapp'] as $flag) {
            $out[$flag] = in_array($out[$flag], ['1', 'true', 'yes', 'on'], true);
        }
        $out['bestsellers_count'] = max(1, min(24, (int) $out['bestsellers_count']));

        return $out;
    }

    public static function imageUrl(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset($path);
    }
}
