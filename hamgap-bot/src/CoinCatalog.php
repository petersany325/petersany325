<?php
declare(strict_types=1);

/** Coin shop catalog — admin sets prices; staff only approves receipts. */
final class CoinCatalog
{
    /**
     * @return list<array{id:string,coins:int,price_key:string,default_price:int,label:string,badge?:string}>
     */
    public static function packs(): array
    {
        return [
            ['id' => '170', 'coins' => 170, 'price_key' => 'pack_170_price', 'default_price' => 190000, 'label' => '۱۷۰ سکه'],
            ['id' => '350', 'coins' => 350, 'price_key' => 'pack_350_price', 'default_price' => 300000, 'label' => '۳۵۰ سکه'],
            ['id' => '500', 'coins' => 500, 'price_key' => 'pack_500_price', 'default_price' => 450000, 'label' => '۵۰۰ سکه'],
            ['id' => '750', 'coins' => 750, 'price_key' => 'pack_750_price', 'default_price' => 600000, 'label' => '۷۵۰ سکه'],
            ['id' => '1000', 'coins' => 1000, 'price_key' => 'pack_1000_price', 'default_price' => 800000, 'label' => '۱۰۰۰ سکه'],
            // "نامحدود" = بسته ویژه بزرگ (سکه واقعی زیاد) تا سوءاستفاده نشود
            [
                'id' => 'unlimited',
                'coins' => 3000,
                'price_key' => 'pack_unlimited_price',
                'default_price' => 1500000,
                'label' => 'بسته ویژه نامحدود',
                'badge' => '۳۰۰۰ سکه',
            ],
        ];
    }

    public static function find(string $id): ?array
    {
        foreach (self::packs() as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }
        return null;
    }

    /** @return array<string,int> id => price */
    public static function prices(Settings $settings): array
    {
        $out = [];
        foreach (self::packs() as $p) {
            $out[$p['id']] = $settings->getInt($p['price_key'], $p['default_price']);
        }
        return $out;
    }

    /** @return list<string> */
    public static function priceKeys(): array
    {
        return array_map(static fn ($p) => $p['price_key'], self::packs());
    }
}
