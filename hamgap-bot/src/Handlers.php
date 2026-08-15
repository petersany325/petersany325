<?php
declare(strict_types=1);

/**
 * HamGap handlers v10 — free search, profile browse cards, support, coin requests.
 * CODE_VERSION verifies deploys. Migrator keeps DB forward-compatible.
 */
final class Handlers
{
    public const CODE_VERSION = '2026-08-15-v10.14';

    private string $assets;
    private Settings $settings;

    public function __construct(
        private array $config,
        private Database $db,
        private Telegram $tg,
        private Matcher $matcher,
        ?Settings $settings = null
    ) {
        $this->assets = rtrim((string)$config['assets_path'], '/');
        $this->settings = $settings ?? new Settings($db);
    }

    public function handle(array $update): void
    {
        if (isset($update['inline_query'])) {
            $this->onInlineQuery($update['inline_query']);
            return;
        }
        if (isset($update['callback_query'])) {
            $this->onCallback($update['callback_query']);
            return;
        }
        if (isset($update['message'])) {
            $this->onMessage($update['message']);
        }
    }

    private function isTypeKeyboardFlow(array $user): bool
    {
        $flow = (string)($user['flow'] ?? '');
        if ($flow === 'kb:type' || $flow === 'support:compose' || $flow === 'set:displayname'
            || $flow === 'set:avatar' || $flow === 'set:bio' || $flow === 'fr:create'
            || $flow === 'fr:join' || $flow === 'edit:city_other' || $flow === 'cr:other') {
            return true;
        }
        foreach (['br:compose:', 'br:repother:', 'pay:receipt:'] as $p) {
            if (str_starts_with($flow, $p)) {
                return true;
            }
        }
        return false;
    }

    /** Keep HamGap reply menu pinned (does not replace inline menus). */
    private function pinHamGapMenu(int $chatId, array &$user): void
    {
        if ($this->isTypeKeyboardFlow($user)) {
            return;
        }
        if (($user['status'] ?? '') === 'chatting') {
            $this->tg->sendMessage($chatId, '💬 چت فعال — از منوی پایین استفاده کن', [
                'reply_markup' => Keyboards::chattingReply(),
            ]);
            return;
        }
        $invite = $this->settings->getInt('invite_reward', 30);
        $this->tg->sendMessage($chatId, '⬇️ منوی هم‌گپ', [
            'reply_markup' => Keyboards::mainReply($invite),
        ]);
    }

    /** Open phone typing keyboard; show a way back to HamGap menu. */
    private function openTypeKeyboard(int $chatId, array &$user, string $hint): void
    {
        $this->tg->sendMessage(
            $chatId,
            $hint . "\n\n⌨️ کیبورد تایپ باز است.\nبعد از نوشتن، «📂 منوی هم‌گپ» را بزن.",
            ['reply_markup' => Keyboards::backToMenuReply()]
        );
    }

    private function restoreHamGapMenu(int $chatId, array &$user, string $note = ''): void
    {
        $tid = (int)$user['telegram_id'];
        $flow = (string)($user['flow'] ?? '');
        if ($flow === 'kb:type') {
            $this->db->updateUser($tid, ['flow' => null]);
            $user = $this->db->findUser($tid) ?? $user;
        }
        $this->clearUi($chatId, $user);
        $this->showMain($chatId, $user, $note !== '' ? $note : 'منوی هم‌گپ فعال شد.');
    }

