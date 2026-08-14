<?php
declare(strict_types=1);

/**
 * Secure admin Telegram console — username/password gate + full CRUD tools.
 */
final class AdminHandlers
{
    public const CODE_VERSION = '2026-08-14-v10.3-admin';

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
        $user = $this->db->upsertUser($tid, $from['username'] ?? null, $from['first_name'] ?? null);
        $flow = (string)($user['flow'] ?? '');

        // ——— Auth gate ———
        if (!$this->isLoggedIn($tid)) {
            if ($text === '/start' || $text === '/admin' || $text === '/login') {
                $this->db->updateUser($tid, ['flow' => 'adm:login:user']);
                $this->tg->sendMessage(
                    $chatId,
                    "🔐 <b>ورود به پنل ادمین هم‌گپ</b>\n\n" .
                    "نام کاربری ادمین را بفرست.\n" .
                    "بدون ورود، هیچ گزینه‌ای در دسترس نیست."
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
                $hours = $this->settings->getInt('admin_session_hours', 12);
                $this->db->createAdminSession($tid, $hours);
                $this->db->updateUser($tid, ['flow' => null, 'is_admin' => 1]);
                $this->tg->sendMessage($chatId, "✅ ورود موفق\nنشست تا حدود {$hours} ساعت فعال است.");
                $this->home($chatId);
                return;
            }
            $this->tg->sendMessage($chatId, "برای ورود به پنل ادمین /login را بزن.");
            return;
        }

        // Logged in
        if ($text === '/logout' || $text === 'خروج') {
            $this->db->destroyAdminSession($tid);
            $this->db->updateUser($tid, ['flow' => null]);
            $this->tg->sendMessage($chatId, 'از پنل خارج شدی.');
            return;
        }

        if ($text === '/start' || $text === '/admin' || $text === 'خانه') {
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
            $value = trim($text);
            if ($value === '') {
                $this->tg->sendMessage($chatId, 'مقدار خالی قبول نیست.');
                return;
            }
            if (in_array($key, [
                'invite_reward', 'message_cost', 'request_cost', 'welcome_coins',
                'connect_any_cost', 'connect_gender_cost', 'connect_province_cost', 'connect_age_cost',
                'admin_session_hours', 'pay_invoice_minutes',
                'pack_100_price', 'pack_300_price', 'pack_1000_price',
            ], true)) {
                if (!ctype_digit($value)) {
                    $this->tg->sendMessage($chatId, 'فقط عدد بفرست.');
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
            $this->tg->sendMessage(
                $chatId,
                "✅ ذخیره شد\n<code>{$key}</code> = <b>" .
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>'
            );
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
            if (!ctype_digit($text)) {
                $this->tg->sendMessage($chatId, 'آیدی عددی تلگرام کارمند را بفرست.');
                return;
            }
            $this->db->addSupportStaff((int)$text);
            $this->tg->sendMessage($chatId, 'کارمند اضافه شد ✅');
            $this->home($chatId);
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
            $this->tg->answerCallback($id, 'اول /login کن', true);
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
                "پیام کوتاه: <b>{$all['message_cost']}</b>\n" .
                "درخواست گفتگو: <b>{$all['request_cost']}</b>\n" .
                "خوش‌آمد: <b>{$all['welcome_coins']}</b>\n" .
                "چت شانسی/جنسیت/استان/سن: " .
                "{$all['connect_any_cost']}/{$all['connect_gender_cost']}/{$all['connect_province_cost']}/{$all['connect_age_cost']}",
                ['reply_markup' => Keyboards::adminCoins()]
            );
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
            $lines = ["🚫 <b>مسدودها</b>"];
            foreach ($rows as $r) {
                $dn = htmlspecialchars((string)($r['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $lines[] = "• {$dn} · <code>" . (int)$r['telegram_id'] . '</code>';
            }
            $this->tg->sendMessage($chatId, implode("\n", $lines), [
                'reply_markup' => Keyboards::adminUsers(),
            ]);
            return;
        }
        if ($data === 'adm:reports') {
            $rows = $this->db->recentReports(12);
            if (!$rows) {
                $this->tg->sendMessage($chatId, 'گزارشی ثبت نشده.', [
                    'reply_markup' => Keyboards::adminMain(),
                ]);
                return;
            }
            $lines = ["🚩 <b>گزارش‌های اخیر</b>"];
            foreach ($rows as $r) {
                $lines[] = '• از <code>' . (int)$r['reporter_id'] . '</code> روی <code>' .
                    (int)$r['reported_id'] . '</code> — ' .
                    htmlspecialchars((string)($r['reason'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            $this->tg->sendMessage($chatId, implode("\n", $lines), [
                'reply_markup' => Keyboards::adminMain(),
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
            $this->tg->sendMessage(
                $chatId,
                "⚙️ <b>تنظیمات عمومی</b>\nبرند: <b>{$brand}</b>\nبات اصلی: <b>@{$main}</b>",
                ['reply_markup' => Keyboards::adminGeneral()]
            );
            return;
        }

        if ($data === 'adm:pay' || $data === 'adm:pay:pending' || str_starts_with($data, 'payadm:')) {
            $this->handlePayAdmin($chatId, $tid, $data);
            return;
        }
        if ($data === 'adm:user:find') {
            $this->db->updateUser($tid, ['flow' => 'adm:user:find']);
            $this->tg->sendMessage($chatId, 'آیدی عددی، کد عمومی (HG…) یا کد دعوت را بفرست.');
            return;
        }
        if ($data === 'adm:staff:add') {
            $this->db->updateUser($tid, ['flow' => 'adm:staff:add']);
            $this->tg->sendMessage($chatId, 'آیدی عددی تلگرام کارمند را بفرست.');
            return;
        }
        if ($data === 'adm:staff:list') {
            $rows = $this->db->listSupportStaff(false);
            if (!$rows) {
                $this->tg->sendMessage($chatId, 'کارمندی ثبت نشده.');
                return;
            }
            $lines = ["👥 <b>کارمندان پشتیبانی</b>"];
            foreach ($rows as $r) {
                $active = !empty($r['is_active']) ? 'فعال' : 'غیرفعال';
                $lines[] = '• <code>' . (int)$r['telegram_id'] . '</code> — ' . $active .
                    ' · [حذف: /]';
            }
            $this->tg->sendMessage($chatId, implode("\n", $lines), [
                'reply_markup' => Keyboards::adminSupport(),
            ]);
            // also send deactivate buttons
            $kb = [];
            foreach ($rows as $r) {
                if (!empty($r['is_active'])) {
                    $sid = (int)$r['telegram_id'];
                    $kb[] = [['text' => "غیرفعال {$sid}", 'callback_data' => 'adm:staff:off:' . $sid]];
                }
            }
            $kb[] = [['text' => 'بازگشت', 'callback_data' => 'adm:support']];
            $this->tg->sendMessage($chatId, 'برای غیرفعال‌سازی کارمند:', [
                'reply_markup' => ['inline_keyboard' => $kb],
            ]);
            return;
        }
        if (str_starts_with($data, 'adm:staff:off:')) {
            $sid = (int)substr($data, strlen('adm:staff:off:'));
            $this->db->deactivateSupportStaff($sid);
            $this->tg->sendMessage($chatId, "کارمند {$sid} غیرفعال شد.");
            return;
        }
        if (str_starts_with($data, 'adm:set:')) {
            $key = substr($data, strlen('adm:set:'));
            $this->db->updateUser($tid, ['flow' => 'adm:set:' . $key]);
            $cur = $this->settings->get($key);
            $this->tg->sendMessage(
                $chatId,
                "مقدار جدید برای <code>{$key}</code> را بفرست.\nفعلی: <b>" .
                htmlspecialchars($cur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>'
            );
            return;
        }

        // User actions
        if (str_starts_with($data, 'adm:ban:')) {
            $target = (int)substr($data, strlen('adm:ban:'));
            $this->db->updateUser($target, ['status' => 'banned', 'partner_id' => null, 'search_pref' => null]);
            $this->tg->sendMessage($chatId, "کاربر {$target} مسدود شد.");
            $u = $this->db->findUser($target);
            if ($u) {
                $this->showUserCard($chatId, $u);
            }
            return;
        }
        if (str_starts_with($data, 'adm:unban:')) {
            $target = (int)substr($data, strlen('adm:unban:'));
            $this->db->updateUser($target, ['status' => 'idle']);
            $this->tg->sendMessage($chatId, "مسدودیت {$target} برداشته شد.");
            $u = $this->db->findUser($target);
            if ($u) {
                $this->showUserCard($chatId, $u);
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
            $this->tg->sendMessage(
                $chatId,
                "⚠️ حذف کامل کاربر <code>{$target}</code>\nاین کار برگشت‌ناپذیر است.",
                ['reply_markup' => Keyboards::adminConfirmDelete($target)]
            );
            return;
        }
        if (str_starts_with($data, 'adm:delgo:')) {
            $target = (int)substr($data, strlen('adm:delgo:'));
            $ok = $this->db->deleteUserHard($target);
            $this->tg->sendMessage($chatId, $ok ? "کاربر {$target} کامل حذف شد." : 'کاربر پیدا نشد.');
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
        $this->tg->sendMessage(
            $chatId,
            "👤 <b>{$dn}</b>\n" .
            "کد: <code>{$pc}</code>\n" .
            'تلگرام: <code>' . (int)$u['telegram_id'] . "</code>\n" .
            'وضعیت: <b>' . htmlspecialchars((string)$u['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
            "جنسیت/سن: <b>{$g}</b> / <b>" . ($u['age'] ?? '-') . "</b>\n" .
            'سکه: <b>' . (int)$u['coins'] . "</b>\n" .
            'استان/شهر: ' . htmlspecialchars((string)($u['province'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            ' / ' . htmlspecialchars((string)($u['city'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ['reply_markup' => Keyboards::adminUserActions((int)$u['telegram_id'])]
        );
    }

    private function resolveUserQuery(string $text): ?array
    {
        if (ctype_digit($text)) {
            return $this->db->findUser((int)$text);
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
                try {
                    $mainTg->sendMessage(
                        (int)$res['telegram_id'],
                        "✅ پرداختت تأیید شد.\n<b>+{$coins} سکه</b> اضافه شد.\nفاکتور: <code>" .
                        htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>'
                    );
                } catch (Throwable $e) {
                }
                $this->tg->sendMessage($chatId, "✅ فاکتور {$inv['invoice_no']} تأیید و {$coins} سکه شارژ شد.");
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
        $card = $this->settings->get('pay_card_number');
        $holder = $this->settings->get('pay_card_holder');
        $bank = $this->settings->get('pay_bank_name');
        $ttl = $this->settings->get('pay_invoice_minutes');
        $p100 = $this->settings->get('pack_100_price');
        $p300 = $this->settings->get('pack_300_price');
        $p1000 = $this->settings->get('pack_1000_price');
        $ch = $this->settings->get('pay_trust_channel');
        $cardShow = $card !== '' ? $card : '— هنوز تنظیم نشده —';
        $this->tg->sendMessage(
            $chatId,
            "💳 <b>پرداخت کارت‌به‌کارت</b>\n\n" .
            "شماره کارت: <code>" . htmlspecialchars($cardShow, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</code>\n" .
            'صاحب حساب: <b>' . htmlspecialchars($holder !== '' ? $holder : '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
            'بانک: <b>' . htmlspecialchars($bank !== '' ? $bank : '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
            'کانال رضایت: <b>' . ($ch !== '' ? '@' . htmlspecialchars($ch, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '—') . "</b>\n" .
            "اعتبار فاکتور: <b>{$ttl}</b> دقیقه\n" .
            "قیمت‌ها: ۱۰۰=<b>{$p100}</b> · ۳۰۰=<b>{$p300}</b> · ۱۰۰۰=<b>{$p1000}</b> تومان\n\n" .
            "کاربر مبلغ یکتا می‌بیند → واریز می‌کند → فیش می‌فرستد → تو تأیید می‌کنی تا سکه خودکار شارژ شود.",
            ['reply_markup' => Keyboards::adminPayHome()]
        );
    }
}
