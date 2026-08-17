<?php
declare(strict_types=1);

/**
 * Secure admin Telegram console — username/password gate + full CRUD tools.
 */
final class AdminHandlers
{
    public const CODE_VERSION = '2026-08-17-v10.34-admin';

    /** Keys editable via adm:set: — anything else is rejected. */
    public const ALLOWED_SET_KEYS = [
        'invite_reward', 'invite_milestone_3', 'invite_milestone_10', 'invite_milestone_25', 'invitee_bonus',
        'message_cost', 'request_cost', 'welcome_coins',
        'connect_any_cost', 'connect_gender_cost', 'connect_province_cost', 'connect_age_cost',
        'admin_session_hours', 'admin_username',
        'pay_invoice_minutes', 'pay_card_number', 'pay_card_holder', 'pay_bank_name', 'pay_trust_channel',
        'pack_170_price', 'pack_350_price', 'pack_500_price', 'pack_750_price', 'pack_1000_price', 'pack_unlimited_price',
        'pack_100_price', 'pack_300_price',
        'low_coin_warn', 'profile_complete_bonus', 'private_room_entry_cost', 'private_room_add_cost',
        'vip_bad_words', 'rules_normal', 'rules_hot', 'rules_vipclub',
        'like_cost', 'room_create_cost', 'room_join_cost', 'report_ban_threshold', 'notify_free_cost',
        'support_bot_username', 'support_hours', 'support_welcome',
        'staff_default_password', 'staff_session_hours',
        'brand_name', 'main_bot_username',
        'vip_price_30', 'vip_days', 'vip_min_account_days', 'vip_min_likes', 'vip_max_reports',
        'vip_require_occupation', 'vip_require_avatar',
    ];

    public function __construct(
        private array $config,
        private Database $db,
        private Telegram $tg,
        private Settings $settings
    ) {
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

    private function isLoggedIn(int $tid): bool
    {
        return $this->db->hasValidAdminSession($tid);
    }

    private function onMessage(array $message): void
    {
        $chatId = (int)($message['chat']['id'] ?? 0);
        $from = $message['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        if ($tid <= 0) {
            return;
        }
        $text = trim((string)($message['text'] ?? ''));
        $user = $this->db->upsertUser(
            $tid,
            $from['username'] ?? null,
            $from['first_name'] ?? null,
            $this->settings->getInt('welcome_coins', 35)
        );
        $flow = (string)($user['flow'] ?? '');

        // ——— Auth gate ———
        if (!$this->isLoggedIn($tid)) {
            if ($text === '/start' || $text === '/admin' || $text === '/login') {
                $this->db->updateUser($tid, ['flow' => 'adm:login:user']);
                $hours = max(2, $this->settings->getInt('admin_session_hours', 2));
                $this->tg->sendMessage(
                    $chatId,
                    "🔐 <b>ورود به پنل ادمین هم‌گپ</b>\n\n" .
                    "نام کاربری ادمین را بفرست.\n" .
                    "بعد از ورود تا حدود <b>{$hours} ساعت</b> دوباره یوزر/رمز نمی‌خواهد."
                );
                return;
            }
            if ($flow === 'adm:login:user') {
                $expected = $this->settings->get('admin_username', 'hamgap_admin');
                if (!hash_equals($expected, $text)) {
                    $this->db->updateUser($tid, ['flow' => null]);
                    $this->tg->sendMessage($chatId, 'نام کاربری نادرست است. دوباره /login بزن.');
                    return;
                }
                $this->db->updateUser($tid, ['flow' => 'adm:login:pass']);
                $this->tg->sendMessage($chatId, 'رمز عبور را بفرست:');
                return;
            }
            if ($flow === 'adm:login:pass') {
                $hash = $this->settings->get('admin_password_hash', '');
                if ($hash === '' || !password_verify($text, $hash)) {
                    $this->db->updateUser($tid, ['flow' => null]);
                    $this->tg->sendMessage($chatId, 'رمز نادرست است. دوباره /login بزن.');
                    return;
                }
                $hours = max(2, $this->settings->getInt('admin_session_hours', 2));
                $this->db->createAdminSession($tid, $hours);
                $this->db->updateUser($tid, ['flow' => null, 'is_admin' => 1]);
                $this->tg->sendMessage(
                    $chatId,
                    "✅ ورود موفق\nتا حدود <b>{$hours} ساعت</b> یوزر/رمز دوباره لازم نیست (با کار در پنل تمدید می‌شود)."
                );
                $this->home($chatId);
                return;
            }
            $this->tg->sendMessage($chatId, "برای ورود به پنل ادمین /login را بزن.");
            return;
        }

        // Keep session alive while working
        $this->db->touchAdminSession($tid, max(2, $this->settings->getInt('admin_session_hours', 2)));

        // Logged in
        if ($text === '/logout' || $text === 'خروج') {
            $this->db->destroyAdminSession($tid);
            $this->db->updateUser($tid, ['flow' => null]);
            $this->tg->sendMessage($chatId, 'از پنل خارج شدی.');
            return;
        }

        if ($text === '/start' || $text === '/admin' || $text === '/login' || $text === 'خانه') {
            $this->db->updateUser($tid, ['flow' => null]);
            $this->home($chatId);
            return;
        }

        // Password change
        if ($flow === 'adm:pwd:new') {
            if (mb_strlen($text) < 8) {
                $this->tg->sendMessage($chatId, 'رمز جدید حداقل ۸ کاراکتر باشد.');
                return;
            }
            $this->db->updateUser($tid, ['flow' => 'adm:pwd:confirm:' . base64_encode($text)]);
            $this->tg->sendMessage($chatId, 'رمز جدید را دوباره بفرست (تأیید):');
            return;
        }
        if (str_starts_with($flow, 'adm:pwd:confirm:')) {
            $encoded = substr($flow, strlen('adm:pwd:confirm:'));
            $first = base64_decode($encoded, true);
            if ($first === false || !hash_equals($first, $text)) {
                $this->db->updateUser($tid, ['flow' => null]);
                $this->tg->sendMessage($chatId, 'تأیید رمز مطابقت نداشت. از منو دوباره تلاش کن.');
                return;
            }
            $this->settings->set('admin_password_hash', password_hash($text, PASSWORD_DEFAULT));
            $this->db->updateUser($tid, ['flow' => null]);
            $this->tg->sendMessage($chatId, 'رمز ادمین با موفقیت تغییر کرد ✅');
            $this->home($chatId);
            return;
        }

        if (str_starts_with($flow, 'adm:set:')) {
            $key = substr($flow, strlen('adm:set:'));
            if (!in_array($key, self::ALLOWED_SET_KEYS, true)) {
                $this->db->updateUser($tid, ['flow' => null]);
                $this->tg->sendMessage($chatId, 'کلید تنظیمات نامعتبر است.');
                return;
            }
            $value = trim($text);
            if ($value === '') {
                $this->tg->sendMessage($chatId, 'مقدار خالی قبول نیست.');
                return;
            }
            if (in_array($key, [
                'invite_reward', 'invite_milestone_3', 'invite_milestone_10', 'invite_milestone_25', 'invitee_bonus',
                'message_cost', 'request_cost', 'welcome_coins',
                'connect_any_cost', 'connect_gender_cost', 'connect_province_cost', 'connect_age_cost',
                'admin_session_hours', 'staff_session_hours', 'pay_invoice_minutes',
                'pack_170_price', 'pack_350_price', 'pack_500_price', 'pack_750_price', 'pack_1000_price', 'pack_unlimited_price',
                'pack_100_price', 'pack_300_price',
                'low_coin_warn', 'profile_complete_bonus', 'private_room_entry_cost', 'private_room_add_cost',
                'like_cost', 'room_create_cost', 'room_join_cost', 'report_ban_threshold', 'notify_free_cost',
                'vip_price_30', 'vip_days', 'vip_min_account_days', 'vip_min_likes', 'vip_max_reports',
                'vip_require_occupation', 'vip_require_avatar',
            ], true)) {
                if (!ctype_digit($value)) {
                    $this->tg->sendMessage($chatId, 'فقط عدد بفرست.');
                    return;
                }
            }
            if ($key === 'staff_default_password') {
                if (mb_strlen($value) < 6) {
                    $this->tg->sendMessage($chatId, 'رمز پیش‌فرض کارمند حداقل ۶ کاراکتر باشد.');
                    return;
                }
            }
            if ($key === 'report_ban_threshold') {
                $n = (int)$value;
                if ($n < 1 || $n > 100) {
                    $this->tg->sendMessage($chatId, 'آستانه بلاک باید بین ۱ تا ۱۰۰ باشد.');
                    return;
                }
            }
            if ($key === 'admin_username') {
                if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $value)) {
                    $this->tg->sendMessage($chatId, 'نام کاربری فقط حروف/عدد/_ و ۳ تا ۳۲ کاراکتر.');
                    return;
                }
            }
            if ($key === 'pay_card_number') {
                $value = preg_replace('/\D+/', '', $value) ?? '';
                if (strlen($value) < 16 || strlen($value) > 19) {
                    $this->tg->sendMessage($chatId, 'شماره کارت باید ۱۶ تا ۱۹ رقم باشد.');
                    return;
                }
            }
            if (in_array($key, ['support_bot_username', 'main_bot_username', 'pay_trust_channel'], true)) {
                $value = ltrim($value, '@');
            }
            $this->settings->set($key, $value);
            $this->db->updateUser($tid, ['flow' => null]);
            $extra = '';
            if ($key === 'staff_default_password') {
                $synced = $this->db->syncAllSupportStaffPasswords($value);
                $extra = "\nرمز همه کارمندان همگام شد: <b>{$synced}</b> نفر.";
            }
            $this->tg->sendMessage(
                $chatId,
                "✅ ذخیره شد\n<code>{$key}</code> = <b>" .
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>' . $extra
            );
            if (in_array($key, ['pay_card_number', 'pay_card_holder', 'pay_bank_name', 'pay_trust_channel', 'pay_invoice_minutes'], true)
                || str_starts_with($key, 'pack_')
            ) {
                $this->handlePayAdmin($chatId, $tid, 'adm:pay');
                return;
            }
            $this->home($chatId);
            return;
        }

