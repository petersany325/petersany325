<?php
declare(strict_types=1);

/**
 * HamGap handlers — brand onboarding + safe UI cleanup.
 * CODE_VERSION verifies deploys. Migrator keeps DB forward-compatible.
 */
final class Handlers
{
    public const CODE_VERSION = '2026-08-14-v7';

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

    private function welcomeTextFirst(): string
    {
        return "به هم‌گپ خوش اومدی 👋\n\n" .
            "یه ربات چت ناشناس برای گپ‌زدن امن و سریع با آدم‌های جدید.\n\n" .
            "🎁 ۳۵ سکه هدیه برای شروع داری.\n" .
            "🎲 چت تصادفی هم کاملاً رایگان و نامحدوده.\n\n" .
            "اول پروفایلت رو بساز تا وصل شی.";
    }

    private function welcomeTextSecond(): string
    {
        return "سلام 😊 عزیز ✋\n\n" .
            "به 《 هم‌گپ 》 خوش اومدی ، توی این ربات می‌تونی افراد نزدیکت رو پیدا کنی و باهاشون آشنا شی " .
            "یا به یه نفر بصورت ناشناس وصل شی و باهاش چت کنی ❗️\n\n" .
            "استفاده از این ربات رایگانه و اطلاعات تلگرام شما مثل اسم، عکس پروفایل یا موقعیت GPS کاملاً محرمانه هست 😎\n\n" .
            "برای شروع بهم بگو دختری یا پسری؟ 👇";
    }

    /** Delete previous bot UI menus (or at least strip their buttons). */
    private function clearUi(int $chatId, array &$user): void
    {
        $tid = (int)$user['telegram_id'];
        foreach ($this->db->getUiMessages($user) as $mid) {
            $deleted = false;
            try {
                $resp = $this->tg->deleteMessage($chatId, $mid);
                $deleted = (bool)($resp['ok'] ?? false);
            } catch (Throwable $e) {
                $deleted = false;
            }
            if (!$deleted) {
                try {
                    $this->tg->clearInlineKeyboard($chatId, $mid);
                } catch (Throwable $e) {
                    // ignore — message may already be gone
                }
            }
        }
        $this->db->setUiMessages($tid, []);
        $user['ui_messages'] = null;
    }

    private function rememberUi(array &$user, array $resp): void
    {
        $mid = Telegram::messageIdFrom($resp);
        if ($mid === null) {
            return;
        }
        $user = $this->db->addUiMessage((int)$user['telegram_id'], $user, $mid);
    }

    private function uiText(int $chatId, array &$user, string $text, array $extra = []): void
    {
        $resp = $this->tg->sendMessage($chatId, $text, $extra);
        $this->rememberUi($user, $resp);
    }

    private function uiPhoto(int $chatId, array &$user, string $path, string $caption, array $markup = []): void
    {
        $resp = $this->tg->sendPhoto($chatId, $path, $caption, $markup);
        $this->rememberUi($user, $resp);
    }

