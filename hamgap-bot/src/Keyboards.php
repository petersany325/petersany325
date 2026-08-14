<?php
declare(strict_types=1);

final class Keyboards
{
    public static function mainReply(): array
    {
        return [
            'keyboard' => [
                [['text' => '🔗 وصلم کن به ناشناس']],
                [['text' => '🔍 پیدا کردن مخاطب'], ['text' => '💎 سکه‌ها']],
                [['text' => '👤 پروفایل من'], ['text' => 'ℹ️ راهنما']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }

    public static function mainInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🔗 وصلم کن به ناشناس', 'callback_data' => 'menu:connect']],
            [
                ['text' => '🔍 پیدا کردن مخاطب', 'callback_data' => 'menu:find'],
                ['text' => '💎 سکه‌ها', 'callback_data' => 'menu:wallet'],
            ],
            [
                ['text' => '👤 پروفایل من', 'callback_data' => 'menu:profile'],
                ['text' => 'ℹ️ راهنما', 'callback_data' => 'menu:help'],
            ],
        ]];
    }

    public static function removeReply(): array
    {
        return ['remove_keyboard' => true];
    }

    public static function connectInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🎲 شانسی · رایگان', 'callback_data' => 'chat:any']],
            [
                ['text' => '👩 فقط دختر · ۱ سکه', 'callback_data' => 'chat:female'],
                ['text' => '👨 فقط پسر · ۱ سکه', 'callback_data' => 'chat:male'],
            ],
            [['text' => '🏙 هم‌استان · ۲ سکه', 'callback_data' => 'chat:province']],
            [['text' => '🎂 هم‌سن · ۲ سکه', 'callback_data' => 'chat:age']],
            [['text' => '🔙 منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function findGenderInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '👩 فقط دختر', 'callback_data' => 'find:gender:female'],
                ['text' => '👨 فقط پسر', 'callback_data' => 'find:gender:male'],
            ],
            [['text' => '👫 همه', 'callback_data' => 'find:gender:any']],
            [['text' => '🔙 بازگشت', 'callback_data' => 'menu:find']],
        ]];
    }

    public static function gender(): array
    {
        return ['inline_keyboard' => [[
            ['text' => '👩 دختر', 'callback_data' => 'reg:gender:female'],
            ['text' => '👨 پسر', 'callback_data' => 'reg:gender:male'],
        ]]];
    }

    public static function age(): array
    {
        $rows = [];
        $row = [];
        foreach ([16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 32, 35, 40] as $age) {
            $row[] = ['text' => (string)$age, 'callback_data' => 'reg:age:' . $age];
            if (count($row) === 3) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $rows[] = $row;
        }
        $rows[] = [['text' => '۴۵+', 'callback_data' => 'reg:age:45']];
        return ['inline_keyboard' => $rows];
    }

    public static function provinces(): array
    {
        $rows = [];
        $row = [];
        foreach (IranLocations::provinces() as $i => $name) {
            $row[] = ['text' => $name, 'callback_data' => 'reg:prov:' . $i];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $rows[] = $row;
        }
        return ['inline_keyboard' => $rows];
    }

    public static function cities(string $province): array
    {
        $rows = [];
        $row = [];
        foreach (IranLocations::cities($province) as $i => $city) {
            $row[] = ['text' => $city, 'callback_data' => 'reg:ci:' . $i];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $rows[] = $row;
        }
        $rows[] = [['text' => '🏙 شهر دیگر', 'callback_data' => 'reg:city:other']];
        $rows[] = [['text' => '🔙 تغییر استان', 'callback_data' => 'reg:prov:back']];
        return ['inline_keyboard' => $rows];
    }

    /** Province picker for find-flow (separate callback prefix) */
    public static function findProvinces(): array
    {
        $rows = [];
        $row = [];
        foreach (IranLocations::provinces() as $i => $name) {
            $row[] = ['text' => $name, 'callback_data' => 'find:prov:' . $i];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $rows[] = $row;
        }
        $rows[] = [['text' => '🔙 منوی اصلی', 'callback_data' => 'menu:main']];
        return ['inline_keyboard' => $rows];
    }

    public static function findCities(string $province): array
    {
        $rows = [];
        $row = [];
        foreach (IranLocations::cities($province) as $i => $city) {
            $row[] = ['text' => $city, 'callback_data' => 'find:ci:' . $i];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $rows[] = $row;
        }
        $rows[] = [['text' => '🔙 تغییر استان', 'callback_data' => 'menu:find']];
        return ['inline_keyboard' => $rows];
    }

    public static function searching(): array
    {
        return ['inline_keyboard' => [[
            ['text' => '❌ لغو جستجو', 'callback_data' => 'chat:cancel'],
        ]]];
    }

    public static function chattingInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '⏭ بعدی', 'callback_data' => 'chat:next'],
                ['text' => '🛑 پایان', 'callback_data' => 'chat:end'],
            ],
            [['text' => '🚩 گزارش', 'callback_data' => 'chat:report']],
        ]];
    }

    public static function chattingReply(): array
    {
        return [
            'keyboard' => [
                [['text' => '⏭ بعدی'], ['text' => '🛑 پایان چت']],
                [['text' => '🚩 گزارش']],
            ],
            'resize_keyboard' => true,
        ];
    }

    public static function profileInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🛠 تنظیمات پروفایل', 'callback_data' => 'menu:profile_settings']],
            [['text' => '✨ دعوت دوستان', 'callback_data' => 'menu:invite']],
            [['text' => '🔙 منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function profileSettingsInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🖼 تغییر عکس پروفایل', 'callback_data' => 'edit:avatar']],
            [['text' => '🔤 تغییر نام کاربری', 'callback_data' => 'edit:displayname']],
            [
                ['text' => '✏️ جنسیت', 'callback_data' => 'edit:gender'],
                ['text' => '✏️ سن', 'callback_data' => 'edit:age'],
            ],
            [['text' => '📍 استان / شهر', 'callback_data' => 'edit:location']],
            [['text' => '🔙 پروفایل', 'callback_data' => 'menu:profile']],
        ]];
    }

    public static function walletInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '✨ دعوت دوست · +۲۰ سکه', 'callback_data' => 'menu:invite']],
            [['text' => '۱۰۰ سکه — ۵۰٬۰۰۰ تومان', 'callback_data' => 'pay:100']],
            [['text' => '۳۰۰ سکه — ۱۲۰٬۰۰۰ تومان ★', 'callback_data' => 'pay:300']],
            [['text' => '۱۰۰۰ سکه — ۳۵۰٬۰۰۰ تومان VIP', 'callback_data' => 'pay:1000']],
            [['text' => '🔙 منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function payMethodInline(string $pack): array
    {
        return ['inline_keyboard' => [
            [['text' => '💳 درگاه بانکی (به‌زودی)', 'callback_data' => 'pay:soon']],
            [['text' => '🏦 کارت‌به‌کارت (به‌زودی)', 'callback_data' => 'pay:soon']],
            [['text' => '🔙 بازگشت', 'callback_data' => 'menu:wallet']],
        ]];
    }
}
