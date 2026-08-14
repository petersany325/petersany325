<?php
declare(strict_types=1);

final class Keyboards
{
    public static function mainInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '🎲 چت تصادفی', 'callback_data' => 'chat:any'],
                ['text' => '💬 چت هوشمند', 'callback_data' => 'menu:smart'],
            ],
            [
                ['text' => '👤 پروفایل', 'callback_data' => 'menu:profile'],
                ['text' => '💎 کیف سکه', 'callback_data' => 'menu:wallet'],
            ],
            [
                ['text' => 'ℹ️ راهنما', 'callback_data' => 'menu:help'],
                ['text' => '🆘 پشتیبانی', 'callback_data' => 'menu:support'],
            ],
        ]];
    }

    public static function mainReply(): array
    {
        return [
            'keyboard' => [
                [['text' => '🎲 چت تصادفی'], ['text' => '💬 چت هوشمند']],
                [['text' => '👤 پروفایل'], ['text' => '💎 کیف سکه']],
                [['text' => 'ℹ️ راهنما'], ['text' => '🆘 پشتیبانی']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }

    public static function removeReply(): array
    {
        return ['remove_keyboard' => true];
    }

    public static function smartInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '👨 چت با پسر · ۱ سکه', 'callback_data' => 'chat:male']],
            [['text' => '👩 چت با دختر · ۱ سکه', 'callback_data' => 'chat:female']],
            [['text' => '🎲 چت تصادفی · رایگان', 'callback_data' => 'chat:any']],
            [['text' => '🔙 بازگشت', 'callback_data' => 'menu:main']],
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

    /** All Iran provinces — index callbacks stay under Telegram 64-byte limit */
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

    /** Cities of selected province + other + back to provinces */
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
            [['text' => '📝 ویرایش پروفایل', 'callback_data' => 'menu:edit_profile']],
            [['text' => '🔙 بازگشت', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function editProfileInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '✏️ جنسیت', 'callback_data' => 'edit:gender'],
                ['text' => '✏️ سن', 'callback_data' => 'edit:age'],
            ],
            [
                ['text' => '✏️ استان/شهر', 'callback_data' => 'edit:location'],
                ['text' => '🔙 پروفایل', 'callback_data' => 'menu:profile'],
            ],
        ]];
    }

    public static function walletInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '۱۰ سکه — ۲۰٬۰۰۰ تومان', 'callback_data' => 'pay:soon']],
            [['text' => '۳۰ سکه — ۵۰٬۰۰۰ تومان ★', 'callback_data' => 'pay:soon']],
            [['text' => '۱۰۰ سکه — ۱۴۰٬۰۰۰ تومان', 'callback_data' => 'pay:soon']],
            [['text' => '🔙 بازگشت', 'callback_data' => 'menu:main']],
        ]];
    }
}