    private function stripCallbackMenu(array $cq): void
    {
        $message = $cq['message'] ?? [];
        $chatId = (int)($message['chat']['id'] ?? 0);
        $mid = (int)($message['message_id'] ?? 0);
        if ($chatId > 0 && $mid > 0) {
            try {
                $this->tg->clearInlineKeyboard($chatId, $mid);
            } catch (Throwable $e) {
                // ignore
            }
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

        if ($text === '/start' || str_starts_with($text, '/start ') || $text === '🏠 منوی اصلی') {
            // 1) clear old UI menus
            $this->clearUi($chatId, $user);
            // 2) incomplete profiles restart from zero (brand-clean onboarding)
            if (!$this->isProfileComplete($user)) {
                $this->db->updateUser($tid, [
                    'gender' => null,
                    'age' => null,
                    'city' => null,
                    'flow' => null,
                    'status' => 'idle',
                    'partner_id' => null,
                    'search_pref' => null,
                ]);
                $user = $this->db->findUser($tid) ?? $user;
            }
            // 3) output fresh screens
            $this->ensureProfileOrMain($chatId, $user, true);
            return;
        }

        if (($user['flow'] ?? '') === 'reg:city_other') {
            $this->saveCity($chatId, $user, $text);
            return;
        }

        if (!$this->isProfileComplete($user)) {
            // Do NOT stack menus. Clear once, show only the current step.
            $this->clearUi($chatId, $user);
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

        if (($user['flow'] ?? '') === 'edit:city_other') {
            $city = mb_substr(trim($text), 0, 64);
            if (mb_strlen($city) < 2) {
                $this->tg->sendMessage($chatId, 'نام شهر معتبر بفرست.');
                return;
            }
            $this->db->updateUser($tid, ['city' => $city, 'flow' => null]);
            $fresh = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $fresh);
            $this->showProfile($chatId, $fresh);
            return;
        }

        match ($text) {
            '🎲 چت تصادفی' => $this->startChat($chatId, $user, 'any'),
            '💬 چت هوشمند' => $this->showSmart($chatId, $user),
            '👤 پروفایل' => $this->showProfile($chatId, $user),
            '💎 کیف سکه' => $this->showWallet($chatId, $user),
            'ℹ️ راهنما' => $this->showHelp($chatId, $user),
            '🆘 پشتیبانی' => $this->showSupport($chatId, $user),
            default => $this->showMain($chatId, $user, 'از منوی زیر یک گزینه را لمس کن 🙂'),
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

        if (str_starts_with($data, 'reg:gender:')) {
            $g = substr($data, strlen('reg:gender:'));
            if (!in_array($g, ['male', 'female'], true)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $editing = $this->isProfileComplete($user);
            $this->db->updateUser($tid, ['gender' => $g, 'flow' => null]);
            $this->tg->answerCallback($id, $g === 'female' ? 'دختر ✅' : 'پسر ✅');
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            if ($editing) {
                $this->showProfile($chatId, $user);
                return;
            }
            $this->askAgeMenu($chatId, $user);
            return;
        }

        if (str_starts_with($data, 'reg:age:')) {
            $age = (int)substr($data, strlen('reg:age:'));
            if ($age < 13 || $age > 80) {
                $this->tg->answerCallback($id, 'سن نامعتبر', true);
                return;
            }
            $wasComplete = $this->isProfileComplete($user);
            $this->db->updateUser($tid, ['age' => $age, 'flow' => null]);
            $this->tg->answerCallback($id, "سن {$age} ✅");
            $this->stripCallbackMenu($cq);
            $fresh = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $fresh);
            if ($wasComplete) {
                $this->showProfile($chatId, $fresh);
                return;
            }
            if ($this->isProfileComplete($fresh)) {
                $this->finishRegistration($chatId, $fresh);
                return;
            }
            $this->askCityMenu($chatId, $fresh);
            return;
        }

        if ($data === 'reg:city:other') {
            $flow = $this->isProfileComplete($user) ? 'edit:city_other' : 'reg:city_other';
            $this->db->updateUser($tid, ['flow' => $flow]);
            $this->tg->answerCallback($id, 'نام شهر را بفرست');
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->uiText(
                $chatId,
                $user,
                "🏙 <b>شهر دیگر</b>\nنام شهرت را همین‌جا بنویس و ارسال کن:"
            );
            return;
        }

        if (str_starts_with($data, 'reg:city:')) {
            $city = substr($data, strlen('reg:city:'));
            $city = mb_substr(trim($city), 0, 64);
            if ($city === '' || $city === 'other' || mb_strlen($city) < 2) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $this->tg->answerCallback($id, "{$city} ✅");
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->saveCity($chatId, $user, $city);
            return;
        }

        $user = $this->db->findUser($tid) ?? $user;
        if (!$this->isProfileComplete($user)) {
            $this->tg->answerCallback($id, 'اول پروفایل را کامل کن', true);
            $this->stripCallbackMenu($cq);
            $this->ensureProfileOrMain($chatId, $user, false);
            return;
        }

        $this->tg->answerCallback($id);
        $this->stripCallbackMenu($cq);

        switch ($data) {
            case 'menu:main':
                $this->clearUi($chatId, $user);
                $this->showMain($chatId, $user);
                break;
            case 'menu:smart':
                $this->clearUi($chatId, $user);
                $this->showSmart($chatId, $user);
                break;
            case 'menu:profile':
                $this->clearUi($chatId, $user);
                $this->showProfile($chatId, $user);
                break;
            case 'menu:edit_profile':
                $this->clearUi($chatId, $user);
                $this->uiText($chatId, $user, "📝 <b>ویرایش پروفایل</b>\nکدام مورد را می‌خواهی تغییر بدهی؟", [
                    'reply_markup' => Keyboards::editProfileInline(),
                ]);
                break;
            case 'menu:wallet':
                $this->clearUi($chatId, $user);
                $this->showWallet($chatId, $user);
                break;
            case 'menu:help':
                $this->showHelp($chatId, $user);
                break;
            case 'menu:support':
                $this->showSupport($chatId, $user);
                break;
            case 'chat:any':
                $this->startChat($chatId, $user, 'any');
                break;
            case 'chat:male':
                $this->startChat($chatId, $user, 'male');
                break;
            case 'chat:female':
                $this->startChat($chatId, $user, 'female');
                break;
            case 'chat:cancel':
                $this->matcher->cancelSearch($user);
                $user = $this->db->findUser($tid) ?? $user;
                $this->clearUi($chatId, $user);
                $this->showMain($chatId, $user, 'جستجو لغو شد.');
                break;
            case 'chat:end':
                $this->endAndMenu($chatId, $user);
                break;
            case 'chat:next':
                $this->nextChat($chatId, $user);
                break;
            case 'chat:report':
                $this->report($chatId, $user);
                break;
            case 'edit:gender':
                $this->clearUi($chatId, $user);
                $this->uiText($chatId, $user, "جنسیت جدید را انتخاب کن 👇", [
                    'reply_markup' => Keyboards::gender(),
                ]);
                break;
            case 'edit:age':
                $this->clearUi($chatId, $user);
                $this->uiText($chatId, $user, "سن جدید را انتخاب کن 👇", [
                    'reply_markup' => Keyboards::age(),
                ]);
                break;
            case 'edit:city':
                $this->clearUi($chatId, $user);
                $this->uiText($chatId, $user, "شهر جدید را انتخاب کن 👇", [
                    'reply_markup' => Keyboards::city(),
                ]);
                break;
            case 'pay:soon':
                $this->tg->answerCallback($id, 'پرداخت به‌زودی فعال می‌شود', true);
                break;
            default:
                break;
        }
    }

    private function isProfileComplete(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        return !empty($user['gender']) && !empty($user['age']) && !empty($user['city']);
    }

    private function ensureProfileOrMain(int $chatId, array &$user, bool $freshStart): void
    {
        if ($this->isProfileComplete($user)) {
            $this->showMain($chatId, $user, $freshStart ? 'دوباره خوش اومدی 🌿' : '');
            return;
        }

        if (empty($user['gender'])) {
            if ($freshStart) {
                $this->uiText($chatId, $user, $this->welcomeTextFirst(), [
                    'reply_markup' => Keyboards::removeReply(),
                ]);
            }
            $this->uiText($chatId, $user, $this->welcomeTextSecond(), [
                'reply_markup' => Keyboards::gender(),
            ]);
            return;
        }

        if (empty($user['age'])) {
            if ($freshStart) {
                $this->uiText($chatId, $user, $this->welcomeTextFirst());
            }
            $this->askAgeMenu($chatId, $user);
            return;
        }

        if ($freshStart) {
            $this->uiText($chatId, $user, $this->welcomeTextFirst());
        }
        $this->askCityMenu($chatId, $user);
    }

    private function askAgeMenu(int $chatId, array &$user): void
    {
        $this->uiText(
            $chatId,
            $user,
            "سن‌ات چند ساله؟\nمثلاً: <b>24</b>\n\nاز منوی زیر انتخاب کن 👇",
            ['reply_markup' => Keyboards::age()]
        );
    }

    private function askCityMenu(int $chatId, array &$user): void
    {
        $this->uiText($chatId, $user, "شهر خودت رو از منوی زیر انتخاب کن 👇", [
            'reply_markup' => Keyboards::city(),
        ]);
    }

    private function saveCity(int $chatId, array $user, string $city): void
    {
        $tid = (int)$user['telegram_id'];
        $city = mb_substr(trim($city), 0, 64);
        if (mb_strlen($city) < 2) {
            $this->uiText($chatId, $user, 'نام شهر معتبر بفرست یا از منو انتخاب کن.', [
                'reply_markup' => Keyboards::city(),
            ]);
            return;
        }
        $wasComplete = $this->isProfileComplete($user);
        $this->db->updateUser($tid, ['city' => $city, 'flow' => null]);
        $fresh = $this->db->findUser($tid) ?? $user;
        $this->clearUi($chatId, $fresh);
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
            $user,
            "پروفایل آماده شد ✅\n۳۵ سکه هدیه گرفتی.\nحالا می‌تونی چت رو شروع کنی."
        );
    }

    private function showHelp(int $chatId, array &$user): void
    {
        $name = $this->botName();
        $this->uiText(
            $chatId,
            $user,
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

    private function showSupport(int $chatId, array &$user): void
    {
        $this->uiText(
            $chatId,
            $user,
            "🆘 <b>پشتیبانی هم‌گپ</b>\n\n" .
            "اگر مشکل، انتقاد یا پیشنهادی داری از همین بخش پیام بده.\n" .
            "پشتیبانی فقط از داخل ربات است."
        );
    }

    private function showMain(int $chatId, array &$user, string $extra = ''): void
    {
        $caption = trim(
            ($extra !== '' ? $extra . "\n\n" : '') .
            "《 <b>{$this->botName()}</b> 》\nچت ناشناس · امن · سریع"
        );
        $path = $this->assets . '/menu-main.jpg';
        if (is_file($path)) {
            $this->uiPhoto($chatId, $user, $path, $caption, Keyboards::mainInline());
        } else {
            $this->uiText($chatId, $user, $caption, [
                'reply_markup' => Keyboards::mainInline(),
            ]);
        }
        // Reply keyboard (bottom) is not deleted via deleteMessage the same way;
        // keep a short helper text tracked too.
        $this->uiText($chatId, $user, 'منوی سریع پایین صفحه 👇', [
            'reply_markup' => Keyboards::mainReply(),
        ]);
    }

    private function showSmart(int $chatId, array &$user): void
    {
        $path = $this->assets . '/menu-smart.jpg';
        $caption = "💬 <b>چت هوشمند</b>\nمخاطبت رو از منوی زیر انتخاب کن.\nهزینه فقط بعد از اتصال موفق کم می‌شود.";
        if (is_file($path)) {
            $this->uiPhoto($chatId, $user, $path, $caption, Keyboards::smartInline());
        } else {
            $this->uiText($chatId, $user, $caption, [
                'reply_markup' => Keyboards::smartInline(),
            ]);
        }
    }

    private function showProfile(int $chatId, array &$user): void
    {
        $g = $user['gender'] === 'female' ? 'دختر' : ($user['gender'] === 'male' ? 'پسر' : '-');
        $text = "👤 <b>پروفایل من</b>\n" .
            "جنسیت: <b>{$g}</b>\n" .
            "سن: <b>" . ($user['age'] ?? '-') . "</b>\n" .
            "شهر: <b>" . htmlspecialchars((string)($user['city'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
            "سکه: <b>" . (int)$user['coins'] . "</b>";
        $this->uiText($chatId, $user, $text, [
            'reply_markup' => Keyboards::profileInline(),
        ]);
    }

    private function showWallet(int $chatId, array &$user): void
    {
        $path = $this->assets . '/menu-wallet.jpg';
        $caption = "💎 موجودی: <b>" . (int)$user['coins'] . "</b> سکه\nاز منوی زیر پکیج را انتخاب کن.";
        if (is_file($path)) {
            $this->uiPhoto($chatId, $user, $path, $caption, Keyboards::walletInline());
        } else {
            $this->uiText($chatId, $user, $caption, [
                'reply_markup' => Keyboards::walletInline(),
            ]);
        }
    }

    private function startChat(int $chatId, array &$user, string $pref): void
    {
        $result = $this->matcher->startSearch($user, $pref);
        if (!($result['ok'] ?? false) && ($result['error'] ?? '') === 'no_coins') {
            $this->uiText($chatId, $user, "سکه کافی نداری.\nاز کیف سکه شارژ کن یا چت تصادفی رایگان برو.");
            $this->showWallet($chatId, $user);
            return;
        }

        if (!empty($result['matched'])) {
            $this->notifyConnected($chatId, (int)$result['partner']['telegram_id']);
            return;
        }

        $label = $pref === 'male' ? 'پسر' : ($pref === 'female' ? 'دختر' : 'تصادفی');
        $this->clearUi($chatId, $user);
        $this->uiText(
            $chatId,
            $user,
            "🔍 در حال پیدا کردن مخاطب ({$label})...\nلطفاً صبر کن.",
            ['reply_markup' => Keyboards::searching()]
        );
    }

    private function notifyConnected(int $a, int $b): void
    {
        $path = $this->assets . '/menu-chat.jpg';
        $caption = "✅ وصل شدید!\nهویت‌ها مخفی است · محترمانه گفتگو کنید.";
        foreach ([$a, $b] as $cid) {
            $u = $this->db->findUser($cid);
            if (!$u) {
                continue;
            }
            $this->clearUi($cid, $u);
            if (is_file($path)) {
                $this->uiPhoto($cid, $u, $path, $caption, Keyboards::chattingInline());
            } else {
                $this->uiText($cid, $u, $caption, [
                    'reply_markup' => Keyboards::chattingInline(),
                ]);
            }
            $this->uiText($cid, $u, 'چت فعال شد. پیام بفرست 👇', [
                'reply_markup' => Keyboards::chattingReply(),
            ]);
        }
    }

    private function endAndMenu(int $chatId, array $user): void
    {
        $partnerId = $this->matcher->endChat($user, true);
        $this->clearUi($chatId, $user);
        $this->tg->sendMessage($chatId, 'چت پایان یافت.');
        $fresh = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        if ($partnerId) {
            $p = $this->db->findUser($partnerId);
            if ($p) {
                $this->clearUi($partnerId, $p);
                $this->tg->sendMessage($partnerId, 'طرف مقابل چت را پایان داد.');
                $this->showMain($partnerId, $p);
            }
        }
        $this->showMain($chatId, $fresh);
    }

    private function nextChat(int $chatId, array $user): void
    {
        $partnerId = $this->matcher->endChat($user, true);
        if ($partnerId) {
            $p = $this->db->findUser($partnerId);
            if ($p) {
                $this->clearUi($partnerId, $p);
                $this->tg->sendMessage($partnerId, 'طرف مقابل رفت سراغ نفر بعدی.');
                $this->showMain($partnerId, $p);
            }
        }
        $fresh = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $this->clearUi($chatId, $fresh);
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
