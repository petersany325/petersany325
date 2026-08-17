<?php
declare(strict_types=1);

/**
 * Support-staff moderation panel (password-gated) on the support bot.
 * Scope: search, ban/unban, rename, DM/chat, open tickets, add coins, pending receipts, change own password.
 * Receipt approve/deny is also handled by SupportHandlers (payadm).
 * Not included: economy price settings, hard-delete, staff admin CRUD.
 */
final class StaffHandlers
{
    public const CODE_VERSION = '2026-08-17-v10.35-staff';

    public function __construct(
        private array $config,
        private Database $db,
        private Telegram $tg,
        private Settings $settings
    ) {
    }

    public function isStaffMember(int $tid): bool
    {
        return $this->db->isActiveSupportStaff($tid);
    }

    public function isLoggedIn(int $tid): bool
    {
        return $this->isStaffMember($tid) && $this->db->hasValidStaffSession($tid);
    }

    /** @return bool true if this update was fully handled */
    public function handle(array $update): bool
    {
        if (isset($update['callback_query'])) {
            return $this->onCallback($update['callback_query']);
        }
        if (isset($update['message'])) {
            return $this->onMessage($update['message']);
        }
        return false;
    }

    private function onCallback(array $cq): bool
    {
        $id = (string)($cq['id'] ?? '');
        $data = (string)($cq['data'] ?? '');
        $from = $cq['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        $chatId = (int)($cq['message']['chat']['id'] ?? $tid);

        if (!str_starts_with($data, 'stf:')) {
            return false;
        }
        if (!$this->isStaffMember($tid)) {
            $this->tg->answerCallback($id, 'کارمند فعال نیستی', true);
            return true;
        }

        // Login / logout / home — honor existing session (no re-auth for ~2h)
        if ($data === 'stf:login' || $data === 'stf:home') {
            $this->tg->answerCallback($id);
            $this->ensureUser($tid, $from);
            if ($this->isLoggedIn($tid)) {
                $this->touchSession($tid);
                $this->home($chatId);
                return true;
            }
            $this->db->updateUser($tid, ['flow' => 'stf:login:pass']);
            $hours = max(2, $this->settings->getInt('staff_session_hours', 2));
            $this->tg->trySendMessage(
                $chatId,
                "🔐 برای باز شدن پنل، رمز عبور کارمند را بفرست:\n" .
                "بعد از ورود تا حدود <b>{$hours} ساعت</b> دوباره رمز نمی‌خواهد.\n" .
                "(اگر ادمین رمز را عوض کرده، همان رمز جدید را بزن)"
            );
            return true;
        }
        if ($data === 'stf:logout') {
            $this->tg->answerCallback($id);
            $this->db->destroyStaffSession($tid);
            $this->db->updateUser($tid, ['flow' => null]);
            $this->tg->trySendMessage($chatId, 'از پنل کارمند خارج شدی.');
            return true;
        }

        if (!$this->isLoggedIn($tid)) {
            $this->tg->answerCallback($id, 'اول وارد پنل شو', true);
            $this->db->updateUser($tid, ['flow' => 'stf:login:pass']);
            $this->tg->trySendMessage($chatId, "🔐 اول رمز پنل را بفرست:");
            return true;
        }

        $this->tg->answerCallback($id);
        $this->ensureUser($tid, $from);
        $this->touchSession($tid);

        if ($data === 'stf:home') {
            $this->home($chatId);
            return true;
        }
        if ($data === 'stf:find') {
            $this->askFind($chatId, $tid);
            return true;
        }
        if ($data === 'stf:banned') {
            $this->showBanned($chatId);
            return true;
        }
        if ($data === 'stf:recent') {
            $this->showRecent($chatId);
            return true;
        }
        if ($data === 'stf:tickets') {
            $this->showTickets($chatId);
            return true;
        }
        if ($data === 'stf:pwd') {
            $this->askPasswordChange($chatId, $tid);
            return true;
        }
        if ($data === 'stf:chat') {
            $this->askChatTarget($chatId, $tid);
            return true;
        }
        if ($data === 'stf:pay:pending') {
            $this->showPendingPayments($chatId);
            return true;
        }
        if (str_starts_with($data, 'stf:card:')) {
            $this->showCard($chatId, (int)substr($data, strlen('stf:card:')));
            return true;
        }
        if (str_starts_with($data, 'stf:ban:')) {
            $this->banUser($chatId, $tid, (int)substr($data, strlen('stf:ban:')));
            return true;
        }
        if (str_starts_with($data, 'stf:unban:')) {
            $this->unbanUser($chatId, $tid, (int)substr($data, strlen('stf:unban:')));
            return true;
        }
        if (str_starts_with($data, 'stf:rename:')) {
            $this->askRename($chatId, $tid, (int)substr($data, strlen('stf:rename:')));
            return true;
        }
        if (str_starts_with($data, 'stf:msg:')) {
            $this->askMessage($chatId, $tid, (int)substr($data, strlen('stf:msg:')));
            return true;
        }
        if (str_starts_with($data, 'stf:wipe:')) {
            $this->wipeProfile($chatId, (int)substr($data, strlen('stf:wipe:')));
            return true;
        }
        if (str_starts_with($data, 'stf:give:')) {
            $parts = explode(':', substr($data, strlen('stf:give:')));
            $target = (int)($parts[0] ?? 0);
            $amount = (int)($parts[1] ?? 0);
            $this->giveCoins($chatId, $tid, $target, $amount);
            return true;
        }
        if (str_starts_with($data, 'stf:addcoins:')) {
            $target = (int)substr($data, strlen('stf:addcoins:'));
            $this->db->updateUser($tid, ['flow' => 'stf:addcoins:' . $target]);
            $this->tg->trySendMessage(
                $chatId,
                "➕ چند سکه به کاربر <code>{$target}</code> اضافه شود؟\nفقط عدد مثبت بفرست (مثلاً ۱۷۰)."
            );
            return true;
        }
        return true;
    }

    private function onMessage(array $message): bool
    {
        $chatId = (int)($message['chat']['id'] ?? 0);
        $from = $message['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        $text = trim((string)($message['text'] ?? ''));
        if ($tid <= 0) {
            return false;
        }

        $panelCmds = ['/panel', '/staff', '/login', 'پنل کارمند', 'ورود پنل'];
        if (!$this->isStaffMember($tid)) {
            if (in_array($text, $panelCmds, true)) {
                $this->tg->trySendMessage(
                    $chatId,
                    "⛔ تو در لیست <b>کارمندان فعال</b> نیستی.\n" .
                    "ادمین باید از پنل ادمین → پشتیبانی و کارمندان، آیدی‌ات را اضافه/فعال کند."
                );
                return true;
            }
            return false;
        }

        $this->ensureUser($tid, $from);
        $user = $this->db->findUser($tid) ?? [];
        $flow = (string)($user['flow'] ?? '');

        // Commands always available to staff
        if ($text === '/panel' || $text === '/staff' || $text === 'پنل کارمند') {
            if ($this->isLoggedIn($tid)) {
                $this->touchSession($tid);
                $left = $this->db->staffSessionMinutesLeft($tid);
                $this->tg->trySendMessage(
                    $chatId,
                    "✅ هنوز وارد هستی — حدود <b>{$left}</b> دقیقه از نشست مانده.\nرمز دوباره لازم نیست."
                );
                $this->home($chatId);
            } else {
                $this->db->updateUser($tid, ['flow' => 'stf:login:pass']);
                $hours = max(2, $this->settings->getInt('staff_session_hours', 2));
                $this->tg->trySendMessage(
                    $chatId,
                    "🔐 برای باز شدن پنل، رمز کارمند را بفرست:\n" .
                    "بعد از ورود تا حدود <b>{$hours} ساعت</b> دوباره رمز نمی‌خواهد."
                );
            }
            return true;
        }
        if ($text === '/logout' || $text === 'خروج پنل') {
            $this->db->destroyStaffSession($tid);
            $this->db->updateUser($tid, ['flow' => null]);
            $this->tg->trySendMessage($chatId, 'از پنل کارمند خارج شدی.');
            return true;
        }
        if ($text === '/login' || $text === 'ورود پنل') {
            if ($this->isLoggedIn($tid)) {
                $this->touchSession($tid);
                $left = $this->db->staffSessionMinutesLeft($tid);
                $this->tg->trySendMessage($chatId, "✅ قبلاً وارد شدی (~{$left} دقیقه مانده). پنل باز شد.");
                $this->home($chatId);
                return true;
            }
            $this->db->updateUser($tid, ['flow' => 'stf:login:pass']);
            $default = $this->settings->get('staff_default_password', 'HamGapStaff1');
            $this->db->ensureSupportStaffPassword($tid, $default);
            $hours = max(2, $this->settings->getInt('staff_session_hours', 2));
            $this->tg->trySendMessage(
                $chatId,
                "🔐 رمز عبور پنل کارمند را بفرست:\n" .
                "بعد از ورود تا حدود <b>{$hours} ساعت</b> رمز دوباره لازم نیست."
            );
            return true;
        }

        // Login flow
        if ($flow === 'stf:login:pass' && $text !== '') {
            $this->db->updateUser($tid, ['flow' => null]);
            $default = $this->settings->get('staff_default_password', 'HamGapStaff1');
            $this->db->ensureSupportStaffPassword($tid, $default);
            if (!$this->db->verifySupportStaffPassword($tid, $text, $default)) {
                $this->tg->trySendMessage(
                    $chatId,
                    "رمز نادرست است.\n" .
                    "دوباره /login بزن.\n" .
                    "ادمین می‌تواند از لیست کارمندان → 🔑 رمز را ریست کند."
                );
                return true;
            }
            $hours = max(2, $this->settings->getInt('staff_session_hours', 2));
            try {
                $this->db->createStaffSession($tid, $hours);
            } catch (Throwable $e) {
                $this->tg->trySendMessage(
                    $chatId,
                    "ورود انجام شد ولی نشست ذخیره نشد.\nخطا: " .
                    htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                );
                return true;
            }
            if (!$this->isLoggedIn($tid)) {
                $this->tg->trySendMessage($chatId, 'نشست ساخته نشد. یک‌بار دیگر /login را بزن.');
                return true;
            }
            $this->tg->trySendMessage(
                $chatId,
                "✅ ورود موفق — پنل باز شد.\nتا حدود <b>{$hours} ساعت</b> رمز دوباره نمی‌خواهد (با کار کردن تمدید می‌شود)."
            );
            $this->home($chatId);
            return true;
        }

        if (!$this->isLoggedIn($tid)) {
            if (str_starts_with($flow, 'stf:')) {
                $this->db->updateUser($tid, ['flow' => 'stf:login:pass']);
                $this->tg->trySendMessage($chatId, '🔐 نشست منقضی شده. رمز پنل را بفرست:');
                return true;
            }
            return false;
        }

        $this->touchSession($tid);

        // Password change (pending hash — no long flow value)
        if ($flow === 'stf:pwd:new' && $text !== '') {
            if (mb_strlen($text) < 6) {
                $this->tg->trySendMessage($chatId, 'رمز حداقل ۶ کاراکتر باشد.');
                return true;
            }
            $this->db->setSupportStaffPendingPassword($tid, $text);
            $this->db->updateUser($tid, ['flow' => 'stf:pwd:confirm']);
            $this->tg->trySendMessage($chatId, 'رمز جدید را دوباره بفرست:');
            return true;
        }
        if ($flow === 'stf:pwd:confirm' && $text !== '') {
            $this->db->updateUser($tid, ['flow' => null]);
            if (!$this->db->confirmSupportStaffPendingPassword($tid, $text)) {
                $this->tg->trySendMessage($chatId, 'تکرار رمز مطابقت نداشت. از منو دوباره تلاش کن.');
                $this->home($chatId);
                return true;
            }
            $this->tg->trySendMessage($chatId, 'رمز پنل کارمند ذخیره شد ✅ — از الان با رمز جدید وارد شو.');
            $this->home($chatId);
            return true;
        }

        // Find user
        if ($flow === 'stf:find' && $text !== '') {
            $this->db->updateUser($tid, ['flow' => null]);
            $target = $this->resolveUserQuery($text);
            if (!$target) {
                $this->tg->trySendMessage($chatId, 'کاربر پیدا نشد.', [
                    'reply_markup' => Keyboards::staffMain(),
                ]);
                return true;
            }
            $this->showCard($chatId, (int)$target['telegram_id']);
            return true;
        }

        // Rename
        if (str_starts_with($flow, 'stf:rename:') && $text !== '') {
            $targetId = (int)substr($flow, strlen('stf:rename:'));
            $this->db->updateUser($tid, ['flow' => null]);
            $len = mb_strlen($text);
            if ($len < 2 || $len > 32 || preg_match('/[@\/\\\\]|https?:/iu', $text)) {
                $this->tg->trySendMessage($chatId, 'نام نامعتبر است (۲ تا ۳۲ کاراکتر، بدون لینک/@).');
                $this->showCard($chatId, $targetId);
                return true;
            }
            $this->db->updateUser($targetId, ['display_name' => $text]);
            $this->tg->trySendMessage($chatId, 'نام کاربر ذخیره شد ✅');
            try {
                $main = $this->mainTg();
                $main->trySendMessage($targetId, 'نام نمایشی‌ات توسط پشتیبانی به <b>' .
                    htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b> تغییر کرد.');
            } catch (Throwable $e) {
            }
            $this->showCard($chatId, $targetId);
            return true;
        }

        // Message / chat to user
        if (str_starts_with($flow, 'stf:msg:') && $text !== '') {
            $targetId = (int)substr($flow, strlen('stf:msg:'));
            $this->db->updateUser($tid, ['flow' => null]);
            $ok = $this->deliverToUser(
                $targetId,
                "💬 <b>پیام پشتیبانی هم‌گپ</b>\n" .
                htmlspecialchars(mb_substr($text, 0, 1500), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
            $this->tg->trySendMessage($chatId, $ok ? 'پیام ارسال شد ✅' : 'ارسال ناموفق بود.');
            $this->showCard($chatId, $targetId);
            return true;
        }

        // Add custom coin amount
        if (str_starts_with($flow, 'stf:addcoins:') && $text !== '') {
            $targetId = (int)substr($flow, strlen('stf:addcoins:'));
            $this->db->updateUser($tid, ['flow' => null]);
            if (!ctype_digit($text) || (int)$text < 1 || (int)$text > 100000) {
                $this->tg->trySendMessage($chatId, 'عدد معتبر بین ۱ تا ۱۰۰۰۰۰ بفرست.');
                $this->showCard($chatId, $targetId);
                return true;
            }
            $this->giveCoins($chatId, $tid, $targetId, (int)$text);
            return true;
        }

        if ($flow === 'stf:chat:target' && $text !== '') {
            $target = $this->resolveUserQuery($text);
            $this->db->updateUser($tid, ['flow' => null]);
            if (!$target) {
                $this->tg->trySendMessage($chatId, 'کاربر پیدا نشد.');
                $this->home($chatId);
                return true;
            }
            $this->db->updateUser($tid, ['flow' => 'stf:msg:' . (int)$target['telegram_id']]);
            $dn = htmlspecialchars((string)($target['display_name'] ?? $target['telegram_id']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->tg->trySendMessage($chatId, "متن پیام برای <b>{$dn}</b> را بفرست:");
            return true;
        }

        // Logged-in staff free text that's not a panel flow → let ticket desk handle
        if (str_starts_with($flow, 'stf:')) {
            $this->db->updateUser($tid, ['flow' => null]);
            $this->home($chatId);
            return true;
        }

        return false;
    }

    public function promptLogin(int $chatId): void
    {
        $this->tg->trySendMessage(
            $chatId,
            "🔐 <b>پنل کارمند پشتیبانی</b>\n" .
            "برای جستجو، بلاک، رفع بلاک، تغییر نام و پیام به کاربر وارد شو.\n" .
            "دستور: /login یا دکمه زیر.",
            ['reply_markup' => Keyboards::staffLogin()]
        );
    }

    public function home(int $chatId): void
    {
        $hours = max(2, $this->settings->getInt('staff_session_hours', 2));
        $left = $this->db->staffSessionMinutesLeft($chatId);
        $sess = $left > 0
            ? "⏱ نشست فعال: حدود <b>{$left}</b> دقیقه مانده (با کار در پنل تمدید می‌شود)\n"
            : "⏱ بعد از ورود تا حدود <b>{$hours} ساعت</b> رمز دوباره لازم نیست\n";
        $this->tg->trySendMessage(
            $chatId,
            "🛠 <b>پنل کارمند هم‌گپ</b>\n" .
            $sess .
            "نسخه: <code>" . self::CODE_VERSION . "</code>\n\n" .
            "• جستجو و کارت کاربر\n" .
            "• افزودن سکه / فیش‌های در انتظار\n" .
            "• مسدود / رفع مسدود\n" .
            "• تغییر نام · پیام · تیکت‌ها",
            ['reply_markup' => Keyboards::staffMain()]
        );
    }

    private function touchSession(int $tid): void
    {
        $hours = max(2, $this->settings->getInt('staff_session_hours', 2));
        $this->db->touchStaffSession($tid, $hours);
    }

    private function askFind(int $chatId, int $tid): void
    {
        $this->db->updateUser($tid, ['flow' => 'stf:find']);
        $this->tg->trySendMessage(
            $chatId,
            "🔎 آیدی عددی، @یوزرنیم، کد عمومی (HG…) یا کد دعوت را بفرست."
        );
    }

    private function askChatTarget(int $chatId, int $tid): void
    {
        $this->db->updateUser($tid, ['flow' => 'stf:chat:target']);
        $this->tg->trySendMessage($chatId, 'کاربر هدف را بفرست (آیدی / @یوزرنیم / کد):');
    }

    private function askPasswordChange(int $chatId, int $tid): void
    {
        $this->db->updateUser($tid, ['flow' => 'stf:pwd:new']);
        $this->tg->trySendMessage($chatId, 'رمز جدید پنل کارمند را بفرست (حداقل ۶ کاراکتر):');
    }

    private function askRename(int $chatId, int $tid, int $targetId): void
    {
        $this->db->updateUser($tid, ['flow' => 'stf:rename:' . $targetId]);
        $this->tg->trySendMessage($chatId, "نام نمایشی جدید برای <code>{$targetId}</code> را بفرست:");
    }

    private function askMessage(int $chatId, int $tid, int $targetId): void
    {
        $this->db->updateUser($tid, ['flow' => 'stf:msg:' . $targetId]);
        $this->tg->trySendMessage($chatId, "متن پیام برای کاربر <code>{$targetId}</code> را بفرست:");
    }

    private function showCard(int $chatId, int $targetId): void
    {
        $u = $this->db->findUser($targetId);
        if (!$u) {
            $this->tg->trySendMessage($chatId, 'کاربر پیدا نشد.', [
                'reply_markup' => Keyboards::staffMain(),
            ]);
            return;
        }
        $dn = htmlspecialchars((string)($u['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pc = htmlspecialchars((string)($u['public_code'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $status = htmlspecialchars((string)($u['status'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $g = Gender::label((string)($u['gender'] ?? ''));
        $ban = trim((string)($u['ban_reason'] ?? ''));
        $banLine = ($u['status'] ?? '') === 'banned'
            ? ('دلیل مسدود: <b>' . htmlspecialchars($ban !== '' ? $ban : '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n")
            : '';
        $this->tg->trySendMessage(
            $chatId,
            "👤 <b>{$dn}</b>\n" .
            "کد: <code>{$pc}</code>\n" .
            "تلگرام: <code>{$targetId}</code>\n" .
            "وضعیت: <b>{$status}</b>\n" .
            $banLine .
            "سکه: <b>" . (int)($u['coins'] ?? 0) . "</b>\n" .
            "جنسیت/سن: <b>{$g}</b> / <b>" . ($u['age'] ?? '-') . "</b>\n" .
            'استان/شهر: ' . htmlspecialchars((string)($u['province'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            ' / ' . htmlspecialchars((string)($u['city'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ['reply_markup' => Keyboards::staffUserActions($targetId, ($u['status'] ?? '') === 'banned')]
        );
    }

    private function giveCoins(int $chatId, int $staffTid, int $targetId, int $amount): void
    {
        $amount = max(0, min(100000, $amount));
        if ($targetId <= 0 || $amount < 1) {
            $this->tg->trySendMessage($chatId, 'مقدار نامعتبر است.');
            return;
        }
        $u = $this->db->findUser($targetId);
        if (!$u) {
            $this->tg->trySendMessage($chatId, 'کاربر پیدا نشد.');
            return;
        }
        $this->db->addCoins($targetId, $amount, 'staff_grant', (string)$staffTid);
        $fresh = $this->db->findUser($targetId) ?? $u;
        $this->tg->trySendMessage(
            $chatId,
            "✅ <b>+{$amount}</b> سکه به کاربر <code>{$targetId}</code> اضافه شد.\n" .
            "موجودی الان: <b>" . (int)$fresh['coins'] . '</b>'
        );
        $this->deliverToUser(
            $targetId,
            "✅ پشتیبانی <b>+{$amount} سکه</b> به حسابت اضافه کرد.\nموجودی: <b>" . (int)$fresh['coins'] . '</b>'
        );
        $this->showCard($chatId, $targetId);
    }

    private function showPendingPayments(int $chatId): void
    {
        $rows = $this->db->listPendingPaymentInvoices(20);
        if (!$rows) {
            $this->tg->trySendMessage($chatId, 'فیش در انتظاری نیست.', [
                'reply_markup' => Keyboards::staffMain(),
            ]);
            return;
        }
        $this->tg->trySendMessage($chatId, "📥 <b>فیش‌های در انتظار</b>\nبا دکمه زیر تأیید/رد کن تا سکه شارژ شود.");
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
            $this->tg->trySendMessage($chatId, $text, ['reply_markup' => $markup]);
        }
        $this->tg->trySendMessage($chatId, 'بازگشت به پنل:', [
            'reply_markup' => Keyboards::staffMain(),
        ]);
    }

    private function banUser(int $chatId, int $staffTid, int $targetId): void
    {
        if ($targetId === $staffTid) {
            $this->tg->trySendMessage($chatId, 'نمی‌توانی خودت را مسدود کنی.');
            return;
        }
        $this->db->updateUser($targetId, [
            'status' => 'banned',
            'partner_id' => null,
            'search_pref' => null,
            'ban_reason' => 'staff',
        ]);
        $this->tg->trySendMessage($chatId, "🚫 کاربر <code>{$targetId}</code> مسدود شد.");
        $this->deliverToUser(
            $targetId,
            "⛔️ حسابت توسط پشتیبانی مسدود شد.\nبرای پیگیری با پشتیبانی پیام بده."
        );
        $this->showCard($chatId, $targetId);
    }

    private function unbanUser(int $chatId, int $staffTid, int $targetId): void
    {
        $this->db->updateUser($targetId, ['status' => 'idle', 'ban_reason' => null]);
        $this->tg->trySendMessage($chatId, "✅ مسدودیت <code>{$targetId}</code> برداشته شد.");
        $this->deliverToUser($targetId, '✅ مسدودیت حسابت برداشته شد. دوباره می‌تونی از بات استفاده کنی.');
        $this->showCard($chatId, $targetId);
    }

    private function wipeProfile(int $chatId, int $targetId): void
    {
        $this->db->wipePublicProfile($targetId);
        $this->tg->trySendMessage($chatId, "پروفایل عمومی <code>{$targetId}</code> ریست شد.");
        $this->showCard($chatId, $targetId);
    }

    private function showBanned(int $chatId): void
    {
        $rows = $this->db->bannedUsers(15);
        if (!$rows) {
            $this->tg->trySendMessage($chatId, 'لیست مسدودها خالی است.', [
                'reply_markup' => Keyboards::staffMain(),
            ]);
            return;
        }
        $this->tg->trySendMessage($chatId, "🚫 <b>مسدودهای اخیر</b>");
        foreach ($rows as $r) {
            $dn = htmlspecialchars((string)($r['display_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $tid = (int)$r['telegram_id'];
            $this->tg->trySendMessage(
                $chatId,
                "• {$dn} — <code>{$tid}</code>",
                ['reply_markup' => Keyboards::staffBannedItem($tid)]
            );
        }
        $this->tg->trySendMessage($chatId, 'بازگشت به پنل:', [
            'reply_markup' => Keyboards::staffMain(),
        ]);
    }

    private function showRecent(int $chatId): void
    {
        $rows = $this->db->recentUsers(10);
        $lines = ["🆕 <b>کاربران اخیر</b>"];
        $kb = [];
        foreach ($rows as $r) {
            $dn = htmlspecialchars((string)($r['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $tid = (int)$r['telegram_id'];
            $st = htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $lines[] = "• {$dn} — <code>{$tid}</code> — {$st}";
            $kb[] = [['text' => "کارت {$dn}", 'callback_data' => 'stf:card:' . $tid]];
        }
        $kb[] = [['text' => 'خانه پنل', 'callback_data' => 'stf:home']];
        $this->tg->trySendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => ['inline_keyboard' => $kb],
        ]);
    }

    private function showTickets(int $chatId): void
    {
        $rows = $this->db->listOpenSupportTickets(12);
        if (!$rows) {
            $this->tg->trySendMessage($chatId, 'تیکت بازی نیست.', [
                'reply_markup' => Keyboards::staffMain(),
            ]);
            return;
        }
        $lines = ["🆘 <b>تیکت‌های باز</b>", 'برای پذیرش از اعلان تیکت دکمه پذیرش را بزن.', ''];
        foreach ($rows as $r) {
            $id = (int)$r['id'];
            $uid = (int)$r['user_telegram_id'];
            $staff = $r['staff_telegram_id'] !== null ? (int)$r['staff_telegram_id'] : 0;
            $msg = htmlspecialchars(mb_substr((string)($r['last_message'] ?? ''), 0, 80), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $who = $staff > 0 ? "کارمند <code>{$staff}</code>" : 'در انتظار پذیرش';
            $lines[] = "#{$id} کاربر <code>{$uid}</code> — {$who}\n📝 {$msg}";
        }
        $this->tg->trySendMessage($chatId, implode("\n\n", $lines), [
            'reply_markup' => Keyboards::staffMain(),
        ]);
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

    private function ensureUser(int $tid, array $from): void
    {
        $this->db->upsertUser(
            $tid,
            $from['username'] ?? null,
            $from['first_name'] ?? null,
            $this->settings->getInt('welcome_coins', 35)
        );
    }

    private function mainTg(): Telegram
    {
        $token = (string)($this->config['bot_token'] ?? '');
        return $token !== '' ? new Telegram($token) : $this->tg;
    }

    private function deliverToUser(int $userTid, string $html): bool
    {
        if ($this->tg->trySendMessage($userTid, $html)) {
            return true;
        }
        return $this->mainTg()->trySendMessage($userTid, $html);
    }
}
