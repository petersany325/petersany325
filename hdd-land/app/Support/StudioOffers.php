<?php

namespace App\Support;

/**
 * Sellable studio items. Prices stay empty until Peter sets them.
 * Every URL points at a real page on this site.
 */
class StudioOffers
{
    public const PRICE_LABEL = 'قیمت اعلام می‌شود';

    /** @return list<array<string, mixed>> */
    public static function design(): array
    {
        $rows = [];
        $services = class_exists(StudioDesignCopy::class) ? StudioDesignCopy::services() : [];
        foreach ($services as $row) {
            $slug = (string) ($row['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $rows[] = [
                'sku' => 'design-'.$slug,
                'kind' => 'design',
                'slug' => $slug,
                'title' => (string) ($row['title'] ?? ''),
                'short' => (string) ($row['short'] ?? ''),
                'url' => '/design/'.$slug,
                'sell_url' => '/pay/start?sku=design-'.$slug,
                'price' => '',
                'price_label' => self::PRICE_LABEL,
                'commission' => $slug === 'corporate' ? 10 : 8,
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public static function software(): array
    {
        $rows = [];
        if (class_exists(StudioAccountingCopy::class)) {
            foreach (StudioAccountingCopy::industries() as $row) {
                $slug = (string) ($row['slug'] ?? '');
                if ($slug === '') {
                    continue;
                }
                $rows[] = [
                    'sku' => 'acc-'.$slug,
                    'kind' => 'accounting',
                    'slug' => $slug,
                    'title' => (string) ($row['title'] ?? ''),
                    'short' => (string) ($row['short'] ?? ''),
                    'url' => '/computer-accounting/'.$slug,
                    'sell_url' => '/pay/start?sku=acc-'.$slug,
                    'price' => '',
                    'price_label' => self::PRICE_LABEL,
                    'commission' => $slug === 'corporate' ? 10 : 8,
                ];
            }
        }
        foreach (self::medical() as $row) {
            $rows[] = $row;
        }
        $rows[] = [
            'sku' => 'acc-web',
            'kind' => 'accounting-web',
            'slug' => 'web',
            'title' => 'حسابداری وب تعمیرگاه',
            'short' => 'دفتر تحت وب تعمیرکاران و وصل به قبض',
            'url' => '/accounting',
            'sell_url' => '/pay/start?sku=acc-web',
            'price' => '',
            'price_label' => self::PRICE_LABEL,
            'commission' => 8,
        ];
        $rows[] = [
            'sku' => 'repair-shop',
            'kind' => 'repair',
            'slug' => 'repair-shop',
            'title' => 'سایت مدیریت تعمیرگاه',
            'short' => 'پذیرش، ارجاع، انبار و کارتابل نیرو',
            'url' => '/repair-shop',
            'sell_url' => '/pay/start?sku=repair-shop',
            'price' => '',
            'price_label' => self::PRICE_LABEL,
            'commission' => 8,
        ];

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public static function medical(): array
    {
        return [
            [
                'sku' => 'med-beauty',
                'kind' => 'medical',
                'slug' => 'beauty',
                'title' => 'نرم‌افزار پزشکی زیبایی',
                'short' => 'نوبت، پرونده و حساب کلینیک زیبایی',
                'url' => '/software/medical-beauty',
                'sell_url' => '/pay/start?sku=med-beauty',
                'price' => '',
                'price_label' => self::PRICE_LABEL,
                'commission' => 12,
            ],
            [
                'sku' => 'med-physio',
                'kind' => 'medical',
                'slug' => 'physiotherapy',
                'title' => 'نرم‌افزار فیزیوتراپی',
                'short' => 'نوبت واحد، پرونده و شرح حال کلینیک فیزیوتراپی',
                'url' => '/software/medical-physiotherapy',
                'sell_url' => '',
                'price' => '',
                'price_label' => '',
                'commission' => 12,
            ],
            [
                'sku' => 'med-psych',
                'kind' => 'medical',
                'slug' => 'psychology',
                'title' => 'نرم‌افزار روانشناسی',
                'short' => 'نوبت، جلسه و پرونده مرکز روانشناسی',
                'url' => '/software/medical-psychology',
                'sell_url' => '/pay/start?sku=med-psych',
                'price' => '',
                'price_label' => self::PRICE_LABEL,
                'commission' => 12,
            ],
            [
                'sku' => 'med-dental',
                'kind' => 'medical',
                'slug' => 'dental',
                'title' => 'نرم‌افزار دندانپزشکی',
                'short' => 'نوبت یونیت، چارت دندانی و پرونده مطب',
                'url' => '/software/medical-dental',
                'sell_url' => '',
                'price' => '',
                'price_label' => '',
                'commission' => 12,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function catalog(): array
    {
        $saved = class_exists(StudioStaffDesk::class) ? StudioStaffDesk::priceMap() : [];
        $rows = [];
        foreach (array_merge(self::design(), self::software()) as $row) {
            $over = $saved[$row['sku']] ?? null;
            if (is_array($over)) {
                if (($over['price'] ?? null) !== null && (int) $over['price'] > 0) {
                    $row['price'] = (string) (int) $over['price'];
                    $row['price_label'] = number_format((int) $over['price']).' تومان';
                }
                if (($over['commission'] ?? null) !== null) {
                    $row['commission'] = (float) $over['commission'];
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public static function find(string $sku): ?array
    {
        foreach (self::catalog() as $row) {
            if (($row['sku'] ?? '') === $sku) {
                return $row;
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $offer
     *  @return array{price:int,rate:float,share:int,source:string,base:float}
     */
    public static function quote(array $offer, int $userId = 0): array
    {
        $base = (float) ($offer['commission'] ?? 0);
        $person = class_exists(StudioStaffDesk::class) ? StudioStaffDesk::personRate($userId) : null;
        $rate = $person ?? $base;
        $price = (int) preg_replace('/\D+/', '', (string) ($offer['price'] ?? '')) ?: 0;
        $share = $price > 0 ? (int) round($price * $rate / 100) : 0;

        return [
            'price' => $price,
            'rate' => $rate,
            'share' => $share,
            'source' => $person !== null ? 'person' : 'software',
            'base' => $base,
        ];
    }

    public static function priceText(array $row): string
    {
        $price = trim((string) ($row['price'] ?? ''));

        return $price !== '' ? $price : self::PRICE_LABEL;
    }
}
