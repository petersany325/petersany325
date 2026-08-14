<?php
declare(strict_types=1);

/**
 * Separate Telegram admin bot — modern console for settings / users / support staff.
 * No website panel required.
 */
final class AdminHandlers
{
    public const CODE_VERSION = '2026-08-14-v10-admin';

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

    private function isAdmin(int $tid): bool
    {
        $admins = $this->config['admin_ids'] ?? [];
        if (is_array($admins) && in_array($tid, array_map('intval', $admins), true)) {
            return true;
        }
        $user = $this->db->findUser($tid);
        return $user && !empty($user['is_admin']);
    }

    private function deny(int $chatId): void
    {
        $this->tg->sendMessage($chatId, 'دسترسی ادمین نداری.');
    }

    private function onMessage(array $message): void
    {
        $chatId = (int)($message['chat']['id'] ?? 0);
        $from = $message['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        $text = trim((string)($message['text'] ?? ''));

        if (!$this->isAdmin($tid)) {
            $this->deny($chatId);
            return;
        }

        $user = $this->db->upsertUser($tid, $from['username'] ?? null, $from['first_name'] ?? null);
        $flow = (string)($user['flow'] ?? '');

        if ($text === '/start' || $text === '/admin' || $text === 'خانه') {
            $this->db->updateUser($tid, ['flow' => null]);
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
            if (in_array($key, ['invite_reward', 'message_cost', 'request_cost', 'welcome_coins'], true)) {
                if (!ctype_digit($value)) {
                    $this->tg->sendMessage($chatId, 'فقط عدد بفرست.');
                    return;
                }
            }
            if ($key === 'support_bot_username') {
                $value = ltrim($value, '@');
            }
            $this->settings->set($key, $value);
            $this->db->updateUser($tid, ['flow' => null]);
            $this->tg->sendMessage($chatId, "✅ ذخیره شد:\n<code>{$key}</code> = <b>" .
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>');
            $this->home($chatId);
            return;
        }

        if ($flow === 'adm:user:find') {
            $this->db->updateUser($tid, ['flow' => null]);
            $target = null;
            if (ctype_digit($text)) {
                $target = $this->db->findUser((int)$text);
            } else {
                $code = strtoupper(ltrim($text, '@'));
                $target = $this->db->findByPublicCode($code);
                if (!$target) {
                    $target = $this->db->findByReferralCode($code);
                }
            }
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

        if (!$this->isAdmin($tid)) {
            $this->tg->answerCallback($id, 'دسترسی ندارید', true);
            return;
        }

        $this->tg->answerCallback($id);

        if ($data === 'adm:home') {
            $this->home($chatId);
            return;
        }
        if ($data === 'adm:stats') {
            $s = $this->db->countUsers();
            $this->tg->sendMessage(
                $chatId,
                "📊 <b>آمار هم‌گپ</b>\n" .
                "کل کاربران: <b>{$s['total']}</b>\n" .
                "پروفایل کامل: <b>{$s['complete']}</b>\n" .
                "در حال چت: <b>{$s['chatting']}</b>\n" .
                "مسدود: <b>{$s['banned']}</b>",
                ['reply_markup' => Keyboards::adminMain()]
            );
            return;
        }
        if ($data === 'adm:coins') {
            $all = $this->settings->all();
            $this->tg->sendMessage(
                $chatId,
                "🪙 <b>تنظیمات سکه</b>\n" .
                "پاداش دعوت: <b>{$all['invite_reward']}</b>\n" .
                "هزینه پیام: <b>{$all['message_cost']}</b>\n" .
                "هزینه درخواست: <b>{$all['request_cost']}</b>\n" .
                "سکه خوش‌آمد: <b>{$all['welcome_coins']}</b>\n\n" .
                "برای ویرایش یکی را انتخاب کن:",
                ['reply_markup' => Keyboards::adminCoins()]
            );
            return;
        }
        if ($data === 'adm:users') {
            $this->tg->sendMessage($chatId, "👥 <b>مدیریت کاربران</b>\nجستجو با آیدی عددی یا کد عمومی (مثل HGAB12CD).", [
                'reply_markup' => Keyboards::adminUsers(),
            ]);
            return;
        }
        if ($data === 'adm:support') {
            $u = $this->settings->get('support_bot_username');
            $this->tg->sendMessage(
                $chatId,
                "🆘 <b>پشتیبانی</b>\n" .
                'بات پشتیبانی: <b>' . ($u !== '' ? '@' . htmlspecialchars($u, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'ثبت نشده') . "</b>\n" .
                'ساعات: <b>' . htmlspecialchars($this->settings->get('support_hours'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>',
                ['reply_markup' => Keyboards::adminSupport()]
            );
            return;
        }
        if ($data === 'adm:general') {
            $this->tg->sendMessage($chatId, '⚙️ تنظیمات عمومی برند و متن‌ها:', [
                'reply_markup' => Keyboards::adminGeneral(),
            ]);
            return;
        }
        if ($data === 'adm:user:find') {
            $this->db->updateUser($tid, ['flow' => 'adm:user:find']);
            $this->tg->sendMessage($chatId, 'آیدی عددی یا کد عمومی کاربر را بفرست.');
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
                $lines[] = '• <code>' . (int)$r['telegram_id'] . '</code> — ' . $active;
            }
            $this->tg->sendMessage($chatId, implode("\n", $lines), [
                'reply_markup' => Keyboards::adminSupport(),
            ]);
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
        if (str_starts_with($data, 'adm:ban:')) {
            $target = (int)substr($data, strlen('adm:ban:'));
            $this->db->updateUser($target, ['status' => 'banned', 'partner_id' => null, 'search_pref' => null]);
            $this->tg->sendMessage($chatId, "کاربر {$target} مسدود شد.");
            return;
        }
        if (str_starts_with($data, 'adm:unban:')) {
            $target = (int)substr($data, strlen('adm:unban:'));
            $this->db->updateUser($target, ['status' => 'idle']);
            $this->tg->sendMessage($chatId, "مسدودیت {$target} برداشته شد.");
            return;
        }
        if (str_starts_with($data, 'adm:wipe:')) {
            $target = (int)substr($data, strlen('adm:wipe:'));
            $this->db->wipePublicProfile($target);
            $this->tg->sendMessage($chatId, "پروفایل عمومی {$target} پاک‌سازی شد.");
            return;
        }
        if (str_starts_with($data, 'adm:give:')) {
            $parts = explode(':', substr($data, strlen('adm:give:')));
            $target = (int)($parts[0] ?? 0);
            $amount = (int)($parts[1] ?? 0);
            if ($target && $amount) {
                $this->db->addCoins($target, $amount, 'admin_grant', (string)$tid);
                $this->tg->sendMessage($chatId, "+{$amount} سکه به {$target} اضافه شد.");
            }
            return;
        }
    }

    private function home(int $chatId): void
    {
        $brand = htmlspecialchars($this->settings->get('brand_name', 'هم‌گپ'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->tg->sendMessage(
            $chatId,
            "🛠 <b>کنسول ادمین {$brand}</b>\n" .
            "تنظیمات، کاربران و پشتیبانی — همه از همین بات.\n" .
            'نسخه: <code>' . self::CODE_VERSION . '</code>',
            ['reply_markup' => Keyboards::adminMain()]
        );
    }

    private function showUserCard(int $chatId, array $u): void
    {
        $dn = htmlspecialchars((string)($u['display_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pc = htmlspecialchars((string)($u['public_code'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->tg->sendMessage(
            $chatId,
            "👤 <b>{$dn}</b>\n" .
            "کد: <code>{$pc}</code>\n" .
            'تلگرام: <code>' . (int)$u['telegram_id'] . "</code>\n" .
            'وضعیت: <b>' . htmlspecialchars((string)$u['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
            'سکه: <b>' . (int)$u['coins'] . "</b>\n" .
            'استان/شهر: ' . htmlspecialchars((string)($u['province'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            ' / ' . htmlspecialchars((string)($u['city'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ['reply_markup' => Keyboards::adminUserActions((int)$u['telegram_id'])]
        );
    }
}
