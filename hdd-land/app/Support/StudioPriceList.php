<?php

namespace App\Support;

/**
 * Published computer-accounting prices, in toman.
 */
class StudioPriceList
{
    /** @return list<array{sku:string,title:string,million:int,kind:string,url:string}> */
    public static function editions(): array
    {
        return [
            self::row('acc-shop', 'نرم‌افزار فروشگاهی', 25, 'edition', '/computer-accounting/shop'),
            self::row('acc-corporate', 'نرم‌افزار شرکتی', 30, 'edition', '/computer-accounting/corporate'),
            self::row('acc-supermarket', 'نرم‌افزار سوپرمارکت', 25, 'edition', '/computer-accounting/supermarket'),
            self::row('acc-distribution', 'نرم‌افزار پخش', 30, 'edition', '/computer-accounting/distribution'),
            self::row('acc-service', 'نرم‌افزار خدماتی', 20, 'edition', '/computer-accounting/service'),
            self::row('acc-manufacturing', 'نرم‌افزار تولیدی', 30, 'edition', '/computer-accounting/manufacturing'),
            self::row('acc-technicians', 'نرم‌افزار تعمیرکاران', 25, 'edition', '/computer-accounting/technicians'),
            self::row('acc-restaurant', 'نرم‌افزار رستوران', 25, 'edition', '/computer-accounting/restaurant'),
            self::row('acc-cafe', 'نرم‌افزار کافی‌شاپ', 25, 'edition', '/computer-accounting/cafe'),
            self::row('acc-contracting', 'نرم‌افزار پیمانکاری', 20, 'edition', '/computer-accounting/contracting'),
            self::row('acc-automation', 'نرم‌افزار اتوماسیون اداری', 25, 'edition', '/computer-accounting/automation'),
        ];
    }

    /** @return array{sku:string,title:string,million:int,kind:string,url:string} */
    public static function bundle(): array
    {
        return self::row('acc-all', 'همه نسخه‌ها', 200, 'bundle', '/computer-accounting#prices');
    }

    /** @return list<array{sku:string,title:string,million:int,kind:string,url:string}> */
    public static function addons(): array
    {
        return [
            self::row('addon-sms', 'افزونه پیامک', 1, 'addon', '/computer-accounting#prices'),
            self::row('addon-pos', 'افزونه صندوق فروشگاه', 1, 'addon', '/computer-accounting#prices'),
            self::row('addon-web', 'افزونه نسخه وب', 15, 'addon', '/computer-accounting#prices'),
            self::row('addon-multi', 'افزونه چند شرکتی', 10, 'addon', '/computer-accounting#prices'),
            self::row('addon-visitor', 'افزونه پخش ویزیتور', 5, 'addon', '/computer-accounting#prices'),
            self::row('addon-api', 'افزونه اتصال برنامه‌های دیگر (API)', 10, 'addon', '/computer-accounting#prices'),
            self::row('addon-ai-eco', 'افزونه هوش مصنوعی اقتصادی داخلی', 25, 'addon', '/computer-accounting#prices'),
            self::row('addon-ai-pro', 'افزونه هوش مصنوعی داخلی حرفه‌ای', 80, 'addon', '/computer-accounting#prices'),
            self::row('addon-client', 'افزونه کلاینت', 2, 'addon', '/computer-accounting#prices'),
            self::row('addon-tax', 'افزونه حسابداری مالیاتی', 15, 'addon', '/computer-accounting#prices'),
            self::row('addon-book2', 'افزونه دفتر دوم', 10, 'addon', '/computer-accounting#prices'),
            self::row('addon-fx', 'افزونه حسابداری چند ارزی', 25, 'addon', '/computer-accounting#prices'),
        ];
    }

    /** @return array{sku:string,title:string,million:int,kind:string,url:string,price:int,price_label:string}|null */
    public static function find(string $sku): ?array
    {
        foreach (array_merge(self::editions(), [self::bundle()], self::addons()) as $row) {
            if ($row['sku'] === $sku) {
                $row['price'] = $row['million'] * 1000000;
                $row['price_label'] = self::label($row['million']);

                return $row;
            }
        }

        return null;
    }

    public static function label(int $million): string
    {
        $digits = strtr((string) $million, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);

        return $digits.' میلیون تومان';
    }

    /** @return array{sku:string,title:string,million:int,kind:string,url:string} */
    protected static function row(string $sku, string $title, int $million, string $kind, string $url): array
    {
        return [
            'sku' => $sku,
            'title' => $title,
            'million' => $million,
            'kind' => $kind,
            'url' => $url,
        ];
    }
}
