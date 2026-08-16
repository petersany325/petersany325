<?php
declare(strict_types=1);

/**
 * Approximate Iran city centers for VIP club GPS honesty checks.
 * Not precise geocoding — enough to catch obvious city lies (e.g. آمل vs تهران).
 */
final class GeoCheck
{
    /** Max km from claimed city center before warning. */
    public const WARN_KM = 85.0;

    /**
     * @return array{lat:float,lng:float}|null
     */
    public static function cityCenter(string $city): ?array
    {
        $city = trim($city);
        if ($city === '') {
            return null;
        }
        $map = self::centers();
        if (isset($map[$city])) {
            return $map[$city];
        }
        // soft match without ZWNJ / spaces
        $norm = self::norm($city);
        foreach ($map as $name => $coords) {
            if (self::norm($name) === $norm) {
                return $coords;
            }
        }
        return null;
    }

    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @return array{ok:bool,distance_km:?float,message:string}
     */
    public static function checkClaim(string $city, float $lat, float $lng): array
    {
        $center = self::cityCenter($city);
        if (!$center) {
            return [
                'ok' => true,
                'distance_km' => null,
                'message' => 'موقعیت ثبت شد. شهر شما در فهرست دقیق GPS نیست؛ لطفاً شهر پروفایل را درست نگه دارید.',
            ];
        }
        $km = self::haversineKm($lat, $lng, $center['lat'], $center['lng']);
        if ($km <= self::WARN_KM) {
            return [
                'ok' => true,
                'distance_km' => $km,
                'message' => 'موقعیت مکانی با شهر پروفایل هم‌خوان است ✅',
            ];
        }
        return [
            'ok' => false,
            'distance_km' => $km,
            'message' =>
                "⚠️ موقعیت GPS با شهر ثبت‌شده (<b>" .
                htmlspecialchars($city, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                "</b>) هم‌خوان نیست.\n" .
                "لطفاً شهر/استان پروفایل را درست کن؛ در کلاب VIP اطلاعات مکانی باید واقعی باشد.",
        ];
    }

    private static function norm(string $s): string
    {
        $s = str_replace(["\u{200C}", ' ', '‌'], '', $s);
        return mb_strtolower($s);
    }

    /** @return array<string, array{lat:float,lng:float}> */
    private static function centers(): array
    {
        return [
            'تهران' => ['lat' => 35.6892, 'lng' => 51.3890],
            'کرج' => ['lat' => 35.8400, 'lng' => 50.9391],
            'مشهد' => ['lat' => 36.2605, 'lng' => 59.6168],
            'اصفهان' => ['lat' => 32.6546, 'lng' => 51.6680],
            'شیراز' => ['lat' => 29.5918, 'lng' => 52.5837],
            'تبریز' => ['lat' => 38.0962, 'lng' => 46.2738],
            'اهواز' => ['lat' => 31.3183, 'lng' => 48.6706],
            'قم' => ['lat' => 34.6416, 'lng' => 50.8746],
            'کرمان' => ['lat' => 30.2832, 'lng' => 57.0788],
            'رشت' => ['lat' => 37.2808, 'lng' => 49.5832],
            'ساری' => ['lat' => 36.5633, 'lng' => 53.0601],
            'آمل' => ['lat' => 36.4696, 'lng' => 52.3507],
            'بابل' => ['lat' => 36.5513, 'lng' => 52.6789],
            'قائم‌شهر' => ['lat' => 36.4631, 'lng' => 52.8601],
            'بابلسر' => ['lat' => 36.7025, 'lng' => 52.6576],
            'چالوس' => ['lat' => 36.6550, 'lng' => 51.4204],
            'نوشهر' => ['lat' => 36.6484, 'lng' => 51.4960],
            'ارومیه' => ['lat' => 37.5527, 'lng' => 45.0761],
            'یزد' => ['lat' => 31.8974, 'lng' => 54.3569],
            'کرمانشاه' => ['lat' => 34.3142, 'lng' => 47.0650],
            'همدان' => ['lat' => 34.7983, 'lng' => 48.5146],
            'اردبیل' => ['lat' => 38.2498, 'lng' => 48.2933],
            'زاهدان' => ['lat' => 29.4963, 'lng' => 60.8629],
            'بندرعباس' => ['lat' => 27.1832, 'lng' => 56.2666],
            'بوشهر' => ['lat' => 28.9234, 'lng' => 50.8203],
            'گرگان' => ['lat' => 36.8456, 'lng' => 54.4393],
            'سنندج' => ['lat' => 35.3219, 'lng' => 46.9862],
            'خرم‌آباد' => ['lat' => 33.4878, 'lng' => 48.3558],
            'اراک' => ['lat' => 34.0917, 'lng' => 49.6892],
            'زنجان' => ['lat' => 36.6769, 'lng' => 48.4963],
            'قزوین' => ['lat' => 36.2797, 'lng' => 50.0049],
            'سمنان' => ['lat' => 35.5769, 'lng' => 53.3953],
            'ایلام' => ['lat' => 33.6374, 'lng' => 46.4226],
            'یاسوج' => ['lat' => 30.6684, 'lng' => 51.5876],
            'شهرکرد' => ['lat' => 32.3256, 'lng' => 50.8644],
            'بیرجند' => ['lat' => 32.8649, 'lng' => 59.2262],
            'بجنورد' => ['lat' => 37.4750, 'lng' => 57.3310],
            'اسلامشهر' => ['lat' => 35.5446, 'lng' => 51.2302],
            'شهریار' => ['lat' => 35.6588, 'lng' => 51.0578],
        ];
    }
}
