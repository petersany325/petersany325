<?php
declare(strict_types=1);

final class Keyboards
{
    public static function mainReply(): array
    {
        return [
            'keyboard' => [
                [['text' => '💬 چت ناشناس']],
                [['text' => '🔍 جستجوی کاربران'], ['text' => '👥 چت با دوستان']],
                [['text' => '👤 پروفایل'], ['text' => '💎 کیف‌پول']],
                [['text' => '✨ دعوت دوستان · +۳۰'], ['text' => '🆘 پشتیبانی']],
                [['text' => 'ℹ️ راهنما']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
    }

    public static function mainInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '💬 چت ناشناس · رایگان', 'callback_data' => 'menu:connect']],
            [
                ['text' => '🔍 جستجوی کاربران', 'callback_data' => 'menu:find'],
                ['text' => '👥 چت با دوستان', 'callback_data' => 'menu:friends'],
            ],
            [
                ['text' => '👤 پروفایل', 'callback_data' => 'menu:profile'],
                ['text' => '💎 کیف‌پول', 'callback_data' => 'menu:wallet'],
            ],
            [
                ['text' => '🆘 پشتیبانی', 'callback_data' => 'menu:support'],
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
        // Anonymous chat filters — free; monetize message/request elsewhere.
        return ['inline_keyboard' => [
            [['text' => '🎲 چت شانسی · رایگان', 'callback_data' => 'chat:any']],
            [
                ['text' => '👩 فقط دختر', 'callback_data' => 'chat:female'],
                ['text' => '👨 فقط پسر', 'callback_data' => 'chat:male'],
            ],
            [['text' => '🏙 هم‌استان', 'callback_data' => 'chat:province']],
            [['text' => '🎂 هم‌سن', 'callback_data' => 'chat:age']],
            [['text' => 'بازگشت', 'callback_data' => 'menu:main']],
        ]];
    }

    /** Modern search hub — calm & structured (not competitor green spam). */
    public static function searchHubInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🟢 آنلاین الان', 'callback_data' => 'sr:online']],
            [
                ['text' => '✨ تازه‌واردها', 'callback_data' => 'sr:new'],
                ['text' => '📍 نزدیک من', 'callback_data' => 'sr:nearby'],
            ],
            [
                ['text' => '🏙 هم‌استان', 'callback_data' => 'sr:sameprov'],
                ['text' => '🎂 هم‌سن', 'callback_data' => 'sr:sameage'],
            ],
            [['text' => '🎛 جستجوی پیشرفته', 'callback_data' => 'sr:advanced']],
            [
                ['text' => '👩 آنلاین دختر', 'callback_data' => 'sr:online:female'],
                ['text' => '👨 آنلاین پسر', 'callback_data' => 'sr:online:male'],
            ],
            [['text' => '💬 چت ناشناس سریع', 'callback_data' => 'menu:connect']],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function advancedGenderInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '👩 دختر', 'callback_data' => 'adv:gender:female'],
                ['text' => '👨 پسر', 'callback_data' => 'adv:gender:male'],
            ],
            [['text' => 'همه', 'callback_data' => 'adv:gender:any']],
            [['text' => 'بازگشت به جستجو', 'callback_data' => 'menu:find']],
        ]];
    }

    public static function advancedAgeInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '۱۶–۲۰', 'callback_data' => 'adv:age:16:20']],
            [['text' => '۲۱–۲۵', 'callback_data' => 'adv:age:21:25']],
            [['text' => '۲۶–۳۰', 'callback_data' => 'adv:age:26:30']],
            [['text' => '۳۱–۴۰', 'callback_data' => 'adv:age:31:40']],
            [['text' => 'همه سن‌ها', 'callback_data' => 'adv:age:any']],
            [['text' => 'بازگشت', 'callback_data' => 'sr:advanced']],
        ]];
    }

    public static function advancedProvinces(): array
    {
        $rows = [];
        $row = [];
        foreach (IranLocations::provinces() as $i => $name) {
            $row[] = ['text' => $name, 'callback_data' => 'adv:prov:' . $i];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $rows[] = $row;
        }
        $rows[] = [['text' => 'همه استان‌ها', 'callback_data' => 'adv:prov:all']];
        $rows[] = [['text' => 'بازگشت', 'callback_data' => 'menu:find']];
        return ['inline_keyboard' => $rows];
    }

    public static function advancedCities(string $province): array
    {
        $rows = [];
        $row = [];
        foreach (IranLocations::cities($province) as $i => $city) {
            $row[] = ['text' => $city, 'callback_data' => 'adv:ci:' . $i];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $rows[] = $row;
        }
        $rows[] = [['text' => 'همه شهرهای استان', 'callback_data' => 'adv:ci:all']];
        $rows[] = [['text' => 'تغییر استان', 'callback_data' => 'sr:advanced']];
        return ['inline_keyboard' => $rows];
    }

    public static function findGenderInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '👩 دختر · رایگان', 'callback_data' => 'find:gender:female'],
                ['text' => '👨 پسر · رایگان', 'callback_data' => 'find:gender:male'],
            ],
            [['text' => 'همه · رایگان', 'callback_data' => 'find:gender:any']],
            [['text' => 'بازگشت', 'callback_data' => 'menu:find']],
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
        $rows[] = [['text' => 'تغییر استان', 'callback_data' => 'reg:prov:back']];
        return ['inline_keyboard' => $rows];
    }

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
        $rows[] = [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']];
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
        $rows[] = [['text' => 'تغییر استان', 'callback_data' => 'menu:find']];
        return ['inline_keyboard' => $rows];
    }

    public static function searching(): array
    {
        return ['inline_keyboard' => [[
            ['text' => 'لغو جستجو', 'callback_data' => 'chat:cancel'],
        ]]];
    }

    public static function chattingInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => 'نفر بعدی', 'callback_data' => 'chat:next'],
                ['text' => 'پایان گفتگو', 'callback_data' => 'chat:end'],
            ],
            [['text' => 'گزارش', 'callback_data' => 'chat:report']],
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

    /** Modern profile card actions — minimal, not competitor-style green spam. */
    public static function browseProfileInline(string $publicCode, int $requestCost, int $messageCost): array
    {
        $code = preg_replace('/[^A-Za-z0-9_]/', '', $publicCode) ?? '';
        return ['inline_keyboard' => [
            [['text' => "درخواست گفتگو · {$requestCost} سکه", 'callback_data' => 'br:req:' . $code]],
            [['text' => "پیام کوتاه · {$messageCost} سکه", 'callback_data' => 'br:msg:' . $code]],
            [
                ['text' => 'بعدی', 'callback_data' => 'br:next'],
                ['text' => 'گزارش', 'callback_data' => 'br:rep:' . $code],
            ],
            [
                ['text' => 'بلاک', 'callback_data' => 'br:blk:' . $code],
                ['text' => 'پایان جستجو', 'callback_data' => 'menu:main'],
            ],
        ]];
    }

    public static function profileInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'تنظیمات پروفایل', 'callback_data' => 'menu:profile_settings']],
            [['text' => 'دعوت دوستان · +۳۰ سکه', 'callback_data' => 'menu:invite']],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function profileSettingsInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'عکس پروفایل', 'callback_data' => 'edit:avatar']],
            [['text' => 'نام کاربری', 'callback_data' => 'edit:displayname']],
            [
                ['text' => 'جنسیت', 'callback_data' => 'edit:gender'],
                ['text' => 'سن', 'callback_data' => 'edit:age'],
            ],
            [['text' => 'استان / شهر', 'callback_data' => 'edit:location']],
            [['text' => 'بازگشت به پروفایل', 'callback_data' => 'menu:profile']],
        ]];
    }

    public static function walletInline(int $inviteReward = 30): array
    {
        return ['inline_keyboard' => [
            [['text' => "دعوت دوست · +{$inviteReward} سکه", 'callback_data' => 'menu:invite']],
            [['text' => '۱۰۰ سکه — ۵۰٬۰۰۰ تومان', 'callback_data' => 'pay:100']],
            [['text' => '۳۰۰ سکه — ۱۲۰٬۰۰۰ تومان', 'callback_data' => 'pay:300']],
            [['text' => '۱۰۰۰ سکه — ۳۵۰٬۰۰۰ تومان', 'callback_data' => 'pay:1000']],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function payMethodInline(string $pack): array
    {
        return ['inline_keyboard' => [
            [['text' => 'درگاه بانکی (به‌زودی)', 'callback_data' => 'pay:soon']],
            [['text' => 'کارت‌به‌کارت (به‌زودی)', 'callback_data' => 'pay:soon']],
            [['text' => 'بازگشت', 'callback_data' => 'menu:wallet']],
        ]];
    }

    public static function supportInline(?string $supportUsername): array
    {
        $rows = [];
        if ($supportUsername) {
            $u = ltrim($supportUsername, '@');
            $rows[] = [['text' => 'گفتگو با پشتیبانی', 'url' => 'https://t.me/' . $u]];
        }
        $rows[] = [['text' => 'ارسال پیام اینجا', 'callback_data' => 'support:compose']];
        $rows[] = [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']];
        return ['inline_keyboard' => $rows];
    }

    public static function friendsInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'لینک دعوت من', 'callback_data' => 'menu:invite']],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function adminMain(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'آمار لحظه‌ای', 'callback_data' => 'adm:stats']],
            [['text' => 'تنظیمات سکه و هزینه', 'callback_data' => 'adm:coins']],
            [['text' => 'مدیریت کاربران', 'callback_data' => 'adm:users']],
            [['text' => 'پشتیبانی و کارمندان', 'callback_data' => 'adm:support']],
            [['text' => 'تنظیمات عمومی', 'callback_data' => 'adm:general']],
        ]];
    }

    public static function adminCoins(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'پاداش دعوت', 'callback_data' => 'adm:set:invite_reward']],
            [['text' => 'هزینه پیام', 'callback_data' => 'adm:set:message_cost']],
            [['text' => 'هزینه درخواست گفتگو', 'callback_data' => 'adm:set:request_cost']],
            [['text' => 'سکه خوش‌آمد', 'callback_data' => 'adm:set:welcome_coins']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }

    public static function adminUsers(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'جستجوی کاربر با کد/آیدی', 'callback_data' => 'adm:user:find']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }

    public static function adminUserActions(int $telegramId): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => 'مسدود', 'callback_data' => 'adm:ban:' . $telegramId],
                ['text' => 'رفع مسدود', 'callback_data' => 'adm:unban:' . $telegramId],
            ],
            [['text' => 'حذف پروفایل عمومی', 'callback_data' => 'adm:wipe:' . $telegramId]],
            [['text' => '+۵۰ سکه', 'callback_data' => 'adm:give:' . $telegramId . ':50']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:users']],
        ]];
    }

    public static function adminSupport(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'ثبت یوزرنیم بات پشتیبانی', 'callback_data' => 'adm:set:support_bot_username']],
            [['text' => 'افزودن کارمند (آیدی عددی)', 'callback_data' => 'adm:staff:add']],
            [['text' => 'لیست کارمندان', 'callback_data' => 'adm:staff:list']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }

    public static function adminGeneral(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'نام برند', 'callback_data' => 'adm:set:brand_name']],
            [['text' => 'ساعات پشتیبانی', 'callback_data' => 'adm:set:support_hours']],
            [['text' => 'متن خوش‌آمد پشتیبانی', 'callback_data' => 'adm:set:support_welcome']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }
}
