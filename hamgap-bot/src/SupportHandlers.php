<?php
declare(strict_types=1);

/**
 * Support bot desk:
 * 1) User asks → all active staff see the question + «پذیرش پشتیبانی»
 * 2) One staff accepts → private support chat opens
 * 3) Free-text replies both ways until closed
 */
final class SupportHandlers
{
    public function __construct(
        private array $config,
        private Database $db,
        private Telegram $tg,
        private Settings $settings
    ) {
    }

    public function handle(array $update): void
    {
        // Staff moderation panel (password-gated) — handle first when relevant
        require_once __DIR__ . '/StaffHandlers.php';
        require_once __DIR__ . '/Gender.php';
        $staffPanel = new StaffHandlers($this->config, $this->db, $this->tg, $this->settings);
        if ($staffPanel->handle($update)) {
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

    /**
     * Create/update ticket and notify staff (used by support bot + main bot compose).
     * @return array{ticket_id:int, notified:int}
     */
    public function intakeCustomerMessage(int $userTid, string $displayName, string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['ticket_id' => 0, 'notified' => 0];
        }

        $open = $this->db->findOpenSupportTicketForUser($userTid);
        if ($open && !empty($open['staff_telegram_id'])) {
            // Already assigned — relay to that staff
            $ticketId = (int)$open['id'];
            $this->db->touchSupportTicketMessage($ticketId, $text);
            $staffId = (int)$open['staff_telegram_id'];
            $name = htmlspecialchars($displayName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $ok = $this->tg->trySendMessage(
                $staffId,
                "💬 پیام جدید کاربر ({$name}):\n" .
                htmlspecialchars(mb_substr($text, 0, 1500), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                ['reply_markup' => Keyboards::supportChatActions($ticketId)]
            );
            return ['ticket_id' => $ticketId, 'notified' => $ok ? 1 : 0];
        }

        $ticket = $this->db->openSupportTicket($userTid, $text);
        $ticketId = (int)$ticket['id'];
        $notified = $this->broadcastNewTicket($ticketId, $userTid, $displayName, $text);
        return ['ticket_id' => $ticketId, 'notified' => $notified];
    }

    private function onCallback(array $cq): void
    {
        $id = (string)($cq['id'] ?? '');
        $data = (string)($cq['data'] ?? '');
        $from = $cq['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        $chatId = (int)($cq['message']['chat']['id'] ?? $tid);
        $msgId = (int)($cq['message']['message_id'] ?? 0);

        if (!$this->canHandleSupportDesk($tid)) {
            $this->tg->answerCallback($id, 'فقط کارمند/ادمین پشتیبانی', true);
            return;
        }

        // Receipt approve / deny (staff may confirm payment → coins auto-added)
        if (str_starts_with($data, 'payadm:')) {
            $this->handlePayReview($id, $chatId, $tid, $data);
            return;
        }

        if (str_starts_with($data, 'sup:take:')) {
            $ticketId = (int)substr($data, strlen('sup:take:'));
            $this->acceptTicket($id, $chatId, $msgId, $tid, $ticketId);
            return;
        }
        if (str_starts_with($data, 'sup:close:')) {
            $ticketId = (int)substr($data, strlen('sup:close:'));
            $this->closeTicketByStaff($id, $chatId, $tid, $ticketId);
            return;
        }

        $this->tg->answerCallback($id);
    }

    private function onMessage(array $message): void
    {
        $chatId = (int)($message['chat']['id'] ?? 0);
        $from = $message['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        $text = trim((string)($message['text'] ?? ''));
        $name = (string)($from['first_name'] ?? 'کاربر');

        $isStaffActive = $this->db->isActiveSupportStaff($tid);
        $staffRow = $this->db->getSupportStaff($tid);
        $isStaffInactive = $staffRow !== null && !$isStaffActive;

        if ($isStaffInactive) {
            $this->tg->trySendMessage(
                $chatId,
                "⛔ حساب کارمندی پشتیبانی تو <b>غیرفعال</b> است.\n" .
                "از ادمین بخواه در پنل ادمین وضعیتت را فعال کند."
            );
            return;
        }

        if ($text === '/start') {
            if ($isStaffActive) {
                $hours = $this->settings->get('support_hours');
                $assigned = $this->db->findAssignedSupportTicketForStaff($tid);
                $extra = $assigned
                    ? "\n\nالان تیکت #<b>" . (int)$assigned['id'] . "</b> باز است — همین‌جا جواب بنویس."
                    : "\n\nوقتی کاربر سؤال بفرستد، متن سؤال را می‌بینی و با «پذیرش پشتیبانی» چت باز می‌شود.";
                $this->tg->trySendMessage(
                    $chatId,
                    "✅ کارمند پشتیبانی: <b>فعال</b>\n" .
                    "ساعات: <b>" . htmlspecialchars($hours, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>" .
                    $extra . "\n\n" .
                    "🛠 برای جستجو / بلاک / تغییر نام / پیام:\n" .
                    "<b>/panel</b> یا /login",
                    ['reply_markup' => Keyboards::staffLogin()]
                );
                return;
            }
            $welcome = $this->settings->get('support_welcome');
            $hours = $this->settings->get('support_hours');
            $this->tg->trySendMessage(
                $chatId,
                $welcome . "\n\nساعات پاسخگویی: <b>" .
                htmlspecialchars($hours, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n" .
                "سؤالت را همین‌جا بنویس؛ یک کارمند پذیرش می‌کند و جواب می‌دهد."
            );
            return;
        }

        if ($isStaffActive) {
            $this->handleStaffMessage($chatId, $tid, $text);
            return;
        }

        if ($text === '') {
            $this->tg->trySendMessage($chatId, 'فعلاً فقط متن پشتیبانی می‌شود.');
            return;
        }

        // Customer → intake
        $result = $this->intakeCustomerMessage($tid, $name, $text);
        $assigned = $this->db->findOpenSupportTicketForUser($tid);
        if ($assigned && !empty($assigned['staff_telegram_id'])) {
            $this->tg->trySendMessage($chatId, 'پیامت به کارمند پشتیبانی رسید ✅');
            return;
        }
        $this->tg->trySendMessage(
            $chatId,
            $result['notified'] > 0
                ? "پیامت ثبت شد ✅\nکارمندان سؤال را دیدند؛ به‌محض پذیرش، پاسخ می‌گیری."
                : "پیامت ثبت شد.\nهنوز کارمند فعالی بات پشتیبانی را /start نزده یا در دسترس نیست. ادمین هم مطلع شد."
        );
    }

    private function handleStaffMessage(int $chatId, int $staffTid, string $text): void
    {
        if ($text === '/close' || $text === 'پایان پشتیبانی') {
            $ticket = $this->db->findAssignedSupportTicketForStaff($staffTid);
            if (!$ticket) {
                $this->tg->trySendMessage($chatId, 'تیکت بازی نداری.');
                return;
            }
            $this->finishTicket((int)$ticket['id'], $staffTid);
            return;
        }

        $ticket = $this->db->findAssignedSupportTicketForStaff($staffTid);
        if (!$ticket) {
            $this->tg->trySendMessage(
                $chatId,
                "الان تیکت بازی نداری.\n" .
                "وقتی سؤال کاربر آمد، دکمه <b>پذیرش پشتیبانی</b> را بزن تا چت باز شود."
            );
            return;
        }

        if ($text === '') {
            $this->tg->trySendMessage($chatId, 'متن پاسخ را بنویس.');
            return;
        }

        $ticketId = (int)$ticket['id'];
        $userId = (int)$ticket['user_telegram_id'];
        $this->db->touchSupportTicketMessage($ticketId, $text);
        $delivered = $this->deliverToCustomer(
            $userId,
            "💬 <b>پاسخ پشتیبانی هم‌گپ</b>\n" .
            htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
        $this->tg->trySendMessage(
            $chatId,
            $delivered
                ? 'ارسال شد به مشتری ✅'
                : 'ارسال به مشتری ناموفق بود (شاید بات را بلاک کرده).',
            ['reply_markup' => Keyboards::supportChatActions($ticketId)]
        );
    }

    /**
     * Claim a waiting ticket (used by support bot + admin bot emergency).
     * @return array{ok:bool,toast:string,detail:string}
     */
    public function claimTicket(int $staffTid, int $ticketId): array
    {
        if (!$this->canHandleSupportDesk($staffTid)) {
            return [
                'ok' => false,
                'toast' => 'دسترسی نداری',
                'detail' => 'فقط کارمند/ادمین پشتیبانی می‌تواند بپذیرد.',
            ];
        }
        $ticket = $this->db->findSupportTicket($ticketId);
        if (!$ticket || ($ticket['status'] ?? '') !== 'open') {
            return ['ok' => false, 'toast' => 'تیکت نیست', 'detail' => 'تیکت پیدا نشد یا بسته شده.'];
        }
        if (!empty($ticket['staff_telegram_id'])) {
            $owner = (int)$ticket['staff_telegram_id'];
            if ($owner === $staffTid) {
                return [
                    'ok' => true,
                    'toast' => 'قبلاً پذیرفتی',
                    'detail' => "تیکت #{$ticketId} از قبل مال توست. در بات پشتیبانی جواب بده.",
                ];
            }
            return [
                'ok' => false,
                'toast' => 'گرفته شده',
                'detail' => 'کارمند دیگری این تیکت را پذیرفته.',
            ];
        }
        if (!$this->db->assignSupportTicket($ticketId, $staffTid)) {
            return ['ok' => false, 'toast' => 'دیر رسیدی', 'detail' => 'کسی زودتر پذیرفت.'];
        }
        if (!$this->db->isActiveSupportStaff($staffTid)) {
            $this->db->activateSupportStaff($staffTid);
        }

        $userId = (int)$ticket['user_telegram_id'];
        $q = htmlspecialchars((string)($ticket['last_message'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $supUser = trim($this->settings->get('support_bot_username'));
        $supUser = $supUser !== '' ? ltrim($supUser, '@') : 'HamGapXHelpBot';

        foreach ($this->db->listSupportStaff(true) as $row) {
            $sid = (int)$row['telegram_id'];
            if ($sid === $staffTid) {
                continue;
            }
            $this->tg->trySendMessage($sid, "تیکت #{$ticketId} توسط کارمند دیگری پذیرفته شد.");
        }

        $this->deliverToCustomer(
            $userId,
            "✅ کارمند پشتیبانی پیامت را پذیرفت.\nهمین‌جا می‌توانی ادامه بدهی؛ پاسخ را همین بات می‌فرستد."
        );

        // Open chat prompt on support bot for the staff
        $openMsg =
            "✅ تیکت #<b>{$ticketId}</b> را پذیرفتی.\n" .
            "کاربر: <code>{$userId}</code>\n" .
            "سؤال:\n{$q}\n\n" .
            "از این لحظه در <b>بات پشتیبانی</b> (@{$supUser}) هر متنی بفرستی به مشتری می‌رود.\n" .
            "برای پایان: دکمه زیر یا /close";
        $this->tg->trySendMessage($staffTid, $openMsg, [
            'reply_markup' => Keyboards::supportChatActions($ticketId),
        ]);

        return [
            'ok' => true,
            'toast' => 'پشتیبانی باز شد',
            'detail' => $openMsg . "\n\n👉 برو @{$supUser} و جواب را همان‌جا بنویس.",
        ];
    }

    /** @return array{ok:bool,toast:string,detail:string} */
    public function claimClose(int $staffTid, int $ticketId): array
    {
        $ticket = $this->db->findSupportTicket($ticketId);
        if (!$ticket || ($ticket['status'] ?? '') !== 'open') {
            return ['ok' => false, 'toast' => 'بسته است', 'detail' => 'تیکت بسته است.'];
        }
        if ((int)($ticket['staff_telegram_id'] ?? 0) !== $staffTid && !$this->canHandleSupportDesk($staffTid)) {
            return ['ok' => false, 'toast' => 'مال تو نیست', 'detail' => 'این تیکت مال تو نیست.'];
        }
        if ((int)($ticket['staff_telegram_id'] ?? 0) !== $staffTid) {
            return ['ok' => false, 'toast' => 'مال تو نیست', 'detail' => 'این تیکت مال تو نیست.'];
        }
        $this->finishTicket($ticketId, $staffTid);
        return ['ok' => true, 'toast' => 'بسته شد', 'detail' => "تیکت #{$ticketId} بسته شد ✅"];
    }

    private function acceptTicket(string $callbackId, int $chatId, int $msgId, int $staffTid, int $ticketId): void
    {
        $res = $this->claimTicket($staffTid, $ticketId);
        $this->tg->answerCallback($callbackId, $res['toast'], !($res['ok'] ?? false));
        if ($res['ok']) {
            $this->tg->trySendMessage($chatId, $res['detail'], [
                'reply_markup' => Keyboards::supportChatActions($ticketId),
            ]);
            if ($msgId > 0) {
                try {
                    $this->tg->clearInlineKeyboard($chatId, $msgId);
                } catch (Throwable $e) {
                }
            }
        } else {
            $this->tg->trySendMessage($chatId, $res['detail']);
        }
    }

    private function closeTicketByStaff(string $callbackId, int $chatId, int $staffTid, int $ticketId): void
    {
        $res = $this->claimClose($staffTid, $ticketId);
        $this->tg->answerCallback($callbackId, $res['toast'], !($res['ok'] ?? false));
        $this->tg->trySendMessage($chatId, $res['detail']);
    }

    private function finishTicket(int $ticketId, int $staffTid): void
    {
        $ticket = $this->db->findSupportTicket($ticketId);
        $this->db->closeSupportTicket($ticketId);
        $this->tg->trySendMessage($staffTid, "تیکت #{$ticketId} بسته شد ✅");
        if ($ticket) {
            $this->deliverToCustomer(
                (int)$ticket['user_telegram_id'],
                "پشتیبانی این گفتگو را بست.\nاگر باز سؤال داشتی همین‌جا پیام بفرست."
            );
        }
    }

    private function broadcastNewTicket(int $ticketId, int $userTid, string $displayName, string $text): int
    {
        $name = htmlspecialchars($displayName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $body = htmlspecialchars(mb_substr($text, 0, 1500), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $payload =
            "🆘 <b>سؤال جدید پشتیبانی</b>\n" .
            "تیکت #<b>{$ticketId}</b>\n" .
            "از: {$name} (<code>{$userTid}</code>)\n\n" .
            "📝 {$body}\n\n" .
            "اگر می‌خواهی خودت جواب بدهی، «پذیرش پشتیبانی» را بزن تا چت باز شود.";

        $kb = Keyboards::supportTicketOffer($ticketId);
        $sent = 0;

        // Staff MUST get the offer on the SUPPORT bot (where they /start and chat).
        foreach ($this->db->listSupportStaff(true) as $row) {
            $sid = (int)$row['telegram_id'];
            if ($this->tg->trySendMessage($sid, $payload, ['reply_markup' => $kb])) {
                $sent++;
            }
        }

        // Admins get a read-only ping (accept happens on support bot).
        $adminNote =
            "ℹ️ تیکت پشتیبانی #{$ticketId} ثبت شد.\n" .
            "از: {$name} (<code>{$userTid}</code>)\n" .
            "📝 {$body}\n\n" .
            "کارمند باید در <b>بات پشتیبانی</b> دکمه پذیرش را بزند.";
        $adminIds = [];
        foreach ($this->db->listPanelAdminIds() as $aid) {
            $adminIds[$aid] = true;
        }
        foreach (($this->config['admin_ids'] ?? []) as $aid) {
            $adminIds[(int)$aid] = true;
        }
        $adminToken = (string)($this->config['admin_bot_token'] ?? '');
        if ($adminToken === '') {
            $adminToken = $this->settings->get('admin_bot_token', '');
        }
        if ($adminToken !== '' && $adminIds) {
            $adminTg = new Telegram($adminToken);
            foreach (array_keys($adminIds) as $aid) {
                $adminTg->trySendMessage((int)$aid, $adminNote);
            }
        }

        // If no staff received on support bot, try admin bot WITH accept as emergency
        if ($sent === 0 && $adminToken !== '') {
            $adminTg = new Telegram($adminToken);
            foreach ($this->db->listSupportStaff(true) as $row) {
                $sid = (int)$row['telegram_id'];
                if ($adminTg->trySendMessage($sid, $payload . "\n\n⚠️ این پیام اضطراری از بات ادمین است؛ بعد از پذیرش در بات پشتیبانی جواب بده.", ['reply_markup' => $kb])) {
                    $sent++;
                }
            }
            foreach (array_keys($adminIds) as $aid) {
                if ($adminTg->trySendMessage((int)$aid, $payload, ['reply_markup' => $kb])) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    private function handlePayReview(string $callbackId, int $chatId, int $tid, string $data): void
    {
        require_once __DIR__ . '/Keyboards.php';
        $ok = str_starts_with($data, 'payadm:ok:');
        $invId = (int)substr($data, strlen($ok ? 'payadm:ok:' : 'payadm:no:'));
        $mainToken = (string)($this->config['bot_token'] ?? '');
        $mainTg = $mainToken !== '' ? new Telegram($mainToken) : $this->tg;
        if ($ok) {
            $res = $this->db->approvePaymentInvoice($invId, $tid);
            if (!($res['ok'] ?? false)) {
                $msg = match ((string)($res['error'] ?? '')) {
                    'already' => 'قبلاً تأیید شده',
                    'closed' => 'این فاکتور بسته است',
                    default => 'فاکتور پیدا نشد',
                };
                $this->tg->answerCallback($callbackId, $msg, true);
                return;
            }
            $coins = (int)$res['coins'];
            $inv = $res['invoice'];
            $type = (string)($res['type'] ?? 'coins');
            $this->tg->answerCallback($callbackId, 'تأیید پول انجام شد');
            try {
                if ($type === 'vip') {
                    $days = (int)($res['days'] ?? 30);
                    $mainTg->sendMessage(
                        (int)$res['telegram_id'],
                        "✅ پرداخت استار کلاب تأیید شد.\nعضویت <b>{$days} روزه</b> فعال شد ⭐"
                    );
                } else {
                    $mainTg->sendMessage(
                        (int)$res['telegram_id'],
                        "✅ <b>تأیید پول انجام شد</b>\n<b>+{$coins} سکه</b> به حسابت اضافه شد.\nفاکتور: <code>" .
                        htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>'
                    );
                }
            } catch (Throwable $e) {
            }
            $this->tg->sendMessage(
                $chatId,
                $type === 'vip'
                    ? "✅ فاکتور {$inv['invoice_no']} تأیید و استار کلاب فعال شد."
                    : "✅ تأیید پول انجام شد — فاکتور {$inv['invoice_no']} · {$coins} سکه شارژ شد."
            );
            return;
        }
        $res = $this->db->rejectPaymentInvoice($invId, $tid);
        if (!($res['ok'] ?? false)) {
            $this->tg->answerCallback($callbackId, 'رد نشد', true);
            return;
        }
        $inv = $res['invoice'];
        $this->tg->answerCallback($callbackId, 'تأیید پول انجام نشد');
        try {
            $mainTg->sendMessage(
                (int)$inv['telegram_id'],
                "❌ <b>تأیید پول انجام نشد</b>\nفاکتور <code>" .
                htmlspecialchars((string)$inv['invoice_no'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                "</code> رد شد.\nاگر مبلغ اشتباه واریز کردی، دوباره از کیف‌پول فاکتور بگیر."
            );
        } catch (Throwable $e) {
        }
        $this->tg->sendMessage($chatId, "فاکتور {$inv['invoice_no']} رد شد (تأیید پول انجام نشد).");
    }

    private function deliverToCustomer(int $userTid, string $html): bool
    {
        if ($this->tg->trySendMessage($userTid, $html)) {
            return true;
        }
        $mainToken = (string)($this->config['bot_token'] ?? '');
        if ($mainToken === '') {
            return false;
        }
        $mainTg = new Telegram($mainToken);
        return $mainTg->trySendMessage($userTid, $html);
    }

    private function canHandleSupportDesk(int $tid): bool
    {
        if ($this->db->isActiveSupportStaff($tid)) {
            return true;
        }
        $u = $this->db->findUser($tid);
        if ($u && !empty($u['is_admin'])) {
            return true;
        }
        foreach (($this->config['admin_ids'] ?? []) as $aid) {
            if ((int)$aid === $tid) {
                return true;
            }
        }
        return false;
    }
}
