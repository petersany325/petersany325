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
                'sku' => 'med-opera',
                'kind' => 'medical',
                'slug' => 'opera',
                'title' => 'نرم‌افزار پزشکی اوپرا',
                'short' => 'اتاق عمل، پذیرش و پرونده جراحی',
                'url' => '/software/medical-opera',
                'sell_url' => '/pay/start?sku=med-opera',
                'price' => '',
                'price_label' => self::PRICE_LABEL,
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
        ];
    }

    /** @return array<string, mixed>|null */
    public static function find(string $sku): ?array
    {
        foreach (array_merge(self::design(), self::software()) as $row) {
            if (($row['sku'] ?? '') === $sku) {
                return $row;
            }
        }

        return null;
    }

    public static function priceText(array $row): string
    {
        $price = trim((string) ($row['price'] ?? ''));

        return $price !== '' ? $price : self::PRICE_LABEL;
    }
}