    private function botName(): string
    {
        return htmlspecialchars((string)$this->config['bot_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function botUsername(): string
    {
        $u = trim((string)($this->config['bot_username'] ?? 'HamGapXBot'));
        return $u !== '' ? $u : 'HamGapXBot';
    }

    private function welcomeTextFirst(): string
    {
        $gift = max(0, $this->settings->getInt('welcome_coins', 35));
        $giftLine = $gift > 0
            ? "🎁 {$gift} سکه هدیه برای شروع داری.\n"
            : '';
        return "به هم‌گپ خوش اومدی 👋\n\n" .
            "یه ربات چت ناشناس برای گپ‌زدن امن و سریع با آدم‌های جدید.\n\n" .
            $giftLine .
            "🎲 چت شانسی هم کاملاً رایگان و نامحدوده.\n\n" .
            "اول پروفایلت رو بساز تا وصل شی.";
    }

    private function welcomeTextSecond(): string
    {
        return "سلام 😊 عزیز ✋\n\n" .
            "به 《 هم‌گپ 》 خوش اومدی ، توی این ربات می‌تونی افراد نزدیکت رو پیدا کنی و باهاشون آشنا شی " .
            "یا به یه نفر بصورت ناشناس وصل شی و باهاش چت کنی ❗️\n\n" .
            "اطلاعات تلگرام شما مثل اسم و عکس پروفایل محرمانه می‌مونه 😎\n\n" .
            "برای شروع جنسیتت را انتخاب کن 👇";
    }

    /** @return array{100:int,300:int,1000:int} */
    private function packPrices(): array
    {
        return [
            100 => $this->settings->getInt('pack_100_price', 50000),
            300 => $this->settings->getInt('pack_300_price', 120000),
            1000 => $this->settings->getInt('pack_1000_price', 350000),
        ];
    }

    /** @return array{any:int,gender:int,province:int,age:int} */
    private function connectCosts(): array
    {
        return [
            'any' => $this->settings->getInt('connect_any_cost', 0),
            'gender' => $this->settings->getInt('connect_gender_cost', 0),
            'province' => $this->settings->getInt('connect_province_cost', 0),
            'age' => $this->settings->getInt('connect_age_cost', 0),
        ];
    }

    private function browseProfileMarkup(array $target): array
    {
        return Keyboards::browseProfileInline(
            (string)$target['public_code'],
            $this->settings->getInt('request_cost', 1),
            $this->settings->getInt('message_cost', 2),
            $this->settings->getInt('like_cost', 0),
            $this->db->countLikes((int)$target['telegram_id']),
            max(1, $this->settings->getInt('notify_free_cost', 1))
        );
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

    private function uiPhotoFileId(int $chatId, array &$user, string $fileId, string $caption, array $markup = []): void
    {
        $resp = $this->tg->sendPhotoFileId($chatId, $fileId, $caption, $markup);
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
            $from['first_name'] ?? null,
            $this->settings->getInt('welcome_coins', 35)
        );
        $this->db->ensureIdentity($user);
        $this->db->touchSeen($tid);
        $user = $this->db->findUser($tid) ?? $user;

        if (($user['status'] ?? '') === 'banned') {
            $this->tg->sendMessage(
                $chatId,
                $this->bannedAccountText()
            );
            return;
        }

        $text = trim((string)($message['text'] ?? ''));

        // Always allow restoring HamGap menu / canceling type mode
        if ($text === '📂 منوی هم‌گپ' || $text === '/menu') {
            $this->db->updateUser($tid, ['flow' => null]);
            $user = $this->db->findUser($tid) ?? $user;
            if (($user['status'] ?? '') === 'chatting') {
                $this->endAndMenu($chatId, $user);
            } else {
                $this->restoreHamGapMenu($chatId, $user);
            }
            return;
        }
        if ($text === '⌨️ کیبورد تایپ') {
            $this->db->updateUser($tid, ['flow' => 'kb:type']);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->openTypeKeyboard(
                $chatId,
                $user,
                "⌨️ کیبورد تایپ فعال شد.\nحالا می‌تونی متن آزاد بنویسی."
            );
            return;
        }
        // Cancel compose if user taps another HamGap menu item
        if ($text !== '' && $this->isTypeKeyboardFlow($user) && $this->isHamGapMenuLabel($text)
            && !in_array($text, ['📂 منوی هم‌گپ', '⌨️ کیبورد تایپ'], true)) {
            $this->db->updateUser($tid, ['flow' => null]);
            $user = $this->db->findUser($tid) ?? $user;
            // fall through to normal menu routing below
        }

        // Admin flows / login on main bot
        if (str_starts_with((string)($user['flow'] ?? ''), 'adm:') || $text === '/admin' || $text === '/login' || $text === '/logout') {
            require_once __DIR__ . '/AdminHandlers.php';
            $admin = new AdminHandlers($this->config, $this->db, $this->tg, $this->settings);
            $admin->handle(['message' => $message]);
            return;
        }

        // Compose short message to a browsed profile
        if (str_starts_with((string)($user['flow'] ?? ''), 'br:compose:') && $text !== '') {
            $this->sendBrowseMessage($chatId, $user, $text);
            return;
        }
        // Extra details for "other" report reason
        if (str_starts_with((string)($user['flow'] ?? ''), 'br:repother:') && $text !== '' && !str_starts_with($text, '/')) {
            $this->finishBrowseReportOther($chatId, $user, $text);
            return;
        }
        if (($user['flow'] ?? '') === 'cr:other' && $text !== '' && !str_starts_with($text, '/')) {
            $this->finishChatReportOther($chatId, $user, $text);
            return;
        }
        if (($user['flow'] ?? '') === 'support:compose' && $text !== '' && !str_starts_with($text, '/')) {
            $this->forwardSupportFromMain($chatId, $user, $text);
            return;
        }

        // Friend room create / join flows
        if (($user['flow'] ?? '') === 'fr:create' && $text !== '' && !str_starts_with($text, '/')) {
            $cost = $this->settings->getInt('room_create_cost', 5);
            if (!$this->db->spendCoins($tid, $cost, 'room_create', null)) {
                $this->db->updateUser($tid, ['flow' => null]);
                $user = $this->db->findUser($tid) ?? $user;
                $this->showNeedCoins($chatId, $user, $cost, 'ساخت گپ');
                return;
            }
            $room = $this->db->createFriendRoom($tid, $text);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $code = htmlspecialchars((string)$room['code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $title = htmlspecialchars((string)$room['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $joinCost = $this->settings->getInt('room_join_cost', 1);
            $this->uiText(
                $chatId,
                $user,
                "گپ گروهی ساخته شد ✅ (−{$cost} سکه)\nعنوان: <b>{$title}</b>\nکد دعوت: <code>{$code}</code>\n\n" .
                "هر نفری که با کد وارد شود <b>{$joinCost}</b> سکه می‌پردازد.\n" .
                "با بستن گپ، کل تاریخچه برای همه پاک می‌شود.",
                ['reply_markup' => Keyboards::roomActiveInline((string)$room['code'])]
            );
            return;
        }
        if (($user['flow'] ?? '') === 'fr:join' && $text !== '' && !str_starts_with($text, '/')) {
            $joinCost = $this->settings->getInt('room_join_cost', 1);
            if ($joinCost > 0 && !$this->db->spendCoins($tid, $joinCost, 'room_join', trim($text))) {
                $this->db->updateUser($tid, ['flow' => null]);
                $user = $this->db->findUser($tid) ?? $user;
                $this->showNeedCoins($chatId, $user, $joinCost, 'ورود به گپ');
                return;
            }
            $result = $this->db->joinFriendRoom($tid, $text);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            if (!($result['ok'] ?? false)) {
                if ($joinCost > 0) {
                    $this->db->addCoins($tid, $joinCost, 'room_join_refund', 'failed');
                }
                $err = match ((string)($result['error'] ?? '')) {
                    'full' => 'ظرفیت گپ پر است.',
                    'closed' => 'این گپ بسته شده.',
                    default => 'کد گپ پیدا نشد.',
                };
                $this->uiText($chatId, $user, $err, ['reply_markup' => Keyboards::friendsInline($this->settings->getInt('room_create_cost', 5))]);
                return;
            }
            $room = $result['room'];
            $this->announceRoom($room, "یک عضو جدید وارد گپ شد (−{$joinCost} سکه).");
            $this->enterRoomUi($chatId, $user, $room);
            return;
        }
        if (($user['flow'] ?? '') === 'set:bio' && $text !== '') {
            $bio = mb_substr(trim($text), 0, 180);
            if ($bio === '-' || mb_strtolower($bio) === 'پاک') {
                $bio = null;
            }
            $this->db->updateUser($tid, ['bio' => $bio, 'flow' => null]);
            $fresh = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $fresh);
            $this->showProfile($chatId, $fresh);
            return;
        }

        // Active friend-room chat relay (mixed gender group)
        if (($user['status'] ?? '') === 'room' && !empty($user['active_room_id'])) {
            if (in_array($text, ['🚪 ترک گپ', '/leave'], true)) {
                $result = $this->db->leaveFriendRoom($tid);
                $fresh = $this->db->findUser($tid) ?? $user;
                $this->clearUi($chatId, $fresh);
                if (!empty($result['closed']) && !empty($result['members'])) {
                    foreach ($result['members'] as $m) {
                        $mid = (int)$m['telegram_id'];
                        if ($mid === $tid) {
                            continue;
                        }
                        try {
                            $this->tg->sendMessage($mid, "گپ بسته شد.\nتمام اطلاعات و تاریخچه گپ کاملاً پاک شد.");
                        } catch (Throwable $e) {
                        }
                    }
                    $this->tg->sendMessage($chatId, 'گپ بسته و پاک‌سازی شد.');
                }
                $this->showFriends($chatId, $fresh);
                return;
            }
            if ($text !== '' || !empty($message['photo']) || !empty($message['voice']) || !empty($message['sticker'])) {
                $this->relayRoomMessage($user, $message);
                return;
            }
        }

        // Avatar upload
        if (!empty($message['photo']) && ($user['flow'] ?? '') === 'set:avatar') {
            $photos = $message['photo'];
            $best = end($photos);
            $fileId = (string)($best['file_id'] ?? '');
            if ($fileId === '') {
                $this->tg->sendMessage($chatId, 'عکس معتبر دریافت نشد. دوباره بفرست.');
                return;
            }
            $this->db->updateUser($tid, [
                'avatar_file_id' => $fileId,
                'flow' => null,
            ]);
            $fresh = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $fresh);
            $this->showProfile($chatId, $fresh);
            return;
        }

        // Payment receipt photo
        if (!empty($message['photo']) && str_starts_with((string)($user['flow'] ?? ''), 'pay:receipt:')) {
            $invId = (int)substr((string)$user['flow'], strlen('pay:receipt:'));
            $photos = $message['photo'];
            $best = end($photos);
            $fileId = (string)($best['file_id'] ?? '');
            $this->submitPaymentReceipt($chatId, $user, $invId, $fileId);
            return;
        }

        // /start [ref_CODE] or main menu reset
        if (
            $text === '/start' ||
            str_starts_with($text, '/start ') ||
            $text === '🏠 منوی اصلی'
        ) {
            $refCode = null;
            if (preg_match('/^\/start(?:\s+ref_([A-Za-z0-9]+))?/u', $text, $m)) {
                $refCode = $m[1] ?? null;
            }
            $this->maybeApplyReferral($user, $refCode);

            $this->clearUi($chatId, $user);
            if (!$this->isProfileComplete($user)) {
                // Reset incomplete profile fields; keep display_name / avatar / referral
                $this->db->updateUser($tid, [
                    'gender' => null,
                    'age' => null,
                    'city' => null,
                    'province' => null,
                    'flow' => null,
                    'status' => 'idle',
                    'partner_id' => null,
                    'search_pref' => null,
                ]);
                $user = $this->db->findUser($tid) ?? $user;
            }
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

        // Slash commands (profile complete)
        if ($text === '/admin' || $text === '/login' || $text === '/logout') {
            require_once __DIR__ . '/AdminHandlers.php';
            $admin = new AdminHandlers($this->config, $this->db, $this->tg, $this->settings);
            $admin->handle(['message' => $message]);
            return;
        }
        if ($text === '/profile') {
            $this->clearUi($chatId, $user);
            $this->showProfile($chatId, $user);
            return;
        }
        if ($text === '/coins') {
            $this->clearUi($chatId, $user);
            $this->showWallet($chatId, $user);
            return;
        }
        if ($text === '/search') {
            $this->clearUi($chatId, $user);
            $this->showFind($chatId, $user);
            return;
        }
        if ($text === '/link') {
            $this->clearUi($chatId, $user);
            $this->showInvite($chatId, $user);
            return;
        }

        if (($user['status'] ?? '') === 'chatting' && !empty($user['partner_id'])) {
            if (in_array($text, ['🛑 پایان چت', '🛑 پایان', '/end', '📂 منوی هم‌گپ'], true)) {
                $this->endAndMenu($chatId, $user);
                return;
            }
            if (in_array($text, ['⏭ بعدی', '/next'], true)) {
                $this->nextChat($chatId, $user);
                return;
            }
            if (in_array($text, ['🚩 گزارش', '/report'], true)) {
                $this->showChatReportForm($chatId, $user);
                return;
            }
            if (in_array($text, ['📥 درخواست‌ها', '/requests'], true)) {
                $this->showRequestInbox($chatId, $user);
                return;
            }
            if ($this->isHamGapMenuLabel($text)) {
                $this->endAndMenu($chatId, $user);
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

        if (($user['flow'] ?? '') === 'set:displayname') {
            $name = trim($text);
            $len = mb_strlen($name);
            if ($len < 2 || $len > 32) {
                $this->tg->sendMessage($chatId, 'نام کاربری باید بین ۲ تا ۳۲ کاراکتر باشد.');
                return;
            }
            // Block telegram @handles and urls
            if (preg_match('/[@\/\\\\]|https?:/iu', $name)) {
                $this->tg->sendMessage($chatId, 'نام کاربری نامعتبر است. بدون لینک و @ بفرست.');
                return;
            }
            $this->db->updateUser($tid, [
                'display_name' => mb_substr($name, 0, 32),
                'flow' => null,
            ]);
            $fresh = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $fresh);
            $this->showProfile($chatId, $fresh);
            return;
        }

        if (($user['flow'] ?? '') === 'set:avatar') {
            $this->tg->sendMessage($chatId, 'لطفاً یک عکس بفرست (نه متن).');
            return;
        }

        match (true) {
            in_array($text, ['🔗 وصلم کن به ناشناس', '🔗 وصل ناشناس', '💬 چت ناشناس'], true)
                => $this->showConnect($chatId, $user),
            in_array($text, ['🔍 پیدا کردن مخاطب', '🔍 جستجوی کاربران'], true)
                => $this->showFind($chatId, $user),
            in_array($text, ['👥 وصل به دوستان', '👥 چت با دوستان', '👫 چت با دوستان'], true)
                => $this->showFriends($chatId, $user),
            in_array($text, ['💎 سکه‌ها', '💎 کیف‌پول'], true)
                => $this->showWallet($chatId, $user),
            in_array($text, ['👤 پروفایل من', '👤 پروفایل'], true)
                => $this->showProfile($chatId, $user),
            str_starts_with($text, '✨ دعوت')
                => $this->showInvite($chatId, $user),
            $text === '🆘 پشتیبانی'
                => $this->showSupport($chatId, $user),
            $text === 'ℹ️ راهنما'
                => $this->showHelp($chatId, $user),
            default => (function () use ($chatId, &$user): void {
                $this->clearUi($chatId, $user);
                $this->showMain($chatId, $user, 'از منوی هم‌گپ پایین صفحه یک گزینه را لمس کن.');
            })(),
        };
    }

    private function isHamGapMenuLabel(string $text): bool
    {
        if (in_array($text, [
            '💬 چت ناشناس', '🔍 جستجوی کاربران', '👫 چت با دوستان', '👥 چت با دوستان',
            '👤 پروفایل', '💎 کیف‌پول', '🆘 پشتیبانی', 'ℹ️ راهنما', '⌨️ کیبورد تایپ',
            '📂 منوی هم‌گپ', '⏭ بعدی', '🛑 پایان چت', '📥 درخواست‌ها', '🚩 گزارش',
        ], true)) {
            return true;
        }
        return str_starts_with($text, '✨ دعوت');
    }

    private function onCallback(array $cq): void
    {
        $id = (string)$cq['id'];
        $data = (string)($cq['data'] ?? '');
        $message = $cq['message'] ?? [];
        $chatId = (int)($message['chat']['id'] ?? 0);
        $from = $cq['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        $user = $this->db->upsertUser(
            $tid,
            $from['username'] ?? null,
            $from['first_name'] ?? null,
            $this->settings->getInt('welcome_coins', 35)
        );
        $this->db->touchSeen($tid);
        $user = $this->db->findUser($tid) ?? $user;

        // Admin console — always hand off; AdminHandlers enforces login
        if (str_starts_with($data, 'adm:')) {
            require_once __DIR__ . '/AdminHandlers.php';
            $admin = new AdminHandlers($this->config, $this->db, $this->tg, $this->settings);
            $admin->handle(['callback_query' => $cq]);
            return;
        }

        if (($user['status'] ?? '') === 'banned') {
            $this->tg->answerCallback($id, 'مسدود شده‌اید — با پشتیبانی تماس بگیرید', true);
            return;
        }

        // ——— Registration ———
        if (str_starts_with($data, 'reg:gender:')) {
            $g = substr($data, strlen('reg:gender:'));
            if (!Gender::isValid($g)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $editing = $this->isProfileComplete($user);
            $this->db->updateUser($tid, ['gender' => $g, 'flow' => null]);
            $this->tg->answerCallback($id, Gender::short($g) . ' ✅');
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
            $this->askProvinceMenu($chatId, $fresh);
            return;
        }

        if ($data === 'reg:prov:back') {
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->db->updateUser($tid, ['province' => null, 'city' => null, 'flow' => null]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->askProvinceMenu($chatId, $user);
            return;
        }

        if (str_starts_with($data, 'reg:prov:')) {
            $idx = (int)substr($data, strlen('reg:prov:'));
            $provinces = IranLocations::provinces();
            if (!isset($provinces[$idx])) {
                $this->tg->answerCallback($id, 'استان نامعتبر', true);
                return;
            }
            $province = $provinces[$idx];
            $this->db->updateUser($tid, [
                'province' => $province,
                'city' => null,
                'flow' => null,
            ]);
            $this->tg->answerCallback($id, "{$province} ✅");
            $this->stripCallbackMenu($cq);
            $fresh = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $fresh);
            $this->askCityMenu($chatId, $fresh);
            return;
        }

        if (str_starts_with($data, 'reg:ci:')) {
            $idx = (int)substr($data, strlen('reg:ci:'));
            $province = (string)($user['province'] ?? '');
            $cities = $province !== '' ? IranLocations::cities($province) : [];
            if ($province === '' || !isset($cities[$idx])) {
                $this->tg->answerCallback($id, 'اول استان را انتخاب کن', true);
                $this->stripCallbackMenu($cq);
                $this->clearUi($chatId, $user);
                $this->askProvinceMenu($chatId, $user);
                return;
            }
            $city = $cities[$idx];
            $this->tg->answerCallback($id, "{$city} ✅");
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->saveCity($chatId, $user, $city);
            return;
        }

        if ($data === 'reg:city:other') {
            if (empty($user['province'])) {
                $this->tg->answerCallback($id, 'اول استان را انتخاب کن', true);
                $this->stripCallbackMenu($cq);
                $this->clearUi($chatId, $user);
                $this->askProvinceMenu($chatId, $user);
                return;
            }
            $flow = $this->isProfileComplete($user) ? 'edit:city_other' : 'reg:city_other';
            $this->db->updateUser($tid, ['flow' => $flow]);
            $this->tg->answerCallback($id, 'نام شهر را بفرست');
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->uiText(
                $chatId,
                $user,
                "🏙 <b>شهر دیگر</b> ({$user['province']})\nنام شهرت را همین‌جا بنویس و ارسال کن:"
            );
            return;
        }

        // legacy city callbacks ignored safely
        if (str_starts_with($data, 'reg:city:')) {
            $this->tg->answerCallback($id, 'لطفاً دوباره از منوی استان انتخاب کن', true);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->askProvinceMenu($chatId, $user);
            return;
        }

        $user = $this->db->findUser($tid) ?? $user;
        if (!$this->isProfileComplete($user)) {
            $this->tg->answerCallback($id, 'اول پروفایل را کامل کن', true);
            $this->stripCallbackMenu($cq);
            $this->ensureProfileOrMain($chatId, $user, false);
            return;
        }

        // ——— Search hub (modern presets) ———
        if (str_starts_with($data, 'sr:') || str_starts_with($data, 'adv:')) {
            $this->handleSearchHubCallback($cq, $user, $id, $data, $chatId, $tid);
            return;
        }

        // ——— Find flow (before generic answer so we can toast) ———
        if (str_starts_with($data, 'find:prov:')) {
            $idx = (int)substr($data, strlen('find:prov:'));
            $provinces = IranLocations::provinces();
            if (!isset($provinces[$idx])) {
                $this->tg->answerCallback($id, 'استان نامعتبر', true);
                return;
            }
            $province = $provinces[$idx];
            $this->db->updateUser($tid, ['flow' => 'find:' . $idx]);
            $this->tg->answerCallback($id, "{$province} ✅");
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $safe = htmlspecialchars($province, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->uiText($chatId, $user, "🏙 شهر مورد نظر در استان <b>{$safe}</b> را انتخاب کن 👇", [
                'reply_markup' => Keyboards::findCities($province),
            ]);
            return;
        }

        if (str_starts_with($data, 'find:ci:')) {
            $cityIdx = (int)substr($data, strlen('find:ci:'));
            $flow = (string)($user['flow'] ?? '');
            if (!preg_match('/^find:(\d+)$/', $flow, $fm)) {
                $this->tg->answerCallback($id, 'اول استان را انتخاب کن', true);
                $this->stripCallbackMenu($cq);
                $this->clearUi($chatId, $user);
                $this->showFind($chatId, $user);
                return;
            }
            $provIdx = (int)$fm[1];
            $provinces = IranLocations::provinces();
            if (!isset($provinces[$provIdx])) {
                $this->tg->answerCallback($id, 'استان نامعتبر', true);
                return;
            }
            $province = $provinces[$provIdx];
            $cities = IranLocations::cities($province);
            if (!isset($cities[$cityIdx])) {
                $this->tg->answerCallback($id, 'شهر نامعتبر', true);
                return;
            }
            $city = $cities[$cityIdx];
            $this->db->updateUser($tid, ['flow' => 'find:' . $provIdx . ':' . $cityIdx]);
            $this->tg->answerCallback($id, "{$city} ✅");
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $pSafe = htmlspecialchars($province, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $cSafe = htmlspecialchars($city, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->uiText(
                $chatId,
                $user,
                "🔍 جستجو در <b>{$pSafe}</b> / <b>{$cSafe}</b>\nجنسیت مخاطب را انتخاب کن 👇",
                ['reply_markup' => Keyboards::findGenderInline()]
            );
            return;
        }

        if (str_starts_with($data, 'find:gender:')) {
            $g = substr($data, strlen('find:gender:'));
            if (!Gender::isFilter($g)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $flow = (string)($user['flow'] ?? '');
            $filters = [];
            if (preg_match('/^find:(\d+):(\d+)$/', $flow, $fm)) {
                $provinces = IranLocations::provinces();
                $provIdx = (int)$fm[1];
                $cityIdx = (int)$fm[2];
                if (isset($provinces[$provIdx])) {
                    $province = $provinces[$provIdx];
                    $cities = IranLocations::cities($province);
                    $city = $cities[$cityIdx] ?? '';
                    $filters['province'] = $province;
                    if ($city !== '') {
                        $filters['city'] = $city;
                    }
                }
            }
            if (Gender::isValid($g)) {
                $filters['gender'] = $g;
            }
            $this->tg->answerCallback($id, 'جستجو رایگان ✅');
            $this->stripCallbackMenu($cq);
            $this->beginBrowse($chatId, $user, $filters);
            return;
        }

        // Browse profile actions
        if ($data === 'br:next') {
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->showNextBrowseCard($chatId, $user);
            return;
        }
        if ($data === 'br:list') {
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->db->updateUser($tid, ['browse_view' => 'list']);
            $user = $this->db->findUser($tid) ?? $user;
            $this->renderBrowseBatch($chatId, $user, 0);
            return;
        }
        if (str_starts_with($data, 'br:req:')) {
            $code = substr($data, strlen('br:req:'));
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->sendBrowseRequest($chatId, $user, $code);
            return;
        }
        if (str_starts_with($data, 'br:like:')) {
            $code = substr($data, strlen('br:like:'));
            $target = $this->db->findByPublicCode($code);
            if (!$target) {
                $this->tg->answerCallback($id, 'کاربر پیدا نشد', true);
                return;
            }
            $to = (int)$target['telegram_id'];
            if ($this->db->isBlocked($tid, $to) || $this->db->isBlocked($to, $tid)) {
                $this->tg->answerCallback($id, 'امکان‌پذیر نیست', true);
                return;
            }
            $likeCost = $this->settings->getInt('like_cost', 0);
            if ($likeCost > 0 && !$this->db->spendCoins($tid, $likeCost, 'like', (string)$to)) {
                $this->tg->answerCallback($id, 'سکه کافی نداری', true);
                $this->showNeedCoins($chatId, $user, $likeCost, 'لایک');
                return;
            }
            $res = $this->db->addLike($tid, $to);
            if ($res === 'already') {
                if ($likeCost > 0) {
                    $this->db->addCoins($tid, $likeCost, 'like_refund', 'already');
                }
                $this->tg->answerCallback($id, 'قبلاً لایک کردی', true);
                return;
            }
            $this->tg->answerCallback($id, 'لایک ثبت شد ❤️');
            try {
                $fromName = htmlspecialchars((string)($user['display_name'] ?? 'کسی'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $this->tg->sendMessage($to, "❤️ یک لایک جدید از <b>{$fromName}</b> گرفتی.");
            } catch (Throwable $e) {
            }
            return;
        }
        if (str_starts_with($data, 'br:friend:')) {
            $code = substr($data, strlen('br:friend:'));
            $target = $this->db->findByPublicCode($code);
            if (!$target) {
                $this->tg->answerCallback($id, 'کاربر پیدا نشد', true);
                return;
            }
            $to = (int)$target['telegram_id'];
            $res = $this->db->requestFriendship($tid, $to);
            if ($res === 'already') {
                $this->tg->answerCallback($id, 'قبلاً دوست هستید', true);
                return;
            }
            if ($res === 'pending') {
                $this->tg->answerCallback($id, 'درخواست قبلی در انتظار است', true);
                return;
            }
            $fromName = htmlspecialchars((string)($user['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            try {
                $this->tg->sendMessage(
                    $to,
                    "درخواست دوستی جدید از <b>{$fromName}</b>",
                    ['reply_markup' => Keyboards::friendRequestInline($tid)]
                );
            } catch (Throwable $e) {
            }
            $this->tg->answerCallback($id, 'درخواست دوستی ارسال شد');
            return;
        }
        if (str_starts_with($data, 'br:msg:')) {
            $code = substr($data, strlen('br:msg:'));
            $target = $this->db->findByPublicCode($code);
            if (!$target) {
                $this->tg->answerCallback($id, 'کاربر پیدا نشد', true);
                return;
            }
            $this->db->updateUser($tid, ['flow' => 'br:compose:' . $code]);
            $this->tg->answerCallback($id, 'متن پیام را بفرست');
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $cost = $this->settings->getInt('message_cost', 1);
            $this->clearUi($chatId, $user);
            $dn = htmlspecialchars((string)($target['display_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->openTypeKeyboard(
                $chatId,
                $user,
                "پیام کوتاه برای <b>{$dn}</b>\nهزینه ارسال: <b>{$cost}</b> سکه\nمتن را همین‌جا بنویس."
            );
            return;
        }
        if (str_starts_with($data, 'br:rep:')) {
            $code = substr($data, strlen('br:rep:'));
            $target = $this->db->findByPublicCode($code);
            if (!$target) {
                $this->tg->answerCallback($id, 'کاربر پیدا نشد', true);
                return;
            }
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->showReportForm($chatId, $user, $target, 'br');
            return;
        }
        if (str_starts_with($data, 'br:rp:')) {
            // br:rp:CODE:reason
            $rest = substr($data, strlen('br:rp:'));
            $parts = explode(':', $rest, 2);
            $code = $parts[0] ?? '';
            $reasonKey = $parts[1] ?? '';
            $this->handleBrowseReportChoice($cq, $user, $id, $chatId, $tid, $code, $reasonKey);
            return;
        }
        if (str_starts_with($data, 'br:rc:')) {
            $code = substr($data, strlen('br:rc:'));
            $this->tg->answerCallback($id, 'انصراف');
            $this->stripCallbackMenu($cq);
            $target = $this->db->findByPublicCode($code);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            if ($target) {
                $this->renderBrowseCard($chatId, $user, $target);
            } else {
                $this->showNextBrowseCard($chatId, $user);
            }
            return;
        }
        if (str_starts_with($data, 'br:blk:')) {
            $code = substr($data, strlen('br:blk:'));
            $target = $this->db->findByPublicCode($code);
            if ($target) {
                $this->db->blockUser($tid, (int)$target['telegram_id']);
            }
            $this->tg->answerCallback($id, 'کاربر بلاک شد');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->showNextBrowseCard($chatId, $user);
            return;
        }
        if (str_starts_with($data, 'br:wait:')) {
            $code = substr($data, strlen('br:wait:'));
            $this->subscribeChatWait($cq, $user, $id, $chatId, $tid, $code);
            return;
        }
        if (str_starts_with($data, 'cr:')) {
            $reasonKey = substr($data, strlen('cr:'));
            $this->handleChatReportChoice($cq, $user, $id, $chatId, $tid, $reasonKey);
            return;
        }
        if ($data === 'chat:continue') {
            $this->tg->answerCallback($id, 'ادامه چت');
            $this->stripCallbackMenu($cq);
            return;
        }

        // Chat request accept / hold / decline / inbox / unblock
        if ($data === 'req:inbox' || str_starts_with($data, 'req:') || str_starts_with($data, 'blk:')) {
            $this->handleRequestCallback($cq, $user, $id, $data, $chatId, $tid);
            return;
        }

        // Display modes + batch list
        if ($data === 'vw:noop') {
            $this->tg->answerCallback($id);
            return;
        }
        if ($data === 'vw:pick' || str_starts_with($data, 'vw:')) {
            if ($data === 'vw:pick') {
                $cache = $this->db->getBrowseCache($user) ?? [];
                $found = count($cache['ids'] ?? []);
                $this->tg->answerCallback($id);
                $this->stripCallbackMenu($cq);
                $this->clearUi($chatId, $user);
                $this->uiText(
                    $chatId,
                    $user,
                    "حالت نمایش نتایج را انتخاب کن:",
                    ['reply_markup' => Keyboards::browseViewPicker(max(1, $found))]
                );
                return;
            }
            $mode = substr($data, 3);
            if (!in_array($mode, ['card', 'list', 'photo', 'menu', 'slide'], true)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $this->db->updateUser($tid, ['browse_view' => $mode, 'browse_cursor' => 0]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id, 'حالت نمایش ست شد');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            if ($mode === 'card') {
                $this->showNextBrowseCard($chatId, $user);
            } elseif ($mode === 'slide') {
                $this->openSlideMode($chatId, $user);
            } else {
                $this->renderBrowseBatch($chatId, $user, 0);
            }
            return;
        }
        if (str_starts_with($data, 'sl:')) {
            $idx = (int)substr($data, strlen('sl:'));
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->db->updateUser($tid, ['browse_view' => 'slide', 'browse_cursor' => $idx]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->renderSlideCard($chatId, $user, $idx);
            return;
        }
        if (str_starts_with($data, 'bl:p:')) {
            $page = (int)substr($data, strlen('bl:p:'));
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->renderBrowseBatch($chatId, $user, max(0, $page));
            return;
        }
        if (str_starts_with($data, 'bl:o:')) {
            $idx = (int)substr($data, strlen('bl:o:'));
            $cache = $this->db->getBrowseCache($user) ?? [];
            $ids = $cache['ids'] ?? [];
            if (!isset($ids[$idx])) {
                $this->tg->answerCallback($id, 'کاربر در فهرست نیست', true);
                return;
            }
            $target = $this->db->findUser((int)$ids[$idx]);
            if (!$target) {
                $this->tg->answerCallback($id, 'کاربر پیدا نشد', true);
                return;
            }
            $this->db->updateUser($tid, ['browse_cursor' => $idx]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->renderBrowseCard($chatId, $user, $target);
            return;
        }

        // Privacy
        if ($data === 'pr:home' || str_starts_with($data, 'pr:')) {
            $this->handlePrivacyCallback($cq, $user, $id, $data, $chatId, $tid);
            return;
        }

        // Occupation / education tag
        if (str_starts_with($data, 'occ:')) {
            $this->handleOccupationCallback($cq, $user, $id, $data, $chatId, $tid);
            return;
        }

        // Friend rooms + friendship responses
        if (str_starts_with($data, 'fr:') || str_starts_with($data, 'frnd:')) {
            $this->handleFriendsCallback($cq, $user, $id, $data, $chatId, $tid);
            return;
        }

        if ($data === 'support:compose') {
            $this->db->updateUser($tid, ['flow' => 'support:compose']);
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->openTypeKeyboard($chatId, $user, "پیام پشتیبانی را بنویس.\nهمکاران ما پاسخ می‌دهند.");
            return;
        }

        if ($data === 'pay:soon') {
            $this->tg->answerCallback($id, 'درگاه بانکی به‌زودی فعال می‌شود', true);
            return;
        }

        // Card-to-card payment
        if (str_starts_with($data, 'pay:') || str_starts_with($data, 'payadm:')) {
            $this->handlePaymentCallback($cq, $user, $id, $data, $chatId, $tid);
            return;
        }

        $this->tg->answerCallback($id);
        $this->stripCallbackMenu($cq);

        switch ($data) {
            case 'menu:main':
                $this->clearUi($chatId, $user);
                $this->showMain($chatId, $user);
                break;
            case 'menu:connect':
                $this->clearUi($chatId, $user);
                $this->showConnect($chatId, $user);
                break;
            case 'menu:find':
                $this->clearUi($chatId, $user);
                $this->db->updateUser($tid, ['flow' => null]);
                $user = $this->db->findUser($tid) ?? $user;
                $this->showFind($chatId, $user);
                break;
            case 'menu:profile':
                $this->clearUi($chatId, $user);
                $this->showProfile($chatId, $user);
                break;
            case 'menu:blocks':
                $this->clearUi($chatId, $user);
                $this->showBlocks($chatId, $user);
                break;
            case 'menu:profile_settings':
                $this->clearUi($chatId, $user);
                $this->uiText($chatId, $user, "🛠 <b>تنظیمات پروفایل</b>\nکدام مورد را می‌خواهی تغییر بدهی؟", [
                    'reply_markup' => Keyboards::profileSettingsInline(),
                ]);
                break;
            case 'menu:wallet':
                $this->clearUi($chatId, $user);
                $this->showWallet($chatId, $user);
                break;
            case 'menu:help':
                $this->clearUi($chatId, $user);
                $this->showHelp($chatId, $user);
                break;
            case 'menu:friends':
                $this->clearUi($chatId, $user);
                $this->showFriends($chatId, $user);
                break;
            case 'menu:support':
                $this->clearUi($chatId, $user);
                $this->showSupport($chatId, $user);
                break;
            case 'menu:invite':
                $this->clearUi($chatId, $user);
                $this->showInvite($chatId, $user);
                break;
            case 'invite:copy':
                $this->clearUi($chatId, $user);
                $this->showInviteCopyText($chatId, $user);
                break;
            case 'invite:milestones':
                $this->clearUi($chatId, $user);
                $this->showInviteMilestones($chatId, $user);
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
            case 'chat:shemale':
                $this->startChat($chatId, $user, 'shemale');
                break;
            case 'chat:province':
                $this->startChat($chatId, $user, 'province');
                break;
            case 'chat:age':
                $this->startChat($chatId, $user, 'age');
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
                $this->tg->answerCallback($id);
                $this->stripCallbackMenu($cq);
                $this->showChatReportForm($chatId, $user);
                break;
            case 'edit:gender':
                $this->clearUi($chatId, $user);
                $this->uiText($chatId, $user, 'جنسیت جدید را انتخاب کن 👇', [
                    'reply_markup' => Keyboards::gender(),
                ]);
                break;
            case 'edit:age':
                $this->clearUi($chatId, $user);
                $this->uiText($chatId, $user, 'سن جدید را انتخاب کن 👇', [
                    'reply_markup' => Keyboards::age(),
                ]);
                break;
            case 'edit:location':
            case 'edit:city':
                $this->clearUi($chatId, $user);
                $this->db->updateUser($tid, ['province' => null, 'city' => null, 'flow' => null]);
                $user = $this->db->findUser($tid) ?? $user;
                $this->askProvinceMenu($chatId, $user);
                break;
            case 'edit:avatar':
                $this->db->updateUser($tid, ['flow' => 'set:avatar']);
                $user = $this->db->findUser($tid) ?? $user;
                $this->clearUi($chatId, $user);
                $this->openTypeKeyboard($chatId, $user, "🖼 <b>عکس پروفایل</b>\nیک عکس همین‌جا بفرست.");
                break;
            case 'edit:namehub':
                $this->clearUi($chatId, $user);
                $this->uiText(
                    $chatId,
                    $user,
                    "🔤 <b>نام کاربری</b>\nفعلی: <b>" .
                    htmlspecialchars((string)($user['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    "</b>\nدستی بنویس یا خودکار بساز.",
                    ['reply_markup' => Keyboards::nameHubInline()]
                );
                break;
            case 'edit:nameauto':
                $auto = $this->db->generateDisplayName();
                $this->db->updateUser($tid, ['display_name' => $auto, 'flow' => null]);
                $user = $this->db->findUser($tid) ?? $user;
                $this->tg->answerCallback($id, 'نام خودکار ست شد');
                $this->stripCallbackMenu($cq);
                $this->clearUi($chatId, $user);
                $this->showProfile($chatId, $user);
                break;
            case 'edit:displayname':
                $this->db->updateUser($tid, ['flow' => 'set:displayname']);
                $user = $this->db->findUser($tid) ?? $user;
                $this->clearUi($chatId, $user);
                $this->openTypeKeyboard(
                    $chatId,
                    $user,
                    "🔤 <b>نام کاربری دستی</b>\nنام نمایشی جدیدت را بنویس (۲ تا ۳۲ کاراکتر).\nفعلی: <b>" .
                    htmlspecialchars((string)($user['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    '</b>'
                );
                break;
            case 'edit:bio':
                $this->db->updateUser($tid, ['flow' => 'set:bio']);
                $user = $this->db->findUser($tid) ?? $user;
                $this->clearUi($chatId, $user);
                $cur = htmlspecialchars((string)($user['bio'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $this->openTypeKeyboard(
                    $chatId,
                    $user,
                    "📝 <b>بیو / معرفی</b>\nحداکثر ۱۸۰ کاراکتر.\nفعلی: {$cur}\n\nبرای پاک کردن بنویس: <code>پاک</code>"
                );
                break;
            case 'edit:occupation':
                $this->clearUi($chatId, $user);
                $this->askOccupation($chatId, $user, false);
                break;
            default:
                break;
        }
    }

    private function maybeApplyReferral(array &$user, ?string $refCode): void
    {
        if ($refCode === null || $refCode === '') {
            return;
        }
        if (!empty($user['referred_by'])) {
            return;
        }
        // فقط قبل از تکمیل پروفایل معرف را ذخیره می‌کنیم
        if ($this->isProfileComplete($user)) {
            return;
        }
        $referrer = $this->db->findByReferralCode($refCode);
        if (!$referrer) {
            return;
        }
        $refTid = (int)$referrer['telegram_id'];
        $myTid = (int)$user['telegram_id'];
        if ($refTid === $myTid) {
            return;
        }
        $this->db->updateUser($myTid, ['referred_by' => $refTid]);
        // پاداش فقط بعد از تکمیل پروفایل در finishRegistration پرداخت می‌شود
        $user = $this->db->findUser($myTid) ?? $user;
    }

    private function payReferralIfDue(array $user): void
    {
        $refTid = (int)($user['referred_by'] ?? 0);
        if ($refTid <= 0) {
            return;
        }
        $myTid = (int)$user['telegram_id'];
        if ($refTid === $myTid || !$this->db->findUser($refTid)) {
            return;
        }
        if ($this->db->hasCoinReason($refTid, 'referral', (string)$myTid)) {
            return;
        }
        $reward = max(0, $this->settings->getInt('invite_reward', 30));
        if ($reward > 0) {
            $this->db->addCoins($refTid, $reward, 'referral', (string)$myTid);
            try {
                $this->tg->sendMessage(
                    $refTid,
                    "🎉 یک دوست با لینک دعوتت ثبت‌نام را کامل کرد.\n" .
                    "<b>+{$reward} سکه</b> به حسابت اضافه شد.\n" .
                    "از منوی «دعوت دوستان» آمار و جایزه‌های پله‌ای را ببین."
                );
            } catch (Throwable $e) {
            }
        }

        // هدیه کوچک برای دعوت‌شونده (علاوه بر welcome)
        $inviteeBonus = max(0, $this->settings->getInt('invitee_bonus', 10));
        if ($inviteeBonus > 0 && !$this->db->hasCoinReason($myTid, 'invitee_bonus', (string)$refTid)) {
            $this->db->addCoins($myTid, $inviteeBonus, 'invitee_bonus', (string)$refTid);
        }

        $this->maybePayInviteMilestones($refTid);
    }

    private function maybePayInviteMilestones(int $refTid): void
    {
        $count = $this->db->countSuccessfulInvites($refTid);
        $tiers = [
            3 => ['key' => 'invite_milestone_3', 'default' => 50],
            10 => ['key' => 'invite_milestone_10', 'default' => 150],
            25 => ['key' => 'invite_milestone_25', 'default' => 400],
        ];
        foreach ($tiers as $need => $cfg) {
            if ($count < $need) {
                continue;
            }
            $reason = 'invite_milestone_' . $need;
            if ($this->db->hasCoinReason($refTid, $reason)) {
                continue;
            }
            $bonus = max(0, $this->settings->getInt($cfg['key'], $cfg['default']));
            if ($bonus <= 0) {
                continue;
            }
            $this->db->addCoins($refTid, $bonus, $reason, (string)$count);
            try {
                $this->tg->sendMessage(
                    $refTid,
                    "🏆 جایزه پله‌ای دعوت!\n" .
                    "به <b>{$need}</b> دعوت موفق رسیدی.\n" .
                    "<b>+{$bonus} سکه</b> بونوس گرفتی."
                );
            } catch (Throwable $e) {
            }
        }
    }

    private function inviteLinkFor(array $user): string
    {
        $code = (string)($user['referral_code'] ?? '');
        return 'https://t.me/' . $this->botUsername() . '?start=ref_' . $code;
    }

    private function inviteSharePlain(array $user): string
    {
        $link = $this->inviteLinkFor($user);
        $reward = $this->settings->getInt('invite_reward', 30);
        $name = $this->botName();
        return "سلام 👋\nبا هم‌گپ می‌تونی ناشناس چت کنی و آدم‌های نزدیکت رو پیدا کنی.\n" .
            "با لینک من بیا؛ ثبت‌نام رایگانه و منم {$reward} سکه می‌گیرم ✨\n" .
            $link . "\n\nربات: {$name}";
    }

    private function inviteShareUrl(array $user): string
    {
        $link = $this->inviteLinkFor($user);
        $text = "با هم‌گپ ناشناس چت کن — لینک دعوت من:";
        return 'https://t.me/share/url?url=' . rawurlencode($link) . '&text=' . rawurlencode($text);
    }

    private function inviteMilestoneProgressLine(int $count): string
    {
        $m3 = $this->settings->getInt('invite_milestone_3', 50);
        $m10 = $this->settings->getInt('invite_milestone_10', 150);
        $m25 = $this->settings->getInt('invite_milestone_25', 400);
        $mark = static function (int $have, int $need): string {
            return $have >= $need ? '✅' : '▫️';
        };
        return
            "{$mark($count, 3)} ۳ دعوت → +{$m3} سکه\n" .
            "{$mark($count, 10)} ۱۰ دعوت → +{$m10} سکه\n" .
            "{$mark($count, 25)} ۲۵ دعوت → +{$m25} سکه";
    }

    private function showInvite(int $chatId, array &$user): void
    {
        $this->db->ensureIdentity($user);
        $user = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $link = $this->inviteLinkFor($user);
        $safeLink = htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $invite = $this->settings->getInt('invite_reward', 30);
        $invitee = $this->settings->getInt('invitee_bonus', 10);
        $tid = (int)$user['telegram_id'];
        $okCount = $this->db->countSuccessfulInvites($tid);
        $earned = $this->db->sumInviteEarnings($tid);
        if ($okCount >= 25) {
            $nextHint = 'همه جایزه‌های پله‌ای را گرفتی 🔥';
        } elseif ($okCount >= 10) {
            $left = 25 - $okCount;
            $nextHint = "تا جایزه ۲۵ دعوت: <b>{$left}</b> نفر مانده";
        } elseif ($okCount >= 3) {
            $left = 10 - $okCount;
            $nextHint = "تا جایزه ۱۰ دعوت: <b>{$left}</b> نفر مانده";
        } else {
            $left = 3 - $okCount;
            $nextHint = "تا اولین جایزه پله‌ای: <b>{$left}</b> نفر مانده";
        }

        $text =
            "✨ <b>هاب دعوت هم‌گپ</b>\n\n" .
            "با دعوت دوست هم تو سکه می‌گیری، هم دوستت راحت‌تر شروع می‌کنه.\n\n" .
            "🔗 لینک اختصاصی تو:\n<code>{$safeLink}</code>\n\n" .
            "🎁 هر دعوت موفق (پروفایل کامل): <b>+{$invite}</b> سکه\n" .
            "🎁 هدیه دوستت با لینک تو: <b>+{$invitee}</b> سکه اضافه\n\n" .
            "📊 آمار تو:\n" .
            "• دعوت موفق: <b>{$okCount}</b>\n" .
            "• سکه کسب‌شده از دعوت: <b>{$earned}</b>\n" .
            "• {$nextHint}\n\n" .
            "<b>جایزه‌های پله‌ای</b>\n" .
            $this->inviteMilestoneProgressLine($okCount) . "\n\n" .
            "از دکمه «اشتراک‌گذاری» بفرست تو پی وی / استوری / گروه.";

        $this->clearUi($chatId, $user);
        $this->uiText($chatId, $user, $text, [
            'reply_markup' => Keyboards::inviteHubInline($this->inviteShareUrl($user)),
        ]);
        $this->pinHamGapMenu($chatId, $user);
    }

    private function showInviteCopyText(int $chatId, array &$user): void
    {
        $this->db->ensureIdentity($user);
        $user = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $plain = $this->inviteSharePlain($user);
        $safe = htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->uiText(
            $chatId,
            $user,
            "📋 <b>متن آماده</b>\nروی متن بزن و کپی کن، بعد برای دوستات بفرست:\n\n<code>{$safe}</code>",
            ['reply_markup' => Keyboards::inviteHubInline($this->inviteShareUrl($user))]
        );
        $this->pinHamGapMenu($chatId, $user);
    }

    private function showInviteMilestones(int $chatId, array &$user): void
    {
        $tid = (int)$user['telegram_id'];
        $count = $this->db->countSuccessfulInvites($tid);
        $earned = $this->db->sumInviteEarnings($tid);
        $per = $this->settings->getInt('invite_reward', 30);
        $text =
            "🏆 <b>جایزه‌های دعوت</b>\n\n" .
            "هر دوست با پروفایل کامل: <b>+{$per}</b> سکه\n" .
            "دعوت‌های موفق تو: <b>{$count}</b>\n" .
            "مجموع سکه دعوت: <b>{$earned}</b>\n\n" .
            $this->inviteMilestoneProgressLine($count) . "\n\n" .
            "نکته: پاداش فقط وقتی داده می‌شود که دوست پروفایلش را کامل کند.";
        $this->uiText($chatId, $user, $text, [
            'reply_markup' => Keyboards::inviteHubInline($this->inviteShareUrl($user)),
        ]);
        $this->pinHamGapMenu($chatId, $user);
    }

    private function prefLabel(string $pref): string
    {
        return match ($pref) {
            'male' => 'پسر',
            'female' => 'دختر',
            'shemale' => 'شیمیل / دوجنسه',
            'province' => 'هم‌استان',
            'age' => 'هم‌سن',
            'any' => 'شانسی',
            default => 'مخاطب',
        };
    }

    private function isProfileComplete(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        return !empty($user['gender']) && !empty($user['age']) && !empty($user['province']) && !empty($user['city']);
    }

    private function ensureProfileOrMain(int $chatId, array &$user, bool $freshStart): void
    {
        if ($this->isProfileComplete($user)) {
            $this->showMain($chatId, $user, $freshStart ? 'دوباره خوش اومدی 🌿' : '');
            return;
        }

        if (empty($user['gender'])) {
            if ($freshStart) {
                $this->uiText($chatId, $user, $this->welcomeTextFirst());
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

        if (empty($user['province'])) {
            if ($freshStart) {
                $this->uiText($chatId, $user, $this->welcomeTextFirst());
            }
            $this->askProvinceMenu($chatId, $user);
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

    private function askProvinceMenu(int $chatId, array &$user): void
    {
        $this->uiText($chatId, $user, 'استان خودت رو از منوی زیر انتخاب کن 👇', [
            'reply_markup' => Keyboards::provinces(),
        ]);
    }

    private function askCityMenu(int $chatId, array &$user): void
    {
        $province = (string)($user['province'] ?? '');
        if ($province === '' || IranLocations::cities($province) === []) {
            $this->askProvinceMenu($chatId, $user);
            return;
        }
        $safe = htmlspecialchars($province, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->uiText($chatId, $user, "شهر خودت در استان <b>{$safe}</b> رو انتخاب کن 👇", [
            'reply_markup' => Keyboards::cities($province),
        ]);
    }

    private function saveCity(int $chatId, array $user, string $city): void
    {
        $tid = (int)$user['telegram_id'];
        $city = mb_substr(trim($city), 0, 64);
        if (mb_strlen($city) < 2) {
            $this->uiText($chatId, $user, 'نام شهر معتبر بفرست یا از منو انتخاب کن.', [
                'reply_markup' => !empty($user['province'])
                    ? Keyboards::cities((string)$user['province'])
                    : Keyboards::provinces(),
            ]);
            return;
        }
        if (empty($user['province'])) {
            $this->clearUi($chatId, $user);
            $this->askProvinceMenu($chatId, $user);
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
        $this->db->ensureIdentity($user);
        $fresh = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $this->payReferralIfDue($fresh);
        $fresh = $this->db->findUser((int)$user['telegram_id']) ?? $fresh;
        $dn = htmlspecialchars((string)($fresh['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $gift = max(0, $this->settings->getInt('welcome_coins', 35));
        $invitee = max(0, $this->settings->getInt('invitee_bonus', 10));
        $giftLine = $gift > 0 ? "{$gift} سکه هدیه گرفتی.\n" : '';
        if (!empty($fresh['referred_by']) && $invitee > 0 && $this->db->hasCoinReason((int)$fresh['telegram_id'], 'invitee_bonus')) {
            $giftLine .= "بونوس دعوت: +{$invitee} سکه.\n";
        }
        $invite = $this->settings->getInt('invite_reward', 30);
        $this->showMain(
            $chatId,
            $fresh,
            "پروفایل آماده شد ✅\n" .
            "نام کاربری تو: <b>{$dn}</b>\n" .
            $giftLine .
            "از «پروفایل من» می‌تونی عکس و نام را عوض کنی.\n" .
            "دوستات رو دعوت کن و برای هر نفر <b>+{$invite}</b> سکه بگیر ✨"
        );
        $this->showProfile($chatId, $fresh);
        // Optional occupation tag right after signup
        if (empty($fresh['occupation'])) {
            $this->askOccupation($chatId, $fresh, true);
        }
    }

    private function askOccupation(int $chatId, array &$user, bool $afterSignup): void
    {
        $cur = Occupation::badge((string)($user['occupation'] ?? ''));
        $curLine = $cur !== '' ? "فعلی: <b>{$cur}</b>\n\n" : '';
        $note = $afterSignup
            ? "اختیاریه — می‌تونی رد شی.\n"
            : '';
        $this->uiText(
            $chatId,
            $user,
            "🎓 <b>تخصص / تحصیلات</b>\n" .
            $note .
            $curLine .
            "این تگ <i>خوداظهاری</i> است و مدرک رسمی نیست.\n" .
            "برای تفکیک بهتر در جستجو انتخاب کن 👇",
            [
                'reply_markup' => Keyboards::occupationInline(
                    $afterSignup,
                    $afterSignup ? 'menu:main' : 'menu:profile_settings'
                ),
            ]
        );
        $this->pinHamGapMenu($chatId, $user);
    }

    private function handleOccupationCallback(
        array $cq,
        array &$user,
        string $id,
        string $data,
        int $chatId,
        int $tid
    ): void {
        if ($data === 'occ:skip') {
            $this->tg->answerCallback($id, 'باشه');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->showMain($chatId, $user, 'هر وقت خواستی از تنظیمات پروفایل تخصصت را اضافه کن.');
            return;
        }
        if ($data === 'occ:clear') {
            $this->db->updateUser($tid, ['occupation' => null]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id, 'پاک شد');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->showProfile($chatId, $user);
            return;
        }
        if (str_starts_with($data, 'occ:set:')) {
            $key = substr($data, strlen('occ:set:'));
            if (!Occupation::isValid($key)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $this->db->updateUser($tid, ['occupation' => $key, 'show_occupation' => 1]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id, Occupation::short($key) . ' ✅');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->showProfile($chatId, $user);
            return;
        }
        $this->tg->answerCallback($id);
    }

    private function showHelp(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $name = $this->botName();
        $invite = $this->settings->getInt('invite_reward', 30);
        $msgCost = $this->settings->getInt('message_cost', 2);
        $reqCost = $this->settings->getInt('request_cost', 1);
        $banAt = max(1, $this->settings->getInt('report_ban_threshold', 10));
        $caption =
            "ℹ️ <b>راهنمای {$name}</b>\n\n" .
            "💬 چت ناشناس — اتصال رندوم (هزینه از پنل ادمین)\n" .
            "🔍 جستجو — کارت · کشویی ستونی · فهرست · عکس\n" .
            "👤 پروفایل — عکس، نام، بیو، تخصص/تحصیلات، لایک، حریم خصوصی\n" .
            "🎓 تخصص — پزشک، مهندس، دانشجو و… (خوداظهاری · قابل فیلتر در جستجو)\n" .
            "📩 چت خصوصی — درخواست با تأیید · اگر طرف مقابل مشغول بود می‌تونی خبر پایان چت بگیری\n" .
            "✉️ پیام بدون درخواست — هر پیام {$msgCost} سکه · درخواست {$reqCost} سکه\n" .
            "👥 گپ دوستان — ساخت/ورود با سکه · بستن = پاک‌سازی کامل\n" .
            "🚩 گزارش تخلف — با {$banAt} گزارش (قابل تنظیم در ادمین) خودکار مسدود؛ فعال‌سازی با پشتیبانی\n" .
            "✨ دعوت — هر دعوت کامل +{$invite} سکه · جایزه پله‌ای ۳/۱۰/۲۵\n\n" .
            "دستورها: /profile /coins /search /link\n" .
            "رمز و کارت بانکی را در چت نفرست.";
        $path = $this->assets . '/menu-help.jpg';
        if (is_file($path)) {
            $this->uiPhoto($chatId, $user, $path, $caption, Keyboards::helpInline());
        } else {
            $this->uiText($chatId, $user, $caption, [
                'reply_markup' => Keyboards::helpInline(),
            ]);
        }
        $this->pinHamGapMenu($chatId, $user);
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
        $this->uiText($chatId, $user, '⬇️ منوی هم‌گپ', [
            'reply_markup' => Keyboards::mainReply($this->settings->getInt('invite_reward', 30)),
        ]);
    }

    private function showConnect(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $path = $this->assets . '/menu-smart.jpg';
        $costs = $this->connectCosts();
        $lines = [
            "💬 <b>چت ناشناس</b>",
            "بدون نمایش هویت تلگرام وصل شو.",
            '',
            '• شانسی: ' . ($costs['any'] > 0 ? "{$costs['any']} سکه" : 'رایگان'),
            '• فیلتر جنسیت: ' . ($costs['gender'] > 0 ? "{$costs['gender']} سکه" : 'رایگان'),
            '• هم‌استان: ' . ($costs['province'] > 0 ? "{$costs['province']} سکه" : 'رایگان'),
            '• هم‌سن: ' . ($costs['age'] > 0 ? "{$costs['age']} سکه" : 'رایگان'),
        ];
        $caption = implode("\n", $lines);
        $kb = Keyboards::connectInline($costs);
        if (is_file($path)) {
            $this->uiPhoto($chatId, $user, $path, $caption, $kb);
        } else {
            $this->uiText($chatId, $user, $caption, [
                'reply_markup' => $kb,
            ]);
        }
        $this->pinHamGapMenu($chatId, $user);
    }

    private function showFind(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $this->db->updateUser((int)$user['telegram_id'], ['flow' => null, 'browse_cursor' => 0]);
        $user = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $this->uiText(
            $chatId,
            $user,
            "🔍 <b>جستجوی کاربران</b> · رایگان\n\n" .
            "یک گزینه را انتخاب کن؛ بعد از پیدا شدن، کارت کاربر باز می‌شود.\n" .
            "روی کارت می‌تونی: پیام بفرستی، درخواست چت بدهی، گزارش تخلف بزنی یا بلاک کنی.\n\n" .
            "📍 نزدیک من → تا ۱۰۰ نفر نزدیک\n" .
            "🟢 آنلاین → کسانی که الان فعال‌اند",
            ['reply_markup' => Keyboards::searchHubInline()]
        );
        $this->pinHamGapMenu($chatId, $user);
    }

    private function showFriends(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $invite = $this->settings->getInt('invite_reward', 30);
        $createCost = $this->settings->getInt('room_create_cost', 5);
        $joinCost = $this->settings->getInt('room_join_cost', 1);
        $this->uiText(
            $chatId,
            $user,
            "👥 <b>چت با دوستان</b>\n\n" .
            "ساخت گپ گروهی: <b>{$createCost}</b> سکه\n" .
            "ورود هر نفر با کد: <b>{$joinCost}</b> سکه\n" .
            "با بستن گپ، کل پیام‌ها و اطلاعات گپ پاک می‌شود.\n\n" .
            "یا با لینک دعوت دوست جدید بیاور و <b>+{$invite} سکه</b> بگیر.",
            ['reply_markup' => Keyboards::friendsInline($createCost)]
        );
        $this->pinHamGapMenu($chatId, $user);
    }

    private function showSupport(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $u = trim($this->settings->get('support_bot_username'));
        $hours = htmlspecialchars($this->settings->get('support_hours'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $line = $u !== ''
            ? 'بات پشتیبانی: <b>@' . htmlspecialchars($u, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>'
            : 'بات پشتیبانی هنوز از پنل ادمین تنظیم نشده؛ می‌توانی همین‌جا پیام بفرستی.';
        $this->uiText(
            $chatId,
            $user,
            "پشتیبانی و خدمات هم‌گپ\n{$line}\nساعات پاسخگویی: <b>{$hours}</b>",
            ['reply_markup' => Keyboards::supportInline($u !== '' ? $u : null)]
        );
        $this->pinHamGapMenu($chatId, $user);
    }

    private function showProfile(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $this->db->ensureIdentity($user);
        $user = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $g = Gender::label((string)($user['gender'] ?? ''));
        $occBadge = Occupation::badge((string)($user['occupation'] ?? ''));
        $dn = htmlspecialchars((string)($user['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $prov = htmlspecialchars((string)($user['province'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $city = htmlspecialchars((string)($user['city'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pc = htmlspecialchars((string)($user['public_code'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $bio = trim((string)($user['bio'] ?? ''));
        $bioLine = $bio !== ''
            ? htmlspecialchars($bio, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '<i>بیو هنوز نوشته نشده</i>';
        $vis = match ((string)($user['profile_visibility'] ?? 'public')) {
            'hidden' => 'مخفی کامل',
            'friends' => 'فقط دوستان',
            default => 'عمومی',
        };
        $flags = [];
        foreach ([
            'show_gender' => 'جنسیت',
            'show_occupation' => 'تخصص',
            'show_age' => 'سن',
            'show_province' => 'استان',
            'show_city' => 'شهر',
            'show_online' => 'آنلاین',
            'show_avatar' => 'عکس',
        ] as $k => $label) {
            $flags[] = ((int)($user[$k] ?? 1) === 1 ? '✅' : '🚫') . $label;
        }
        $occLine = $occBadge !== ''
            ? "تخصص: <b>" . htmlspecialchars($occBadge, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b> <i>(خوداظهاری)</i>\n"
            : "تخصص: <i>ثبت نشده</i>\n";
        $text = "👤 <b>پروفایل من</b>\n" .
            "────────────\n" .
            "<b>{$dn}</b>\n" .
            "کد عمومی: <code>{$pc}</code>\n" .
            "{$g} · سن " . ($user['age'] ?? '-') . "\n" .
            $occLine .
            "{$prov} / {$city}\n" .
            "سکه: <b>" . (int)$user['coins'] . "</b>\n" .
            "❤️ لایک‌ها: <b>" . $this->db->countLikes((int)$user['telegram_id']) . "</b>\n" .
            "نمایش پروفایل: <b>{$vis}</b>\n" .
            "فیلدها: " . implode(' · ', $flags) . "\n" .
            "────────────\n" .
            "📝 {$bioLine}";

        $avatar = (string)($user['avatar_file_id'] ?? '');
        $invite = $this->settings->getInt('invite_reward', 30);
        $kb = Keyboards::profileInline($invite);
        if ($avatar !== '') {
            $this->uiPhotoFileId($chatId, $user, $avatar, $text, $kb);
        } else {
            $this->uiText($chatId, $user, $text, [
                'reply_markup' => $kb,
            ]);
        }
        $this->pinHamGapMenu($chatId, $user);
    }

    private function showWallet(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $invite = $this->settings->getInt('invite_reward', 30);
        $msgCost = $this->settings->getInt('message_cost', 1);
        $reqCost = $this->settings->getInt('request_cost', 1);
        $packs = $this->packPrices();
        $path = $this->assets . '/menu-wallet.jpg';
        $caption = "کیف‌پول تو\n" .
            "موجودی: <b>" . (int)$user['coins'] . "</b> سکه\n\n" .
            "جستجو رایگان است.\n" .
            "هر پیام کوتاه: {$msgCost} سکه · هر درخواست گفتگو: {$reqCost} سکه\n" .
            "دعوت دوست: +{$invite} سکه\n\n" .
            "برای خرید سکه یک بسته را انتخاب کن. فاکتور با مبلغ یکتا صادر می‌شود.";
        $kb = Keyboards::walletInline($invite, $packs);
        if (is_file($path)) {
            $this->uiPhoto($chatId, $user, $path, $caption, $kb);
        } else {
            $this->uiText($chatId, $user, $caption, [
                'reply_markup' => $kb,
            ]);
        }
        $this->pinHamGapMenu($chatId, $user);
    }

    private function startChat(
        int $chatId,
        array &$user,
        string $pref,
        array $filters = [],
        string $extraNote = ''
    ): void {
        if (($user['status'] ?? '') === 'chatting') {
            $ended = [(int)$user['telegram_id']];
            $oldPartner = $this->matcher->endChat($user, true);
            if ($oldPartner) {
                $ended[] = $oldPartner;
                $p = $this->db->findUser($oldPartner);
                if ($p) {
                    try {
                        $this->clearUi($oldPartner, $p);
                        $this->tg->sendMessage($oldPartner, 'طرف مقابل چت را ترک کرد.');
                        $this->showMain($oldPartner, $p);
                    } catch (Throwable $e) {
                    }
                }
            }
            $this->flushChatWaitNotices($ended);
            $user = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        }

        $result = $this->matcher->startSearch($user, $pref, $filters);

        if (!($result['ok'] ?? false)) {
            $err = (string)($result['error'] ?? '');
            if ($err === 'no_coins') {
                $need = max(1, Matcher::costFor($pref, $this->settings));
                $this->showNeedCoins($chatId, $user, $need, 'شروع چت');
                return;
            }
            if ($err === 'need_province') {
                $this->uiText($chatId, $user, 'برای هم‌استان، اول استان پروفایلت را کامل کن.');
                $this->showProfile($chatId, $user);
                return;
            }
            if ($err === 'need_age') {
                $this->uiText($chatId, $user, 'برای هم‌سن، اول سن پروفایلت را کامل کن.');
                $this->showProfile($chatId, $user);
                return;
            }
            $this->uiText($chatId, $user, 'الان نشد شروع کنیم. دوباره تلاش کن.');
            return;
        }

        if (!empty($result['matched'])) {
            $this->notifyConnected($chatId, (int)$result['partner']['telegram_id']);
            return;
        }

        $label = $this->prefLabel($pref);
        $note = $extraNote !== '' ? "\n{$extraNote}" : '';
        $this->clearUi($chatId, $user);
        $this->uiText(
            $chatId,
            $user,
            "🔍 در حال پیدا کردن مخاطب ({$label})...{$note}\nلطفاً صبر کن.",
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
        $endedIds = [(int)$user['telegram_id']];
        $partnerId = $this->matcher->endChat($user, true);
        if ($partnerId) {
            $endedIds[] = $partnerId;
        }
        $this->clearUi($chatId, $user);
        $this->tg->sendMessage($chatId, 'چت پایان یافت.\nتاریخچه این گفتگو پاک شد و لاگی نگه داشته نمی‌شود.');
        $fresh = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        if ($partnerId) {
            $p = $this->db->findUser($partnerId);
            if ($p) {
                $this->clearUi($partnerId, $p);
                $this->tg->sendMessage($partnerId, 'طرف مقابل چت را پایان داد.\nتاریخچه پاک شد.');
                $this->showMain($partnerId, $p);
            }
        }
        $this->flushChatWaitNotices($endedIds);
        $this->showMain($chatId, $fresh);
    }

    private function nextChat(int $chatId, array $user): void
    {
        $endedIds = [(int)$user['telegram_id']];
        $partnerId = $this->matcher->endChat($user, true);
        if ($partnerId) {
            $endedIds[] = $partnerId;
            $p = $this->db->findUser($partnerId);
            if ($p) {
                $this->clearUi($partnerId, $p);
                $this->tg->sendMessage($partnerId, 'طرف مقابل رفت سراغ نفر بعدی.');
                $this->showMain($partnerId, $p);
            }
        }
        $this->flushChatWaitNotices($endedIds);
        $fresh = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $this->clearUi($chatId, $fresh);
        $this->startChat($chatId, $fresh, 'any');
    }

    private function report(int $chatId, array $user): void
    {
        $this->showChatReportForm($chatId, $user);
    }

    private function showChatReportForm(int $chatId, array &$user): void
    {
        $partnerId = !empty($user['partner_id']) ? (int)$user['partner_id'] : null;
        if (!$partnerId) {
            $this->uiText($chatId, $user, 'الان در چت نیستی.');
            return;
        }
        $partner = $this->db->findUser($partnerId);
        $dn = htmlspecialchars((string)($partner['display_name'] ?? 'طرف مقابل'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->clearUi($chatId, $user);
        $this->uiText(
            $chatId,
            $user,
            "⚠️ <b>فرم گزارش تخلف</b>\n\n" .
            "چرا می‌خوای <b>{$dn}</b> را گزارش کنی؟\n\n" .
            "توجه: همه گزارش‌ها بررسی می‌شوند.\n" .
            "⛔️ گزارش عمداً اشتباه ممکن است حساب خودت را محدود کند.\n\n" .
            "دلیل را انتخاب کن 👇",
            ['reply_markup' => Keyboards::chatReportReasonsInline()]
        );
    }

    private function showReportForm(int $chatId, array &$user, array $target, string $prefix = 'br'): void
    {
        $dn = htmlspecialchars((string)($target['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pc = htmlspecialchars((string)($target['public_code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $meta = [];
        if ((int)($target['show_gender'] ?? 1) === 1) {
            $meta[] = Gender::label((string)($target['gender'] ?? ''));
        }
        if ((int)($target['show_age'] ?? 1) === 1) {
            $meta[] = (int)($target['age'] ?? 0) . ' ساله';
        }
        $loc = [];
        if ((int)($target['show_province'] ?? 1) === 1 && !empty($target['province'])) {
            $loc[] = htmlspecialchars((string)$target['province'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if ((int)($target['show_city'] ?? 1) === 1 && !empty($target['city'])) {
            $loc[] = htmlspecialchars((string)$target['city'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $lines = [
            '⚠️ <b>فرم گزارش تخلف</b>',
            '',
            "<b>{$dn}</b>",
        ];
        if ($meta) {
            $lines[] = implode(' · ', $meta);
        }
        if ($loc) {
            $lines[] = implode(' · ', $loc);
        }
        $lines[] = "شناسه: <code>{$pc}</code>";
        $lines[] = '';
        $lines[] = "چرا می‌خوای <b>{$dn}</b> را گزارش کنی؟";
        $lines[] = '';
        $lines[] = 'توجه: همه گزارش‌ها بررسی می‌شوند.';
        $lines[] = '⛔️ گزارش عمداً اشتباه ممکن است حساب خودت را محدود کند.';
        $lines[] = '';
        $lines[] = 'دلیل را انتخاب کن 👇';
        $this->uiText($chatId, $user, implode("\n", $lines), [
            'reply_markup' => Keyboards::reportReasonsInline((string)$target['public_code'], $prefix),
        ]);
    }

    private function handleBrowseReportChoice(
        array $cq,
        array &$user,
        string $id,
        int $chatId,
        int $tid,
        string $code,
        string $reasonKey
    ): void {
        $labels = Keyboards::reportReasonLabels();
        if (!isset($labels[$reasonKey])) {
            $this->tg->answerCallback($id, 'نامعتبر', true);
            return;
        }
        $target = $this->db->findByPublicCode($code);
        if (!$target) {
            $this->tg->answerCallback($id, 'کاربر پیدا نشد', true);
            return;
        }
        if ($reasonKey === 'other') {
            $this->db->updateUser($tid, ['flow' => 'br:repother:' . $code]);
            $this->tg->answerCallback($id, 'توضیح را بنویس');
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $dn = htmlspecialchars((string)($target['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->openTypeKeyboard(
                $chatId,
                $user,
                "✏️ توضیح گزارش برای <b>{$dn}</b> را بنویس و بفرست.\n(حداکثر چند خط کوتاه)"
            );
            return;
        }
        $label = $labels[$reasonKey];
        $this->applyReportAndMaybeBan($tid, (int)$target['telegram_id'], $label);
        $this->tg->answerCallback($id, 'گزارش ثبت شد');
        $this->stripCallbackMenu($cq);
        $this->clearUi($chatId, $user);
        $this->uiText($chatId, $user, "✅ گزارش «{$label}» ثبت شد.\nممنون از همکاری‌ات.", [
            'reply_markup' => ['inline_keyboard' => [
                [['text' => 'کاربر بعدی', 'callback_data' => 'br:next']],
                [['text' => 'بازگشت به جستجو', 'callback_data' => 'menu:find']],
            ]],
        ]);
    }

    private function finishBrowseReportOther(int $chatId, array &$user, string $text): void
    {
        $tid = (int)$user['telegram_id'];
        $flow = (string)($user['flow'] ?? '');
        $code = str_starts_with($flow, 'br:repother:') ? substr($flow, strlen('br:repother:')) : '';
        $this->db->updateUser($tid, ['flow' => null]);
        $user = $this->db->findUser($tid) ?? $user;
        $target = $this->db->findByPublicCode($code);
        if (!$target) {
            $this->uiText($chatId, $user, 'کاربر پیدا نشد.');
            return;
        }
        $note = mb_substr(trim($text), 0, 400);
        $reason = 'سایر موارد: ' . $note;
        $this->applyReportAndMaybeBan($tid, (int)$target['telegram_id'], $reason);
        $this->clearUi($chatId, $user);
        $this->uiText($chatId, $user, "✅ گزارش با توضیح تو ثبت شد.\nممنون از همکاری‌ات.", [
            'reply_markup' => ['inline_keyboard' => [
                [['text' => 'کاربر بعدی', 'callback_data' => 'br:next']],
                [['text' => 'بازگشت به جستجو', 'callback_data' => 'menu:find']],
            ]],
        ]);
    }

    private function handleChatReportChoice(
        array $cq,
        array &$user,
        string $id,
        int $chatId,
        int $tid,
        string $reasonKey
    ): void {
        $labels = Keyboards::reportReasonLabels();
        if (!isset($labels[$reasonKey])) {
            $this->tg->answerCallback($id, 'نامعتبر', true);
            return;
        }
        $partnerId = !empty($user['partner_id']) ? (int)$user['partner_id'] : null;
        if (!$partnerId) {
            $this->tg->answerCallback($id, 'الان در چت نیستی', true);
            return;
        }
        if ($reasonKey === 'other') {
            $this->db->updateUser($tid, ['flow' => 'cr:other']);
            $this->tg->answerCallback($id, 'توضیح را بنویس');
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->openTypeKeyboard($chatId, $user, "✏️ دلیل گزارش را کوتاه بنویس و بفرست.");
            return;
        }
        $label = $labels[$reasonKey];
        $this->applyReportAndMaybeBan($tid, $partnerId, $label);
        $this->tg->answerCallback($id, 'گزارش ثبت شد');
        $this->stripCallbackMenu($cq);
        $this->endAndMenu($chatId, $user);
    }

    private function finishChatReportOther(int $chatId, array &$user, string $text): void
    {
        $tid = (int)$user['telegram_id'];
        $partnerId = !empty($user['partner_id']) ? (int)$user['partner_id'] : null;
        $this->db->updateUser($tid, ['flow' => null]);
        $user = $this->db->findUser($tid) ?? $user;
        if ($partnerId) {
            $note = mb_substr(trim($text), 0, 400);
            $this->applyReportAndMaybeBan($tid, $partnerId, 'سایر موارد: ' . $note);
        }
        $this->endAndMenu($chatId, $user);
    }

    private function handleSearchHubCallback(
        array $cq,
        array &$user,
        string $id,
        string $data,
        int $chatId,
        int $tid
    ): void {
        if ($data === 'sr:advanced') {
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->db->updateUser($tid, ['flow' => 'adv:', 'browse_cursor' => 0]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->uiText(
                $chatId,
                $user,
                "🎛 <b>جستجوی پیشرفته</b>\nاول استان را انتخاب کن (یا همه استان‌ها).",
                ['reply_markup' => Keyboards::advancedProvinces()]
            );
            return;
        }

        if (str_starts_with($data, 'sr:online')) {
            $filters = ['online_only' => 1];
            if ($data === 'sr:online:female') {
                $filters['gender'] = 'female';
            } elseif ($data === 'sr:online:male') {
                $filters['gender'] = 'male';
            } elseif ($data === 'sr:online:shemale') {
                $filters['gender'] = 'shemale';
            }
            $this->tg->answerCallback($id, 'آنلاین‌ها');
            $this->stripCallbackMenu($cq);
            $this->beginBrowse($chatId, $user, $filters);
            return;
        }
        if ($data === 'sr:slide') {
            $this->tg->answerCallback($id, 'نمایش کشویی');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            // Seed cache with online users then open slide / inline
            $rows = $this->db->listBrowseProfiles($tid, ['online_only' => 1], 50);
            $ids = [];
            foreach ($rows as $row) {
                $ids[] = (int)$row['telegram_id'];
            }
            $this->db->setBrowseCache($tid, ['ids' => $ids, 'filters' => ['online_only' => 1], 'page' => 0]);
            $this->db->updateUser($tid, [
                'flow' => 'browse:' . rawurlencode(json_encode(['online_only' => 1], JSON_UNESCAPED_UNICODE)),
                'browse_view' => 'slide',
                'browse_cursor' => 0,
            ]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->openSlideMode($chatId, $user);
            return;
        }
        if ($data === 'sr:new') {
            $this->tg->answerCallback($id, 'تازه‌واردها');
            $this->stripCallbackMenu($cq);
            $this->beginBrowse($chatId, $user, ['new_only' => 1]);
            return;
        }
        if ($data === 'sr:nearby') {
            $city = (string)($user['city'] ?? '');
            $prov = (string)($user['province'] ?? '');
            if ($city === '' || $prov === '') {
                $this->tg->answerCallback($id, 'اول استان و شهر پروفایلت را کامل کن', true);
                return;
            }
            $this->tg->answerCallback($id, 'جستجوی نزدیک‌ترین‌ها…');
            $this->stripCallbackMenu($cq);
            $this->beginBrowse($chatId, $user, [
                'nearby_rank' => 1,
                'nearby_city' => $city,
                'nearby_province' => $prov,
                'pick_view' => 1,
            ], 100);
            return;
        }
        if ($data === 'sr:sameprov') {
            $prov = (string)($user['province'] ?? '');
            if ($prov === '') {
                $this->tg->answerCallback($id, 'اول استان پروفایلت را کامل کن', true);
                return;
            }
            $this->tg->answerCallback($id, 'هم‌استان');
            $this->stripCallbackMenu($cq);
            $this->beginBrowse($chatId, $user, ['same_province' => $prov]);
            return;
        }
        if ($data === 'sr:sameage') {
            $age = (int)($user['age'] ?? 0);
            if ($age <= 0) {
                $this->tg->answerCallback($id, 'اول سن پروفایلت را کامل کن', true);
                return;
            }
            $this->tg->answerCallback($id, 'هم‌سن');
            $this->stripCallbackMenu($cq);
            $this->beginBrowse($chatId, $user, ['age_near' => 1, 'viewer_age' => $age]);
            return;
        }
        if ($data === 'sr:occ') {
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->uiText(
                $chatId,
                $user,
                "🎓 <b>جستجو بر اساس تخصص / تحصیلات</b>\n" .
                "تگ‌ها خوداظهاری هستند؛ مدرک رسمی نیست.\nیکی را انتخاب کن 👇",
                ['reply_markup' => Keyboards::occupationSearchInline()]
            );
            return;
        }
        if (str_starts_with($data, 'sr:occ:')) {
            $key = substr($data, strlen('sr:occ:'));
            if ($key === 'any') {
                $this->tg->answerCallback($id, 'دارای تخصص');
                $this->stripCallbackMenu($cq);
                $this->beginBrowse($chatId, $user, ['has_occupation' => 1]);
                return;
            }
            if (!Occupation::isValid($key)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $this->tg->answerCallback($id, Occupation::short($key));
            $this->stripCallbackMenu($cq);
            $this->beginBrowse($chatId, $user, ['occupation' => $key]);
            return;
        }

        // Advanced wizard
        if (str_starts_with($data, 'adv:prov:')) {
            $part = substr($data, strlen('adv:prov:'));
            $flow = 'adv:';
            if ($part === 'all') {
                $flow = 'adv:all';
                $this->tg->answerCallback($id, 'همه استان‌ها');
                $this->stripCallbackMenu($cq);
                $this->db->updateUser($tid, ['flow' => $flow]);
                $user = $this->db->findUser($tid) ?? $user;
                $this->clearUi($chatId, $user);
                $this->uiText($chatId, $user, 'جنسیت مخاطب را انتخاب کن 👇', [
                    'reply_markup' => Keyboards::advancedGenderInline(),
                ]);
                return;
            }
            $idx = (int)$part;
            $provinces = IranLocations::provinces();
            if (!isset($provinces[$idx])) {
                $this->tg->answerCallback($id, 'استان نامعتبر', true);
                return;
            }
            $province = $provinces[$idx];
            $this->db->updateUser($tid, ['flow' => 'adv:' . $idx]);
            $this->tg->answerCallback($id, "{$province} ✅");
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $safe = htmlspecialchars($province, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->uiText($chatId, $user, "شهر در استان <b>{$safe}</b> را انتخاب کن 👇", [
                'reply_markup' => Keyboards::advancedCities($province),
            ]);
            return;
        }

        if (str_starts_with($data, 'adv:ci:')) {
            $part = substr($data, strlen('adv:ci:'));
            $flow = (string)($user['flow'] ?? '');
            if (!preg_match('/^adv:(\d+)$/', $flow, $fm)) {
                $this->tg->answerCallback($id, 'اول استان را انتخاب کن', true);
                return;
            }
            $provIdx = (int)$fm[1];
            $provinces = IranLocations::provinces();
            if (!isset($provinces[$provIdx])) {
                $this->tg->answerCallback($id, 'استان نامعتبر', true);
                return;
            }
            $province = $provinces[$provIdx];
            if ($part === 'all') {
                $this->db->updateUser($tid, ['flow' => 'adv:' . $provIdx . ':all']);
            } else {
                $cityIdx = (int)$part;
                $cities = IranLocations::cities($province);
                if (!isset($cities[$cityIdx])) {
                    $this->tg->answerCallback($id, 'شهر نامعتبر', true);
                    return;
                }
                $this->db->updateUser($tid, ['flow' => 'adv:' . $provIdx . ':' . $cityIdx]);
                $this->tg->answerCallback($id, $cities[$cityIdx] . ' ✅');
                $this->stripCallbackMenu($cq);
                $user = $this->db->findUser($tid) ?? $user;
                $this->clearUi($chatId, $user);
                $this->uiText($chatId, $user, 'جنسیت مخاطب را انتخاب کن 👇', [
                    'reply_markup' => Keyboards::advancedGenderInline(),
                ]);
                return;
            }
            $this->tg->answerCallback($id, 'همه شهرها');
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->uiText($chatId, $user, 'جنسیت مخاطب را انتخاب کن 👇', [
                'reply_markup' => Keyboards::advancedGenderInline(),
            ]);
            return;
        }

        if (str_starts_with($data, 'adv:gender:')) {
            $g = substr($data, strlen('adv:gender:'));
            if (!Gender::isFilter($g)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $flow = (string)($user['flow'] ?? '');
            $this->db->updateUser($tid, ['flow' => $flow . '|g:' . $g]);
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->uiText($chatId, $user, 'بازه سنی را انتخاب کن 👇', [
                'reply_markup' => Keyboards::advancedAgeInline(),
            ]);
            return;
        }

        if (str_starts_with($data, 'adv:age:')) {
            $rest = substr($data, strlen('adv:age:'));
            $filters = $this->parseAdvancedFlow((string)($user['flow'] ?? ''));
            if ($rest === 'any') {
                // no age bounds
            } else {
                $parts = explode(':', $rest);
                if (count($parts) === 2) {
                    $filters['age_min'] = (int)$parts[0];
                    $filters['age_max'] = (int)$parts[1];
                }
            }
            $this->tg->answerCallback($id, 'شروع جستجو');
            $this->stripCallbackMenu($cq);
            $this->beginBrowse($chatId, $user, $filters);
            return;
        }

        $this->tg->answerCallback($id);
    }

    /** @return array<string,mixed> */
    private function parseAdvancedFlow(string $flow): array
    {
        $filters = [];
        // adv:all|g:female  OR adv:3:5|g:male OR adv:3:all|g:any
        if (!str_starts_with($flow, 'adv:')) {
            return $filters;
        }
        $body = substr($flow, 4);
        $gender = null;
        if (str_contains($body, '|g:')) {
            [$loc, $gPart] = explode('|g:', $body, 2);
            $gender = $gPart;
            $body = $loc;
        }
        if ($gender && Gender::isValid($gender)) {
            $filters['gender'] = $gender;
        }
        if ($body === 'all' || $body === '') {
            return $filters;
        }
        $bits = explode(':', $body);
        $provinces = IranLocations::provinces();
        $provIdx = (int)($bits[0] ?? -1);
        if (!isset($provinces[$provIdx])) {
            return $filters;
        }
        $filters['province'] = $provinces[$provIdx];
        if (isset($bits[1]) && $bits[1] !== 'all') {
            $cities = IranLocations::cities($filters['province']);
            $ci = (int)$bits[1];
            if (isset($cities[$ci])) {
                $filters['city'] = $cities[$ci];
            }
        }
        return $filters;
    }

    /** @param array<string,mixed> $filters */
    private function beginBrowse(int $chatId, array &$user, array $filters, int $limit = 100): void
    {
        $tid = (int)$user['telegram_id'];
        unset($filters['pick_view']);
        $rows = $this->db->listBrowseProfiles($tid, $filters, $limit);
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['telegram_id'];
        }
        $encoded = rawurlencode(json_encode($filters, JSON_UNESCAPED_UNICODE));
        $this->db->updateUser($tid, [
            'flow' => 'browse:' . $encoded,
            'browse_cursor' => 0,
        ]);
        $this->db->setBrowseCache($tid, [
            'ids' => $ids,
            'filters' => $filters,
            'page' => 0,
        ]);
        $user = $this->db->findUser($tid) ?? $user;
        $this->clearUi($chatId, $user);
        $found = count($ids);
        if ($found === 0) {
            $this->uiText($chatId, $user, "با این فیلتر کسی پیدا نشد.\nیک گزینه دیگر از جستجو را امتحان کن.", [
                'reply_markup' => Keyboards::searchHubInline(),
            ]);
            return;
        }
        // Default: open first profile card immediately — clearer than an extra picker step.
        $this->db->updateUser($tid, ['browse_view' => 'card', 'browse_cursor' => 0]);
        $user = $this->db->findUser($tid) ?? $user;
        $title = !empty($filters['nearby_rank'])
            ? "📍 <b>{$found}</b> نفر نزدیک پیدا شد"
            : (!empty($filters['online_only'])
                ? "🟢 <b>{$found}</b> نفر آنلاین پیدا شد"
                : "🔍 <b>{$found}</b> نفر پیدا شد");
        $this->uiText(
            $chatId,
            $user,
            "{$title}\nکارت اول را باز کردم. با دکمه‌ها چت بخواه، پیام بده، گزارش کن یا بلاک کن.\nبرای تغییر نحوه نمایش از «حالت نمایش» استفاده کن — حالت <b>کشویی ستونی</b> محیط گرافیکی دارد."
        );
        $this->showNextBrowseCard($chatId, $user);
    }

    private function publicBaseUrl(): string
    {
        $u = trim((string)($this->config['public_base_url'] ?? ''));
        if ($u !== '') {
            return rtrim($u, '/');
        }
        return 'https://dev.hdd-land.com';
    }

    private function supportsInlineQueries(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $me = $this->tg->getMe();
            $cached = (bool)($me['result']['supports_inline_queries'] ?? false);
        } catch (Throwable $e) {
            $cached = false;
        }
        return $cached;
    }

    private function openSlideMode(int $chatId, array &$user): void
    {
        $cache = $this->db->getBrowseCache($user) ?? [];
        $ids = $cache['ids'] ?? [];
        if ($ids === []) {
            $this->uiText($chatId, $user, 'اول یک جستجو انجام بده، بعد نمایش کشویی را باز کن.', [
                'reply_markup' => Keyboards::searchHubInline(),
            ]);
            return;
        }
        $uname = $this->botUsername();
        $lines = [
            '🎞 <b>نمایش کشویی ستونی</b>',
            'کاربران را یکی‌یکی با عکس ورق بزن — مثل گالری روان.',
            'گزارش تخلف و بلاک هم روی هر کارت هست.',
        ];
        if ($this->supportsInlineQueries()) {
            $lines[] = '';
            $lines[] = "برای لیست کشویی تلگرام (Inline) روی دکمه بزن یا در چت بنویس:";
            $lines[] = '<code>@' . htmlspecialchars($uname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' آنلاین</code>';
            $this->uiText($chatId, $user, implode("\n", $lines), [
                'reply_markup' => Keyboards::openInlineSlide('آنلاین'),
            ]);
        } else {
            $lines[] = '';
            $lines[] = '💡 برای فعال شدن حالت اینلاین تلگرام، ادمین باید در @BotFather گزینه Inline Mode را روشن کند.';
            $this->uiText($chatId, $user, implode("\n", $lines));
        }
        $this->renderSlideCard($chatId, $user, 0);
    }

    private function renderSlideCard(int $chatId, array &$viewer, int $index): void
    {
        $cache = $this->db->getBrowseCache($viewer) ?? [];
        $ids = $cache['ids'] ?? [];
        if ($ids === []) {
            $this->uiText($chatId, $viewer, 'فهرست خالی است.', [
                'reply_markup' => Keyboards::searchHubInline(),
            ]);
            return;
        }
        $total = count($ids);
        $index = max(0, min($index, $total - 1));
        $target = $this->db->findUser((int)$ids[$index]);
        if (!$target) {
            if ($index + 1 < $total) {
                $this->renderSlideCard($chatId, $viewer, $index + 1);
            } else {
                $this->uiText($chatId, $viewer, 'کاربری برای نمایش نماند.');
            }
            return;
        }
        $this->db->updateUser((int)$viewer['telegram_id'], ['browse_cursor' => $index, 'browse_view' => 'slide']);
        $viewer = $this->db->findUser((int)$viewer['telegram_id']) ?? $viewer;

        $caption = "🎞 <b>کشویی</b> · " . ($index + 1) . "/{$total}\n────────\n" .
            $this->formatPublicProfile($viewer, $target, false);
        $profileKb = $this->browseProfileMarkup($target);
        $navKb = Keyboards::slideNavInline($index, $total);
        $rows = array_merge($navKb['inline_keyboard'], $profileKb['inline_keyboard']);
        // Drop duplicate "حالت نمایش/پایان" from profile if present at end — keep nav's
        $markup = ['inline_keyboard' => $rows];
        $avatar = ((int)($target['show_avatar'] ?? 1) === 1) ? (string)($target['avatar_file_id'] ?? '') : '';
        if ($avatar !== '') {
            $this->uiPhotoFileId($chatId, $viewer, $avatar, $caption, $markup);
        } else {
            $this->uiText($chatId, $viewer, $caption, ['reply_markup' => $markup]);
        }
    }

    private function onInlineQuery(array $iq): void
    {
        $qid = (string)($iq['id'] ?? '');
        $from = $iq['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        $query = trim((string)($iq['query'] ?? ''));
        $offset = (int)($iq['offset'] ?? 0);
        if ($qid === '' || $tid <= 0) {
            return;
        }
        $user = $this->db->upsertUser(
            $tid,
            $from['username'] ?? null,
            $from['first_name'] ?? null,
            $this->settings->getInt('welcome_coins', 35)
        );
        $this->db->touchSeen($tid);
        $user = $this->db->findUser($tid) ?? $user;
        if (($user['status'] ?? '') === 'banned') {
            $this->tg->answerInlineQuery($qid, [], 1, true, '', [
                'text' => 'حساب مسدود است — پشتیبانی',
                'start_parameter' => 'support',
            ]);
            return;
        }

        $profiles = $this->resolveInlineProfiles($user, $query, $offset, 40);
        $results = [];
        $thumbFallback = $this->publicBaseUrl() . '/assets/banners/menu-main.jpg';
        $req = $this->settings->getInt('request_cost', 1);
        $msg = $this->settings->getInt('message_cost', 2);
        $like = $this->settings->getInt('like_cost', 0);
        $notify = max(1, $this->settings->getInt('notify_free_cost', 1));
        foreach ($profiles as $target) {
            $code = preg_replace('/[^A-Za-z0-9_]/', '', (string)($target['public_code'] ?? '')) ?? '';
            if ($code === '') {
                continue;
            }
            $title = (string)($target['display_name'] ?? 'کاربر');
            $descParts = [];
            if ((int)($target['show_age'] ?? 1) === 1 && !empty($target['age'])) {
                $descParts[] = (int)$target['age'] . ' ساله';
            }
            if ((int)($target['show_city'] ?? 1) === 1 && !empty($target['city'])) {
                $descParts[] = (string)$target['city'];
            } elseif ((int)($target['show_province'] ?? 1) === 1 && !empty($target['province'])) {
                $descParts[] = (string)$target['province'];
            }
            $presence = $this->formatPresence($target, false);
            if ($presence !== '') {
                // strip emoji noise for description length
                $descParts[] = preg_replace('/[^\p{L}\p{N}\s‌]/u', '', $presence) ?: $presence;
            }
            $description = implode(' · ', array_filter($descParts));
            $caption = $this->formatPublicProfile($user, $target, true);
            $likes = $this->db->countLikes((int)$target['telegram_id']);
            $markup = Keyboards::browseProfileInline($code, $req, $msg, $like, $likes, $notify);
            $avatar = ((int)($target['show_avatar'] ?? 1) === 1) ? (string)($target['avatar_file_id'] ?? '') : '';
            if ($avatar !== '') {
                $results[] = [
                    'type' => 'photo',
                    'id' => substr($code, 0, 64),
                    'photo_file_id' => $avatar,
                    'title' => $title,
                    'description' => mb_substr($description, 0, 120),
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $markup,
                ];
            } else {
                $results[] = [
                    'type' => 'article',
                    'id' => substr($code, 0, 64),
                    'title' => $title,
                    'description' => mb_substr($description, 0, 120),
                    'thumb_url' => $thumbFallback,
                    'input_message_content' => [
                        'message_text' => $caption,
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true,
                    ],
                    'reply_markup' => $markup,
                ];
            }
        }
        $next = count($profiles) >= 40 ? (string)($offset + 40) : '';
        $button = [
            'text' => 'باز کردن هم‌گپ',
            'start_parameter' => 'inline',
        ];
        try {
            $this->tg->answerInlineQuery($qid, $results, 2, true, $next, $button);
        } catch (Throwable $e) {
            try {
                $this->tg->answerInlineQuery($qid, [], 1, true);
            } catch (Throwable $e2) {
            }
        }
    }

    /** @return list<array> */
    private function resolveInlineProfiles(array $viewer, string $query, int $offset, int $limit): array
    {
        $q = mb_strtolower(trim($query));
        $filters = [];
        $useCache = ($q === '' || $q === 'cache' || $q === 'لیست' || $q === 'نتایج');
        if ($useCache) {
            $cache = $this->db->getBrowseCache($viewer) ?? [];
            $ids = $cache['ids'] ?? [];
            if (is_array($ids) && $ids !== []) {
                $slice = array_slice($ids, $offset, $limit);
                return $this->db->findUsersByTelegramIds($slice);
            }
            $filters = ['online_only' => 1];
        } elseif (str_contains($q, 'آنلاین') || str_contains($q, 'online')) {
            $filters = ['online_only' => 1];
            if (str_contains($q, 'دختر')) {
                $filters['gender'] = 'female';
            } elseif (str_contains($q, 'پسر')) {
                $filters['gender'] = 'male';
            } elseif (str_contains($q, 'شیمیل') || str_contains($q, 'دوجنسه')) {
                $filters['gender'] = 'shemale';
            }
        } elseif (str_contains($q, 'تازه') || str_contains($q, 'new')) {
            $filters = ['new_only' => 1];
        } elseif (str_contains($q, 'نزدیک') || str_contains($q, 'near')) {
            $city = (string)($viewer['city'] ?? '');
            $prov = (string)($viewer['province'] ?? '');
            if ($city !== '' && $prov !== '') {
                $filters = [
                    'nearby_rank' => 1,
                    'nearby_city' => $city,
                    'nearby_province' => $prov,
                ];
            } else {
                $filters = ['online_only' => 1];
            }
        } else {
            $filters = ['online_only' => 1];
        }
        // listBrowseProfiles has no offset — fetch and slice
        $rows = $this->db->listBrowseProfiles((int)$viewer['telegram_id'], $filters, min(100, $offset + $limit));
        return array_slice($rows, $offset, $limit);
    }

    /** @return array{province?:string,city?:string,gender?:string} */
    private function browseFiltersFromUser(array $user): array
    {
        $flow = (string)($user['flow'] ?? '');
        if (!str_starts_with($flow, 'browse:')) {
            return [];
        }
        $raw = rawurldecode(substr($flow, strlen('browse:')));
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function showNextBrowseCard(int $chatId, array &$user): void
    {
        $tid = (int)$user['telegram_id'];
        $cache = $this->db->getBrowseCache($user);
        $ids = $cache['ids'] ?? null;
        if (is_array($ids) && $ids !== []) {
            $idx = (int)($user['browse_cursor'] ?? 0);
            if ($idx >= count($ids)) {
                $idx = 0;
            }
            $target = $this->db->findUser((int)$ids[$idx]);
            if (!$target) {
                // skip missing
                $this->db->updateUser($tid, ['browse_cursor' => $idx + 1]);
                $user = $this->db->findUser($tid) ?? $user;
                if ($idx + 1 < count($ids)) {
                    $this->showNextBrowseCard($chatId, $user);
                } else {
                    $this->uiText($chatId, $user, 'به انتهای فهرست رسیدی.', [
                        'reply_markup' => Keyboards::searchHubInline(),
                    ]);
                }
                return;
            }
            $this->db->updateUser($tid, ['browse_cursor' => $idx + 1, 'browse_view' => 'card']);
            $user = $this->db->findUser($tid) ?? $user;
            $this->renderBrowseCard($chatId, $user, $target);
            return;
        }

        $filters = $this->browseFiltersFromUser($user);
        if (!str_starts_with((string)($user['flow'] ?? ''), 'browse:')) {
            $encoded = rawurlencode(json_encode($filters, JSON_UNESCAPED_UNICODE));
            $this->db->updateUser($tid, ['flow' => 'browse:' . $encoded]);
            $user = $this->db->findUser($tid) ?? $user;
        }
        $cursor = (int)($user['browse_cursor'] ?? 0);
        $target = $this->db->nextBrowseProfile($tid, $cursor, $filters);
        if (!$target) {
            $this->uiText($chatId, $user, "کسی با این فیلتر پیدا نشد.\nاستان دیگری را امتحان کن یا بعداً برگرد.", [
                'reply_markup' => Keyboards::searchHubInline(),
            ]);
            return;
        }
        $this->db->ensureIdentity($target);
        $target = $this->db->findUser((int)$target['telegram_id']) ?? $target;
        $this->db->updateUser($tid, ['browse_cursor' => (int)$target['id']]);
        $user = $this->db->findUser($tid) ?? $user;
        $this->renderBrowseCard($chatId, $user, $target);
    }

    private function renderBrowseBatch(int $chatId, array &$user, int $page): void
    {
        $cache = $this->db->getBrowseCache($user);
        $ids = $cache['ids'] ?? [];
        if ($ids === []) {
            $this->uiText($chatId, $user, 'فهرست خالی است. دوباره جستجو کن.', [
                'reply_markup' => Keyboards::searchHubInline(),
            ]);
            return;
        }
        $mode = (string)($user['browse_view'] ?? 'list');
        $perPage = $mode === 'photo' ? 5 : ($mode === 'menu' ? 10 : 8);
        $total = count($ids);
        $totalPages = (int)ceil($total / $perPage);
        $page = max(0, min($page, max(0, $totalPages - 1)));
        $slice = array_slice($ids, $page * $perPage, $perPage, true);

        if ($mode === 'menu') {
            $items = [];
            foreach ($slice as $i => $tidTarget) {
                $t = $this->db->findUser((int)$tidTarget);
                if (!$t) {
                    continue;
                }
                $label = $this->shortProfileLabel($t, true);
                $items[] = ['i' => (int)$i, 'label' => $label];
            }
            $this->uiText(
                $chatId,
                $user,
                "🎛 <b>انتخاب سریع</b> · صفحه " . ($page + 1) . "/{$totalPages}\nروی اسم بزن تا مشخصات کامل و دکمه‌های چت/گزارش/بلاک باز شود.",
                ['reply_markup' => Keyboards::browseMenuGrid($items, $page, $totalPages)]
            );
            return;
        }

        if ($mode === 'photo') {
            $this->uiText(
                $chatId,
                $user,
                "🖼 <b>نمایش با عکس</b> · " . ($page + 1) . "/{$totalPages} از {$total} نفر\nزیر هر کارت می‌تونی پیام، درخواست، گزارش یا بلاک بزنی.",
                ['reply_markup' => Keyboards::browseListNav($page, $totalPages)]
            );
            foreach ($slice as $i => $tidTarget) {
                $t = $this->db->findUser((int)$tidTarget);
                if (!$t) {
                    continue;
                }
                $caption = $this->formatPublicProfile($user, $t, true);
                $req = $this->settings->getInt('request_cost', 1);
                $msg = $this->settings->getInt('message_cost', 2);
                $markup = Keyboards::browsePhotoInline((string)$t['public_code'], $req, $msg, (int)$i);
                $avatar = ((int)($t['show_avatar'] ?? 1) === 1) ? (string)($t['avatar_file_id'] ?? '') : '';
                if ($avatar !== '') {
                    $this->uiPhotoFileId($chatId, $user, $avatar, $caption, $markup);
                } else {
                    $this->uiText($chatId, $user, $caption, ['reply_markup' => $markup]);
                }
            }
            return;
        }

        // list / columnar default
        $lines = [
            "📋 <b>فهرست نتایج</b> · {$total} نفر · صفحه " . ($page + 1) . "/{$totalPages}",
            'روی شماره بزن تا کارت کامل با گزینه‌های چت، گزارش و بلاک باز شود.',
            '────────────',
        ];
        $buttons = [];
        $row = [];
        $n = $page * $perPage;
        foreach ($slice as $i => $tidTarget) {
            $n++;
            $t = $this->db->findUser((int)$tidTarget);
            if (!$t) {
                continue;
            }
            $lines[] = $n . '. ' . $this->shortProfileLabel($t, false);
            $row[] = ['text' => (string)$n, 'callback_data' => 'bl:o:' . (int)$i];
            if (count($row) === 4) {
                $buttons[] = $row;
                $row = [];
            }
        }
        if ($row) {
            $buttons[] = $row;
        }
        $navKb = Keyboards::browseListNav($page, $totalPages);
        $buttons = array_merge($buttons, $navKb['inline_keyboard']);
        $this->uiText($chatId, $user, implode("\n", $lines), [
            'reply_markup' => ['inline_keyboard' => $buttons],
        ]);
    }

    /**
     * Presence line for browse results.
     * Respects privacy show_online. Compact = short for lists/menus.
     */
    private function formatPresence(array $target, bool $compact = false): string
    {
        if ((int)($target['show_online'] ?? 1) !== 1) {
            return '';
        }
        $raw = (string)($target['last_seen_at'] ?? '');
        if ($raw === '') {
            return $compact ? '⚪' : '⚪ وضعیت نامشخص';
        }
        $ts = strtotime($raw);
        if (!$ts) {
            return $compact ? '⚪' : '⚪ وضعیت نامشخص';
        }
        $sec = max(0, time() - $ts);
        // Online window: active in last 3 minutes
        if ($sec < 180) {
            return $compact ? '🟢' : '🟢 آنلاین الان';
        }
        if ($sec < 3600) {
            $m = max(1, (int)floor($sec / 60));
            return $compact ? "🟡 {$m}د" : "🟡 آخرین بازدید {$m} دقیقه پیش";
        }
        if ($sec < 86400) {
            $h = max(1, (int)floor($sec / 3600));
            return $compact ? "🟠 {$h}س" : "🟠 آخرین بازدید {$h} ساعت پیش";
        }
        if ($sec < 86400 * 7) {
            $d = max(1, (int)floor($sec / 86400));
            return $compact ? "⚪ {$d}ر" : "⚪ آخرین بازدید {$d} روز پیش";
        }
        if ($sec < 86400 * 30) {
            $w = max(1, (int)floor($sec / (86400 * 7)));
            return $compact ? "⚪ {$w}هفته" : "⚪ آخرین بازدید حدود {$w} هفته پیش";
        }
        return $compact ? '⚪ قدیمی' : '⚪ آخرین بازدید بیش از یک ماه پیش';
    }

    private function shortProfileLabel(array $target, bool $compact): string
    {
        $dn = (string)($target['display_name'] ?? 'کاربر');
        if (mb_strlen($dn) > 14) {
            $dn = mb_substr($dn, 0, 14) . '…';
        }
        $g = '';
        if ((int)($target['show_gender'] ?? 1) === 1) {
            $g = Gender::emoji((string)($target['gender'] ?? ''));
        }
        $age = ((int)($target['show_age'] ?? 1) === 1) ? (string)(int)($target['age'] ?? 0) : '';
        $city = ((int)($target['show_city'] ?? 1) === 1) ? (string)($target['city'] ?? '') : '';
        $presence = $this->formatPresence($target, true);
        if ($compact) {
            return trim($presence . ' ' . $g . ' ' . $dn . ($age !== '' ? ' ' . $age : ''));
        }
        $parts = array_filter([
            $dn,
            ((int)($target['show_gender'] ?? 1) === 1) ? Gender::short((string)($target['gender'] ?? '')) : null,
            $age !== '' ? $age . 'ساله' : null,
            $city !== '' ? $city : null,
            $presence !== '' ? $presence : null,
        ]);
        return htmlspecialchars(implode(' · ', $parts), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function formatPublicProfile(array $viewer, array $target, bool $short = false): string
    {
        $this->db->ensureIdentity($target);
        $target = $this->db->findUser((int)$target['telegram_id']) ?? $target;
        $dn = htmlspecialchars((string)($target['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lines = ["<b>{$dn}</b>"];
        $meta = [];
        if ((int)($target['show_gender'] ?? 1) === 1) {
            $meta[] = Gender::label((string)($target['gender'] ?? ''));
        }
        if ((int)($target['show_age'] ?? 1) === 1) {
            $meta[] = (int)($target['age'] ?? 0) . ' ساله';
        }
        if ((int)($target['show_occupation'] ?? 1) === 1) {
            $badge = Occupation::badge((string)($target['occupation'] ?? ''));
            if ($badge !== '') {
                $meta[] = $badge;
            }
        }
        if ($meta) {
            $lines[] = implode(' · ', $meta);
        }
        $loc = [];
        if ((int)($target['show_province'] ?? 1) === 1) {
            $loc[] = htmlspecialchars((string)($target['province'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if ((int)($target['show_city'] ?? 1) === 1) {
            $loc[] = htmlspecialchars((string)($target['city'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if ($loc) {
            $lines[] = implode(' · ', $loc);
        }
        $presence = $this->formatPresence($target, false);
        if ($presence !== '') {
            $lines[] = $presence;
        }
        $bio = trim((string)($target['bio'] ?? ''));
        if ($bio !== '' && !$short) {
            $lines[] = '────────';
            $lines[] = htmlspecialchars($bio, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $pc = htmlspecialchars((string)($target['public_code'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $likes = $this->db->countLikes((int)$target['telegram_id']);
        $lines[] = '────────';
        $lines[] = "🤍 لایک‌ها: <b>{$likes}</b>";
        $lines[] = "شناسه: <code>{$pc}</code>";
        if (!$short) {
            $lines[] = '';
            $lines[] = 'از دکمه‌های زیر یکی را انتخاب کن: پیام، درخواست چت، گزارش تخلف یا بلاک.';
        }
        return implode("\n", $lines);
    }

    private function renderBrowseCard(int $chatId, array &$viewer, array $target): void
    {
        $caption = $this->formatPublicProfile($viewer, $target, false);
        $markup = $this->browseProfileMarkup($target);
        $avatar = ((int)($target['show_avatar'] ?? 1) === 1) ? (string)($target['avatar_file_id'] ?? '') : '';
        if ($avatar !== '') {
            $this->uiPhotoFileId($chatId, $viewer, $avatar, $caption, $markup);
        } else {
            $this->uiText($chatId, $viewer, $caption, ['reply_markup' => $markup]);
        }
    }

    private function sendBrowseRequest(int $chatId, array &$user, string $code): void
    {
        $tid = (int)$user['telegram_id'];
        $target = $this->db->findByPublicCode($code);
        if (!$target) {
            $this->uiText($chatId, $user, 'کاربر پیدا نشد.');
            return;
        }
        $to = (int)$target['telegram_id'];
        if ($to === $tid) {
            $this->uiText($chatId, $user, 'نمی‌توانی به خودت درخواست بفرستی.');
            return;
        }
        if ($this->db->isBlocked($tid, $to) || $this->db->isBlocked($to, $tid)) {
            $this->uiText($chatId, $user, 'ارسال درخواست ممکن نیست.');
            return;
        }
        if (($target['status'] ?? '') === 'banned') {
            $this->uiText($chatId, $user, 'این کاربر در دسترس نیست.');
            return;
        }
        $cost = $this->settings->getInt('request_cost', 1);
        if (!$this->db->spendCoins($tid, $cost, 'chat_request', (string)$to)) {
            $this->showNeedCoins($chatId, $user, $cost, 'درخواست چت');
            return;
        }
        $reqId = $this->db->createContactRequest($tid, $to, 'request');
        $fromName = htmlspecialchars((string)($user['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $fromCode = htmlspecialchars((string)($user['public_code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $busy = (($target['status'] ?? '') === 'chatting');
        $extra = $busy
            ? "\n\n⏳ الان در چت دیگری است — می‌توانی رزرو کنی یا بعداً از درخواست‌ها قبول کنی."
            : "\n\nبا قبول، چت خصوصی دو نفره باز می‌شود.";
        try {
            $this->tg->sendMessage(
                $to,
                "📩 <b>درخواست چت خصوصی</b>\nاز: <b>{$fromName}</b>\nکد: <code>{$fromCode}</code>{$extra}",
                ['reply_markup' => Keyboards::chatRequestInline($reqId)]
            );
        } catch (Throwable $e) {
        }
        $user = $this->db->findUser($tid) ?? $user;
        $dn = htmlspecialchars((string)($target['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($busy) {
            $notifyCost = max(0, $this->settings->getInt('notify_free_cost', 1));
            $this->uiText(
                $chatId,
                $user,
                "⏳ <b>{$dn}</b> الان در حال چت است.\n" .
                "درخواستت ارسال شد (−{$cost} سکه).\n\n" .
                "اگر می‌خوای وقتی چتش تموم شد خبرت کنیم، گزینه زیر را بزن (−{$notifyCost} سکه).",
                ['reply_markup' => Keyboards::busyNotifyInline((string)$target['public_code'], $notifyCost)]
            );
            return;
        }
        $this->uiText($chatId, $user, "درخواست چت ارسال شد (−{$cost} سکه).\nتا وقتی طرف مقابل قبول کند، چت باز نمی‌شود.");
        $this->showNextBrowseCard($chatId, $user);
    }

    private function sendBrowseMessage(int $chatId, array &$user, string $text): void
    {
        $tid = (int)$user['telegram_id'];
        $flow = (string)($user['flow'] ?? '');
        $code = str_starts_with($flow, 'br:compose:') ? substr($flow, strlen('br:compose:')) : '';
        $target = $code !== '' ? $this->db->findByPublicCode($code) : null;
        if (!$target) {
            $this->db->updateUser($tid, ['flow' => null]);
            $this->uiText($chatId, $user, 'مخاطب نامعتبر بود. دوباره از جستجو انتخاب کن.');
            return;
        }
        $to = (int)$target['telegram_id'];
        $cost = $this->settings->getInt('message_cost', 2);
        $body = mb_substr(trim($text), 0, 500);
        if (mb_strlen($body) < 1) {
            $this->tg->sendMessage($chatId, 'متن خالی قبول نیست.');
            return;
        }
        if ($this->db->isBlocked($tid, $to) || $this->db->isBlocked($to, $tid)) {
            $this->db->updateUser($tid, ['flow' => null]);
            $this->uiText($chatId, $user, 'ارسال پیام ممکن نیست.');
            return;
        }
        if (!$this->db->spendCoins($tid, $cost, 'direct_message', (string)$to)) {
            $this->db->updateUser($tid, ['flow' => null]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->showNeedCoins($chatId, $user, $cost, 'ارسال پیام');
            return;
        }
        $this->db->createContactRequest($tid, $to, 'message', $body);
        $fromName = htmlspecialchars((string)($user['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $fromCode = htmlspecialchars((string)($user['public_code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safe = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $reqCost = $this->settings->getInt('request_cost', 1);
        try {
            $this->tg->sendMessage(
                $to,
                "💬 پیام بدون درخواست از <b>{$fromName}</b>\nکد: <code>{$fromCode}</code>\n────────\n{$safe}\n\n" .
                "برای چت دوطرفه می‌توانی درخواست چت بپذیری یا خودت درخواست بفرستی ({$reqCost} سکه)."
            );
        } catch (Throwable $e) {
        }
        $this->db->updateUser($tid, ['flow' => null]);
        $user = $this->db->findUser($tid) ?? $user;
        $this->clearUi($chatId, $user);
        $this->uiText($chatId, $user, "پیام ارسال شد (−{$cost} سکه).\nتا وقتی هر دو طرف چت خصوصی را تأیید نکنند، هر پیام جداگانه سکه کم می‌کند.");
        $this->pinHamGapMenu($chatId, $user);
    }

    private function showNeedCoins(int $chatId, array &$user, int $needed, string $action = ''): void
    {
        $user = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $invite = $this->settings->getInt('invite_reward', 30);
        $have = (int)($user['coins'] ?? 0);
        $actionBit = $action !== '' ? " برای «{$action}»" : '';
        $this->clearUi($chatId, $user);
        $this->uiText(
            $chatId,
            $user,
            "⚠️ <b>توجه: سکه کافی نداری!</b>" .
            ($needed > 0 ? " ({$needed} سکه مورد نیاز{$actionBit})" : '') . "\n" .
            "موجودی الان: <b>{$have}</b> سکه\n\n" .
            "💡 برای به‌دست آوردن سکه می‌تونی ربات را به دوستات معرفی کنی و به ازای هر دعوت <b>+{$invite}</b> سکه هدیه بگیری.\n\n" .
            "❗️ اگر دوستی نداری که دعوت کنی، از پنل خرید سکه 💰 استفاده کن و موجودی‌ات را افزایش بده.",
            ['reply_markup' => Keyboards::needCoinsInline($invite)]
        );
        $this->pinHamGapMenu($chatId, $user);
    }

    private function subscribeChatWait(
        array $cq,
        array &$user,
        string $id,
        int $chatId,
        int $tid,
        string $code
    ): void {
        $target = $this->db->findByPublicCode($code);
        if (!$target) {
            $this->tg->answerCallback($id, 'کاربر پیدا نشد', true);
            return;
        }
        $to = (int)$target['telegram_id'];
        if ($to === $tid) {
            $this->tg->answerCallback($id, 'برای خودت ممکن نیست', true);
            return;
        }
        if ($this->db->isBlocked($tid, $to) || $this->db->isBlocked($to, $tid)) {
            $this->tg->answerCallback($id, 'امکان‌پذیر نیست', true);
            return;
        }
        if ($this->db->hasPendingChatWait($tid, $to)) {
            $this->tg->answerCallback($id, 'قبلاً ثبت شده — وقتی آزاد شد خبرت می‌کنیم', true);
            return;
        }
        // If already free, no need to wait
        if (($target['status'] ?? '') !== 'chatting') {
            $this->tg->answerCallback($id, 'الان آزاد است');
            $reqCost = $this->settings->getInt('request_cost', 1);
            $dn = htmlspecialchars((string)($target['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->uiText(
                $chatId,
                $user,
                "🔔 <b>{$dn}</b> الان آزاد است.\nمی‌تونی همین حالا درخواست چت بفرستی.",
                ['reply_markup' => Keyboards::freeNowInline((string)$target['public_code'], $reqCost)]
            );
            return;
        }
        $cost = max(0, $this->settings->getInt('notify_free_cost', 1));
        if ($cost > 0 && !$this->db->spendCoins($tid, $cost, 'notify_free', (string)$to)) {
            $this->tg->answerCallback($id, 'سکه کافی نداری', true);
            $this->showNeedCoins($chatId, $user, $cost, 'خبر پایان چت');
            return;
        }
        $res = $this->db->addChatWaitNotice($tid, $to);
        if ($res === 'already') {
            if ($cost > 0) {
                $this->db->addCoins($tid, $cost, 'notify_free_refund', 'already');
            }
            $this->tg->answerCallback($id, 'قبلاً ثبت شده', true);
            return;
        }
        $this->tg->answerCallback($id, 'ثبت شد 🔔');
        $this->stripCallbackMenu($cq);
        $user = $this->db->findUser($tid) ?? $user;
        $dn = htmlspecialchars((string)($target['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->clearUi($chatId, $user);
        $this->uiText(
            $chatId,
            $user,
            "🔔 خبررسانی ثبت شد" . ($cost > 0 ? " (−{$cost} سکه)" : '') . ".\n" .
            "وقتی چت <b>{$dn}</b> تمام شود، همین‌جا خبرت می‌کنیم تا بتوانی وصل شوی."
        );
    }

    /** @param list<int> $targetIds */
    private function flushChatWaitNotices(array $targetIds): void
    {
        $seen = [];
        $reqCost = $this->settings->getInt('request_cost', 1);
        foreach ($targetIds as $targetId) {
            $targetId = (int)$targetId;
            if ($targetId <= 0 || isset($seen[$targetId])) {
                continue;
            }
            $seen[$targetId] = true;
            $target = $this->db->findUser($targetId);
            if (!$target) {
                continue;
            }
            $dn = htmlspecialchars((string)($target['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $code = (string)($target['public_code'] ?? '');
            foreach ($this->db->listPendingChatWaiters($targetId) as $row) {
                $waiterId = (int)$row['waiter_id'];
                $this->db->markChatWaitNotified((int)$row['id']);
                try {
                    $this->tg->sendMessage(
                        $waiterId,
                        "🔔 چت <b>{$dn}</b> تمام شد!\nالان آزاد است — می‌تونی درخواست چت بفرستی و وصل شی.",
                        ['reply_markup' => Keyboards::freeNowInline($code, $reqCost)]
                    );
                } catch (Throwable $e) {
                }
            }
        }
    }

    private function forwardSupportFromMain(int $chatId, array &$user, string $text): void
    {
        $tid = (int)$user['telegram_id'];
        $this->db->updateUser($tid, ['flow' => null]);
        $staff = $this->db->listSupportStaff(true);
        $payload = "پیام پشتیبانی از کاربر <code>{$tid}</code>\n" .
            htmlspecialchars(mb_substr($text, 0, 1000), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $sent = 0;
        foreach ($staff as $row) {
            try {
                $this->tg->sendMessage((int)$row['telegram_id'], $payload);
                $sent++;
            } catch (Throwable $e) {
            }
        }
        if ($sent === 0) {
            foreach (($this->config['admin_ids'] ?? []) as $aid) {
                try {
                    $this->tg->sendMessage((int)$aid, $payload);
                    $sent++;
                } catch (Throwable $e) {
                }
            }
        }
        $user = $this->db->findUser($tid) ?? $user;
        $this->clearUi($chatId, $user);
        $this->uiText($chatId, $user, $sent > 0 ? 'پیامت به پشتیبانی رسید ✅' : 'پیامت ثبت شد. به‌زودی بررسی می‌شود.');
        $this->showSupport($chatId, $user);
    }

    private function showBlocks(int $chatId, array &$user): void
    {
        $rows = $this->db->listBlockedUsers((int)$user['telegram_id']);
        if (!$rows) {
            $this->uiText($chatId, $user, "لیست بلاک خالی است.", [
                'reply_markup' => Keyboards::profileInline($this->settings->getInt('invite_reward', 30)),
            ]);
            $this->pinHamGapMenu($chatId, $user);
            return;
        }
        $lines = ["🚫 <b>کاربران بلاک‌شده</b>"];
        $kb = [];
        foreach ($rows as $r) {
            $dn = htmlspecialchars((string)($r['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $pc = htmlspecialchars((string)($r['public_code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $lines[] = "• {$dn} — <code>{$pc}</code>";
            $kb[] = [['text' => "رفع بلاک {$dn}", 'callback_data' => 'blk:un:' . (int)$r['telegram_id']]];
        }
        $kb[] = [['text' => 'بازگشت به پروفایل', 'callback_data' => 'menu:profile']];
        $this->uiText($chatId, $user, implode("\n", $lines), ['reply_markup' => ['inline_keyboard' => $kb]]);
        $this->pinHamGapMenu($chatId, $user);
    }

    private function supportContactLine(): string
    {
        $u = trim($this->settings->get('support_bot_username'));
        if ($u === '') {
            $u = 'HamGapXHelpBot';
        }
        $u = ltrim($u, '@');
        return "برای فعال‌سازی دوباره با پشتیبانی تماس بگیر: @{$u}";
    }

    private function bannedAccountText(): string
    {
        $threshold = $this->settings->getInt('report_ban_threshold', 10);
        return "⛔️ حسابت به‌خاطر رسیدن به {$threshold} گزارش تخلف مسدود شده است.\n" .
            $this->supportContactLine();
    }

    private function applyReportAndMaybeBan(int $reporterId, int $reportedId, string $reason): void
    {
        if ($reporterId === $reportedId) {
            return;
        }
        $count = $this->db->addReport($reporterId, $reportedId, $reason);
        $threshold = max(1, $this->settings->getInt('report_ban_threshold', 10));
        $target = $this->db->findUser($reportedId);
        $name = htmlspecialchars((string)($target['display_name'] ?? (string)$reportedId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $left = max(0, $threshold - $count);
        foreach (($this->config['admin_ids'] ?? []) as $aid) {
            try {
                $extra = $left > 0
                    ? "\nباقی‌مانده تا بلاک خودکار: <b>{$left}</b> گزارش"
                    : "\n⚠️ به آستانه بلاک رسید.";
                $this->tg->sendMessage(
                    (int)$aid,
                    "🚩 گزارش تخلف #{$count}/{$threshold}\nروی: <b>{$name}</b>\nآیدی: <code>{$reportedId}</code>\nدلیل: " .
                    htmlspecialchars($reason, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . $extra
                );
            } catch (Throwable $e) {
            }
        }
        if ($count >= $threshold && ($target['status'] ?? '') !== 'banned') {
            if ($target && ($target['status'] ?? '') === 'chatting') {
                $this->matcher->endChat($target, true);
            }
            $this->db->banForReports($reportedId, 'report_threshold');
            try {
                $this->tg->sendMessage(
                    $reportedId,
                    "⛔️ حسابت به‌خاطر رسیدن به <b>{$threshold}</b> گزارش تخلف به‌صورت خودکار مسدود شد.\n" .
                    $this->supportContactLine()
                );
            } catch (Throwable $e) {
            }
            foreach (($this->config['admin_ids'] ?? []) as $aid) {
                try {
                    $this->tg->sendMessage(
                        (int)$aid,
                        "🚫 کاربر <code>{$reportedId}</code> ({$name}) به‌خاطر {$threshold} گزارش تخلف خودکار بلاک شد.\n" .
                        "برای فعال‌سازی از مدیریت کاربران → رفع مسدود استفاده کن."
                    );
                } catch (Throwable $e) {
                }
            }
        }
    }

    private function showRequestInbox(int $chatId, array &$user): void
    {
        $tid = (int)$user['telegram_id'];
        $rows = $this->db->listIncomingRequests($tid);
        $this->clearUi($chatId, $user);
        if (!$rows) {
            $this->uiText($chatId, $user, "درخواست چت معلقی نداری.");
            if (($user['status'] ?? '') === 'chatting') {
                $this->uiText($chatId, $user, 'چت فعال است.', [
                    'reply_markup' => Keyboards::chattingReply(),
                ]);
            }
            return;
        }
        $this->uiText($chatId, $user, "📥 <b>درخواست‌های چت / رزرو</b>\nیکی را انتخاب کن:");
        foreach ($rows as $r) {
            $dn = htmlspecialchars((string)($r['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $st = (string)$r['status'] === 'held' ? 'رزرو شده' : 'جدید';
            $this->uiText(
                $chatId,
                $user,
                "• <b>{$dn}</b> — {$st}",
                ['reply_markup' => Keyboards::chatRequestInline((int)$r['id'])]
            );
        }
    }

    private function handleRequestCallback(
        array $cq,
        array &$user,
        string $id,
        string $data,
        int $chatId,
        int $tid
    ): void {
        if ($data === 'req:inbox') {
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->showRequestInbox($chatId, $user);
            return;
        }
        if (str_starts_with($data, 'blk:un:')) {
            $other = (int)substr($data, strlen('blk:un:'));
            $this->db->unblockUser($tid, $other);
            $this->tg->answerCallback($id, 'رفع بلاک شد');
            $user = $this->db->findUser($tid) ?? $user;
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->showBlocks($chatId, $user);
            return;
        }
        $action = null;
        $reqId = 0;
        if (str_starts_with($data, 'req:ok:')) {
            $action = 'ok';
            $reqId = (int)substr($data, strlen('req:ok:'));
        } elseif (str_starts_with($data, 'req:hold:')) {
            $action = 'hold';
            $reqId = (int)substr($data, strlen('req:hold:'));
        } elseif (str_starts_with($data, 'req:no:')) {
            $action = 'no';
            $reqId = (int)substr($data, strlen('req:no:'));
        } else {
            $this->tg->answerCallback($id);
            return;
        }
        $req = $this->db->findContactRequest($reqId);
        if (!$req || (int)$req['to_id'] !== $tid || (string)$req['kind'] !== 'request') {
            $this->tg->answerCallback($id, 'درخواست نامعتبر', true);
            return;
        }
        if (!in_array((string)$req['status'], ['pending', 'held'], true)) {
            $this->tg->answerCallback($id, 'این درخواست بسته شده', true);
            return;
        }
        $fromId = (int)$req['from_id'];
        if ($action === 'hold') {
            $this->db->updateContactRequest($reqId, ['status' => 'held']);
            $this->tg->answerCallback($id, 'رزرو شد — بعداً از درخواست‌ها باز کن');
            try {
                $this->tg->sendMessage($fromId, 'درخواست چت‌ات رزرو شد. طرف مقابل بعداً جواب می‌دهد.');
            } catch (Throwable $e) {
            }
            return;
        }
        if ($action === 'no') {
            $this->db->updateContactRequest($reqId, ['status' => 'declined']);
            $this->tg->answerCallback($id, 'رد شد');
            try {
                $this->tg->sendMessage($fromId, 'درخواست چت‌ات رد شد.');
            } catch (Throwable $e) {
            }
            return;
        }

        $freed = [];
        if (($user['status'] ?? '') === 'chatting' && !empty($user['partner_id'])) {
            $old = (int)$user['partner_id'];
            $this->matcher->endChat($user, true);
            $freed[] = $old;
            try {
                $this->tg->sendMessage($old, 'طرف مقابل رفت سراغ درخواست چت دیگری. تاریخچه پاک شد.');
            } catch (Throwable $e) {
            }
        }
        $fromUser = $this->db->findUser($fromId);
        if ($fromUser && ($fromUser['status'] ?? '') === 'chatting') {
            $oldFromPartner = !empty($fromUser['partner_id']) ? (int)$fromUser['partner_id'] : null;
            $this->matcher->endChat($fromUser, true);
            if ($oldFromPartner) {
                $freed[] = $oldFromPartner;
                try {
                    $this->tg->sendMessage($oldFromPartner, 'طرف مقابل چت را ترک کرد. تاریخچه پاک شد.');
                } catch (Throwable $e) {
                }
            }
        }
        if ($freed) {
            $this->flushChatWaitNotices($freed);
        }
        $this->db->updateContactRequest($reqId, ['status' => 'accepted']);
        $this->db->openPrivateChat($fromId, $tid, 'request');
        $this->tg->answerCallback($id, 'چت خصوصی باز شد');
        $this->stripCallbackMenu($cq);
        $me = $this->db->findUser($tid) ?? $user;
        $them = $this->db->findUser($fromId);
        foreach ([[$chatId, $me], [$fromId, $them]] as $pair) {
            if (!$pair[1]) {
                continue;
            }
            $cid = (int)$pair[0];
            $u = $pair[1];
            $this->clearUi($cid, $u);
            $this->uiText(
                $cid,
                $u,
                "✅ چت خصوصی فعال شد.\nهویت تلگرام مخفی است · محترمانه صحبت کنید.\nپایان چت = پاک شدن کامل تاریخچه.",
                ['reply_markup' => Keyboards::chattingInline()]
            );
            $this->uiText($cid, $u, 'پیام بفرست 👇', [
                'reply_markup' => Keyboards::chattingReply(),
            ]);
        }
    }

    private function handlePaymentCallback(
        array $cq,
        array &$user,
        string $id,
        string $data,
        int $chatId,
        int $tid
    ): void {
        if (str_starts_with($data, 'payadm:')) {
            if (!$this->canReviewPayments($tid)) {
                $this->tg->answerCallback($id, 'دسترسی ادمین نداری', true);
                return;
            }
            $ok = str_starts_with($data, 'payadm:ok:');
            $invId = (int)substr($data, strlen($ok ? 'payadm:ok:' : 'payadm:no:'));
            if ($ok) {
                $res = $this->db->approvePaymentInvoice($invId, $tid);
                if (!($res['ok'] ?? false)) {
                    $msg = match ((string)($res['error'] ?? '')) {
                        'already' => 'قبلاً تأیید شده',
                        'closed' => 'این فاکتور بسته است',
                        default => 'فاکتور پیدا نشد',
                    };
                    $this->tg->answerCallback($id, $msg, true);
                    return;
                }
                $coins = (int)$res['coins'];
                $userTid = (int)$res['telegram_id'];
                $inv = $res['invoice'];
                $this->tg->answerCallback($id, 'تأیید شد');
                try {
                    $this->tg->sendMessage(
                        $userTid,
                        "✅ پرداختت تأیید شد.\n" .
                        "<b>+{$coins} سکه</b> به حسابت اضافه شد.\n" .
                        "شماره فاکتور: <code>" . htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>'
                    );
                } catch (Throwable $e) {
                }
                $this->tg->sendMessage($chatId, "فاکتور {$inv['invoice_no']} تأیید و {$coins} سکه شارژ شد.");
                return;
            }
            $res = $this->db->rejectPaymentInvoice($invId, $tid);
            if (!($res['ok'] ?? false)) {
                $this->tg->answerCallback($id, 'رد نشد / پیدا نشد', true);
                return;
            }
            $inv = $res['invoice'];
            $this->tg->answerCallback($id, 'رد شد');
            try {
                $this->tg->sendMessage(
                    (int)$inv['telegram_id'],
                    "❌ فیش فاکتور <code>" . htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    "</code> رد شد.\nاگر مبلغ اشتباه واریز کردی، دوباره از کیف‌پول فاکتور جدید بگیر."
                );
            } catch (Throwable $e) {
            }
            $this->tg->sendMessage($chatId, "فاکتور {$inv['invoice_no']} رد شد.");
            return;
        }

        if (str_starts_with($data, 'pay:pack:')) {
            $coins = (int)substr($data, strlen('pay:pack:'));
            if (!in_array($coins, [100, 300, 1000], true)) {
                $this->tg->answerCallback($id, 'بسته نامعتبر', true);
                return;
            }
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->createAndShowInvoice($chatId, $user, $coins);
            return;
        }

        if ($data === 'pay:copycard') {
            $card = preg_replace('/\D+/', '', $this->settings->get('pay_card_number')) ?? '';
            if ($card === '') {
                $this->tg->answerCallback($id, 'شماره کارت تنظیم نشده', true);
                return;
            }
            $this->tg->answerCallback($id, $card, true);
            $this->tg->sendMessage($chatId, "شماره کارت (لمس کن تا کپی شود):\n<code>{$card}</code>");
            return;
        }

        if (str_starts_with($data, 'pay:copyamt:')) {
            $invId = (int)substr($data, strlen('pay:copyamt:'));
            $inv = $this->db->findPaymentInvoice($invId);
            if (!$inv || (int)$inv['telegram_id'] !== $tid) {
                $this->tg->answerCallback($id, 'فاکتور نامعتبر', true);
                return;
            }
            $rial = ((int)$inv['amount_toman']) * 10;
            $this->tg->answerCallback($id, (string)$rial, true);
            $this->tg->sendMessage(
                $chatId,
                "مبلغ دقیق به ریال (لمس کن تا کپی شود):\n<code>{$rial}</code>\n" .
                "به تومان: <code>" . (int)$inv['amount_toman'] . "</code>"
            );
            return;
        }

        if (str_starts_with($data, 'pay:receipt:')) {
            $invId = (int)substr($data, strlen('pay:receipt:'));
            $inv = $this->db->findPaymentInvoice($invId);
            if (!$inv || (int)$inv['telegram_id'] !== $tid || !$this->db->isInvoiceOpen($inv)) {
                $this->tg->answerCallback($id, 'فاکتور منقضی یا نامعتبر است', true);
                return;
            }
            $this->db->updatePaymentInvoice($invId, ['status' => 'awaiting_receipt']);
            $this->db->updateUser($tid, ['flow' => 'pay:receipt:' . $invId]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback(
                $id,
                "⚠️ دقیقاً همان مبلغ فاکتور را واریز کن. مبلغ = کد شناسایی تراکنش توست. مبلغ اشتباه تأیید نمی‌شود.",
                true
            );
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $amt = $this->formatMoney((int)$inv['amount_toman']);
            $this->openTypeKeyboard(
                $chatId,
                $user,
                "📷 <b>ارسال فیش واریزی</b>\n" .
                "فاکتور: <code>" . htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</code>\n" .
                "مبلغ دقیق: <b>{$amt}</b> تومان\n\n" .
                "عکس واضح رسید کارت‌به‌کارت را همین‌جا بفرست."
            );
            return;
        }

        $this->tg->answerCallback($id);
    }

    private function canReviewPayments(int $tid): bool
    {
        if (!empty($this->config['admin_ids']) && in_array($tid, array_map('intval', (array)$this->config['admin_ids']), true)) {
            return true;
        }
        $u = $this->db->findUser($tid);
        if ($u && !empty($u['is_admin'])) {
            return true;
        }
        return $this->db->hasValidAdminSession($tid);
    }

    private function createAndShowInvoice(int $chatId, array &$user, int $packCoins): void
    {
        $tid = (int)$user['telegram_id'];
        $card = preg_replace('/\D+/', '', $this->settings->get('pay_card_number')) ?? '';
        if ($card === '' || strlen($card) < 16) {
            $this->uiText(
                $chatId,
                $user,
                "هنوز شماره کارت فروشگاه تنظیم نشده.\nاز بات ادمین بخش «پرداخت کارت‌به‌کارت» را کامل کن.",
                ['reply_markup' => Keyboards::walletInline(
                    $this->settings->getInt('invite_reward', 30),
                    $this->packPrices()
                )]
            );
            return;
        }
        $base = $this->settings->getInt('pack_' . $packCoins . '_price', match ($packCoins) {
            100 => 50000,
            300 => 120000,
            1000 => 350000,
            default => 50000,
        });
        $ttl = $this->settings->getInt('pay_invoice_minutes', 30);
        $this->db->expireOldInvoices();
        $inv = $this->db->createPaymentInvoice($tid, $packCoins, $base, $ttl);
        $user = $this->db->findUser($tid) ?? $user;
        $this->clearUi($chatId, $user);
        $this->uiText($chatId, $user, $this->invoiceText($inv), [
            'reply_markup' => Keyboards::payInvoiceInline((int)$inv['id']),
        ]);
    }

    private function invoiceText(array $inv): string
    {
        $coins = (int)$inv['pack_coins'];
        $amt = (int)$inv['amount_toman'];
        $amtFmt = $this->formatMoney($amt);
        $rial = $amt * 10;
        $no = htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $card = preg_replace('/\D+/', '', $this->settings->get('pay_card_number')) ?? '';
        $cardFmt = $this->formatCard($card);
        $holder = htmlspecialchars($this->settings->get('pay_card_holder'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $bank = htmlspecialchars($this->settings->get('pay_bank_name'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $ttl = $this->settings->getInt('pay_invoice_minutes', 30);
        $channel = trim($this->settings->get('pay_trust_channel'));
        $lines = [
            "فاکتور <b>{$coins}</b> سکه به مبلغ <b>{$amtFmt}</b> تومان برات صادر شد.",
            '',
            'لطفاً <b>دقیقاً همین مبلغ</b> را کارت‌به‌کارت واریز کن.',
            'این مبلغ، کد شناسایی فاکتور توست تا واریزی‌ات از بقیه جدا شود.',
            '',
            '⚠️ اگر مبلغ اشتباه بفرستی، فیش تأیید نمی‌شود.',
            "⏱ اعتبار فاکتور: <b>{$ttl}</b> دقیقه",
            '',
            "شماره کارت:\n<code>{$card}</code>",
            $cardFmt !== $card ? "نمایش: <b>{$cardFmt}</b>" : '',
            $holder !== '' ? "به نام: <b>{$holder}</b>" : '',
            $bank !== '' ? "بانک: <b>{$bank}</b>" : '',
            "شماره فاکتور: <code>{$no}</code>",
            "مبلغ به ریال: <code>{$rial}</code>",
        ];
        if ($channel !== '') {
            $ch = ltrim($channel, '@');
            $lines[] = '';
            $lines[] = 'کانال رضایت مشتریان: @' . htmlspecialchars($ch, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $lines[] = '';
        $lines[] = 'بعد از واریز، دکمه «ارسال فیش واریزی» را بزن و عکس رسید را بفرست.';
        return implode("\n", array_values(array_filter($lines, static fn ($l) => $l !== '' && $l !== null)));
    }

    private function formatMoney(int $n): string
    {
        return number_format($n, 0, '.', '٬');
    }

    private function formatCard(string $digits): string
    {
        $digits = preg_replace('/\D+/', '', $digits) ?? '';
        if (strlen($digits) < 16) {
            return $digits;
        }
        return trim(chunk_split(substr($digits, 0, 16), 4, '-'), '-');
    }

    private function submitPaymentReceipt(int $chatId, array &$user, int $invId, string $fileId): void
    {
        $tid = (int)$user['telegram_id'];
        if ($fileId === '') {
            $this->tg->sendMessage($chatId, 'عکس فیش معتبر نبود. دوباره بفرست.');
            return;
        }
        $inv = $this->db->findPaymentInvoice($invId);
        if (!$inv || (int)$inv['telegram_id'] !== $tid || !$this->db->isInvoiceOpen($inv)) {
            $this->db->updateUser($tid, ['flow' => null]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->uiText($chatId, $user, 'فاکتور منقضی یا نامعتبر است. دوباره از کیف‌پول فاکتور بگیر.');
            return;
        }
        $this->db->updatePaymentInvoice($invId, [
            'status' => 'submitted',
            'receipt_file_id' => $fileId,
        ]);
        $this->db->updateUser($tid, ['flow' => null]);
        $user = $this->db->findUser($tid) ?? $user;
        $this->clearUi($chatId, $user);
        $this->notifyAdminsOfReceipt($inv, $fileId, $user);
        $this->uiText(
            $chatId,
            $user,
            "✅ فیش فاکتور <code>" . htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            "</code> ثبت شد.\nبعد از بررسی ادمین، سکه خودکار به حسابت اضافه می‌شود.",
            ['reply_markup' => Keyboards::walletInline(
                $this->settings->getInt('invite_reward', 30),
                $this->packPrices()
            )]
        );
    }

    private function notifyAdminsOfReceipt(array $inv, string $fileId, array $user): void
    {
        $amt = $this->formatMoney((int)$inv['amount_toman']);
        $dn = htmlspecialchars((string)($user['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $caption =
            "📥 <b>فیش جدید کارت‌به‌کارت</b>\n" .
            "کاربر: <b>{$dn}</b>\n" .
            "Telegram ID: <code>" . (int)$inv['telegram_id'] . "</code>\n" .
            "فاکتور: <code>" . htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</code>\n" .
            "سکه: <b>" . (int)$inv['pack_coins'] . "</b>\n" .
            "مبلغ دقیق: <b>{$amt}</b> تومان\n" .
            "در اپ بانک همین مبلغ را پیدا کن → تأیید بزن.";
        $markup = Keyboards::payAdminReviewInline((int)$inv['id']);
        $targets = [];
        foreach (($this->config['admin_ids'] ?? []) as $aid) {
            $targets[] = (int)$aid;
        }
        foreach ($this->db->listSupportStaff(true) as $row) {
            // optional: only admins; skip support for money unless also admin
        }
        $targets = array_values(array_unique(array_filter($targets)));
        if (!$targets) {
            // fallback: any user marked is_admin
            $rows = $this->db->pdo()->query("SELECT telegram_id FROM users WHERE is_admin = 1")->fetchAll() ?: [];
            foreach ($rows as $r) {
                $targets[] = (int)$r['telegram_id'];
            }
        }
        foreach ($targets as $aid) {
            try {
                $this->tg->sendPhotoFileId($aid, $fileId, $caption, $markup);
            } catch (Throwable $e) {
                try {
                    $this->tg->sendMessage($aid, $caption, ['reply_markup' => $markup]);
                } catch (Throwable $e2) {
                }
            }
        }
    }

    private function handlePrivacyCallback(
        array $cq,
        array &$user,
        string $id,
        string $data,
        int $chatId,
        int $tid
    ): void {
        if ($data === 'pr:home') {
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->uiText(
                $chatId,
                $user,
                "👁 <b>حریم خصوصی</b>\nمشخص کن پروفایلت برای چه کسانی دیده شود و کدام فیلدها نمایش داده شوند.",
                ['reply_markup' => Keyboards::privacyHomeInline($user)]
            );
            return;
        }
        if (str_starts_with($data, 'pr:vis:')) {
            $vis = substr($data, strlen('pr:vis:'));
            if (!in_array($vis, ['public', 'hidden', 'friends'], true)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $this->db->updateUser($tid, ['profile_visibility' => $vis]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id, 'ذخیره شد');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->uiText(
                $chatId,
                $user,
                "👁 حریم خصوصی به‌روز شد.",
                ['reply_markup' => Keyboards::privacyHomeInline($user)]
            );
            return;
        }
        if (str_starts_with($data, 'pr:tog:')) {
            $field = substr($data, strlen('pr:tog:'));
            $allowed = ['show_age', 'show_city', 'show_province', 'show_gender', 'show_occupation', 'show_online', 'show_avatar'];
            if (!in_array($field, $allowed, true)) {
                $this->tg->answerCallback($id, 'نامعتبر', true);
                return;
            }
            $cur = (int)($user[$field] ?? 1) === 1 ? 1 : 0;
            $this->db->updateUser($tid, [$field => $cur === 1 ? 0 : 1]);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id, 'تغییر کرد');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->uiText(
                $chatId,
                $user,
                "👁 نمایش فیلدها به‌روز شد.",
                ['reply_markup' => Keyboards::privacyHomeInline($user)]
            );
            return;
        }
        $this->tg->answerCallback($id);
    }

    private function handleFriendsCallback(
        array $cq,
        array &$user,
        string $id,
        string $data,
        int $chatId,
        int $tid
    ): void {
        if (str_starts_with($data, 'frnd:ok:') || str_starts_with($data, 'frnd:no:')) {
            $accept = str_starts_with($data, 'frnd:ok:');
            $other = (int)substr($data, strlen($accept ? 'frnd:ok:' : 'frnd:no:'));
            $ok = $this->db->respondFriendship($tid, $other, $accept);
            $this->tg->answerCallback($id, $ok ? ($accept ? 'دوست شدید' : 'رد شد') : 'درخواستی نبود', !$ok);
            if ($ok && $accept) {
                try {
                    $this->tg->sendMessage($other, 'درخواست دوستی‌ات قبول شد ✅');
                } catch (Throwable $e) {
                }
            }
            return;
        }

        if ($data === 'fr:create') {
            $cost = $this->settings->getInt('room_create_cost', 5);
            $joinCost = $this->settings->getInt('room_join_cost', 1);
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->uiText(
                $chatId,
                $user,
                "🏠 <b>ساخت گپ گروهی</b>\n\n" .
                "قبل از ساخت:\n" .
                "• از حسابت <b>{$cost}</b> سکه کم می‌شود\n" .
                "• هر نفری که با کد وارد شود <b>{$joinCost}</b> سکه می‌پردازد\n" .
                "• با بستن گپ، کل اطلاعات و تاریخچه برای همه پاک می‌شود\n\n" .
                "ادامه می‌دهی؟",
                ['reply_markup' => Keyboards::roomCreateConfirmInline($cost)]
            );
            return;
        }
        if ($data === 'fr:create:go') {
            $cost = $this->settings->getInt('room_create_cost', 5);
            if ((int)$user['coins'] < $cost) {
                $this->tg->answerCallback($id, 'سکه کافی نداری', true);
                $this->showNeedCoins($chatId, $user, $cost, 'ساخت گپ');
                return;
            }
            $this->db->updateUser($tid, ['flow' => 'fr:create']);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->openTypeKeyboard(
                $chatId,
                $user,
                "نام گپ را بنویس (مثلاً: گپ جمعه).\nبعد از ارسال نام، <b>{$cost}</b> سکه کم می‌شود."
            );
            return;
        }
        if ($data === 'fr:join') {
            $joinCost = $this->settings->getInt('room_join_cost', 1);
            $this->db->updateUser($tid, ['flow' => 'fr:join']);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->openTypeKeyboard($chatId, $user, "🔑 کد گپ را بفرست:\n(ورود: <b>{$joinCost}</b> سکه)");
            return;
        }
        if ($data === 'fr:leave' || $data === 'fr:close') {
            if ($data === 'fr:close') {
                $roomId = (int)($user['active_room_id'] ?? 0);
                $room = $roomId > 0 ? $this->db->findFriendRoom($roomId) : null;
                if (!$room || (int)$room['owner_id'] !== $tid) {
                    $this->tg->answerCallback($id, 'فقط سازنده گپ می‌تواند ببندد', true);
                    return;
                }
            }
            $result = $this->db->leaveFriendRoom($tid);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id, !empty($result['closed']) ? 'گپ بسته و پاک شد' : 'از گپ خارج شدی');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            if (!empty($result['closed']) && !empty($result['members'])) {
                foreach ($result['members'] as $m) {
                    $mid = (int)$m['telegram_id'];
                    if ($mid === $tid) {
                        continue;
                    }
                    try {
                        $this->tg->sendMessage(
                            $mid,
                            "گپ بسته شد.\nتمام اطلاعات و تاریخچه گپ کاملاً پاک شد."
                        );
                    } catch (Throwable $e) {
                    }
                }
            }
            $this->showFriends($chatId, $user);
            return;
        }
        if ($data === 'fr:list') {
            $rooms = $this->db->listUserRooms($tid);
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            if (!$rooms) {
                $this->uiText($chatId, $user, 'هنوز گپی نداری.', ['reply_markup' => Keyboards::friendsInline()]);
                return;
            }
            $rows = [];
            $lines = ["📂 <b>گپ‌های من</b>"];
            foreach ($rooms as $r) {
                $title = htmlspecialchars((string)$r['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $code = htmlspecialchars((string)$r['code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $lines[] = "• {$title} — <code>{$code}</code>";
                $rows[] = [['text' => 'ورود: ' . mb_substr((string)$r['title'], 0, 20), 'callback_data' => 'fr:enter:' . (int)$r['id']]];
            }
            $rows[] = [['text' => 'بازگشت', 'callback_data' => 'menu:friends']];
            $this->uiText($chatId, $user, implode("\n", $lines), ['reply_markup' => ['inline_keyboard' => $rows]]);
            return;
        }
        if ($data === 'fr:friends') {
            $friends = $this->db->listFriends($tid);
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            if (!$friends) {
                $this->uiText(
                    $chatId,
                    $user,
                    "هنوز دوستی نداری.\nاز جستجوی کاربران «افزودن دوست» را بزن.",
                    ['reply_markup' => Keyboards::friendsInline()]
                );
                return;
            }
            $lines = ['👥 <b>لیست دوستان</b>'];
            foreach ($friends as $f) {
                $lines[] = '• ' . $this->shortProfileLabel($f, false);
            }
            $this->uiText($chatId, $user, implode("\n", $lines), ['reply_markup' => Keyboards::friendsInline()]);
            return;
        }
        if ($data === 'fr:members') {
            $roomId = (int)($user['active_room_id'] ?? 0);
            $room = $roomId > 0 ? $this->db->findFriendRoom($roomId) : null;
            if (!$room) {
                $this->tg->answerCallback($id, 'گپ فعالی نیست', true);
                return;
            }
            $members = $this->db->listRoomMembers($roomId);
            $lines = ['👥 <b>اعضای گپ</b> · ' . count($members) . ' نفر'];
            foreach ($members as $m) {
                $g = Gender::emoji((string)($m['gender'] ?? ''));
                $dn = htmlspecialchars((string)($m['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $role = ($m['role'] ?? '') === 'owner' ? ' (مدیر)' : '';
                $lines[] = "{$g} {$dn}{$role}";
            }
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->uiText($chatId, $user, implode("\n", $lines), [
                'reply_markup' => Keyboards::roomActiveInline((string)$room['code']),
            ]);
            return;
        }
        if (str_starts_with($data, 'fr:enter:')) {
            $roomId = (int)substr($data, strlen('fr:enter:'));
            if (!$this->db->enterFriendRoom($tid, $roomId)) {
                $this->tg->answerCallback($id, 'عضو این گپ نیستی', true);
                return;
            }
            $room = $this->db->findFriendRoom($roomId);
            $user = $this->db->findUser($tid) ?? $user;
            $this->tg->answerCallback($id, 'وارد گپ شدی');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            if ($room) {
                $this->enterRoomUi($chatId, $user, $room);
            }
            return;
        }
        $this->tg->answerCallback($id);
    }

    private function enterRoomUi(int $chatId, array &$user, array $room): void
    {
        $title = htmlspecialchars((string)$room['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $code = htmlspecialchars((string)$room['code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $count = count($this->db->listRoomMembers((int)$room['id']));
        $this->uiText(
            $chatId,
            $user,
            "🏠 گپ فعال: <b>{$title}</b>\nکد: <code>{$code}</code>\nاعضا: {$count}\n\nپیام بفرست تا برای همه ارسال شود.\nبرای خروج: /leave",
            ['reply_markup' => Keyboards::roomActiveInline((string)$room['code'])]
        );
    }

    private function announceRoom(array $room, string $text): void
    {
        foreach ($this->db->listRoomMembers((int)$room['id']) as $m) {
            try {
                $this->tg->sendMessage((int)$m['telegram_id'], $text);
            } catch (Throwable $e) {
            }
        }
    }

    private function relayRoomMessage(array $user, array $message): void
    {
        $roomId = (int)($user['active_room_id'] ?? 0);
        if ($roomId <= 0) {
            return;
        }
        $from = (int)$user['telegram_id'];
        $name = htmlspecialchars((string)($user['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $g = Gender::emoji((string)($user['gender'] ?? ''));
        $text = trim((string)($message['text'] ?? ''));
        $prefix = "{$g}<b>{$name}</b>: ";
        foreach ($this->db->listRoomMembers($roomId) as $m) {
            $to = (int)$m['telegram_id'];
            if ($to === $from) {
                continue;
            }
            try {
                if ($text !== '') {
                    $this->tg->sendMessage($to, $prefix . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                } else {
                    $this->tg->sendMessage($to, $prefix . 'پیام چندرسانه‌ای ↓');
                    $this->tg->copyMessage($to, (int)$message['chat']['id'], (int)$message['message_id']);
                }
            } catch (Throwable $e) {
            }
        }
    }
}
