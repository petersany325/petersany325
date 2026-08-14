<?php
declare(strict_types=1);

final class Handlers
{
    private string $assets;

    public function __construct(
        private array $config,
        private Database $db,
        private Telegram $tg,
        private Matcher $matcher
    ) {
        $this->assets = rtrim($config['assets_path'], '/');
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

        if ($text === '/start' || $text === '🏠 منوی اصلی') {
            $this->ensureProfileOrMain($chatId, $user);
            return;
        }

        if (!$this->isProfileComplete($user)) {
            $this->continueRegistration($chatId, $user, $text);
            return;
        }

        // In chat: relay or commands
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

        if ($text === '🎲 چت تصادفی') {
            $this->startChat($chatId, $user, 'any');
            return;
        }
        if ($text === '💬 چت هوشمند') {
            $this->showSmart($chatId);
            return;
        }
        if ($text === '👤 پروفایل') {
            $this->showProfile($chatId, $user);
            return;
        }
        if ($text === '💎 کیف سکه') {
            $this->showWallet($chatId, $user);
            return;
        }

        // editing flow stored in search_pref temporarily? use city/age free text when idle and waiting
        if (($user['status'] ?? '') === 'idle' && str_starts_with((string)($user['search_pref'] ?? ''), 'edit:')) {
            $this->applyEdit($chatId, $user, $text);
            return;
        }

        $this->showMain($chatId, 'از منو یک گزینه انتخاب کن 🙂');
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

        if (str_starts_with($data, 'reg:gender:')) {
            $g = substr($data, strlen('reg:gender:'));
            $this->db->updateUser($tid, ['gender' => $g]);
            $this->tg->answerCallback($id);
            $this->tg->sendMessage($chatId, "سن‌ات چند ساله؟\nمثلاً: <b>24</b>");
            return;
        }

        if (!$this->isProfileComplete($this->db->findUser($tid) ?? $user) && !str_starts_with($data, 'reg:')) {
            $this->tg->answerCallback($id, 'اول پروفایل را کامل کن', true);
            $this->ensureProfileOrMain($chatId, $this->db->findUser($tid) ?? $user);
            return;
        }

        $user = $this->db->findUser($tid) ?? $user;

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
                $this->tg->sendMessage(
                    $chatId,
                    "📝 <b>ویرایش پروفایل</b>\nکدام مورد را می‌خواهی تغییر بدهی؟",
                    ['reply_markup' => json_encode(Keyboards::editProfileInline(), JSON_UNESCAPED_UNICODE)]
                );
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
                $this->tg->sendMessage($chatId, 'جنسیت جدید را انتخاب کن:', [
                    'reply_markup' => json_encode(Keyboards::gender(), JSON_UNESCAPED_UNICODE),
                ]);
                break;
            case 'edit:age':
                $this->db->updateUser($tid, ['search_pref' => 'edit:age']);
                $this->tg->answerCallback($id);
                $this->tg->sendMessage($chatId, 'سن جدید را بفرست (مثلاً 24):');
                break;
            case 'edit:city':
                $this->db->updateUser($tid, ['search_pref' => 'edit:city']);
                $this->tg->answerCallback($id);
                $this->tg->sendMessage($chatId, 'نام شهرت را بفرست:');
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

    private function ensureProfileOrMain(int $chatId, array $user): void
    {
        if (!$this->isProfileComplete($user)) {
            $name = htmlspecialchars((string)$this->config['bot_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $welcome =
                "به <b>{$name}</b> خوش اومدی 👋\n\n" .
                "یه ربات چت ناشناس برای گپ‌زدن امن و سریع با آدم‌های جدید.\n\n" .
                "🎁 ۳ سکه هدیه برای شروع داری.\n" .
                "🎲 چت تصادفی هم کاملاً رایگان و نامحدوده.\n\n" .
                "اول پروفایلت رو بساز تا وصل شی.";

            // Always show the intro on /start during registration.
            if (empty($user['gender'])) {
                $this->tg->sendMessage(
                    $chatId,
                    $welcome . "\n\nپسر هستی یا دختر؟",
                    ['reply_markup' => json_encode(Keyboards::gender(), JSON_UNESCAPED_UNICODE)]
                );
                return;
            }
            if (empty($user['age'])) {
                $this->tg->sendMessage(
                    $chatId,
                    $welcome . "\n\nسن‌ات چند ساله؟\nمثلاً: <b>24</b>"
                );
                return;
            }
            $this->tg->sendMessage(
                $chatId,
                $welcome . "\n\nشهرت کجاست؟ مثلاً: تهران"
            );
            return;
        }
        $this->showMain($chatId, "دوباره خوش اومدی 🌿");
    }

    private function showHelp(int $chatId): void
    {
        $name = htmlspecialchars((string)$this->config['bot_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->tg->sendMessage(
            $chatId,
            "📘 <b>راهنمای {$name}</b>\n\n" .
            "🔻 <b>{$name} چیه؟</b>\n" .
            "ربات چت ناشناس برای حرف‌زدن و وقت‌گذرونی با افراد جدید — بدون نمایش هویت.\n\n" .
            "🔻 <b>چجوری رایگان استفاده کنم؟</b>\n" .
            "از «🎲 چت تصادفی» هرچقدر خواستی رایگان و نامحدود چت کن.\n\n" .
            "🔻 <b>چت پسر / دختر چی؟</b>\n" .
            "از «💬 چت هوشمند» انتخاب کن؛ هر اتصال موفق ۱ سکه کم می‌شود.\n\n" .
            "🔻 <b>پروفایلمو چجوری عوض کنم؟</b>\n" .
            "منو → 👤 پروفایل → 📝 ویرایش پروفایل\n\n" .
            "🔻 <b>سوال یا مشکل؟</b>\n" .
            "از منو → 🆘 پشتیبانی\n\n" .
            "⚠️ رمز، کارت بانکی و لینک مشکوک را برای کسی نفرست."
        );
    }

    private function showSupport(int $chatId): void
    {
        $this->tg->sendMessage(
            $chatId,
            "🆘 <b>پشتیبانی هم‌گپ</b>\n\n" .
            "اگر به مشکلی خوردی یا انتقاد و پیشنهادی داری، از همین بخش پیام بده.\n" .
            "پشتیبانی فقط از داخل ربات انجام می‌شود؛ به پیام‌های ناشناس با نام هم‌گپ اعتماد نکن."
        );
    }

    private function continueRegistration(int $chatId, array $user, string $text): void
    {
        $tid = (int)$user['telegram_id'];
        if (empty($user['gender'])) {
            $this->tg->sendMessage(
                $chatId,
                'لطفاً جنسیت را از دکمه‌ها انتخاب کن:',
                ['reply_markup' => json_encode(Keyboards::gender(), JSON_UNESCAPED_UNICODE)]
            );
            return;
        }
        if (empty($user['age'])) {
            if (!ctype_digit($text) || (int)$text < 13 || (int)$text > 80) {
                $this->tg->sendMessage($chatId, 'سن را به‌صورت عدد بین ۱۳ تا ۸۰ بفرست.');
                return;
            }
            $this->db->updateUser($tid, ['age' => (int)$text]);
            $this->tg->sendMessage($chatId, 'عالی! شهرت کجاست؟ مثلاً: تهران');
            return;
        }
        if (empty($user['city'])) {
            $city = mb_substr(trim($text), 0, 64);
            if (mb_strlen($city) < 2) {
                $this->tg->sendMessage($chatId, 'نام شهر معتبر بفرست.');
                return;
            }
            $this->db->updateUser($tid, ['city' => $city]);
            $fresh = $this->db->findUser($tid) ?? $user;
            $this->showMain(
                $chatId,
                "پروفایل آماده شد ✅\n۳ سکه هدیه گرفتی.\nبرای راهنما از دکمه ℹ️ راهنما استفاده کن."
            );
            return;
        }
    }

    private function applyEdit(int $chatId, array $user, string $text): void
    {
        $tid = (int)$user['telegram_id'];
        $mode = (string)$user['search_pref'];
        if ($mode === 'edit:age') {
            if (!ctype_digit($text) || (int)$text < 13 || (int)$text > 80) {
                $this->tg->sendMessage($chatId, 'سن نامعتبر است.');
                return;
            }
            $this->db->updateUser($tid, ['age' => (int)$text, 'search_pref' => null]);
            $this->showProfile($chatId, $this->db->findUser($tid) ?? $user);
            return;
        }
        if ($mode === 'edit:city') {
            $city = mb_substr(trim($text), 0, 64);
            $this->db->updateUser($tid, ['city' => $city, 'search_pref' => null]);
            $this->showProfile($chatId, $this->db->findUser($tid) ?? $user);
        }
    }

    private function showMain(int $chatId, string $extra = ''): void
    {
        $caption = trim(($extra ? $extra . "\n\n" : '') . "به دنیای <b>{$this->config['bot_name']}</b> خوش اومدی\nچت ناشناس · امن · سریع");
        $path = $this->assets . '/menu-main.jpg';
        $this->tg->sendPhoto($chatId, $path, $caption, Keyboards::mainInline());
        $this->tg->sendMessage($chatId, 'منوی سریع:', [
            'reply_markup' => json_encode(Keyboards::mainReply(), JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function showSmart(int $chatId): void
    {
        $path = $this->assets . '/menu-smart.jpg';
        $this->tg->sendPhoto(
            $chatId,
            $path,
            "💬 <b>چت هوشمند</b>\nمخاطبت رو انتخاب کن.\nهزینه فقط بعد از اتصال موفق کم می‌شود.",
            Keyboards::smartInline()
        );
    }

    private function showProfile(int $chatId, array $user): void
    {
        $g = $user['gender'] === 'female' ? 'دختر' : ($user['gender'] === 'male' ? 'پسر' : '-');
        $text = "👤 <b>پروفایل من</b>\n" .
            "جنسیت: <b>{$g}</b>\n" .
            "سن: <b>" . ($user['age'] ?? '-') . "</b>\n" .
            "شهر: <b>" . htmlspecialchars((string)($user['city'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
            "سکه: <b>" . (int)$user['coins'] . "</b>\n\n" .
            "برای تغییر اطلاعات، از «📝 ویرایش پروفایل» استفاده کن.";
        $this->tg->sendMessage($chatId, $text, [
            'reply_markup' => json_encode(Keyboards::profileInline(), JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function showWallet(int $chatId, array $user): void
    {
        $path = $this->assets . '/menu-wallet.jpg';
        $this->tg->sendPhoto(
            $chatId,
            $path,
            "💎 موجودی: <b>" . (int)$user['coins'] . "</b> سکه\nپرداخت آنلاین به‌زودی فعال می‌شود.",
            Keyboards::walletInline()
        );
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
            ['reply_markup' => json_encode(Keyboards::searching(), JSON_UNESCAPED_UNICODE)]
        );
    }

    private function notifyConnected(int $a, int $b): void
    {
        $path = $this->assets . '/menu-chat.jpg';
        $caption = "✅ وصل شدید!\nهویت‌ها مخفی است · محترمانه گفتگو کنید.";
        foreach ([$a, $b] as $cid) {
            $this->tg->sendPhoto($cid, $path, $caption, Keyboards::chattingInline());
            $this->tg->sendMessage($cid, 'چت فعال شد. پیام بفرست:', [
                'reply_markup' => json_encode(Keyboards::chattingReply(), JSON_UNESCAPED_UNICODE),
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
        $pref = 'any';
        // keep last intent unknown; default any. For paid, user reselects from smart menu.
        $partnerId = $this->matcher->endChat($user, true);
        if ($partnerId) {
            $this->tg->sendMessage($partnerId, 'طرف مقابل رفت سراغ نفر بعدی.');
            $this->showMain($partnerId);
        }
        $fresh = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $this->startChat($chatId, $fresh, $pref);
    }

    private function report(int $chatId, array $user): void
    {
        $partnerId = $user['partner_id'] ? (int)$user['partner_id'] : null;
        if ($partnerId) {
            $this->db->pdo()->prepare(
                'INSERT INTO reports (reporter_id, reported_id, reason) VALUES (?, ?, ?)'
            )->execute([(int)$user['telegram_id'], $partnerId, 'user_report']);
        }
        $this->endAndMenu($chatId, $user);
    }
}