        if ($flow === 'adm:user:find') {
            $this->db->updateUser($tid, ['flow' => null]);
            $target = $this->resolveUserQuery($text);
            if (!$target) {
                $this->tg->sendMessage($chatId, 'کاربر پیدا نشد.');
                return;
            }
            $this->showUserCard($chatId, $target);
            return;
        }

        if ($flow === 'adm:staff:add') {
            $this->db->updateUser($tid, ['flow' => null]);
            $targetId = 0;
            $label = null;
            if (ctype_digit($text)) {
                $targetId = (int)$text;
            } else {
                $u = $this->db->findByUsername($text);
                if ($u) {
                    $targetId = (int)$u['telegram_id'];
                    $uname = trim((string)($u['username'] ?? ''));
                    $label = $uname !== '' ? $uname : null;
                }
            }
            if ($targetId <= 0) {
                $this->tg->sendMessage(
                    $chatId,
                    "آیدی عددی تلگرام یا @یوزرنیم کارمند را بفرست.\n" .
                    "مثال: <code>123456789</code> یا <code>@username</code>\n" .
                    "برای یوزرنیم باید حداقل یک‌بار بات اصلی را /start زده باشد."
                );
                return;
            }
            $this->db->addSupportStaff($targetId, $label);
            $defaultPwd = $this->settings->get('staff_default_password', 'HamGapStaff1');
            $issued = $this->db->ensureSupportStaffPassword($targetId, $defaultPwd);
            $this->notifyNewSupportStaff($targetId, $issued !== '' ? $issued : $defaultPwd);
            $pwdNote = $issued !== ''
                ? "\nرمز اولیه پنل: <code>" . htmlspecialchars($issued, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</code>"
                : "\nرمز پنل از قبل تنظیم شده (در صورت نیاز ریست کن).";
            $this->tg->sendMessage(
                $chatId,
                "✅ کارمند <code>{$targetId}</code> اضافه و <b>فعال</b> شد.{$pwdNote}\n" .
                "باید بات پشتیبانی را /start بزند و با /login وارد پنل شود.",
                ['reply_markup' => Keyboards::adminSupport()]
            );
            return;
        }

        if (str_starts_with($flow, 'adm:staff:pwd:')) {
            $sid = (int)substr($flow, strlen('adm:staff:pwd:'));
            $this->db->updateUser($tid, ['flow' => null]);
            if ($sid <= 0 || mb_strlen($text) < 6) {
                $this->tg->sendMessage($chatId, 'رمز نامعتبر (حداقل ۶ کاراکتر).');
                return;
            }
            if (!$this->db->getSupportStaff($sid)) {
                $this->db->addSupportStaff($sid);
            }
            $ok = $this->db->setSupportStaffPassword($sid, $text);
            if (!$ok) {
                $this->tg->sendMessage($chatId, 'ذخیره رمز ناموفق بود. دوباره تلاش کن.');
                return;
            }
            // Also update default so login fallback matches
            $this->settings->set('staff_default_password', $text);
            $this->tg->sendMessage(
                $chatId,
                "✅ رمز پنل کارمند <code>{$sid}</code> ذخیره و اعمال شد.\n" .
                "رمز: <code>" . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</code>\n" .
                "کارمند با /login در بات پشتیبانی وارد شود."
            );
            $this->notifyNewSupportStaff($sid, $text);
            return;
        }

        if (str_starts_with($flow, 'adm:edit:')) {
            // adm:edit:FIELD:TELEGRAM_ID
            $rest = substr($flow, strlen('adm:edit:'));
            $parts = explode(':', $rest, 2);
            $field = $parts[0] ?? '';
            $targetId = (int)($parts[1] ?? 0);
            $this->db->updateUser($tid, ['flow' => null]);
            if (!$targetId || $field === '') {
                $this->tg->sendMessage($chatId, 'ویرایش نامعتبر.');
                return;
            }
            $ok = $this->applyUserEdit($targetId, $field, $text);
            if (!$ok) {
                $this->tg->sendMessage($chatId, 'مقدار نامعتبر بود.');
                return;
            }
            $fresh = $this->db->findUser($targetId);
            $this->tg->sendMessage($chatId, 'ویرایش ذخیره شد ✅');
            if ($fresh) {
                $this->showUserCard($chatId, $fresh);
            }
            return;
        }

        if (str_starts_with($flow, 'adm:broadcast')) {
            $this->db->updateUser($tid, ['flow' => null]);
            $this->tg->sendMessage($chatId, 'ارسال همگانی فعلاً برای ایمنی غیرفعال است. از پیام تکی کاربر استفاده کن.');
            $this->home($chatId);
            return;
        }

        $this->home($chatId);
    }

