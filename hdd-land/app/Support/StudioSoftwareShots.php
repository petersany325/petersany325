<?php

namespace App\Support;

class StudioSoftwareShots
{
    public const CORPORATE_PATH = '/computer-accounting/corporate';

    public const HERO = 'images/studio/hesab-corporate/desk.jpg';

    /** @return array<string, mixed> */
    public static function pair(string $key): array
    {
        $row = self::pairs()[$key] ?? null;
        if ($row === null) {
            return [];
        }

        return [
            'type' => 'gallery',
            'layout' => 'pair',
            'id' => 'hesab-corporate-'.$key,
            'kicker' => $row['kicker'],
            'title' => $row['title'],
            'text' => $row['text'],
            'items' => [[
                'image' => $row['image'],
                'title' => $row['title'],
                'text' => $row['text'],
                'wide' => true,
            ]],
        ];
    }

    public static function heroImage(): string
    {
        return self::HERO;
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
        $page['blocks'] = self::applyToBlocks(is_array($page['blocks'] ?? null) ? $page['blocks'] : []);

        return $page;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public static function applyToBlocks(array $blocks): array
    {
        $blocks = array_values(array_filter($blocks, static function ($block): bool {
            if (! is_array($block)) {
                return false;
            }
            $id = (string) ($block['id'] ?? '');
            if (str_starts_with($id, 'hesab-corporate')) {
                return false;
            }
            $type = (string) ($block['type'] ?? '');
            if (in_array($type, ['gallery', 'image'], true) && self::blockHasCorporateShot($block)) {
                return false;
            }

            return true;
        }));

        if ($blocks !== [] && ($blocks[0]['type'] ?? '') === 'hero') {
            $blocks[0]['image'] = self::HERO;
        }

        $blocks = self::stampMenuCards($blocks);
        $blocks = self::insertAfterTitle($blocks, 'چیست و چه کاری', self::pair('docs'));
        $blocks = self::insertAfterTitle($blocks, 'هوش مصنوعی شرکتی', self::pair('ai'));
        if (! self::hasId($blocks, 'hesab-corporate-ai')) {
            $blocks = self::insertAfterTitle($blocks, 'هوش مصنوعی داخل', self::pair('ai'));
        }
        $blocks = self::insertBeforeTitle($blocks, 'امکانات و منو', self::pair('stock'));
        $blocks = self::insertBeforeTitle($blocks, 'صنوف دیگر', self::pair('editions'));

        return array_values($blocks);
    }

    /** @return array<string, array{kicker:string,title:string,text:string,image:string}> */
    protected static function pairs(): array
    {
        return [
            'docs' => [
                'kicker' => 'همان متن، همان صفحه نرم‌افزار',
                'title' => 'اسناد و دفاتر',
                'text' => 'ثبت سند دستی و خودکار از فاکتور. دفتر روزنامه، دفتر کل، معین و تراز آزمایشی از همان اسناد ساخته می‌شود.',
                'image' => 'images/studio/hesab-corporate/docs.jpg',
            ],
            'ai' => [
                'kicker' => 'همان متن، همان صفحه نرم‌افزار',
                'title' => 'هوش مصنوعی شرکتی — تحلیل و گفتگو آفلاین',
                'text' => 'حسابدار می‌پرسد «چرا تراز این ماه اختلاف دارد؟» یا مدیر می‌پرسد «کدام مرکز هزینه از بودجه گذشته؟» جواب از روی همان اسناد دفتر، داخل نرم‌افزار و بدون اینترنت می‌آید.',
                'image' => 'images/studio/hesab-corporate/ai.jpg',
            ],
            'stock' => [
                'kicker' => 'همان متن، همان صفحه نرم‌افزار',
                'title' => 'خرید و فروش بازرگانی',
                'text' => 'پیش‌فاکتور، فاکتور، برگشت از خرید و فروش، اتصال به انبار و صدور سند حسابداری بدون ورود دوباره مبلغ.',
                'image' => 'images/studio/hesab-corporate/stock.jpg',
            ],
            'editions' => [
                'kicker' => 'همان متن، همان صفحه نرم‌افزار',
                'title' => 'حسابداری کامپیوتر برای صنوف دیگر',
                'text' => 'حسابداری شرکتی یکی از نسخه‌های همین دفتر است. سوپرمارکت، فروشگاه، رستوران و بقیه صفحه خودشان را دارند.',
                'image' => 'images/studio/hesab-corporate/editions.jpg',
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    protected static function stampMenuCards(array $blocks): array
    {
        $map = [
            'داشبورد مالی' => self::HERO,
            'اسناد و دفاتر' => 'images/studio/hesab-corporate/docs.jpg',
            'خرید و فروش بازرگانی' => 'images/studio/hesab-corporate/stock.jpg',
            'گفتگو با دستیار مرکزی' => 'images/studio/hesab-corporate/ai.jpg',
        ];
        foreach ($blocks as $i => $block) {
            if (($block['type'] ?? '') !== 'cards' || empty($block['items']) || ! is_array($block['items'])) {
                continue;
            }
            foreach ($block['items'] as $j => $item) {
                $title = trim((string) ($item['title'] ?? ''));
                if (isset($map[$title])) {
                    $blocks[$i]['items'][$j]['image'] = $map[$title];
                }
            }
        }

        return $blocks;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $insert
     * @return list<array<string, mixed>>
     */
    protected static function insertAfterTitle(array $blocks, string $needle, array $insert): array
    {
        if ($insert === [] || self::hasId($blocks, (string) ($insert['id'] ?? ''))) {
            return $blocks;
        }
        foreach ($blocks as $i => $block) {
            if (self::titleHas($block, $needle)) {
                array_splice($blocks, $i + 1, 0, [$insert]);

                return $blocks;
            }
        }

        return $blocks;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $insert
     * @return list<array<string, mixed>>
     */
    protected static function insertBeforeTitle(array $blocks, string $needle, array $insert): array
    {
        if ($insert === [] || self::hasId($blocks, (string) ($insert['id'] ?? ''))) {
            return $blocks;
        }
        foreach ($blocks as $i => $block) {
            if (self::titleHas($block, $needle)) {
                array_splice($blocks, $i, 0, [$insert]);

                return $blocks;
            }
        }

        return $blocks;
    }

    /** @param  list<array<string, mixed>>  $blocks */
    protected static function hasId(array $blocks, string $id): bool
    {
        if ($id === '') {
            return false;
        }
        foreach ($blocks as $block) {
            if ((string) ($block['id'] ?? '') === $id) {
                return true;
            }
        }

        return false;
    }

    /** @param  array<string, mixed>  $block */
    protected static function titleHas(array $block, string $needle): bool
    {
        $title = (string) ($block['title'] ?? '');

        return $needle !== '' && str_contains($title, $needle);
    }

    /** @param  array<string, mixed>  $block */
    protected static function blockHasCorporateShot(array $block): bool
    {
        $blob = json_encode($block, JSON_UNESCAPED_UNICODE) ?: '';

        return str_contains($blob, 'hesab-corporate/');
    }
}
