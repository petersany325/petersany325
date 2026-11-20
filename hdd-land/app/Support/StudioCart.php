<?php

namespace App\Support;

class StudioCart
{
    public const KEY = 'studio_price_cart';

    /** @return list<string> */
    public static function skus(): array
    {
        $raw = session(self::KEY, []);
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $sku) {
            $sku = trim((string) $sku);
            if ($sku !== '' && StudioPriceList::find($sku) !== null) {
                $out[] = $sku;
            }
        }

        return array_values(array_unique($out));
    }

    public static function has(string $sku): bool
    {
        return in_array($sku, self::skus(), true);
    }

    public static function add(string $sku): void
    {
        if (StudioPriceList::find($sku) === null) {
            return;
        }
        $skus = self::skus();
        if (! in_array($sku, $skus, true)) {
            $skus[] = $sku;
        }
        session([self::KEY => $skus]);
    }

    public static function remove(string $sku): void
    {
        session([self::KEY => array_values(array_filter(self::skus(), fn ($row) => $row !== $sku))]);
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
    }

    /** @return list<array{sku:string,title:string,million:int,kind:string,url:string,price:int,price_label:string}> */
    public static function items(): array
    {
        $out = [];
        foreach (self::skus() as $sku) {
            $row = StudioPriceList::find($sku);
            if ($row !== null) {
                $out[] = $row;
            }
        }

        return $out;
    }

    public static function total(): int
    {
        $sum = 0;
        foreach (self::items() as $row) {
            $sum += (int) $row['price'];
        }

        return $sum;
    }

    public static function totalLabel(): string
    {
        return strtr(number_format(self::total()), ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']).' تومان';
    }
}