    private function onCallback(array $cq): void
    {
        $id = (string)$cq['id'];
        $data = (string)($cq['data'] ?? '');
        $message = $cq['message'] ?? [];
        $chatId = (int)($message['chat']['id'] ?? 0);
        $from = $cq['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);

        if (!$this->isLoggedIn($tid)) {
            if (str_starts_with($data, 'sup:')) {
                $this->tg->answerCallback($id, 'اول در بات ادمین /login کن یا از بات پشتیبانی پذیرش بزن', true);
                return;
            }
            $this->tg->answerCallback($id, 'اول /login کن', true);
            return;
        }

        $this->db->touchAdminSession($tid, max(2, $this->settings->getInt('admin_session_hours', 2)));

        // Emergency: accept/close support tickets that were pushed to admin bot
        if (str_starts_with($data, 'sup:')) {
            require_once __DIR__ . '/SupportHandlers.php';
            require_once __DIR__ . '/Keyboards.php';
            $supToken = (string)($this->config['support_bot_token'] ?? '');
            if ($supToken === '') {
                $supToken = $this->settings->get('support_bot_token', '');
            }
            $deskTg = $supToken !== '' ? new Telegram($supToken) : $this->tg;
            $desk = new SupportHandlers($this->config, $this->db, $deskTg, $this->settings);
            if (str_starts_with($data, 'sup:take:')) {
                $ticketId = (int)substr($data, strlen('sup:take:'));
                $res = $desk->claimTicket($tid, $ticketId);
                $this->tg->answerCallback($id, $res['toast'], !($res['ok'] ?? false));
                $this->tg->sendMessage($chatId, $res['detail']);
                return;
            }
            if (str_starts_with($data, 'sup:close:')) {
                $ticketId = (int)substr($data, strlen('sup:close:'));
                $res = $desk->claimClose($tid, $ticketId);
                $this->tg->answerCallback($id, $res['toast'], !($res['ok'] ?? false));
                $this->tg->sendMessage($chatId, $res['detail']);
                return;
            }
            $this->tg->answerCallback($id);
            return;
        }

        $this->tg->answerCallback($id);

        if ($data === 'adm:home') {
            $this->home($chatId);
            return;
        }
        if ($data === 'adm:logout') {
            $this->db->destroyAdminSession($tid);
            $this->db->updateUser($tid, ['flow' => null]);
            $this->tg->sendMessage($chatId, 'خروج انجام شد. برای ورود دوباره /login');
            return;
        }
        if ($data === 'adm:security') {
            $user = htmlspecialchars($this->settings->get('admin_username'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $hours = $this->settings->getInt('admin_session_hours', 12);
            $this->tg->sendMessage(
                $chatId,
                "🛡 <b>امنیت پنل</b>\n" .
                "نام کاربری: <code>{$user}</code>\n" .
                "مدت نشست: <b>{$hours}</b> ساعت\n" .
                "رمز به صورت هش ذخیره می‌شود.",
                ['reply_markup' => Keyboards::adminSecurity()]
            );
            return;
        }
        if ($data === 'adm:pwd:change') {
            $this->db->updateUser($tid, ['flow' => 'adm:pwd:new']);
            $this->tg->sendMessage($chatId, "رمز جدید را بفرست (حداقل ۸ کاراکتر):");
            return;
        }
        if ($data === 'adm:set:admin_username' || $data === 'adm:set:admin_session_hours') {
            $key = substr($data, strlen('adm:set:'));
            $this->db->updateUser($tid, ['flow' => 'adm:set:' . $key]);
            $cur = $this->settings->get($key);
            $this->tg->sendMessage(
                $chatId,
                "مقدار جدید <code>{$key}</code> را بفرست.\nفعلی: <b>" .
                htmlspecialchars($cur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>'
            );
            return;
        }

        if ($data === 'adm:stats') {
            $s = $this->db->countUsers();
            $reports = $this->db->countReports();
            $this->tg->sendMessage(
                $chatId,
                "📊 <b>آمار هم‌گپ</b>\n" .
                "کل کاربران: <b>{$s['total']}</b>\n" .
                "پروفایل کامل: <b>{$s['complete']}</b>\n" .
                "در حال چت: <b>{$s['chatting']}</b>\n" .
                "مسدود: <b>{$s['banned']}</b>\n" .
                "گزارش‌ها: <b>{$reports}</b>",
                ['reply_markup' => Keyboards::adminMain()]
            );
            return;
        }
        if ($data === 'adm:coins') {
            $all = $this->settings->all();
            $this->tg->sendMessage(
                $chatId,
                "🪙 <b>تنظیمات سکه و هزینه</b>\n" .
                "پاداش دعوت: <b>{$all['invite_reward']}</b>\n" .
                "بونوس ۳/۱۰/۲۵ دعوت: <b>{$all['invite_milestone_3']}</b> / <b>{$all['invite_milestone_10']}</b> / <b>{$all['invite_milestone_25']}</b>\n" .
                "هدیه دعوت‌شونده: <b>{$all['invitee_bonus']}</b>\n" .
                "پیام کوتاه: <b>{$all['message_cost']}</b>\n" .
                "درخواست گفتگو: <b>{$all['request_cost']}</b>\n" .
                "خوش‌آمد: <b>{$all['welcome_coins']}</b>\n" .
                "آستانه بلاک با گزارش: <b>{$all['report_ban_threshold']}</b>\n" .
                "خبر آزاد شدن از چت: <b>{$all['notify_free_cost']}</b> سکه\n" .
                "چت شانسی/جنسیت/استان/سن: " .
                "{$all['connect_any_cost']}/{$all['connect_gender_cost']}/{$all['connect_province_cost']}/{$all['connect_age_cost']}\n" .
                "آستانه کمبود سکه: <b>{$all['low_coin_warn']}</b> · بونوس تکمیل پروفایل: <b>{$all['profile_complete_bonus']}</b>\n" .
                "صفحه خصوصی ورود/افزودن: <b>{$all['private_room_entry_cost']}</b> / <b>{$all['private_room_add_cost']}</b>",
                ['reply_markup' => Keyboards::adminCoins()]
            );
            return;
        }
        if ($data === 'adm:vip') {
            $all = $this->settings->all();
            $this->tg->sendMessage(
                $chatId,
                "⭐ <b>استار کلاب</b>\n" .
                "قیمت ۳۰ روز: <b>{$all['vip_price_30']}</b> تومان\n" .
                "مدت: <b>{$all['vip_days']}</b> روز\n" .
                "حداقل روز عضویت: <b>{$all['vip_min_account_days']}</b>\n" .
                "حداقل لایک: <b>{$all['vip_min_likes']}</b>\n" .
                "حداکثر گزارش: <b>{$all['vip_max_reports']}</b>\n" .
                "اجبار تخصص/عکس: <b>{$all['vip_require_occupation']}</b> / <b>{$all['vip_require_avatar']}</b>",
                ['reply_markup' => Keyboards::adminVip()]
            );
            return;
        }
        if ($data === 'adm:vip:pending') {
            $rows = $this->db->listPendingVipRequests(15);
            if (!$rows) {
                $this->tg->sendMessage($chatId, 'درخواست معلقی نیست.', [
                    'reply_markup' => Keyboards::adminVip(),
                ]);
                return;
            }
            $this->tg->sendMessage($chatId, '📥 درخواست‌های استار کلاب:');
            foreach ($rows as $r) {
                $dn = htmlspecialchars((string)($r['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $occ = Occupation::badge((string)($r['occupation'] ?? '')) ?: '—';
                $this->tg->sendMessage(
                    $chatId,
                    "• <b>{$dn}</b>\n<code>" . (int)$r['telegram_id'] . "</code>\nتخصص: {$occ}",
                    ['reply_markup' => Keyboards::adminVipRequestActions((int)$r['telegram_id'])]
                );
            }
            return;
        }
        if (str_starts_with($data, 'adm:vip:ok:')) {
            $target = (int)substr($data, strlen('adm:vip:ok:'));
            $days = max(1, $this->settings->getInt('vip_days', 30));
            $this->db->extendVip($target, $days);
            $this->db->updateUser($target, ['vip_request' => 'approved']);
            $this->tg->sendMessage($chatId, "کاربر {$target} تأیید و {$days} روز استار گرفت.");
            $mainToken = (string)($this->config['bot_token'] ?? '');
            $mainTg = $mainToken !== '' ? new Telegram($mainToken) : $this->tg;
            try {
                $mainTg->sendMessage($target, "✅ درخواست استار کلاب تأیید شد.\nعضویت {$days} روزه فعال شد ⭐");
            } catch (Throwable $e) {
            }
            return;
        }
        if (str_starts_with($data, 'adm:vip:no:')) {
            $target = (int)substr($data, strlen('adm:vip:no:'));
            $this->db->updateUser($target, ['vip_request' => 'rejected']);
            $this->tg->sendMessage($chatId, "درخواست {$target} رد شد.");
            $mainToken = (string)($this->config['bot_token'] ?? '');
            $mainTg = $mainToken !== '' ? new Telegram($mainToken) : $this->tg;
            try {
                $mainTg->sendMessage($target, '❌ درخواست استار کلاب رد شد. پروفایل و شرایط را کامل‌تر کن و دوباره تلاش کن.');
            } catch (Throwable $e) {
            }
            return;
        }
        if ($data === 'adm:users') {
            $this->tg->sendMessage($chatId, "👥 <b>مدیریت کاربران</b>\nجستجو، ویرایش ریز، مسدود، حذف کامل.", [
                'reply_markup' => Keyboards::adminUsers(),
            ]);
            return;
        }
        if ($data === 'adm:users:recent') {
            $rows = $this->db->recentUsers(10);
            $lines = ["🆕 <b>۱۰ کاربر اخیر</b>"];
            foreach ($rows as $r) {
                $dn = htmlspecialchars((string)($r['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $lines[] = "• {$dn} · <code>" . (int)$r['telegram_id'] . '</code> · ' .
                    htmlspecialchars((string)$r['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            $this->tg->sendMessage($chatId, implode("\n", $lines), [
                'reply_markup' => Keyboards::adminUsers(),
            ]);
            return;
        }
        if ($data === 'adm:users:banned') {
            $rows = $this->db->bannedUsers(15);
            if (!$rows) {
                $this->tg->sendMessage($chatId, 'کاربر مسدودی نیست.', [
                    'reply_markup' => Keyboards::adminUsers(),
                ]);
                return;
            }
            $this->tg->sendMessage(
                $chatId,
                "🚫 <b>مسدودها</b>\n" .
                "برای هر نفر می‌تونی رفع مسدود کنی یا حذف کامل بزنی تا با نام جدید ثبت‌نام کند."
            );
            foreach ($rows as $r) {
                $dn = (string)($r['display_name'] ?? 'کاربر');
                $tidU = (int)$r['telegram_id'];
                $label = $dn . ' · ' . $tidU;
                $this->tg->sendMessage(
                    $chatId,
                    "• <b>" . htmlspecialchars($dn, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n<code>{$tidU}</code>",
                    ['reply_markup' => Keyboards::adminBannedListItem($tidU, $label)]
                );
            }
            return;
        }
        if ($data === 'adm:reports') {
            $threshold = $this->settings->getInt('report_ban_threshold', 10);
            $rows = $this->db->recentReports(12);
            $lines = [
                '🚩 <b>گزارش‌های تخلف</b>',
                "آستانه بلاک خودکار: <b>{$threshold}</b> گزارش",
                'با رسیدن به این عدد، کاربر خودکار مسدود می‌شود.\n' .
                "ادمین می‌تواند رفع مسدود کند یا حذف کامل بزند تا کاربر با نام جدید ثبت‌نام کند.",
                '',
            ];
            if (!$rows) {
                $lines[] = 'هنوز گزارشی ثبت نشده.';
            } else {
                $lines[] = '<b>آخرین گزارش‌ها:</b>';
                foreach ($rows as $r) {
                    $lines[] = '• از <code>' . (int)$r['reporter_id'] . '</code> روی <code>' .
                        (int)$r['reported_id'] . '</code> — ' .
                        htmlspecialchars((string)($r['reason'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }
            }
            $this->tg->sendMessage($chatId, implode("\n", $lines), [
                'reply_markup' => Keyboards::adminReports($threshold),
            ]);
            return;
        }
        if ($data === 'adm:support') {
            $u = $this->settings->get('support_bot_username');
            $this->tg->sendMessage(
                $chatId,
                "🆘 <b>پشتیبانی</b>\n" .
                'بات: <b>' . ($u !== '' ? '@' . htmlspecialchars($u, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'ثبت نشده') . "</b>\n" .
                'ساعات: <b>' . htmlspecialchars($this->settings->get('support_hours'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>',
                ['reply_markup' => Keyboards::adminSupport()]
            );
            return;
        }
        if ($data === 'adm:general') {
            $all = $this->settings->all();
            $brand = htmlspecialchars((string)$all['brand_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $main = htmlspecialchars((string)$all['main_bot_username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $thr = htmlspecialchars((string)$all['report_ban_threshold'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->tg->sendMessage(
                $chatId,
                "⚙️ <b>تنظیمات عمومی</b>\nبرند: <b>{$brand}</b>\nبات اصلی: <b>@{$main}</b>\n" .
                "بلاک خودکار بعد از: <b>{$thr}</b> گزارش تخلف",
                ['reply_markup' => Keyboards::adminGeneral()]
            );
            return;
        }

        if ($data === 'adm:pay' || $data === 'adm:pay:pending' || str_starts_with($data, 'payadm:')) {
            try {
                $this->handlePayAdmin($chatId, $tid, $data);
            } catch (Throwable $e) {
                $this->tg->sendMessage(
                    $chatId,
                    "⚠️ باز شدن بخش پرداخت ناموفق بود.\n<code>" .
                    htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>'
                );
            }
            return;
        }
        if ($data === 'adm:user:find') {
            $this->db->updateUser($tid, ['flow' => 'adm:user:find']);
            $this->tg->sendMessage($chatId, 'آیدی عددی، @یوزرنیم، کد عمومی (HG…) یا کد دعوت را بفرست.');
            return;
        }
        if ($data === 'adm:staff:add') {
            $this->db->updateUser($tid, ['flow' => 'adm:staff:add']);
            $this->tg->sendMessage(
                $chatId,
                "آیدی عددی تلگرام یا @یوزرنیم کارمند را بفرست.\n" .
                "بعد از ثبت، وضعیت او <b>فعال</b> می‌شود."
            );
            return;
        }
        if ($data === 'adm:staff:list') {
            $this->showStaffList($chatId);
            return;
        }
        if (str_starts_with($data, 'adm:staff:off:')) {
            $sid = (int)substr($data, strlen('adm:staff:off:'));
            $this->db->deactivateSupportStaff($sid);
            $this->db->destroyStaffSession($sid);
            $this->tg->sendMessage($chatId, "🔴 کارمند <code>{$sid}</code> غیرفعال شد.");
            $u = $this->db->findUser($sid);
            if ($u) {
                $this->showUserCard($chatId, $u);
            } else {
                $this->showStaffList($chatId);
            }
            return;
        }
        if (str_starts_with($data, 'adm:staff:on:')) {
            $sid = (int)substr($data, strlen('adm:staff:on:'));
            $this->db->activateSupportStaff($sid);
            $defaultPwd = $this->settings->get('staff_default_password', 'HamGapStaff1');
            $issued = $this->db->ensureSupportStaffPassword($sid, $defaultPwd);
            $this->notifyNewSupportStaff($sid, $issued !== '' ? $issued : null);
            $this->tg->sendMessage(
                $chatId,
                "🟢 کارمند <code>{$sid}</code> فعال شد.\n" .
                ($issued !== ''
                    ? ("رمز اولیه پنل: <code>" . htmlspecialchars($issued, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</code>\n")
                    : '') .
                "باید بات پشتیبانی را /start و /login بزند."
            );
            $u = $this->db->findUser($sid);
            if ($u) {
                $this->showUserCard($chatId, $u);
            } else {
                $this->showStaffList($chatId);
            }
            return;
        }
        if (str_starts_with($data, 'adm:staff:pwd:')) {
            $sid = (int)substr($data, strlen('adm:staff:pwd:'));
            $this->db->updateUser($tid, ['flow' => 'adm:staff:pwd:' . $sid]);
            $this->tg->sendMessage($chatId, "رمز جدید پنل کارمند <code>{$sid}</code> را بفرست (حداقل ۶ کاراکتر):");
            return;
        }
        if (str_starts_with($data, 'adm:flagadmin:on:')) {
            $sid = (int)substr($data, strlen('adm:flagadmin:on:'));
            $this->db->updateUser($sid, ['is_admin' => 1]);
            $this->tg->sendMessage($chatId, "🛡 پرچم ادمین برای <code>{$sid}</code> روشن شد (اعلان‌های مدیریتی).");
            $u = $this->db->findUser($sid);
            if ($u) {
                $this->showUserCard($chatId, $u);
            }
            return;
        }
        if (str_starts_with($data, 'adm:flagadmin:off:')) {
            $sid = (int)substr($data, strlen('adm:flagadmin:off:'));
            $this->db->updateUser($sid, ['is_admin' => 0]);
            $this->tg->sendMessage($chatId, "پرچم ادمین برای <code>{$sid}</code> خاموش شد.");
            $u = $this->db->findUser($sid);
            if ($u) {
                $this->showUserCard($chatId, $u);
            }
            return;
        }
        if (str_starts_with($data, 'adm:set:')) {
            $key = substr($data, strlen('adm:set:'));
            if (!in_array($key, self::ALLOWED_SET_KEYS, true)) {
                $this->tg->sendMessage($chatId, 'کلید تنظیمات نامعتبر است.');
                return;
            }
            $this->db->updateUser($tid, ['flow' => 'adm:set:' . $key]);
            $cur = $this->settings->get($key);
            if ($key === 'report_ban_threshold') {
                $this->tg->sendMessage(
                    $chatId,
                    "🔢 بعد از چند گزارش تخلف کاربر خودکار بلاک شود؟\n" .
                    "عدد بین ۱ تا ۱۰۰ بفرست.\n" .
                    "فعلی: <b>" . htmlspecialchars($cur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
                    "پیشنهادی: <b>10</b>"
                );
                return;
            }
            $hint = match ($key) {
                'pay_card_number' => "💳 <b>شماره کارت بانکی</b> را بفرست (۱۶ تا ۱۹ رقم، بدون فاصله).\nفعلی: <b>" .
                    htmlspecialchars($cur !== '' ? $cur : 'ثبت نشده', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>',
                'pay_card_holder' => "نام صاحب حساب را بفرست.\nفعلی: <b>" .
                    htmlspecialchars($cur !== '' ? $cur : 'ثبت نشده', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>',
                'pay_bank_name' => "نام بانک را بفرست (مثلاً: ملی).\nفعلی: <b>" .
                    htmlspecialchars($cur !== '' ? $cur : 'ثبت نشده', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>',
                default => "مقدار جدید برای <code>{$key}</code> را بفرست.\nفعلی: <b>" .
                    htmlspecialchars($cur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>',
            };
            $this->tg->sendMessage($chatId, $hint);
            return;
        }

        // User actions
        if (str_starts_with($data, 'adm:usercard:')) {
            $target = (int)substr($data, strlen('adm:usercard:'));
            $u = $this->db->findUser($target);
            if (!$u) {
                $this->tg->sendMessage($chatId, 'کاربر پیدا نشد (شاید قبلاً حذف شده).');
                return;
            }
            $this->showUserCard($chatId, $u);
            return;
        }
        if (str_starts_with($data, 'adm:ban:')) {
            $target = (int)substr($data, strlen('adm:ban:'));
            $this->db->updateUser($target, [
                'status' => 'banned',
                'partner_id' => null,
                'search_pref' => null,
                'ban_reason' => 'admin',
            ]);
            $this->tg->sendMessage(
                $chatId,
                "🚫 کاربر <code>{$target}</code> مسدود شد.\n" .
                "اگر می‌خواهی با نام جدید برگردد، حذف کامل بزن.",
                ['reply_markup' => Keyboards::adminBanDecision($target)]
            );
            try {
                $sup = trim($this->settings->get('support_bot_username'));
                $sup = $sup !== '' ? ltrim($sup, '@') : 'HamGapXHelpBot';
                $this->tg->sendMessage(
                    $target,
                    "⛔️ حسابت توسط مدیریت مسدود شد.\nبرای پیگیری با پشتیبانی تماس بگیر: @{$sup}"
                );
            } catch (Throwable $e) {
            }
            return;
        }
        if (str_starts_with($data, 'adm:unban:')) {
            $target = (int)substr($data, strlen('adm:unban:'));
            $this->db->updateUser($target, ['status' => 'idle', 'ban_reason' => null]);
            $this->tg->sendMessage($chatId, "✅ مسدودیت {$target} برداشته شد.");
            $u = $this->db->findUser($target);
            if ($u) {
                $this->showUserCard($chatId, $u);
            }
            try {
                $this->tg->sendMessage($target, '✅ مسدودیت حسابت برداشته شد. دوباره می‌تونی از بات استفاده کنی.');
            } catch (Throwable $e) {
            }
            return;
        }
        if (str_starts_with($data, 'adm:wipe:')) {
            $target = (int)substr($data, strlen('adm:wipe:'));
            $this->db->wipePublicProfile($target);
            $this->tg->sendMessage($chatId, "پروفایل عمومی {$target} ریست شد.");
            $u = $this->db->findUser($target);
            if ($u) {
                $this->showUserCard($chatId, $u);
            }
            return;
        }
        if (str_starts_with($data, 'adm:delask:')) {
            $target = (int)substr($data, strlen('adm:delask:'));
            $u = $this->db->findUser($target);
            $dn = $u
                ? htmlspecialchars((string)($u['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : '-';
            $this->tg->sendMessage(
                $chatId,
                "⚠️ <b>حذف کامل برای ثبت‌نام دوباره</b>\n" .
                "کاربر: <b>{$dn}</b>\n" .
                "آیدی: <code>{$target}</code>\n\n" .
                "همه داده‌ها پاک می‌شود (پروفایل، سکه، گزارش، بلاک، گپ…).\n" .
                "بعد از حذف، همان اکانت تلگرام می‌تواند با /start از صفر و با نام جدید ثبت‌نام کند.\n" .
                "این کار برگشت‌ناپذیر است.",
                ['reply_markup' => Keyboards::adminConfirmDelete($target)]
            );
            return;
        }
        if (str_starts_with($data, 'adm:delgo:')) {
            $target = (int)substr($data, strlen('adm:delgo:'));
            $ok = $this->db->deleteUserHard($target);
            if ($ok) {
                $this->tg->sendMessage(
                    $chatId,
                    "✅ کاربر <code>{$target}</code> کامل حذف شد.\n" .
                    "حالا می‌تواند /start بزند و با نام جدید ثبت‌نام کند."
                );
                try {
                    $mainToken = (string)($this->config['bot_token'] ?? '');
                    $mainTg = $mainToken !== '' ? new Telegram($mainToken) : $this->tg;
                    $mainTg->sendMessage(
                        $target,
                        "حساب قبلی‌ات در هم‌گپ کامل حذف شد.\n" .
                        "برای شروع دوباره دستور /start را بزن و پروفایل جدید بساز."
                    );
                } catch (Throwable $e) {
                }
            } else {
                $this->tg->sendMessage($chatId, 'کاربر پیدا نشد (شاید قبلاً حذف شده).');
            }
            return;
        }
        if (str_starts_with($data, 'adm:give:')) {
            $parts = explode(':', substr($data, strlen('adm:give:')));
            $target = (int)($parts[0] ?? 0);
            $amount = (int)($parts[1] ?? 0);
            if ($target && $amount) {
                $this->db->addCoins($target, $amount, 'admin_grant', (string)$tid);
                $this->tg->sendMessage($chatId, "+{$amount} سکه به {$target} اضافه شد.");
                $u = $this->db->findUser($target);
                if ($u) {
                    $this->showUserCard($chatId, $u);
                }
            }
            return;
        }
        if (str_starts_with($data, 'adm:take:')) {
            $parts = explode(':', substr($data, strlen('adm:take:')));
            $target = (int)($parts[0] ?? 0);
            $amount = (int)($parts[1] ?? 0);
            if ($target && $amount) {
                $u = $this->db->findUser($target);
                if ($u) {
                    $new = max(0, (int)$u['coins'] - $amount);
                    $this->db->setCoinsAbsolute($target, $new, 'admin_take');
                    $this->tg->sendMessage($chatId, "−{$amount} سکه از {$target} کم شد. موجودی: {$new}");
                    $this->showUserCard($chatId, $this->db->findUser($target) ?? $u);
                }
            }
            return;
        }
        if (str_starts_with($data, 'adm:setcoins:')) {
            $target = (int)substr($data, strlen('adm:setcoins:'));
            $this->db->updateUser($tid, ['flow' => 'adm:edit:coins:' . $target]);
            $this->tg->sendMessage($chatId, "موجودی سکه جدید برای <code>{$target}</code> را عدد بفرست:");
            return;
        }
        if (str_starts_with($data, 'adm:editask:')) {
            // adm:editask:FIELD:TID
            $rest = substr($data, strlen('adm:editask:'));
            $parts = explode(':', $rest, 2);
            $field = $parts[0] ?? '';
            $target = (int)($parts[1] ?? 0);
            if (!$target || $field === '') {
                return;
            }
            $this->db->updateUser($tid, ['flow' => 'adm:edit:' . $field . ':' . $target]);
            $hints = [
                'display_name' => 'نام نمایشی جدید (۲–۳۲ کاراکتر)',
                'gender' => 'male / female / shemale',
                'age' => 'سن عددی ۱۳–۸۰',
                'province' => 'نام استان',
                'city' => 'نام شهر',
                'coins' => 'موجودی سکه (عدد)',
            ];
            $hint = $hints[$field] ?? $field;
            $this->tg->sendMessage($chatId, "ویرایش <b>{$field}</b> برای <code>{$target}</code>\n{$hint}");
            return;
        }
        if (str_starts_with($data, 'adm:msg:')) {
            $target = (int)substr($data, strlen('adm:msg:'));
            $this->db->updateUser($tid, ['flow' => 'adm:edit:msg:' . $target]);
            $this->tg->sendMessage($chatId, "متن پیام ادمین برای کاربر <code>{$target}</code> را بفرست:");
            return;
        }
    }

    private function home(int $chatId): void
    {
        $brand = htmlspecialchars($this->settings->get('brand_name', 'هم‌گپ'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->tg->sendMessage(
            $chatId,
            "🛠 <b>پنل ادمین {$brand}</b>\n" .
            "ورود امن با نام کاربری و رمز.\n" .
            'نسخه: <code>' . self::CODE_VERSION . '</code>',
            ['reply_markup' => Keyboards::adminMain()]
        );
    }

    private function showUserCard(int $chatId, array $u): void
    {
        $dn = htmlspecialchars((string)($u['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pc = htmlspecialchars((string)($u['public_code'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $g = Gender::label((string)($u['gender'] ?? ''));
        $statusLabel = htmlspecialchars((string)$u['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $banReason = trim((string)($u['ban_reason'] ?? ''));
        $banLine = ($u['status'] ?? '') === 'banned'
            ? ("دلیل مسدود: <b>" . htmlspecialchars($banReason !== '' ? $banReason : '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n")
            : '';
        $tid = (int)$u['telegram_id'];
        $staffRow = $this->db->getSupportStaff($tid);
        $staffActive = $staffRow !== null && (int)($staffRow['is_active'] ?? 0) === 1;
        $staffLine = $staffRow === null
            ? "کارمند پشتیبانی: <b>ثبت نشده</b>\n"
            : ('کارمند پشتیبانی: <b>' . ($staffActive ? 'فعال ✅' : 'غیرفعال ⛔') . "</b>\n");
        $isPanelAdmin = !empty($u['is_admin']);
        $adminLine = 'پرچم ادمین: <b>' . ($isPanelAdmin ? 'روشن' : 'خاموش') . "</b>\n";
        $this->tg->sendMessage(
            $chatId,
            "👤 <b>{$dn}</b>\n" .
            "کد: <code>{$pc}</code>\n" .
            'تلگرام: <code>' . $tid . "</code>\n" .
            "وضعیت: <b>{$statusLabel}</b>\n" .
            $banLine .
            $staffLine .
            $adminLine .
            "جنسیت/سن: <b>{$g}</b> / <b>" . ($u['age'] ?? '-') . "</b>\n" .
            'سکه: <b>' . (int)$u['coins'] . "</b>\n" .
            'استان/شهر: ' . htmlspecialchars((string)($u['province'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            ' / ' . htmlspecialchars((string)($u['city'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            ((($u['status'] ?? '') === 'banned')
                ? "\n\nاگر باید با نام جدید برگردد → «حذف کامل برای ثبت‌نام دوباره»."
                : ''),
            ['reply_markup' => Keyboards::adminUserActions(
                $tid,
                $staffActive,
                $staffRow !== null,
                $isPanelAdmin
            )]
        );
    }

    private function showStaffList(int $chatId): void
    {
        $rows = $this->db->listSupportStaff(false);
        if (!$rows) {
            $this->tg->sendMessage(
                $chatId,
                "کارمندی ثبت نشده.\nاز «افزودن و فعال‌سازی کارمند» استفاده کن.",
                ['reply_markup' => Keyboards::adminSupport()]
            );
            return;
        }
        $lines = [
            "👥 <b>کارمندان پشتیبانی</b>",
            "با دکمه‌های زیر می‌توانی فعال/غیرفعال کنی.",
            '',
        ];
        foreach ($rows as $r) {
            $active = (int)($r['is_active'] ?? 0) === 1;
            $label = trim((string)($r['display_label'] ?? ''));
            $extra = $label !== '' ? ' · @' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
            $lines[] = '• <code>' . (int)$r['telegram_id'] . '</code> — <b>' .
                ($active ? 'فعال ✅' : 'غیرفعال ⛔') . '</b>' . $extra;
        }
        $lines[] = '';
        $lines[] = '⚠️ کارمند باید بات پشتیبانی را یک‌بار /start بزند تا تیکت به او برسد.';
        $this->tg->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => Keyboards::adminStaffListControls($rows),
        ]);
    }

    private function notifyNewSupportStaff(int $telegramId, ?string $plainPassword = null): void
    {
        $supUser = trim($this->settings->get('support_bot_username'));
        $supUser = $supUser !== '' ? ltrim($supUser, '@') : 'HamGapXHelpBot';
        $pwdLine = $plainPassword !== null && $plainPassword !== ''
            ? ("\n🔑 رمز پنل کارمند: <code>" . htmlspecialchars($plainPassword, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</code>\n")
            : "\n";
        $msg =
            "✅ تو به‌عنوان <b>کارمند پشتیبانی هم‌گپ</b> فعال شدی.\n\n" .
            "۱) بات پشتیبانی را باز کن و /start بزن: @{$supUser}\n" .
            "۲) با /login یا /panel وارد پنل شو.{$pwdLine}" .
            "۳) در پنل: جستجو، بلاک، رفع بلاک، تغییر نام، پیام به کاربر\n" .
            "۴) وقتی سؤال کاربر آمد «پذیرش پشتیبانی» را بزن و جواب بده.\n\n" .
            "بعد از ورود، از منوی پنل می‌توانی رمز را عوض کنی.";
        try {
            $token = (string)($this->config['support_bot_token'] ?? '');
            if ($token === '') {
                $token = $this->settings->get('support_bot_token', '');
            }
            $supTg = $token !== '' ? new Telegram($token) : $this->tg;
            $supTg->sendMessage($telegramId, $msg);
        } catch (Throwable $e) {
            try {
                $mainToken = (string)($this->config['bot_token'] ?? '');
                if ($mainToken !== '') {
                    (new Telegram($mainToken))->sendMessage($telegramId, $msg);
                }
            } catch (Throwable $e2) {
            }
        }
    }

    private function resolveUserQuery(string $text): ?array
    {
        if (ctype_digit($text)) {
            return $this->db->findUser((int)$text);
        }
        $byUser = $this->db->findByUsername($text);
        if ($byUser) {
            return $byUser;
        }
        $code = strtoupper(ltrim($text, '@'));
        return $this->db->findByPublicCode($code) ?? $this->db->findByReferralCode($code);
    }

    private function applyUserEdit(int $targetId, string $field, string $value): bool
    {
        if ($field === 'msg') {
            try {
                $this->tg->sendMessage(
                    $targetId,
                    "📢 <b>پیام ادمین هم‌گپ</b>\n" .
                    htmlspecialchars(mb_substr($value, 0, 1000), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                );
                return true;
            } catch (Throwable $e) {
                return false;
            }
        }
        if ($field === 'coins') {
            if (!ctype_digit($value)) {
                return false;
            }
            return $this->db->setCoinsAbsolute($targetId, (int)$value, 'admin_set');
        }
        if ($field === 'display_name') {
            $len = mb_strlen($value);
            if ($len < 2 || $len > 32 || preg_match('/[@\/\\\\]|https?:/iu', $value)) {
                return false;
            }
            $this->db->updateUser($targetId, ['display_name' => $value]);
            return true;
        }
        if ($field === 'gender') {
            $g = strtolower(trim($value));
            if (!Gender::isValid($g)) {
                return false;
            }
            $this->db->updateUser($targetId, ['gender' => $g]);
            return true;
        }
        if ($field === 'age') {
            if (!ctype_digit($value)) {
                return false;
            }
            $age = (int)$value;
            if ($age < 13 || $age > 80) {
                return false;
            }
            $this->db->updateUser($targetId, ['age' => $age]);
            return true;
        }
        if ($field === 'province') {
            $this->db->updateUser($targetId, ['province' => mb_substr($value, 0, 64)]);
            return true;
        }
        if ($field === 'city') {
            $this->db->updateUser($targetId, ['city' => mb_substr($value, 0, 64)]);
            return true;
        }
        return false;
    }

    private function handlePayAdmin(int $chatId, int $tid, string $data): void
    {
        if (str_starts_with($data, 'payadm:')) {
            $ok = str_starts_with($data, 'payadm:ok:');
            $invId = (int)substr($data, strlen($ok ? 'payadm:ok:' : 'payadm:no:'));
            $mainToken = (string)($this->config['bot_token'] ?? '');
            $mainTg = $mainToken !== '' ? new Telegram($mainToken) : $this->tg;
            if ($ok) {
                $res = $this->db->approvePaymentInvoice($invId, $tid);
                if (!($res['ok'] ?? false)) {
                    $this->tg->sendMessage($chatId, 'تأیید نشد (قبلاً بسته شده یا پیدا نشد).');
                    return;
                }
                $coins = (int)$res['coins'];
                $inv = $res['invoice'];
                $type = (string)($res['type'] ?? 'coins');
                try {
                    if ($type === 'vip') {
                        $days = (int)($res['days'] ?? 30);
                        $mainTg->sendMessage(
                            (int)$res['telegram_id'],
                            "✅ پرداخت استار کلاب تأیید شد.\nعضویت <b>{$days} روزه</b> فعال شد ⭐\nفاکتور: <code>" .
                            htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>'
                        );
                    } else {
                        $mainTg->sendMessage(
                            (int)$res['telegram_id'],
                            "✅ پرداختت تأیید شد.\n<b>+{$coins} سکه</b> اضافه شد.\nفاکتور: <code>" .
                            htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>'
                        );
                    }
                } catch (Throwable $e) {
                }
                if ($type === 'vip') {
                    $this->tg->sendMessage($chatId, "✅ فاکتور {$inv['invoice_no']} تأیید و استار کلاب فعال شد.");
                } else {
                    $this->tg->sendMessage($chatId, "✅ فاکتور {$inv['invoice_no']} تأیید و {$coins} سکه شارژ شد.");
                }
                return;
            }
            $res = $this->db->rejectPaymentInvoice($invId, $tid);
            if (!($res['ok'] ?? false)) {
                $this->tg->sendMessage($chatId, 'رد نشد.');
                return;
            }
            $inv = $res['invoice'];
            try {
                $mainTg->sendMessage(
                    (int)$inv['telegram_id'],
                    "❌ فیش فاکتور <code>" . htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    "</code> رد شد. از کیف‌پول دوباره فاکتور بگیر."
                );
            } catch (Throwable $e) {
            }
            $this->tg->sendMessage($chatId, "فاکتور {$inv['invoice_no']} رد شد.");
            return;
        }

        if ($data === 'adm:pay:pending') {
            $rows = $this->db->listPendingPaymentInvoices(20);
            if (!$rows) {
                $this->tg->sendMessage($chatId, 'فیش در انتظاری نیست.', [
                    'reply_markup' => Keyboards::adminPayHome(),
                ]);
                return;
            }
            foreach ($rows as $inv) {
                $amt = number_format((int)$inv['amount_toman'], 0, '.', '٬');
                $text =
                    "فاکتور <code>" . htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</code>\n" .
                    'کاربر: <code>' . (int)$inv['telegram_id'] . "</code>\n" .
                    'سکه: <b>' . (int)$inv['pack_coins'] . "</b>\n" .
                    "مبلغ: <b>{$amt}</b> تومان\n" .
                    'وضعیت: <b>' . htmlspecialchars((string)$inv['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>';
                $fileId = (string)($inv['receipt_file_id'] ?? '');
                $markup = Keyboards::payAdminReviewInline((int)$inv['id']);
                if ($fileId !== '') {
                    try {
                        $this->tg->sendPhotoFileId($chatId, $fileId, $text, $markup);
                        continue;
                    } catch (Throwable $e) {
                    }
                }
                $this->tg->sendMessage($chatId, $text, ['reply_markup' => $markup]);
            }
            return;
        }

        // adm:pay home
        require_once __DIR__ . '/CoinCatalog.php';
        $card = $this->settings->get('pay_card_number');
        $holder = $this->settings->get('pay_card_holder');
        $bank = $this->settings->get('pay_bank_name');
        $ch = trim($this->settings->get('pay_trust_channel'));
        $ttl = $this->settings->get('pay_invoice_minutes');
        $prices = CoinCatalog::prices($this->settings);
        $priceLines = [];
        foreach (CoinCatalog::packs() as $p) {
            $priceLines[] = $p['label'] . '=<b>' . (int)($prices[$p['id']] ?? $p['default_price']) . '</b>';
        }
        $cardShow = $card !== '' ? $card : '— هنوز تنظیم نشده —';
        $this->tg->sendMessage(
            $chatId,
            "💳 <b>پرداخت کارت‌به‌کارت</b>\n\n" .
            "شماره کارت فعلی:\n<code>" . htmlspecialchars($cardShow, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</code>\n" .
            "برای عوض کردن کارت، دکمه <b>✏️ ویرایش شماره کارت</b> را بزن و ۱۶ رقم جدید را بفرست.\n\n" .
            'صاحب حساب: <b>' . htmlspecialchars($holder !== '' ? $holder : '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
            'بانک: <b>' . htmlspecialchars($bank !== '' ? $bank : '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
            'کانال رضایت: <b>' . ($ch !== '' ? '@' . htmlspecialchars($ch, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '—') . "</b>\n" .
            "اعتبار فاکتور: <b>{$ttl}</b> دقیقه\n\n" .
            "قیمت بسته‌ها (تومان):\n" . implode("\n", $priceLines) . "\n\n" .
            "تعریف قیمت فقط برای ادمین است.\n" .
            "کارمند پشتیبانی فقط فیش را تأیید/رد می‌کند تا سکه شارژ شود.",
            ['reply_markup' => Keyboards::adminPayHome()]
        );
    }
}
