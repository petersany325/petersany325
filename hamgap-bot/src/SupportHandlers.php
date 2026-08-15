<?php
declare(strict_types=1);

/**
 * Support bot — customers message here; staff listed in support_staff get relays.
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
        if (!isset($update['message'])) {
            return;
        }
        $message = $update['message'];
        $chatId = (int)($message['chat']['id'] ?? 0);
        $from = $message['from'] ?? [];
        $tid = (int)($from['id'] ?? 0);
        $text = trim((string)($message['text'] ?? ''));

        $staffRow = $this->db->getSupportStaff($tid);
        $isStaffActive = $staffRow !== null && (int)($staffRow['is_active'] ?? 0) === 1;
        $isStaffInactive = $staffRow !== null && !$isStaffActive;

        if ($isStaffInactive) {
            $this->tg->sendMessage(
                $chatId,
                "⛔ حساب کارمندی پشتیبانی تو <b>غیرفعال</b> است.\n" .
                "از ادمین بخواه از پنل ادمین → «پشتیبانی و کارمندان» → «لیست / فعال‌سازی کارمندان» وضعیتت را فعال کند.\n" .
                "یا از «مدیریت کاربران» کارت تو را باز کند و دکمه فعال‌سازی کارمند را بزند."
            );
            return;
        }

        $staffIds = array_map(
            static fn($r) => (int)$r['telegram_id'],
            $this->db->listSupportStaff(true)
        );

        if ($isStaffActive && str_starts_with($text, '/reply ')) {
            if (!preg_match('/^\/reply\s+(\d+)\s+(.+)$/us', $text, $m)) {
                $this->tg->sendMessage($chatId, "فرمت:\n<code>/reply 123456789 متن پاسخ</code>");
                return;
            }
            $to = (int)$m[1];
            $body = $m[2];
            $this->tg->sendMessage(
                $to,
                "💬 <b>پاسخ پشتیبانی هم‌گپ</b>\n" . htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
            $this->tg->sendMessage($chatId, 'ارسال شد ✅');
            return;
        }

        if ($text === '/start') {
            if ($isStaffActive) {
                $hours = $this->settings->get('support_hours');
                $this->tg->sendMessage(
                    $chatId,
                    "✅ وضعیت کارمندی: <b>فعال</b>\n" .
                    "ساعات پاسخگویی: <b>" . htmlspecialchars($hours, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n\n" .
                    "تیکت‌های کاربران اینجا می‌آید.\n" .
                    "پاسخ:\n<code>/reply TELEGRAM_ID متن پاسخ</code>"
                );
                return;
            }
            $welcome = $this->settings->get('support_welcome');
            $hours = $this->settings->get('support_hours');
            $this->tg->sendMessage(
                $chatId,
                $welcome . "\n\nساعات پاسخگویی: <b>" .
                htmlspecialchars($hours, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>'
            );
            return;
        }

        if ($isStaffActive) {
            $this->tg->sendMessage(
                $chatId,
                "برای پاسخ به کاربر بنویس:\n<code>/reply TELEGRAM_ID متن</code>\n" .
                "وضعیتت: فعال ✅"
            );
            return;
        }

        if ($text === '') {
            $this->tg->sendMessage($chatId, 'فعلاً فقط متن پشتیبانی می‌شود.');
            return;
        }

        $pdo = $this->db->pdo();
        $st = $pdo->prepare(
            "SELECT id FROM support_tickets WHERE user_telegram_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1"
        );
        $st->execute([$tid]);
        $ticketId = $st->fetchColumn();
        if ($ticketId) {
            $pdo->prepare('UPDATE support_tickets SET last_message = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$text, (int)$ticketId]);
        } else {
            $pdo->prepare(
                "INSERT INTO support_tickets (user_telegram_id, status, last_message) VALUES (?, 'open', ?)"
            )->execute([$tid, $text]);
            $ticketId = (int)$pdo->lastInsertId();
        }

        $name = htmlspecialchars((string)($from['first_name'] ?? 'کاربر'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $payload = "🆘 تیکت #{$ticketId}\nاز: {$name} (<code>{$tid}</code>)\n\n" .
            htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            "\n\nپاسخ با:\n<code>/reply {$tid} متن</code>";

        $sent = 0;
        foreach ($staffIds as $sid) {
            try {
                $this->tg->sendMessage($sid, $payload);
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

        $this->tg->sendMessage(
            $chatId,
            $sent > 0
                ? 'پیامت ثبت شد. به‌زودی پاسخ می‌دهیم ✅'
                : "پیامت ثبت شد.\nفعلاً کارمند فعالی در دسترس نیست یا هنوز بات پشتیبانی را /start نزده؛ به‌زودی بررسی می‌شود."
        );
    }
}
