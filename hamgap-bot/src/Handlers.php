<?php
declare(strict_types=1);

/**
 * HamGap handlers v10 — free search, profile browse cards, support, coin requests.
 * CODE_VERSION verifies deploys. Migrator keeps DB forward-compatible.
 */
final class Handlers
{
    public const CODE_VERSION = '2026-08-14-v10';

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

    private function botUsername(): string
    {
        $u = trim((string)($this->config['bot_username'] ?? 'HamGapXBot'));
        return $u !== '' ? $u : 'HamGapXBot';
    }

    private function welcomeTextFirst(): string
    {
        return "به هم‌گپ خوش اومدی 👋\n\n" .
            "یه ربات چت ناشناس برای گپ‌زدن امن و سریع با آدم‌های جدید.\n\n" .
            "🎁 ۳۵ سکه هدیه برای شروع داری.\n" .
            "🎲 چت شانسی هم کاملاً رایگان و نامحدوده.\n\n" .
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
            $from['first_name'] ?? null
        );
        $this->db->ensureIdentity($user);
        $this->db->touchSeen($tid);
        $user = $this->db->findUser($tid) ?? $user;

        if (($user['status'] ?? '') === 'banned') {
            $this->tg->sendMessage($chatId, 'حساب شما مسدود شده است.');
            return;
        }

        $text = trim((string)($message['text'] ?? ''));

        // Admin flows (settings typing) on main bot
        if (str_starts_with((string)($user['flow'] ?? ''), 'adm:')) {
            $admins = array_map('intval', $this->config['admin_ids'] ?? []);
            if (in_array($tid, $admins, true) || !empty($user['is_admin'])) {
                require_once __DIR__ . '/AdminHandlers.php';
                $admin = new AdminHandlers($this->config, $this->db, $this->tg, $this->settings);
                $admin->handle(['message' => $message]);
                return;
            }
        }

