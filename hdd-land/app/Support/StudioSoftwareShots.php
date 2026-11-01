<?php

namespace App\Support;

class StudioSoftwareShots
{
    public const CORPORATE_PATH = '/computer-accounting/corporate';

    public const GALLERY_ID = 'hesab-corporate-shots';

    public static function heroImage(): string
    {
        return 'images/studio/hesab-corporate/desk.jpg';
    }

    /** @return list<array{image:string,title:string,text:string,wide?:bool}> */
    public static function corporateItems(): array
    {
        return [
            [
                'image' => 'images/studio/hesab-corporate/desk.jpg',
                'title' => 'میز کار سال مالی ۱۴۰۵',
                'text' => 'پیشخوان حسابداری شرکتی: فروش، موجودی، بدهکار، سود و دسترسی سریع به فاکتور و سند.',
                'wide' => true,
            ],
            [
                'image' => 'images/studio/hesab-corporate/docs.jpg',
                'title' => 'صدور سند و فاکتور',
                'text' => 'منوی اسناد: فاکتور فروش، خدمات، دریافت و میانبرهای حسابدار.',
            ],
            [
                'image' => 'images/studio/hesab-corporate/stock.jpg',
                'title' => 'انبار و کالا',
                'text' => 'کاردکس، معرفی کالا و چند انبار روی همان دفتر شرکتی.',
            ],
            [
                'image' => 'images/studio/hesab-corporate/ai.jpg',
                'title' => 'دستیار هوشمند آفلاین',
                'text' => 'گفتگو با دفتر، موجودی و بدهکاران — روی سیستم شما، بدون ارسال داده به اینترنت.',
            ],
            [
                'image' => 'images/studio/hesab-corporate/editions.jpg',
                'title' => 'نسخه شرکتی از همان دفتر',
                'text' => 'حسابداری شرکتی یکی از نسخه‌های HesabHDD است؛ کدینگ و سند مشترک می‌ماند.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function galleryBlock(): array
    {
        return [
            'type' => 'gallery',
            'id' => self::GALLERY_ID,
            'kicker' => 'محیط واقعی نرم‌افزار',
            'title' => 'عکس حسابداری شرکتی HesabHDD',
            'text' => 'همین نرم‌افزار نصب می‌شود. فیلتر تبلیغاتی روی عکس نیست؛ نوار ویندوز بریده شده تا خود میز کار دیده شود.',
            'items' => self::corporateItems(),
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    public static function mergeInto(array $page): array
    {
        $path = class_exists(\Plugins\ThemeBuilder\src\PageBuilder::class)
            ? \Plugins\ThemeBuilder\src\PageBuilder::normPath((string) ($page['path'] ?? ''))
            : (string) ($page['path'] ?? '');
        if ($path !== self::CORPORATE_PATH) {
            return $page;
        }

        $blocks = array_values(is_array($page['blocks'] ?? null) ? $page['blocks'] : []);
        $gallery = self::galleryBlock();
        $hero = self::heroImage();

        if ($blocks !== [] && ($blocks[0]['type'] ?? '') === 'hero') {
            $current = trim((string) ($blocks[0]['image'] ?? ''));
            if ($current === '' || ! str_contains($current, 'hesab-corporate/')) {
                $blocks[0]['image'] = $hero;
            }
        }

        $found = false;
        foreach ($blocks as $i => $block) {
            if (($block['type'] ?? '') === 'gallery' && (string) ($block['id'] ?? '') === self::GALLERY_ID) {
                $blocks[$i] = $gallery;
                $found = true;
                break;
            }
            if (self::blockHasCorporateShot($block)) {
                $found = true;
                break;
            }
        }
        if (! $found) {
            $at = ($blocks !== [] && ($blocks[0]['type'] ?? '') === 'hero') ? 1 : 0;
            array_splice($blocks, $at, 0, [$gallery]);
        }

        $page['blocks'] = $blocks;

        return $page;
    }

    /** @param  array<string, mixed>  $block */
    protected static function blockHasCorporateShot(array $block): bool
    {
        $blob = json_encode($block, JSON_UNESCAPED_UNICODE) ?: '';

        return str_contains($blob, 'hesab-corporate/');
    }
}
