<?php
declare(strict_types=1);

/**
 * HamGap bot handlers — registration is click-only menus where possible.
 * CODE_VERSION bumps on every deploy so we can verify updates landed.
 */
final class Handlers
{
    public const CODE_VERSION = '2026-08-14-v3';

    private string $assets;

    public function __construct(
        private array $config,
        private Database $db,
        private Telegram $tg,
        private Matcher $matcher
    ) {
        $this->assets = rtrim((string)$config['assets_path'], '/');
    }

    public function handle(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->onCallback($update['callback_query']);
            return;
        }
        if (isset($update['message'])) {
            $this->onMessage($update['message']);
        }
    }

    private function botName(): string
    {
        return htmlspecialchars((string)$this->config['bot_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function welcomeText(): string
    {
        $name = $this->botName();
        return "سلام 😊 عزیز ✋\n\n" .
            "به 《 <b>{$name}</b> 》 خوش اومدی ، توی این ربات می‌تونی افراد نزدیکت رو پیدا کنی و باهاشون آشنا شی " .
            "یا به یه نفر بصورت <b>ناشناس</b> وصل شی و باهاش <b>چت</b> کنی ❗️\n\n" .
            "استفاده از این ربات رایگانه و اطلاعات تلگرام شما مثل اسم، عکس پروفایل یا موقعیت GPS کاملاً محرمانه هست 😎";
    }

    private function onMessage(array $message): void
    {
        $chatId = (int)$message['chat']['id'];
        $from = $message['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        if ($tid <= 0) {
            return;
        }

        $user = $this->db->upsertUser(
            $tid,
            $from['username'] ?? null,
            $from['first_name'] ?? null
        );

        if (($user['status'] ?? '') === 'banned') {
            $this->tg->sendMessage($chatId, 'حساب شما مسدود شده است.');
            return;
        }

        $text = trim((string)($message['text'] ?? ''));

        if ($text === '/start' || str_starts_with($text, '/start ') || $text === '🏠 منوی اصلی') {
            $this->ensureProfileOrMain($chatId, $user, true);
            return;
        }

        // Waiting for custom city text
        if (($user['status'] ?? '') === 'idle' && ($user['search_pref'] ?? '') === 'reg:city_other') {
            $this->saveCity($chatId, $user, $text);
            return;
        }

        if (!$this->isProfileComplete($user)) {
            // Force click menus — ignore free text except custom city above.
            $this->ensureProfileOrMain($chatId, $user, false);
            return;
        }

        if (($user['status'] ?? '') === 'chatting' && !empty($user['partner_id'])) {
            if (in_array($text, ['🛑 پایان چت', '🛑 پایان', '/end'], true)) {
                $this->endAndMenu($chatId, $user);
                return;
            }
            if (in_array($text, ['⏭ بعدی', '/next'], true)) {
                $this->nextChat($chatId, $user);
                return;
            }
            if (in_array($text, ['🚩 گزارش', '/report'], true)) {
                $this->report($chatId, $user);
                return;
            }
            $this->tg->copyMessage((int)$user['partner_id'], $chatId, (int)$message['message_id']);
            return;
        }

        // Edit flows that still need text (custom city only; age/gender are buttons)
        if (($user['status'] ?? '') === 'idle' && ($user['search_pref'] ?? '') === 'edit:city_other') {
            $city = mb_substr(trim($text), 0, 64);
            if (mb_strlen($city) < 2) {
                $this->tg->sendMessage($chatId, 'نام شهر معتبر بفرست.');
                return;
            }
            $this->db->updateUser($tid, ['city' => $city, 'search_pref' => null]);
            $this->showProfile($chatId, $this->db->findUser($tid) ?? $user);
            return;
        }

        match ($text) {
            '🎲 چت تصادفی' => $this->startChat($chatId, $user, 'any'),
            '💬 چت هوشمند' => $this->showSmart($chatId),
            '👤 پروفایل' => $this->showProfile($chatId, $user),
            '💎 کیف سکه' => $this->showWallet($chatId, $user),
            'ℹ️ راهنما' => $this->showHelp($chatId),
            '🆘 پشتیبانی' => $this->showSupport($chatId),
            default => $this->showMain($chatId, 'از منوی زیر یک گزینه را لمس کن 🙂'),
        };
    }

    private function onCallback(array $cq): void
    {
        $id = (string)$cq['id'];
        $data = (string)($cq['data'] ?? '');
        $message = $cq['message'] ?? [];
        $chatId = (int)($message['chat']['id'] ?? 0);
        $from = $cq['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        $user = $this->db->upsertUser($tid, $from['username'] ?? null, $from['first_name'] ?? null);

        if (($user['status'] ?? '') === 'banned') {
            $this->tg->answerCallback($id, 'مسدود شده‌اید', true);
            return;
        }

        // --- Registration / profile field callbacks (always allowed) ---
        if (str_starts_with($data, 'reg:gender:')) {
            $g = substr($data, strlen('reg:gender:'));
            if (!in_array($g, ['male', 'female'], true)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $editing = $this->isProfileComplete($user);
            $this->db->updateUser($tid, ['gender' => $g, 'search_pref' => null]);
            $this->tg->answerCallback($id, $g === 'female' ? 'دختر ✅' : 'پسر ✅');
            if ($editing) {
                $this->showProfile($chatId, $this->db->findUser($tid) ?? $user);
                return;
            }
            $this->askAgeMenu($chatId);
            return;
        }

        if (str_starts_with($data, 'reg:age:')) {
            $age = (int)substr($data, strlen('reg:age:'));
            if ($age < 13 || $age > 80) {
                $this->tg->answerCallback($id, 'سن نامعتبر', true);
                return;
            }
            $wasComplete = $this->isProfileComplete($user);
            $this->db->updateUser($tid, ['age' => $age, 'search_pref' => null]);
            $this->tg->answerCallback($id, "سن {$age} ✅");
            $fresh = $this->db->findUser($tid) ?? $user;
            if ($wasComplete) {
                $this->showProfile($chatId, $fresh);
                return;
            }
            if ($this->isProfileComplete($fresh)) {
                $this->finishRegistration($chatId, $fresh);
                return;
            }
            $this->askCityMenu($chatId);
            return;
        }

        if ($data === 'reg:city:other') {
            $this->db->updateUser($tid, ['search_pref' => 'reg:city_other']);
            $this->tg->answerCallback($id);
            $this->tg->sendMessage($chatId, "نام شهرت را بنویس و ارسال کن:");
            return;
        }

        if (str_starts_with($data, 'reg:city:')) {
            $city = substr($data, strlen('reg:city:'));
            $city = mb_substr(trim($city), 0, 64);
            if (mb_strlen($city) < 2) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $this->tg->answerCallback($id, "{$city} ✅");
            $this->saveCity($chatId, $user, $city);
            return;
        }

        // Incomplete profile: only registration callbacks above
        $user = $this->db->findUser($tid) ?? $user;
        if (!$this->isProfileComplete($user)) {
            $this->tg->answerCallback($id, 'اول پروفایل را کامل کن', true);
            $this->ensureProfileOrMain($chatId, $user, false);
            return;
        }

        switch ($data) {
            case 'menu:main':
                $this->tg->answerCallback($id);
                $this->showMain($chatId);
                break;
            case 'menu:smart':
                $this->tg->answerCallback($id);
                $this->showSmart($chatId);
                break;
            case 'menu:profile':
                $this->tg->answerCallback($id);
                $this->showProfile($chatId, $user);
                break;
            case 'menu:edit_profile':
                $this->tg->answerCallback($id);
                $this->tg->sendMessage($chatId, "📝 <b>ویرایش پروفایل</b>\nکدام مورد را می‌خواهی تغییر بدهی؟", [
                    'reply_markup' => Keyboards::editProfileInline(),
                ]);
                break;
            case 'menu:wallet':
                $this->tg->answerCallback($id);
                $this->showWallet($chatId, $user);
                break;
            case 'menu:help':
                $this->tg->answerCallback($id);
                $this->showHelp($chatId);
                break;
            case 'menu:support':
                $this->tg->answerCallback($id);
                $this->showSupport($chatId);
                break;
            case 'chat:any':
                $this->tg->answerCallback($id);
                $this->startChat($chatId, $user, 'any');
                break;
            case 'chat:male':
                $this->tg->answerCallback($id);
                $this->startChat($chatId, $user, 'male');
                break;
            case 'chat:female':
                $this->tg->answerCallback($id);
                $this->startChat($chatId, $user, 'female');
                break;
            case 'chat:cancel':
                $this->matcher->cancelSearch($user);
                $this->tg->answerCallback($id, 'جستجو لغو شد');
                $this->showMain($chatId, 'جستجو لغو شد.');
                break;
            case 'chat:end':
                $this->tg->answerCallback($id);
                $this->endAndMenu($chatId, $user);
                break;
            case 'chat:next':
                $this->tg->answerCallback($id);
                $this->nextChat($chatId, $user);
                break;
            case 'chat:report':
                $this->tg->answerCallback($id, 'گزارش ثبت شد');
                $this->report($chatId, $user);
                break;
            case 'edit:gender':
                $this->tg->answerCallback($id);
                $this->tg->sendMessage($chatId, "جنسیت جدید را انتخاب کن 👇", [
                    'reply_markup' => Keyboards::gender(),
                ]);
                break;
            case 'edit:age':
                $this->tg->answerCallback($id);
                $this->tg->sendMessage($chatId, "سن جدید را انتخاب کن 👇", [
                    'reply_markup' => Keyboards::age(),
                ]);
                break;
            case 'edit:city':
                $this->tg->answerCallback($id);
                $this->tg->sendMessage($chatId, "شهر جدید را انتخاب کن 👇", [
                    'reply_markup' => Keyboards::city(),
                ]);
                break;
            case 'pay:soon':
                $this->tg->answerCallback($id, 'پرداخت به‌زودی فعال می‌شود', true);
                break;
            default:
                $this->tg->answerCallback($id);
        }
    }

    private function isProfileComplete(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        return !empty($user['gender']) && !empty($user['age']) && !empty($user['city']);
    }

    private function ensureProfileOrMain(int $chatId, array $user, bool $withWelcome): void
    {
        if ($this->isProfileComplete($user)) {
            $this->showMain($chatId, $withWelcome ? 'دوباره خوش اومدی 🌿' : '');
            return;
        }

        if ($withWelcome || empty($user['gender'])) {
            $this->tg->sendMessage($chatId, $this->welcomeText(), [
                'reply_markup' => Keyboards::removeReply(),
            ]);
        }

        if (empty($user['gender'])) {
            $this->askGenderMenu($chatId);
            return;
        }
        if (empty($user['age'])) {
            $this->askAgeMenu($chatId);
            return;
        }
        $this->askCityMenu($chatId);
    }

    private function askGenderMenu(int $chatId): void
    {
        $this->tg->sendMessage($chatId, "برای شروع بهم بگو دختری یا پسری؟ 👇", [
            'reply_markup' => Keyboards::gender(),
        ]);
    }

    private function askAgeMenu(int $chatId): void
    {
        $this->tg->sendMessage($chatId, "عالی ✅\nحالا سنت رو از منوی زیر انتخاب کن 👇", [
            'reply_markup' => Keyboards::age(),
        ]);
    }

    private function askCityMenu(int $chatId): void
    {
        $this->tg->sendMessage($chatId, "شهر خودت رو از منوی زیر انتخاب کن 👇", [
            'reply_markup' => Keyboards::city(),
        ]);
    }

    private function saveCity(int $chatId, array $user, string $city): void
    {
        $tid = (int)$user['telegram_id'];
        $city = mb_substr(trim($city), 0, 64);
        if (mb_strlen($city) < 2) {
            $this->tg->sendMessage($chatId, 'نام شهر معتبر بفرست یا از منو انتخاب کن.', [
                'reply_markup' => Keyboards::city(),
            ]);
            return;
        }
        $wasComplete = $this->isProfileComplete($user);
        $this->db->updateUser($tid, ['city' => $city, 'search_pref' => null]);
        $fresh = $this->db->findUser($tid) ?? $user;
        if ($wasComplete) {
            $this->showProfile($chatId, $fresh);
            return;
        }
        if ($this->isProfileComplete($fresh)) {
            $this->finishRegistration($chatId, $fresh);
            return;
        }
        $this->ensureProfileOrMain($chatId, $fresh, false);
    }

    private function finishRegistration(int $chatId, array $user): void
    {
        $this->showMain(
            $chatId,
            "پروفایل آماده شد ✅\n۳ سکه هدیه گرفتی.\nحالا می‌تونی چت رو شروع کنی."
        );
    }

    private function showHelp(int $chatId): void
    {
        $name = $this->botName();
        $this->tg->sendMessage(
            $chatId,
            "📘 <b>راهنمای {$name}</b>\n\n" .
            "🔻 <b>{$name} چیه؟</b>\n" .
            "ربات چت ناشناس برای حرف‌زدن با افراد جدید — بدون نمایش هویت.\n\n" .
            "🔻 <b>چجوری رایگان استفاده کنم؟</b>\n" .
            "از «🎲 چت تصادفی» رایگان و نامحدود چت کن.\n\n" .
            "🔻 <b>چت پسر / دختر؟</b>\n" .
            "از «💬 چت هوشمند» — هر اتصال موفق ۱ سکه.\n\n" .
            "🔻 <b>ویرایش پروفایل</b>\n" .
            "منو → 👤 پروفایل → 📝 ویرایش پروفایل\n\n" .
            "🔻 <b>پشتیبانی</b>\n" .
            "منو → 🆘 پشتیبانی\n\n" .
            "⚠️ رمز، کارت بانکی و لینک مشکوک را نفرست."
        );
    }

    private function showSupport(int $chatId): void
    {
        $this->tg->sendMessage(
            $chatId,
            "🆘 <b>پشتیبانی هم‌گپ</b>\n\n" .
            "اگر مشکل، انتقاد یا پیشنهادی داری از همین بخش پیام بده.\n" .
            "پشتیبانی فقط از داخل ربات است."
        );
    }

    private function showMain(int $chatId, string $extra = ''): void
    {
        $caption = trim(
            ($extra !== '' ? $extra . "\n\n" : '') .
            "《 <b>{$this->botName()}</b> 》\nچت ناشناس · امن · سریع"
        );
        $path = $this->assets . '/menu-main.jpg';
        if (is_file($path)) {
            $this->tg->sendPhoto($chatId, $path, $caption, Keyboards::mainInline());
        } else {
            $this->tg->sendMessage($chatId, $caption, [
                'reply_markup' => Keyboards::mainInline(),
            ]);
        }
        $this->tg->sendMessage($chatId, 'منوی سریع پایین صفحه 👇', [
            'reply_markup' => Keyboards::mainReply(),
        ]);
    }

    private function showSmart(int $chatId): void
    {
        $path = $this->assets . '/menu-smart.jpg';
        $caption = "💬 <b>چت هوشمند</b>\nمخاطبت رو از منوی زیر انتخاب کن.\nهزینه فقط بعد از اتصال موفق کم می‌شود.";
        if (is_file($path)) {
            $this->tg->sendPhoto($chatId, $path, $caption, Keyboards::smartInline());
        } else {
            $this->tg->sendMessage($chatId, $caption, [
                'reply_markup' => Keyboards::smartInline(),
            ]);
        }
    }

    private function showProfile(int $chatId, array $user): void
    {
        $g = $user['gender'] === 'female' ? 'دختر' : ($user['gender'] === 'male' ? 'پسر' : '-');
        $text = "👤 <b>پروفایل من</b>\n" .
            "جنسیت: <b>{$g}</b>\n" .
            "سن: <b>" . ($user['age'] ?? '-') . "</b>\n" .
            "شهر: <b>" . htmlspecialchars((string)($user['city'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
            "سکه: <b>" . (int)$user['coins'] . "</b>";
        $this->tg->sendMessage($chatId, $text, [
            'reply_markup' => Keyboards::profileInline(),
        ]);
    }

    private function showWallet(int $chatId, array $user): void
    {
        $path = $this->assets . '/menu-wallet.jpg';
        $caption = "💎 موجودی: <b>" . (int)$user['coins'] . "</b> سکه\nاز منوی زیر پکیج را انتخاب کن.";
        if (is_file($path)) {
            $this->tg->sendPhoto($chatId, $path, $caption, Keyboards::walletInline());
        } else {
            $this->tg->sendMessage($chatId, $caption, [
                'reply_markup' => Keyboards::walletInline(),
            ]);
        }
    }

    private function startChat(int $chatId, array $user, string $pref): void
    {
        $result = $this->matcher->startSearch($user, $pref);
        if (!($result['ok'] ?? false) && ($result['error'] ?? '') === 'no_coins') {
            $this->tg->sendMessage($chatId, "سکه کافی نداری.\nاز کیف سکه شارژ کن یا چت تصادفی رایگان برو.");
            $this->showWallet($chatId, $user);
            return;
        }

        if (!empty($result['matched'])) {
            $this->notifyConnected($chatId, (int)$result['partner']['telegram_id']);
            return;
        }

        $label = $pref === 'male' ? 'پسر' : ($pref === 'female' ? 'دختر' : 'تصادفی');
        $this->tg->sendMessage(
            $chatId,
            "🔍 در حال پیدا کردن مخاطب ({$label})...\nلطفاً صبر کن.",
            ['reply_markup' => Keyboards::searching()]
        );
    }

    private function notifyConnected(int $a, int $b): void
    {
        $path = $this->assets . '/menu-chat.jpg';
        $caption = "✅ وصل شدید!\nهویت‌ها مخفی است · محترمانه گفتگو کنید.";
        foreach ([$a, $b] as $cid) {
            if (is_file($path)) {
                $this->tg->sendPhoto($cid, $path, $caption, Keyboards::chattingInline());
            } else {
                $this->tg->sendMessage($cid, $caption, [
                    'reply_markup' => Keyboards::chattingInline(),
                ]);
            }
            $this->tg->sendMessage($cid, 'چت فعال شد. پیام بفرست 👇', [
                'reply_markup' => Keyboards::chattingReply(),
            ]);
        }
    }

    private function endAndMenu(int $chatId, array $user): void
    {
        $partnerId = $this->matcher->endChat($user, true);
        $this->tg->sendMessage($chatId, 'چت پایان یافت.');
        if ($partnerId) {
            $this->tg->sendMessage($partnerId, 'طرف مقابل چت را پایان داد.');
            $this->showMain($partnerId);
        }
        $this->showMain($chatId);
    }

    private function nextChat(int $chatId, array $user): void
    {
        $partnerId = $this->matcher->endChat($user, true);
        if ($partnerId) {
            $this->tg->sendMessage($partnerId, 'طرف مقابل رفت سراغ نفر بعدی.');
            $this->showMain($partnerId);
        }
        $fresh = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $this->startChat($chatId, $fresh, 'any');
    }

    private function report(int $chatId, array $user): void
    {
        $partnerId = !empty($user['partner_id']) ? (int)$user['partner_id'] : null;
        if ($partnerId) {
            $this->db->pdo()->prepare(
                'INSERT INTO reports (reporter_id, reported_id, reason) VALUES (?, ?, ?)'
            )->execute([(int)$user['telegram_id'], $partnerId, 'user_report']);
        }
        $this->endAndMenu($chatId, $user);
    }
}