        // Compose short message to a browsed profile
        if (str_starts_with((string)($user['flow'] ?? ''), 'br:compose:') && $text !== '') {
            $this->sendBrowseMessage($chatId, $user, $text);
            return;
        }
        if (($user['flow'] ?? '') === 'support:compose' && $text !== '' && !str_starts_with($text, '/')) {
            $this->forwardSupportFromMain($chatId, $user, $text);
            return;
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
        if ($text === '/admin') {
            $admins = array_map('intval', $this->config['admin_ids'] ?? []);
            if (!in_array($tid, $admins, true) && empty($user['is_admin'])) {
                $this->tg->sendMessage($chatId, 'دسترسی ادمین نداری.');
                return;
            }
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

        match ($text) {
            '🔗 وصلم کن به ناشناس', '🔗 وصل ناشناس' => $this->showConnect($chatId, $user),
            '🔍 پیدا کردن مخاطب', '🔍 جستجوی کاربران' => $this->showFind($chatId, $user),
            '👥 وصل به دوستان' => $this->showFriends($chatId, $user),
            '💎 سکه‌ها', '💎 کیف‌پول' => $this->showWallet($chatId, $user),
            '👤 پروفایل من', '👤 پروفایل' => $this->showProfile($chatId, $user),
            '🆘 پشتیبانی' => $this->showSupport($chatId, $user),
            'ℹ️ راهنما' => $this->showHelp($chatId, $user),
            default => (function () use ($chatId, &$user): void {
                $this->clearUi($chatId, $user);
                $this->showMain($chatId, $user, 'از منوی زیر یک گزینه را لمس کن.');
            })(),
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

        // Admin console callbacks (same bot fallback until dedicated admin bot is wired)
        if (str_starts_with($data, 'adm:')) {
            $admins = array_map('intval', $this->config['admin_ids'] ?? []);
            if (!in_array($tid, $admins, true) && empty($user['is_admin'])) {
                $this->tg->answerCallback($id, 'دسترسی ندارید', true);
                return;
            }
            require_once __DIR__ . '/AdminHandlers.php';
            $admin = new AdminHandlers($this->config, $this->db, $this->tg, $this->settings);
            $admin->handle(['callback_query' => $cq]);
            return;
        }

        if (($user['status'] ?? '') === 'banned') {
            $this->tg->answerCallback($id, 'مسدود شده‌اید', true);
            return;
        }

        // ——— Registration ———
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
            if (!in_array($g, ['male', 'female', 'any'], true)) {
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
            if (in_array($g, ['male', 'female'], true)) {
                $filters['gender'] = $g;
            }
            $encoded = rawurlencode(json_encode($filters, JSON_UNESCAPED_UNICODE));
            $this->db->updateUser($tid, [
                'flow' => 'browse:' . $encoded,
                'browse_cursor' => 0,
            ]);
            $this->tg->answerCallback($id, 'جستجو رایگان ✅');
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->showNextBrowseCard($chatId, $user);
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
        if (str_starts_with($data, 'br:req:')) {
            $code = substr($data, strlen('br:req:'));
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $this->sendBrowseRequest($chatId, $user, $code);
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
            $this->uiText(
                $chatId,
                $user,
                "پیام کوتاه برای <b>{$dn}</b>\nهزینه ارسال: <b>{$cost}</b> سکه\nمتن را همین‌جا بنویس."
            );
            return;
        }
        if (str_starts_with($data, 'br:rep:')) {
            $code = substr($data, strlen('br:rep:'));
            $target = $this->db->findByPublicCode($code);
            if ($target) {
                $this->db->pdo()->prepare(
                    'INSERT INTO reports (reporter_id, reported_id, reason) VALUES (?, ?, ?)'
                )->execute([$tid, (int)$target['telegram_id'], 'browse_report']);
            }
            $this->tg->answerCallback($id, 'گزارش ثبت شد');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->showNextBrowseCard($chatId, $user);
            return;
        }
        if (str_starts_with($data, 'br:blk:')) {
            $code = substr($data, strlen('br:blk:'));
            $target = $this->db->findByPublicCode($code);
            if ($target) {
                $this->db->blockUser($tid, (int)$target['telegram_id']);
            }
            $this->tg->answerCallback($id, 'بلاک شد');
            $this->stripCallbackMenu($cq);
            $this->clearUi($chatId, $user);
            $this->showNextBrowseCard($chatId, $user);
            return;
        }
        if ($data === 'support:compose') {
            $this->db->updateUser($tid, ['flow' => 'support:compose']);
            $this->tg->answerCallback($id);
            $this->stripCallbackMenu($cq);
            $user = $this->db->findUser($tid) ?? $user;
            $this->clearUi($chatId, $user);
            $this->uiText($chatId, $user, "پیام پشتیبانی را بنویس.\nهمکاران ما پاسخ می‌دهند.");
            return;
        }

        if ($data === 'pay:soon') {
            $this->tg->answerCallback($id, 'پرداخت به‌زودی فعال می‌شود', true);
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
            case 'chat:any':
                $this->startChat($chatId, $user, 'any');
                break;
            case 'chat:male':
                $this->startChat($chatId, $user, 'male');
                break;
            case 'chat:female':
                $this->startChat($chatId, $user, 'female');
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
                $this->report($chatId, $user);
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
                $this->uiText($chatId, $user, "🖼 <b>عکس پروفایل</b>\nیک عکس همین‌جا بفرست.");
                break;
            case 'edit:displayname':
                $this->db->updateUser($tid, ['flow' => 'set:displayname']);
                $user = $this->db->findUser($tid) ?? $user;
                $this->clearUi($chatId, $user);
                $this->uiText(
                    $chatId,
                    $user,
                    "🔤 <b>نام کاربری</b>\nنام نمایشی جدیدت را بنویس (۲ تا ۳۲ کاراکتر).\nفعلی: <b>" .
                    htmlspecialchars((string)($user['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    '</b>'
                );
                break;
            case 'pay:100':
            case 'pay:300':
            case 'pay:1000':
                $pack = substr($data, strlen('pay:'));
                $this->clearUi($chatId, $user);
                $this->uiText(
                    $chatId,
                    $user,
                    "💳 <b>خرید {$pack} سکه</b>\nروش پرداخت را انتخاب کن:",
                    ['reply_markup' => Keyboards::payMethodInline($pack)]
                );
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
        // Only attach referral during early onboarding (no gender yet)
        if (!empty($user['gender'])) {
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
        $reward = $this->settings->getInt('invite_reward', 30);
        $this->db->addCoins($refTid, $reward, 'referral', (string)$myTid);
        $user = $this->db->findUser($myTid) ?? $user;
        try {
            $this->tg->sendMessage(
                $refTid,
                "یک دوست با لینک دعوتت وارد شد.\n<b>+{$reward} سکه</b> به حسابت اضافه شد."
            );
        } catch (Throwable $e) {
            // ignore notify failures
        }
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
        $dn = htmlspecialchars((string)($fresh['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->showMain(
            $chatId,
            $fresh,
            "پروفایل آماده شد ✅\n" .
            "نام کاربری تو: <b>{$dn}</b>\n" .
            "۳۵ سکه هدیه گرفتی.\n" .
            "از «پروفایل من» می‌تونی عکس و نام را عوض کنی."
        );
        // Show full profile card right after welcome so the auto username is visible.
        $this->showProfile($chatId, $fresh);
    }

    private function showHelp(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $name = $this->botName();
        $invite = $this->settings->getInt('invite_reward', 30);
        $msgCost = $this->settings->getInt('message_cost', 1);
        $reqCost = $this->settings->getInt('request_cost', 1);
        $this->uiText(
            $chatId,
            $user,
            "راهنمای <b>{$name}</b>\n\n" .
            "وصل ناشناس — اتصال رندوم رایگان\n" .
            "جستجوی کاربران — مرور پروفایل‌ها کاملاً رایگان (حتی فیلتر دختر)\n" .
            "درخواست گفتگو / پیام کوتاه — هر کدام {$reqCost} و {$msgCost} سکه\n" .
            "دعوت دوستان — هر دعوت موفق +{$invite} سکه\n" .
            "پشتیبانی — ارتباط با تیم خدمات\n\n" .
            "دستورها: /profile /coins /search /link\n\n" .
            "رمز، کارت بانکی و لینک مشکوک نفرست."
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
        $this->uiText($chatId, $user, 'منوی سریع پایین صفحه 👇', [
            'reply_markup' => Keyboards::mainReply(),
        ]);
    }

    private function showConnect(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $path = $this->assets . '/menu-smart.jpg';
        $caption = "🔗 <b>وصلم کن به ناشناس</b>\n" .
            "نوع اتصال را انتخاب کن.\n" .
            "هزینه فقط بعد از اتصال موفق کم می‌شود.";
        if (is_file($path)) {
            $this->uiPhoto($chatId, $user, $path, $caption, Keyboards::connectInline());
        } else {
            $this->uiText($chatId, $user, $caption, [
                'reply_markup' => Keyboards::connectInline(),
            ]);
        }
    }

    private function showFind(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $this->db->updateUser((int)$user['telegram_id'], ['flow' => null, 'browse_cursor' => 0]);
        $user = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $this->uiText(
            $chatId,
            $user,
            "جستجوی کاربران · <b>کاملاً رایگان</b>\n" .
            "استان را انتخاب کن؛ بعد کارت پروفایل‌ها را یکی‌یکی می‌بینی.\n" .
            "فقط وقتی درخواست گفتگو یا پیام بفرستی سکه کم می‌شود.",
            ['reply_markup' => Keyboards::findProvinces()]
        );
    }

    private function showFriends(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $invite = $this->settings->getInt('invite_reward', 30);
        $this->uiText(
            $chatId,
            $user,
            "وصل به دوستان\n\n" .
            "لینک دعوتت را برای دوستانت بفرست.\n" .
            "هر نفری که با لینک تو وارد شود، <b>+{$invite} سکه</b> می‌گیری.\n" .
            "بعداً می‌توانی از طریق جستجو یا چت ناشناس به جمع وصل شوی.",
            ['reply_markup' => Keyboards::friendsInline()]
        );
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
    }

    private function showProfile(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $g = $user['gender'] === 'female' ? 'دختر' : ($user['gender'] === 'male' ? 'پسر' : '-');
        $dn = htmlspecialchars((string)($user['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $prov = htmlspecialchars((string)($user['province'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $city = htmlspecialchars((string)($user['city'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pc = htmlspecialchars((string)($user['public_code'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = "پروفایل من\n" .
            "────────────\n" .
            "<b>{$dn}</b>\n" .
            "کد عمومی: <code>{$pc}</code>\n" .
            "{$g} · سن " . ($user['age'] ?? '-') . "\n" .
            "{$prov} / {$city}\n" .
            "سکه: <b>" . (int)$user['coins'] . "</b>";

        $avatar = (string)($user['avatar_file_id'] ?? '');
        if ($avatar !== '') {
            $this->uiPhotoFileId($chatId, $user, $avatar, $text, Keyboards::profileInline());
        } else {
            $this->uiText($chatId, $user, $text, [
                'reply_markup' => Keyboards::profileInline(),
            ]);
        }
    }

    private function showWallet(int $chatId, array &$user): void
    {
        $this->clearUi($chatId, $user);
        $invite = $this->settings->getInt('invite_reward', 30);
        $msgCost = $this->settings->getInt('message_cost', 1);
        $reqCost = $this->settings->getInt('request_cost', 1);
        $path = $this->assets . '/menu-wallet.jpg';
        $caption = "کیف‌پول تو\n" .
            "موجودی: <b>" . (int)$user['coins'] . "</b> سکه\n\n" .
            "جستجو رایگان است.\n" .
            "هر پیام کوتاه: {$msgCost} سکه · هر درخواست گفتگو: {$reqCost} سکه\n" .
            "دعوت دوست: +{$invite} سکه";
        if (is_file($path)) {
            $this->uiPhoto($chatId, $user, $path, $caption, Keyboards::walletInline($invite));
        } else {
            $this->uiText($chatId, $user, $caption, [
                'reply_markup' => Keyboards::walletInline($invite),
            ]);
        }
    }

    private function showInvite(int $chatId, array &$user): void
    {
        $this->db->ensureIdentity($user);
        $user = $this->db->findUser((int)$user['telegram_id']) ?? $user;
        $code = (string)($user['referral_code'] ?? '');
        $uname = $this->botUsername();
        $link = 'https://t.me/' . $uname . '?start=ref_' . $code;
        $safeLink = htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $invite = $this->settings->getInt('invite_reward', 30);
        $this->uiText(
            $chatId,
            $user,
            "دعوت دوستان\n\n" .
            "لینک اختصاصی تو:\n<code>{$safeLink}</code>\n\n" .
            "هر دوست جدیدی که با این لینک وارد شود، <b>+{$invite} سکه</b> می‌گیری."
        );
    }

    private function prefLabel(string $pref): string
    {
        return match ($pref) {
            'male' => 'پسر',
            'female' => 'دختر',
            'province' => 'هم‌استان',
            'age' => 'هم‌سن',
            'any' => 'شانسی',
            default => 'مخاطب',
        };
    }

    private function startChat(
        int $chatId,
        array &$user,
        string $pref,
        array $filters = [],
        string $extraNote = ''
    ): void {
        $result = $this->matcher->startSearch($user, $pref, $filters);

        if (!($result['ok'] ?? false)) {
            $err = (string)($result['error'] ?? '');
            if ($err === 'no_coins') {
                $this->uiText($chatId, $user, "سکه کافی نداری.\nاز بخش سکه‌ها شارژ کن یا چت شانسی رایگان برو.");
                $this->showWallet($chatId, $user);
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
        $filters = $this->browseFiltersFromUser($user);
        // Keep browse flow sticky
        if (!str_starts_with((string)($user['flow'] ?? ''), 'browse:')) {
            $encoded = rawurlencode(json_encode($filters, JSON_UNESCAPED_UNICODE));
            $this->db->updateUser($tid, ['flow' => 'browse:' . $encoded]);
            $user = $this->db->findUser($tid) ?? $user;
        }
        $cursor = (int)($user['browse_cursor'] ?? 0);
        $target = $this->db->nextBrowseProfile($tid, $cursor, $filters);
        if (!$target) {
            $this->uiText($chatId, $user, "کسی با این فیلتر پیدا نشد.\nاستان دیگری را امتحان کن یا بعداً برگرد.", [
                'reply_markup' => Keyboards::findProvinces(),
            ]);
            return;
        }
        $this->db->ensureIdentity($target);
        $target = $this->db->findUser((int)$target['telegram_id']) ?? $target;
        $this->db->updateUser($tid, ['browse_cursor' => (int)$target['id']]);
        $user = $this->db->findUser($tid) ?? $user;
        $this->renderBrowseCard($chatId, $user, $target);
    }

    private function renderBrowseCard(int $chatId, array &$viewer, array $target): void
    {
        $this->db->ensureIdentity($target);
        $target = $this->db->findUser((int)$target['telegram_id']) ?? $target;
        $dn = htmlspecialchars((string)($target['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $g = ($target['gender'] ?? '') === 'female' ? 'دختر' : (($target['gender'] ?? '') === 'male' ? 'پسر' : '—');
        $prov = htmlspecialchars((string)($target['province'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $city = htmlspecialchars((string)($target['city'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pc = htmlspecialchars((string)($target['public_code'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $age = (int)($target['age'] ?? 0);
        $online = 'آفلاین';
        if (!empty($target['last_seen_at'])) {
            $ts = strtotime((string)$target['last_seen_at']);
            if ($ts && (time() - $ts) < 300) {
                $online = 'آنلاین الان';
            } elseif ($ts && (time() - $ts) < 3600) {
                $online = 'فعال اخیراً';
            }
        }
        // Modern, calm card — not competitor green spam layout.
        $caption =
            "<b>{$dn}</b>\n" .
            "{$g} · {$age} ساله\n" .
            "{$prov} · {$city}\n" .
            "{$online}\n" .
            "────────\n" .
            "شناسه: <code>{$pc}</code>";

        $req = $this->settings->getInt('request_cost', 1);
        $msg = $this->settings->getInt('message_cost', 1);
        $markup = Keyboards::browseProfileInline((string)$target['public_code'], $req, $msg);
        $avatar = (string)($target['avatar_file_id'] ?? '');
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
        $cost = $this->settings->getInt('request_cost', 1);
        if (!$this->db->spendCoins($tid, $cost, 'chat_request', (string)$to)) {
            $this->uiText($chatId, $user, 'سکه کافی نداری.');
            $this->showWallet($chatId, $user);
            return;
        }
        $this->db->createContactRequest($tid, $to, 'request');
        $fromName = htmlspecialchars((string)($user['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $fromCode = htmlspecialchars((string)($user['public_code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        try {
            $this->tg->sendMessage(
                $to,
                "درخواست گفتگو جدید\nاز: <b>{$fromName}</b>\nکد: <code>{$fromCode}</code>\n\nاگر مایلید در بات اصلی به جستجو بروید یا چت ناشناس را باز کنید."
            );
        } catch (Throwable $e) {
            // keep going
        }
        $user = $this->db->findUser($tid) ?? $user;
        $this->uiText($chatId, $user, "درخواست ارسال شد (−{$cost} سکه).");
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
        $cost = $this->settings->getInt('message_cost', 1);
        $body = mb_substr(trim($text), 0, 500);
        if (mb_strlen($body) < 1) {
            $this->tg->sendMessage($chatId, 'متن خالی قبول نیست.');
            return;
        }
        if (!$this->db->spendCoins($tid, $cost, 'direct_message', (string)$to)) {
            $this->db->updateUser($tid, ['flow' => null]);
            $this->uiText($chatId, $user, 'سکه کافی نداری.');
            $this->showWallet($chatId, $user);
            return;
        }
        $this->db->createContactRequest($tid, $to, 'message', $body);
        $fromName = htmlspecialchars((string)($user['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safe = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        try {
            $this->tg->sendMessage(
                $to,
                "پیام کوتاه از <b>{$fromName}</b>\n────────\n{$safe}"
            );
        } catch (Throwable $e) {
        }
        // Restore browse filters if we still have them in a previous flow — restart browse empty filters ok
        $this->db->updateUser($tid, ['flow' => null]);
        $user = $this->db->findUser($tid) ?? $user;
        $this->clearUi($chatId, $user);
        $this->uiText($chatId, $user, "پیام ارسال شد (−{$cost} سکه).");
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
}
