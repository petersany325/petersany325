<?php
declare(strict_types=1);

final class Keyboards
{
    public static function mainReply(int $inviteReward = 30): array
    {
        return [
            'keyboard' => [
                [['text' => '💬 چت ناشناس']],
                [['text' => '🔍 جستجوی کاربران'], ['text' => '👫 چت با دوستان']],
                [['text' => '👤 پروفایل'], ['text' => '💎 کیف‌پول']],
                [['text' => '⭐ استار کلاب'], ['text' => "✨ دعوت دوستان · +{$inviteReward}"]],
                [['text' => '🆘 پشتیبانی'], ['text' => 'ℹ️ راهنما']],
                [['text' => '⌨️ کیبورد تایپ']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
            'input_field_placeholder' => 'از منوی هم‌گپ انتخاب کن…',
        ];
    }

    /** Shown while typing is needed — one button to restore HamGap menu. */
    public static function backToMenuReply(): array
    {
        return [
            'keyboard' => [
                [['text' => '📂 منوی هم‌گپ']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
            'input_field_placeholder' => 'اینجا بنویس…',
        ];
    }

    public static function typeModeInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '📂 بازگشت به منوی هم‌گپ', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function mainInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '💬 چت ناشناس · رایگان', 'callback_data' => 'menu:connect']],
            [
                ['text' => '🔍 جستجوی کاربران', 'callback_data' => 'menu:find'],
                ['text' => '👫 چت با دوستان', 'callback_data' => 'menu:friends'],
            ],
            [
                ['text' => '👤 پروفایل', 'callback_data' => 'menu:profile'],
                ['text' => '💎 کیف‌پول', 'callback_data' => 'menu:wallet'],
            ],
            [['text' => '⭐ استار کلاب', 'callback_data' => 'menu:vip']],
            [
                ['text' => '🆘 پشتیبانی', 'callback_data' => 'menu:support'],
                ['text' => 'ℹ️ راهنما', 'callback_data' => 'menu:help'],
            ],
            [['text' => '✨ دعوت دوستان و سکه رایگان', 'callback_data' => 'menu:invite']],
        ]];
    }

    public static function helpInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '💬 چت ناشناس', 'callback_data' => 'menu:connect']],
            [['text' => '🔍 جستجوی کاربران', 'callback_data' => 'menu:find'],
                ['text' => '👫 چت با دوستان', 'callback_data' => 'menu:friends']],
            [['text' => '💎 کیف‌پول', 'callback_data' => 'menu:wallet'],
                ['text' => '👤 پروفایل', 'callback_data' => 'menu:profile']],
            [
                ['text' => 'ℹ️ راهنما', 'callback_data' => 'menu:help'],
                ['text' => '🆘 پشتیبانی', 'callback_data' => 'menu:support'],
            ],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function removeReply(): array
    {
        return ['remove_keyboard' => true];
    }

    /** Modern search hub — calm & structured (not competitor green spam). */
    public static function searchHubInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🟢 آنلاین الان', 'callback_data' => 'sr:online']],
            [['text' => '🎞 نمایش کشویی کاربران', 'callback_data' => 'sr:slide']],
            [
                ['text' => '✨ تازه‌واردها', 'callback_data' => 'sr:new'],
                ['text' => '📍 نزدیک من', 'callback_data' => 'sr:nearby'],
            ],
            [
                ['text' => '🏙 هم‌استان', 'callback_data' => 'sr:sameprov'],
                ['text' => '🎂 هم‌سن', 'callback_data' => 'sr:sameage'],
            ],
            [['text' => '🎓 تخصص / تحصیلات', 'callback_data' => 'sr:occ']],
            [['text' => '⭐ فقط اعضای استار کلاب', 'callback_data' => 'sr:vip']],
            [['text' => '🎛 جستجوی پیشرفته', 'callback_data' => 'sr:advanced']],
            [
                ['text' => '👩 آنلاین دختر', 'callback_data' => 'sr:online:female'],
                ['text' => '👨 آنلاین پسر', 'callback_data' => 'sr:online:male'],
            ],
            [['text' => '🌈 آنلاین شیمیل / دوجنسه', 'callback_data' => 'sr:online:shemale']],
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
            [['text' => '🌈 شیمیل / دوجنسه', 'callback_data' => 'adv:gender:shemale']],
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
            [['text' => '🌈 شیمیل / دوجنسه · رایگان', 'callback_data' => 'find:gender:shemale']],
            [['text' => 'همه · رایگان', 'callback_data' => 'find:gender:any']],
            [['text' => 'بازگشت', 'callback_data' => 'menu:find']],
        ]];
    }

    public static function gender(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '👩 دختر', 'callback_data' => 'reg:gender:female'],
                ['text' => '👨 پسر', 'callback_data' => 'reg:gender:male'],
            ],
            [['text' => '🌈 شیمیل / دوجنسه', 'callback_data' => 'reg:gender:shemale']],
        ]];
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
            [
                ['text' => '📥 درخواست‌ها / رزرو', 'callback_data' => 'req:inbox'],
                ['text' => 'گزارش تخلف', 'callback_data' => 'chat:report'],
            ],
        ]];
    }

    public static function chattingReply(): array
    {
        return [
            'keyboard' => [
                [['text' => '⏭ بعدی'], ['text' => '🛑 پایان چت']],
                [['text' => '📥 درخواست‌ها'], ['text' => '🚩 گزارش']],
                [['text' => '📂 منوی هم‌گپ']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
            'input_field_placeholder' => 'پیام چت را بنویس…',
        ];
    }

    /** @return array<string,string> reason_key => label */
    public static function reportReasonLabels(): array
    {
        return [
            'harass' => 'ایجاد مزاحمت',
            'insult' => 'فحاشی و توهین',
            'wrong_gender' => 'جنسیت اشتباه',
            'spam' => 'تبلیغ ربات یا کانال',
            'nsfw' => 'محتوای غیراخلاقی',
            'privacy' => 'پخش شماره یا اطلاعات شخصی',
            'bad_profile' => 'کلمه یا عکس نامناسب در پروفایل',
            'other' => 'سایر موارد (توضیح بده)',
        ];
    }

    public static function reportReasonsInline(string $publicCode, string $prefix = 'br'): array
    {
        $code = preg_replace('/[^A-Za-z0-9_]/', '', $publicCode) ?? '';
        $rows = [];
        foreach (self::reportReasonLabels() as $key => $label) {
            $rows[] = [['text' => $label, 'callback_data' => "{$prefix}:rp:{$code}:{$key}"]];
        }
        $rows[] = [['text' => 'انصراف', 'callback_data' => "{$prefix}:rc:{$code}"]];
        return ['inline_keyboard' => $rows];
    }

    public static function chatReportReasonsInline(): array
    {
        $rows = [];
        foreach (self::reportReasonLabels() as $key => $label) {
            $rows[] = [['text' => $label, 'callback_data' => 'cr:' . $key]];
        }
        $rows[] = [['text' => 'انصراف', 'callback_data' => 'chat:continue']];
        return ['inline_keyboard' => $rows];
    }

    /** Profile card actions after search — clear choices for the user. */
    public static function browseProfileInline(
        string $publicCode,
        int $requestCost,
        int $messageCost,
        int $likeCost = 0,
        int $likeCount = 0,
        int $notifyCost = 1
    ): array {
        $code = preg_replace('/[^A-Za-z0-9_]/', '', $publicCode) ?? '';
        $likeLabel = $likeCost > 0
            ? "🤍 لایک · {$likeCount} (−{$likeCost})"
            : "🤍 لایک · {$likeCount}";
        $waitLabel = "چت کردنش تموم شد بهم خبر بده 🔔 · {$notifyCost}🪙";
        return ['inline_keyboard' => [
            [['text' => $likeLabel, 'callback_data' => 'br:like:' . $code]],
            [
                ['text' => "پیام مستقیم · {$messageCost}🪙", 'callback_data' => 'br:msg:' . $code],
                ['text' => "درخواست چت · {$requestCost}🪙", 'callback_data' => 'br:req:' . $code],
            ],
            [
                ['text' => 'گزارش تخلف 🚨', 'callback_data' => 'br:rep:' . $code],
                ['text' => 'بلاک 🚫', 'callback_data' => 'br:blk:' . $code],
            ],
            [['text' => $waitLabel, 'callback_data' => 'br:wait:' . $code]],
            [
                ['text' => '➕ دوست', 'callback_data' => 'br:friend:' . $code],
                ['text' => 'کاربر بعدی ⏭', 'callback_data' => 'br:next'],
            ],
            [
                ['text' => '📋 فهرست', 'callback_data' => 'br:list'],
                ['text' => 'حالت نمایش', 'callback_data' => 'vw:pick'],
            ],
            [['text' => 'پایان جستجو', 'callback_data' => 'menu:find']],
        ]];
    }

    /** Compact actions under photo-mode previews. */
    public static function browsePhotoInline(
        string $publicCode,
        int $requestCost,
        int $messageCost,
        int $index
    ): array {
        $code = preg_replace('/[^A-Za-z0-9_]/', '', $publicCode) ?? '';
        return ['inline_keyboard' => [
            [['text' => 'باز کردن کارت کامل', 'callback_data' => 'bl:o:' . $index]],
            [
                ['text' => "پیام · {$messageCost}🪙", 'callback_data' => 'br:msg:' . $code],
                ['text' => "درخواست · {$requestCost}🪙", 'callback_data' => 'br:req:' . $code],
            ],
            [
                ['text' => 'گزارش تخلف 🚨', 'callback_data' => 'br:rep:' . $code],
                ['text' => 'بلاک 🚫', 'callback_data' => 'br:blk:' . $code],
            ],
            [['text' => '🤍 لایک', 'callback_data' => 'br:like:' . $code]],
        ]];
    }

    public static function chatRequestInline(int $requestId): array
    {
        $id = (string)$requestId;
        return ['inline_keyboard' => [
            [
                ['text' => '✅ قبول چت', 'callback_data' => 'req:ok:' . $id],
                ['text' => '⏳ رزرو', 'callback_data' => 'req:hold:' . $id],
            ],
            [['text' => '❌ رد', 'callback_data' => 'req:no:' . $id]],
        ]];
    }

    public static function roomCreateConfirmInline(int $cost): array
    {
        return ['inline_keyboard' => [
            [['text' => "✅ بله، بساز (−{$cost} سکه)", 'callback_data' => 'fr:create:go']],
            [['text' => 'انصراف', 'callback_data' => 'menu:friends']],
        ]];
    }

    public static function browseViewPicker(int $found): array
    {
        return ['inline_keyboard' => [
            [['text' => "✅ شروع با کارت تکی ({$found} نفر)", 'callback_data' => 'vw:card']],
            [['text' => '🎞 نمایش کشویی ستونی (گرافیکی)', 'callback_data' => 'vw:slide']],
            [
                ['text' => '📋 فهرست عددی', 'callback_data' => 'vw:list'],
                ['text' => '🖼 چندتایی با عکس', 'callback_data' => 'vw:photo'],
            ],
            [['text' => '🎛 انتخاب از منوی اسم‌ها', 'callback_data' => 'vw:menu']],
            [['text' => 'جستجوی جدید', 'callback_data' => 'menu:find']],
        ]];
    }

    public static function slideNavInline(int $index, int $total): array
    {
        $total = max(1, $total);
        $index = max(0, min($index, $total - 1));
        $nav = [];
        if ($index > 0) {
            $nav[] = ['text' => '‹ قبلی', 'callback_data' => 'sl:' . ($index - 1)];
        }
        $nav[] = ['text' => ($index + 1) . ' / ' . $total, 'callback_data' => 'vw:noop'];
        if ($index + 1 < $total) {
            $nav[] = ['text' => 'بعدی ›', 'callback_data' => 'sl:' . ($index + 1)];
        }
        return ['inline_keyboard' => [
            $nav,
            [
                ['text' => 'حالت نمایش', 'callback_data' => 'vw:pick'],
                ['text' => 'پایان', 'callback_data' => 'menu:find'],
            ],
        ]];
    }

    /** Opens Telegram inline search UI (swipeable graphical list). */
    public static function openInlineSlide(string $query = ''): array
    {
        return ['inline_keyboard' => [
            [['text' => '🎞 باز کردن نمایش کشویی', 'switch_inline_query_current_chat' => $query]],
            [['text' => '🎞 کشویی داخل ربات', 'callback_data' => 'vw:slide']],
            [['text' => 'بازگشت', 'callback_data' => 'vw:pick']],
        ]];
    }

    public static function browseListNav(int $page, int $totalPages): array
    {
        $rows = [];
        $nav = [];
        if ($page > 0) {
            $nav[] = ['text' => '‹ قبلی', 'callback_data' => 'bl:p:' . ($page - 1)];
        }
        $nav[] = ['text' => ($page + 1) . '/' . max(1, $totalPages), 'callback_data' => 'vw:noop'];
        if ($page + 1 < $totalPages) {
            $nav[] = ['text' => 'بعدی ›', 'callback_data' => 'bl:p:' . ($page + 1)];
        }
        $rows[] = $nav;
        $rows[] = [
            ['text' => 'حالت نمایش', 'callback_data' => 'vw:pick'],
            ['text' => 'پایان', 'callback_data' => 'menu:find'],
        ];
        return ['inline_keyboard' => $rows];
    }

    /** @param list<array{i:int,label:string}> $items */
    public static function browseMenuGrid(array $items, int $page, int $totalPages): array
    {
        $rows = [];
        $row = [];
        foreach ($items as $it) {
            $row[] = ['text' => $it['label'], 'callback_data' => 'bl:o:' . $it['i']];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $rows[] = $row;
        }
        $nav = [];
        if ($page > 0) {
            $nav[] = ['text' => '‹', 'callback_data' => 'bl:p:' . ($page - 1)];
        }
        $nav[] = ['text' => ($page + 1) . '/' . max(1, $totalPages), 'callback_data' => 'vw:noop'];
        if ($page + 1 < $totalPages) {
            $nav[] = ['text' => '›', 'callback_data' => 'bl:p:' . ($page + 1)];
        }
        $rows[] = $nav;
        $rows[] = [
            ['text' => 'حالت نمایش', 'callback_data' => 'vw:pick'],
            ['text' => 'پایان', 'callback_data' => 'menu:find'],
        ];
        return ['inline_keyboard' => $rows];
    }

    public static function profileInline(int $inviteReward = 30): array
    {
        return ['inline_keyboard' => [
            [['text' => '✏️ ویرایش مشخصات', 'callback_data' => 'menu:profile_settings']],
            [['text' => '📸 آلبوم عکس', 'callback_data' => 'menu:album']],
            [
                ['text' => '🖼 عکس عمومی', 'callback_data' => 'edit:avatar'],
                ['text' => '👁 حریم خصوصی', 'callback_data' => 'pr:home'],
            ],
            [
                ['text' => '🔤 نام کاربری', 'callback_data' => 'edit:namehub'],
                ['text' => '📝 بیو / معرفی', 'callback_data' => 'edit:bio'],
            ],
            [
                ['text' => '🚫 بلاک‌شده‌ها', 'callback_data' => 'menu:blocks'],
                ['text' => '📥 درخواست‌های چت', 'callback_data' => 'req:inbox'],
            ],
            [['text' => "دعوت دوستان · +{$inviteReward} سکه", 'callback_data' => 'menu:invite']],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function occupationInline(bool $allowSkip = true, string $back = 'menu:profile_settings'): array
    {
        $rows = [
            [
                ['text' => '🩺 پزشک / کادر درمان', 'callback_data' => 'occ:set:doctor'],
                ['text' => '🛠 مهندس', 'callback_data' => 'occ:set:engineer'],
            ],
            [
                ['text' => '🎓 فارغ‌التحصیل', 'callback_data' => 'occ:set:graduate'],
                ['text' => '📚 دانشجو', 'callback_data' => 'occ:set:student'],
            ],
            [
                ['text' => '⚖️ حقوق / وکالت', 'callback_data' => 'occ:set:lawyer'],
                ['text' => '💼 کسب‌وکار', 'callback_data' => 'occ:set:business'],
            ],
            [
                ['text' => '🎨 هنر / رسانه', 'callback_data' => 'occ:set:arts'],
                ['text' => '✨ سایر', 'callback_data' => 'occ:set:other'],
            ],
            [['text' => 'پاک کردن تخصص', 'callback_data' => 'occ:clear']],
        ];
        if ($allowSkip) {
            $rows[] = [['text' => 'فعلاً رد شو', 'callback_data' => 'occ:skip']];
        }
        $rows[] = [['text' => 'بازگشت', 'callback_data' => $back]];
        return ['inline_keyboard' => $rows];
    }

    public static function occupationSearchInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '🩺 پزشک', 'callback_data' => 'sr:occ:doctor'],
                ['text' => '🛠 مهندس', 'callback_data' => 'sr:occ:engineer'],
            ],
            [
                ['text' => '🎓 فارغ‌التحصیل', 'callback_data' => 'sr:occ:graduate'],
                ['text' => '📚 دانشجو', 'callback_data' => 'sr:occ:student'],
            ],
            [
                ['text' => '⚖️ حقوق', 'callback_data' => 'sr:occ:lawyer'],
                ['text' => '💼 کسب‌وکار', 'callback_data' => 'sr:occ:business'],
            ],
            [
                ['text' => '🎨 هنر', 'callback_data' => 'sr:occ:arts'],
                ['text' => 'همه با تخصص', 'callback_data' => 'sr:occ:any'],
            ],
            [['text' => 'بازگشت به جستجو', 'callback_data' => 'menu:find']],
        ]];
    }

    public static function profileSettingsInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '📸 آلبوم عکس', 'callback_data' => 'menu:album']],
            [['text' => '🖼 عکس عمومی (برای همه)', 'callback_data' => 'edit:avatar']],
            [['text' => '🔤 نام کاربری (دستی / خودکار)', 'callback_data' => 'edit:namehub']],
            [['text' => '📝 بیو / معرفی کوتاه', 'callback_data' => 'edit:bio']],
            [['text' => '🎓 تخصص / تحصیلات', 'callback_data' => 'edit:occupation']],
            [
                ['text' => 'جنسیت', 'callback_data' => 'edit:gender'],
                ['text' => 'سن', 'callback_data' => 'edit:age'],
            ],
            [['text' => 'استان / شهر', 'callback_data' => 'edit:location']],
            [['text' => '👁 حریم خصوصی و نمایش', 'callback_data' => 'pr:home']],
            [['text' => 'بازگشت به پروفایل', 'callback_data' => 'menu:profile']],
        ]];
    }

    public static function albumHubInline(int $albumCount, int $maxAlbum, bool $hasPublic): array
    {
        $pub = $hasPublic ? '✅ دارد' : '❌ ندارد';
        return ['inline_keyboard' => [
            [['text' => "🖼 عکس عمومی ({$pub})", 'callback_data' => 'edit:avatar']],
            [['text' => "➕ افزودن عکس دوستان ({$albumCount}/{$maxAlbum})", 'callback_data' => 'alb:add']],
            [['text' => '🗑 مدیریت / حذف عکس دوستان', 'callback_data' => 'alb:manage']],
            [['text' => '👀 پیش‌نمایش آلبوم من', 'callback_data' => 'alb:preview']],
            [['text' => 'بازگشت به پروفایل', 'callback_data' => 'menu:profile']],
        ]];
    }

    public static function albumManageInline(array $photos): array
    {
        $kb = [];
        $i = 1;
        foreach ($photos as $p) {
            $pid = (int)($p['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $kb[] = [['text' => "🗑 حذف عکس {$i}", 'callback_data' => 'alb:del:' . $pid]];
            $i++;
        }
        $kb[] = [['text' => 'بازگشت به آلبوم', 'callback_data' => 'menu:album']];
        return ['inline_keyboard' => $kb];
    }

    public static function nameHubInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '✍️ نوشتن نام دستی', 'callback_data' => 'edit:displayname']],
            [['text' => '🎲 ساخت نام خودکار هم‌گپ', 'callback_data' => 'edit:nameauto']],
            [['text' => 'بازگشت', 'callback_data' => 'menu:profile_settings']],
        ]];
    }

    public static function privacyHomeInline(array $user): array
    {
        $vis = (string)($user['profile_visibility'] ?? 'public');
        $mark = static function (string $key, array $user): string {
            return ((int)($user[$key] ?? 1) === 1) ? '✅' : '🚫';
        };
        $visLabel = match ($vis) {
            'hidden' => 'مخفی کامل',
            'friends' => 'فقط دوستان',
            default => 'عمومی',
        };
        return ['inline_keyboard' => [
            [['text' => "وضعیت پروفایل: {$visLabel}", 'callback_data' => 'vw:noop']],
            [
                ['text' => '🌍 عمومی', 'callback_data' => 'pr:vis:public'],
                ['text' => '🔒 مخفی', 'callback_data' => 'pr:vis:hidden'],
            ],
            [['text' => '👥 فقط دوستان / خاص', 'callback_data' => 'pr:vis:friends']],
            [['text' => $mark('show_gender', $user) . ' جنسیت', 'callback_data' => 'pr:tog:show_gender']],
            [['text' => $mark('show_occupation', $user) . ' تخصص / تحصیلات', 'callback_data' => 'pr:tog:show_occupation']],
            [
                ['text' => $mark('show_age', $user) . ' سن', 'callback_data' => 'pr:tog:show_age'],
                ['text' => $mark('show_online', $user) . ' آنلاین', 'callback_data' => 'pr:tog:show_online'],
            ],
            [
                ['text' => $mark('show_province', $user) . ' استان', 'callback_data' => 'pr:tog:show_province'],
                ['text' => $mark('show_city', $user) . ' شهر', 'callback_data' => 'pr:tog:show_city'],
            ],
            [['text' => $mark('show_avatar', $user) . ' عکس عمومی', 'callback_data' => 'pr:tog:show_avatar']],
            [['text' => '📸 مدیریت آلبوم', 'callback_data' => 'menu:album']],
            [['text' => 'بازگشت به پروفایل', 'callback_data' => 'menu:profile']],
        ]];
    }

    public static function busyNotifyInline(string $publicCode, int $notifyCost = 1): array
    {
        $code = preg_replace('/[^A-Za-z0-9_]/', '', $publicCode) ?? '';
        return ['inline_keyboard' => [
            [['text' => "🔔 خبرم کن وقتی آزاد شد · {$notifyCost}🪙", 'callback_data' => 'br:wait:' . $code]],
            [
                ['text' => 'کاربر بعدی', 'callback_data' => 'br:next'],
                ['text' => 'جستجو', 'callback_data' => 'menu:find'],
            ],
        ]];
    }

    public static function freeNowInline(string $publicCode, int $requestCost = 1): array
    {
        $code = preg_replace('/[^A-Za-z0-9_]/', '', $publicCode) ?? '';
        return ['inline_keyboard' => [
            [['text' => "درخواست چت · {$requestCost}🪙", 'callback_data' => 'br:req:' . $code]],
            [['text' => 'جستجوی کاربران', 'callback_data' => 'menu:find']],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function needCoinsInline(int $inviteReward = 30): array
    {
        return ['inline_keyboard' => [
            [['text' => "✨ دعوت دوستان و دریافت سکه رایگان (+{$inviteReward})", 'callback_data' => 'menu:invite']],
            [['text' => '💳 خرید سکه 💰', 'callback_data' => 'menu:wallet']],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function walletInline(int $inviteReward = 30, array $packPrices = []): array
    {
        $rows = [
            [['text' => "دعوت دوست · +{$inviteReward} سکه", 'callback_data' => 'menu:invite']],
        ];
        foreach (CoinCatalog::packs() as $p) {
            $price = (int)($packPrices[$p['id']] ?? $p['default_price']);
            $fmt = self::formatToman($price);
            $extra = !empty($p['badge']) ? " ({$p['badge']})" : '';
            $rows[] = [['text' => "{$p['label']}{$extra} — {$fmt} تومان", 'callback_data' => 'pay:pack:' . $p['id']]];
        }
        $rows[] = [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']];
        return ['inline_keyboard' => $rows];
    }

    public static function formatToman(int $amount): string
    {
        return number_format($amount, 0, '', '٬');
    }

    public static function connectModeInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '💬 چت معمولی', 'callback_data' => 'mode:normal']],
            [['text' => '🔥 چت هات', 'callback_data' => 'mode:hot']],
            [['text' => '⭐ کلاب VIP', 'callback_data' => 'mode:vipclub']],
            [['text' => 'بازگشت', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function chatRulesInline(string $mode): array
    {
        $mode = preg_replace('/[^a-z]/', '', $mode) ?? 'normal';
        return ['inline_keyboard' => [
            [['text' => '✅ قوانین را خواندم — ورود', 'callback_data' => 'mode:ok:' . $mode]],
            [['text' => 'انصراف', 'callback_data' => 'menu:connect']],
        ]];
    }

    public static function connectInline(array $costs = [], string $mode = 'normal'): array
    {
        $any = (int)($costs['any'] ?? 0);
        $gender = (int)($costs['gender'] ?? 0);
        $prov = (int)($costs['province'] ?? 0);
        $age = (int)($costs['age'] ?? 0);
        $mode = preg_replace('/[^a-z]/', '', $mode) ?: 'normal';
        $lab = static function (string $title, int $cost): string {
            return $cost > 0 ? "{$title} · {$cost}🪙" : "{$title} · رایگان";
        };
        $p = static function (string $pref) use ($mode): string {
            return 'chat:' . $mode . ':' . $pref;
        };
        return ['inline_keyboard' => [
            [['text' => $lab('🎲 چت شانسی', $any), 'callback_data' => $p('any')]],
            [
                ['text' => $lab('👩 فقط دختر', $gender), 'callback_data' => $p('female')],
                ['text' => $lab('👨 فقط پسر', $gender), 'callback_data' => $p('male')],
            ],
            [['text' => $lab('🌈 فقط شیمیل / دوجنسه', $gender), 'callback_data' => $p('shemale')]],
            [['text' => $lab('🏙 هم‌استان', $prov), 'callback_data' => $p('province')]],
            [['text' => $lab('🎂 هم‌سن', $age), 'callback_data' => $p('age')]],
            [['text' => 'تغییر نوع چت', 'callback_data' => 'menu:connect']],
            [['text' => 'بازگشت', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function payInvoiceInline(int $invoiceId): array
    {
        $id = (string)$invoiceId;
        return ['inline_keyboard' => [
            [['text' => '📋 کپی مبلغ (ریال)', 'callback_data' => 'pay:copyamt:' . $id]],
            [['text' => '💳 کپی شماره کارت', 'callback_data' => 'pay:copycard']],
            [['text' => '📷 ارسال فیش واریزی', 'callback_data' => 'pay:receipt:' . $id]],
            [['text' => 'بازگشت به کیف‌پول', 'callback_data' => 'menu:wallet']],
        ]];
    }

    public static function payAdminReviewInline(int $invoiceId): array
    {
        $id = (string)$invoiceId;
        return ['inline_keyboard' => [
            [
                ['text' => '✅ تأیید پول انجام شد — شارژ سکه', 'callback_data' => 'payadm:ok:' . $id],
                ['text' => '❌ تأیید پول انجام نشد', 'callback_data' => 'payadm:no:' . $id],
            ],
        ]];
    }

    public static function adminPayHome(): array
    {
        $rows = [
            [['text' => 'شماره کارت', 'callback_data' => 'adm:set:pay_card_number']],
            [['text' => 'نام صاحب حساب', 'callback_data' => 'adm:set:pay_card_holder']],
            [['text' => 'نام بانک', 'callback_data' => 'adm:set:pay_bank_name']],
            [['text' => 'کانال رضایت (اختیاری)', 'callback_data' => 'adm:set:pay_trust_channel']],
            [['text' => 'اعتبار فاکتور (دقیقه)', 'callback_data' => 'adm:set:pay_invoice_minutes']],
        ];
        foreach (CoinCatalog::packs() as $p) {
            $rows[] = [['text' => 'قیمت ' . $p['label'], 'callback_data' => 'adm:set:' . $p['price_key']]];
        }
        $rows[] = [['text' => '📥 فیش‌های در انتظار', 'callback_data' => 'adm:pay:pending']];
        $rows[] = [['text' => 'بازگشت', 'callback_data' => 'adm:home']];
        return ['inline_keyboard' => $rows];
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

    public static function supportTicketOffer(int $ticketId): array
    {
        return ['inline_keyboard' => [
            [['text' => '✅ پذیرش پشتیبانی و باز شدن چت', 'callback_data' => 'sup:take:' . $ticketId]],
        ]];
    }

    public static function supportChatActions(int $ticketId): array
    {
        return ['inline_keyboard' => [
            [['text' => '🏁 پایان گفتگوی پشتیبانی', 'callback_data' => 'sup:close:' . $ticketId]],
            [['text' => '🛠 پنل کارمند', 'callback_data' => 'stf:home']],
        ]];
    }

    public static function staffLogin(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🔐 ورود به پنل کارمند', 'callback_data' => 'stf:login']],
        ]];
    }

    public static function staffMain(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🔎 جستجوی کاربر', 'callback_data' => 'stf:find']],
            [
                ['text' => '🆕 کاربران اخیر', 'callback_data' => 'stf:recent'],
                ['text' => '🚫 مسدودها', 'callback_data' => 'stf:banned'],
            ],
            [['text' => '💬 پیام / چت با کاربر', 'callback_data' => 'stf:chat']],
            [['text' => '🆘 تیکت‌های باز', 'callback_data' => 'stf:tickets']],
            [['text' => '🔑 تغییر رمز پنل', 'callback_data' => 'stf:pwd']],
            [['text' => '🚪 خروج از پنل', 'callback_data' => 'stf:logout']],
        ]];
    }

    public static function staffUserActions(int $telegramId, bool $banned = false): array
    {
        $id = (string)$telegramId;
        $banRow = $banned
            ? [['text' => '✅ رفع مسدود', 'callback_data' => 'stf:unban:' . $id]]
            : [['text' => '🚫 مسدود کردن', 'callback_data' => 'stf:ban:' . $id]];
        return ['inline_keyboard' => [
            $banRow,
            [
                ['text' => '✏️ تغییر نام', 'callback_data' => 'stf:rename:' . $id],
                ['text' => '💬 پیام', 'callback_data' => 'stf:msg:' . $id],
            ],
            [['text' => '♻️ ریست پروفایل عمومی', 'callback_data' => 'stf:wipe:' . $id]],
            [['text' => 'خانه پنل', 'callback_data' => 'stf:home']],
        ]];
    }

    public static function staffBannedItem(int $telegramId): array
    {
        $id = (string)$telegramId;
        return ['inline_keyboard' => [
            [
                ['text' => '👤 کارت', 'callback_data' => 'stf:card:' . $id],
                ['text' => '✅ رفع مسدود', 'callback_data' => 'stf:unban:' . $id],
            ],
        ]];
    }

    public static function friendsInline(int $createCost = 5): array
    {
        return ['inline_keyboard' => [
            [['text' => "🏠 ساخت گپ گروهی (−{$createCost} سکه)", 'callback_data' => 'fr:create']],
            [['text' => '🔑 ورود با کد گپ', 'callback_data' => 'fr:join']],
            [['text' => '📂 گپ‌های من', 'callback_data' => 'fr:list']],
            [['text' => '👥 لیست دوستان', 'callback_data' => 'fr:friends']],
            [['text' => 'لینک دعوت من', 'callback_data' => 'menu:invite']],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function roomActiveInline(string $code, bool $isPrivate = false, int $addCost = 2): array
    {
        $code = preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '';
        $rows = [];
        if ($isPrivate) {
            $rows[] = [['text' => "➕ افزودن نفر (−{$addCost} سکه)", 'callback_data' => 'prv:add']];
            $rows[] = [['text' => '👥 اعضای صفحه', 'callback_data' => 'fr:members']];
            $rows[] = [['text' => '🚪 ترک صفحه اختصاصی', 'callback_data' => 'fr:leave']];
            $rows[] = [['text' => '🗑 بستن صفحه', 'callback_data' => 'fr:close']];
        } else {
            $rows[] = [['text' => '👥 اعضای گپ', 'callback_data' => 'fr:members']];
            $rows[] = [['text' => '🗑 بستن و پاک‌سازی کامل', 'callback_data' => 'fr:close']];
            $rows[] = [['text' => '🚪 ترک گپ', 'callback_data' => 'fr:leave']];
            $rows[] = [['text' => 'منوی دوستان', 'callback_data' => 'menu:friends']];
        }
        $rows[] = [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']];
        return ['inline_keyboard' => $rows];
    }

    public static function requestLocationInline(): array
    {
        return [
            'keyboard' => [
                [['text' => '📍 ارسال موقعیت مکانی', 'request_location' => true]],
                [['text' => '📂 منوی هم‌گپ']],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];
    }

    public static function profileNudgeInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '👤 تکمیل پروفایل', 'callback_data' => 'menu:profile']],
            [['text' => '🖼 افزودن عکس', 'callback_data' => 'edit:avatar']],
            [['text' => '📍 تأیید موقعیت GPS', 'callback_data' => 'gps:share']],
            [['text' => 'بعداً', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function friendRequestInline(int $fromTid): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '✅ قبول', 'callback_data' => 'frnd:ok:' . $fromTid],
                ['text' => '❌ رد', 'callback_data' => 'frnd:no:' . $fromTid],
            ],
        ]];
    }

    public static function vipHubInline(bool $eligible, bool $active, bool $pending): array
    {
        $rows = [];
        if ($active) {
            $rows[] = [['text' => '🔁 تمدید اشتراک ۳۰ روزه', 'callback_data' => 'vip:buy']];
        } elseif ($eligible) {
            $rows[] = [['text' => '💳 خرید اشتراک استار کلاب', 'callback_data' => 'vip:buy']];
        } elseif ($pending) {
            $rows[] = [['text' => '⏳ درخواست در صف بررسی ادمین', 'callback_data' => 'vip:noop']];
        } else {
            $rows[] = [['text' => '📨 درخواست بررسی برای عضویت', 'callback_data' => 'vip:request']];
        }
        $rows[] = [['text' => '⭐ جستجوی اعضای استار', 'callback_data' => 'sr:vip']];
        $rows[] = [
            ['text' => '🎓 تکمیل تخصص', 'callback_data' => 'edit:occupation'],
            ['text' => '👤 پروفایل', 'callback_data' => 'menu:profile'],
        ];
        $rows[] = [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']];
        return ['inline_keyboard' => $rows];
    }

    public static function adminMain(): array
    {
        return ['inline_keyboard' => [
            [['text' => '📊 آمار لحظه‌ای', 'callback_data' => 'adm:stats']],
            [['text' => '🪙 سکه و هزینه‌ها', 'callback_data' => 'adm:coins']],
            [['text' => '⭐ استار کلاب', 'callback_data' => 'adm:vip']],
            [['text' => '👥 مدیریت کاربران', 'callback_data' => 'adm:users']],
            [['text' => '🚩 گزارش‌ها', 'callback_data' => 'adm:reports']],
            [['text' => '🆘 پشتیبانی و کارمندان', 'callback_data' => 'adm:support']],
            [['text' => '⚙️ تنظیمات عمومی', 'callback_data' => 'adm:general']],
            [['text' => '💳 پرداخت کارت‌به‌کارت', 'callback_data' => 'adm:pay']],
            [['text' => '🛡 امنیت و رمز', 'callback_data' => 'adm:security']],
            [['text' => '🚪 خروج از پنل', 'callback_data' => 'adm:logout']],
        ]];
    }

    public static function adminVip(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'قیمت ۳۰ روز', 'callback_data' => 'adm:set:vip_price_30']],
            [['text' => 'مدت اشتراک (روز)', 'callback_data' => 'adm:set:vip_days']],
            [
                ['text' => 'حداقل روز عضویت', 'callback_data' => 'adm:set:vip_min_account_days'],
                ['text' => 'حداقل لایک', 'callback_data' => 'adm:set:vip_min_likes'],
            ],
            [['text' => 'حداکثر گزارش مجاز', 'callback_data' => 'adm:set:vip_max_reports']],
            [
                ['text' => 'اجبار تخصص ۰/۱', 'callback_data' => 'adm:set:vip_require_occupation'],
                ['text' => 'اجبار عکس ۰/۱', 'callback_data' => 'adm:set:vip_require_avatar'],
            ],
            [['text' => '📥 درخواست‌های در صف', 'callback_data' => 'adm:vip:pending']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }

    public static function adminVipRequestActions(int $telegramId): array
    {
        $id = (string)$telegramId;
        return ['inline_keyboard' => [
            [
                ['text' => '✅ واجد شرایط + ۳۰ روز هدیه', 'callback_data' => 'adm:vip:ok:' . $id],
                ['text' => '❌ رد', 'callback_data' => 'adm:vip:no:' . $id],
            ],
            [['text' => 'بازگشت', 'callback_data' => 'adm:vip']],
        ]];
    }

    public static function adminSecurity(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'تغییر رمز عبور', 'callback_data' => 'adm:pwd:change']],
            [['text' => 'تغییر نام کاربری', 'callback_data' => 'adm:set:admin_username']],
            [['text' => 'مدت نشست (ساعت)', 'callback_data' => 'adm:set:admin_session_hours']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }

    public static function inviteHubInline(string $shareUrl): array
    {
        return ['inline_keyboard' => [
            [['text' => '📤 اشتراک‌گذاری برای دوستان', 'url' => $shareUrl]],
            [['text' => '📋 متن آماده کپی', 'callback_data' => 'invite:copy']],
            [['text' => '🏆 وضعیت جایزه‌ها', 'callback_data' => 'invite:milestones']],
            [
                ['text' => '💎 کیف‌پول', 'callback_data' => 'menu:wallet'],
                ['text' => 'منوی اصلی', 'callback_data' => 'menu:main'],
            ],
        ]];
    }

    public static function inviteCopyDoneInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '📤 اشتراک‌گذاری', 'callback_data' => 'menu:invite']],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu:main']],
        ]];
    }

    public static function adminCoins(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'پاداش دعوت (هر نفر)', 'callback_data' => 'adm:set:invite_reward']],
            [
                ['text' => 'بونوس ۳ دعوت', 'callback_data' => 'adm:set:invite_milestone_3'],
                ['text' => 'بونوس ۱۰', 'callback_data' => 'adm:set:invite_milestone_10'],
            ],
            [['text' => 'بونوس ۲۵ دعوت', 'callback_data' => 'adm:set:invite_milestone_25']],
            [['text' => 'هدیه دعوت‌شونده', 'callback_data' => 'adm:set:invitee_bonus']],
            [['text' => 'هزینه پیام بدون درخواست', 'callback_data' => 'adm:set:message_cost']],
            [['text' => 'هزینه درخواست چت', 'callback_data' => 'adm:set:request_cost']],
            [['text' => 'هزینه لایک', 'callback_data' => 'adm:set:like_cost']],
            [['text' => 'ساخت گپ گروهی', 'callback_data' => 'adm:set:room_create_cost']],
            [['text' => 'ورود به گپ (هر نفر)', 'callback_data' => 'adm:set:room_join_cost']],
            [['text' => 'هزینه خبر آزاد شدن از چت', 'callback_data' => 'adm:set:notify_free_cost']],
            [['text' => 'بلاک خودکار بعد از چند گزارش؟', 'callback_data' => 'adm:set:report_ban_threshold']],
            [['text' => 'سکه خوش‌آمد', 'callback_data' => 'adm:set:welcome_coins']],
            [['text' => 'هزینه چت شانسی', 'callback_data' => 'adm:set:connect_any_cost']],
            [['text' => 'هزینه فیلتر جنسیت', 'callback_data' => 'adm:set:connect_gender_cost']],
            [['text' => 'هزینه هم‌استان', 'callback_data' => 'adm:set:connect_province_cost']],
            [['text' => 'هزینه هم‌سن', 'callback_data' => 'adm:set:connect_age_cost']],
            [['text' => 'هشدار کمبود سکه (آستانه)', 'callback_data' => 'adm:set:low_coin_warn']],
            [['text' => 'بونوس تکمیل پروفایل', 'callback_data' => 'adm:set:profile_complete_bonus']],
            [['text' => 'ورود صفحه چت خصوصی', 'callback_data' => 'adm:set:private_room_entry_cost']],
            [['text' => 'افزودن نفر به صفحه خصوصی', 'callback_data' => 'adm:set:private_room_add_cost']],
            [['text' => 'کلمات ممنوع VIP (با ویرگول)', 'callback_data' => 'adm:set:vip_bad_words']],
            [['text' => 'متن قوانین چت معمولی', 'callback_data' => 'adm:set:rules_normal']],
            [['text' => 'متن قوانین چت هات', 'callback_data' => 'adm:set:rules_hot']],
            [['text' => 'متن قوانین کلاب VIP', 'callback_data' => 'adm:set:rules_vipclub']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }

    public static function adminUsers(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🔎 جستجوی کاربر', 'callback_data' => 'adm:user:find']],
            [['text' => '🆕 کاربران اخیر', 'callback_data' => 'adm:users:recent']],
            [['text' => '🚫 لیست مسدودها', 'callback_data' => 'adm:users:banned']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }

    public static function adminUserActions(
        int $telegramId,
        bool $isSupportStaffActive = false,
        bool $isSupportStaffRow = false,
        bool $isPanelAdmin = false
    ): array {
        $id = (string)$telegramId;
        $staffBtn = $isSupportStaffActive
            ? ['text' => '🆘 کارمند پشتیبانی: فعال ← غیرفعال', 'callback_data' => 'adm:staff:off:' . $id]
            : ['text' => '🆘 کارمند پشتیبانی: غیرفعال ← فعال', 'callback_data' => 'adm:staff:on:' . $id];
        if (!$isSupportStaffRow && !$isSupportStaffActive) {
            $staffBtn = ['text' => '🆘 افزودن و فعال‌سازی کارمند پشتیبانی', 'callback_data' => 'adm:staff:on:' . $id];
        }
        $adminBtn = $isPanelAdmin
            ? ['text' => '🛡 پرچم ادمین: روشن ← خاموش', 'callback_data' => 'adm:flagadmin:off:' . $id]
            : ['text' => '🛡 پرچم ادمین: خاموش ← روشن', 'callback_data' => 'adm:flagadmin:on:' . $id];
        return ['inline_keyboard' => [
            [
                ['text' => '🚫 مسدود', 'callback_data' => 'adm:ban:' . $id],
                ['text' => '✅ رفع مسدود', 'callback_data' => 'adm:unban:' . $id],
            ],
            [$staffBtn],
            [$adminBtn],
            [
                ['text' => '+۵۰ سکه', 'callback_data' => 'adm:give:' . $id . ':50'],
                ['text' => '−۵۰ سکه', 'callback_data' => 'adm:take:' . $id . ':50'],
            ],
            [['text' => 'تنظیم موجودی سکه', 'callback_data' => 'adm:setcoins:' . $id]],
            [
                ['text' => 'ویرایش نام', 'callback_data' => 'adm:editask:display_name:' . $id],
                ['text' => 'ویرایش سن', 'callback_data' => 'adm:editask:age:' . $id],
            ],
            [
                ['text' => 'ویرایش جنسیت', 'callback_data' => 'adm:editask:gender:' . $id],
                ['text' => 'ویرایش شهر', 'callback_data' => 'adm:editask:city:' . $id],
            ],
            [['text' => 'ویرایش استان', 'callback_data' => 'adm:editask:province:' . $id]],
            [['text' => 'پیام به کاربر', 'callback_data' => 'adm:msg:' . $id]],
            [['text' => 'ریست پروفایل عمومی', 'callback_data' => 'adm:wipe:' . $id]],
            [['text' => '🗑 حذف کامل → ثبت‌نام دوباره با نام جدید', 'callback_data' => 'adm:delask:' . $id]],
            [['text' => 'بازگشت', 'callback_data' => 'adm:users']],
        ]];
    }

    public static function adminStaffListControls(array $rows): array
    {
        $kb = [];
        foreach ($rows as $r) {
            $sid = (int)($r['telegram_id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $active = (int)($r['is_active'] ?? 0) === 1;
            if ($active) {
                $kb[] = [
                    ['text' => "🔴 غیرفعال {$sid}", 'callback_data' => 'adm:staff:off:' . $sid],
                    ['text' => "🔑 رمز {$sid}", 'callback_data' => 'adm:staff:pwd:' . $sid],
                ];
            } else {
                $kb[] = [
                    ['text' => "🟢 فعال {$sid}", 'callback_data' => 'adm:staff:on:' . $sid],
                    ['text' => "🔑 رمز {$sid}", 'callback_data' => 'adm:staff:pwd:' . $sid],
                ];
            }
        }
        $kb[] = [['text' => 'افزودن کارمند جدید', 'callback_data' => 'adm:staff:add']];
        $kb[] = [['text' => 'بازگشت', 'callback_data' => 'adm:support']];
        return ['inline_keyboard' => $kb];
    }

    /** After ban / auto-ban — admin chooses keep banned, unban, or wipe for fresh start. */
    public static function adminBanDecision(int $telegramId): array
    {
        $id = (string)$telegramId;
        return ['inline_keyboard' => [
            [
                ['text' => '✅ رفع مسدود', 'callback_data' => 'adm:unban:' . $id],
                ['text' => '👤 کارت کاربر', 'callback_data' => 'adm:usercard:' . $id],
            ],
            [['text' => '🗑 حذف کامل برای ثبت‌نام دوباره', 'callback_data' => 'adm:delask:' . $id]],
        ]];
    }

    public static function adminConfirmDelete(int $telegramId): array
    {
        return ['inline_keyboard' => [
            [['text' => 'بله، حذف کامل و اجازه ثبت‌نام دوباره', 'callback_data' => 'adm:delgo:' . $telegramId]],
            [['text' => 'انصراف', 'callback_data' => 'adm:usercard:' . $telegramId]],
        ]];
    }

    public static function adminBannedListItem(int $telegramId, string $label): array
    {
        $id = (string)$telegramId;
        $short = mb_strlen($label) > 28 ? mb_substr($label, 0, 28) . '…' : $label;
        return ['inline_keyboard' => [
            [['text' => $short, 'callback_data' => 'adm:usercard:' . $id]],
            [
                ['text' => '✅ رفع مسدود', 'callback_data' => 'adm:unban:' . $id],
                ['text' => '🗑 حذف برای شروع دوباره', 'callback_data' => 'adm:delask:' . $id],
            ],
        ]];
    }

    public static function adminSupport(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'یوزرنیم بات پشتیبانی', 'callback_data' => 'adm:set:support_bot_username']],
            [['text' => 'ساعات پشتیبانی', 'callback_data' => 'adm:set:support_hours']],
            [['text' => 'متن خوش‌آمد پشتیبانی', 'callback_data' => 'adm:set:support_welcome']],
            [['text' => 'رمز پیش‌فرض کارمند', 'callback_data' => 'adm:set:staff_default_password']],
            [['text' => 'مدت نشست کارمند (ساعت)', 'callback_data' => 'adm:set:staff_session_hours']],
            [['text' => '➕ افزودن و فعال‌سازی کارمند', 'callback_data' => 'adm:staff:add']],
            [['text' => '👥 لیست / فعال‌سازی کارمندان', 'callback_data' => 'adm:staff:list']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }

    public static function adminReports(int $threshold = 10): array
    {
        return ['inline_keyboard' => [
            [['text' => "تغییر آستانه بلاک (الان {$threshold})", 'callback_data' => 'adm:set:report_ban_threshold']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }

    public static function adminGeneral(): array
    {
        return ['inline_keyboard' => [
            [['text' => 'نام برند', 'callback_data' => 'adm:set:brand_name']],
            [['text' => 'یوزرنیم بات اصلی', 'callback_data' => 'adm:set:main_bot_username']],
            [['text' => 'بلاک خودکار بعد از چند گزارش؟', 'callback_data' => 'adm:set:report_ban_threshold']],
            [['text' => 'بازگشت', 'callback_data' => 'adm:home']],
        ]];
    }
}
