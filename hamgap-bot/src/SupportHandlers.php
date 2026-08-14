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

        $staffIds = array_map(static fn($r) => (int)$r['telegram_id'], $this->db->listSupportStaff(true));
        $isStaff = in_array($tid, $staffIds, true);

        if ($isStaff && str_starts_with($text, '/reply ')) {
            // /reply TELEGRAM_ID message...
            if (!preg_match('/^\/reply\s+(\d+)\s+(.+)$/us', $text, $m)) {
                $this->tg->sendMessage($chatId, "فرمت:\n<code>/reply 123456789 متن پاسخ</code>");
                return;
            }
            $to = (int)$m[1];
            $body = $m[2];
            $this->tg->sendMessage($to, "💬 <b>پاسخ پشتیبانی هم‌گپ</b>\n" . htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            $this->tg->sendMessage($chatId, 'ارسال شد ✅');
            return;
        }

        if ($text === '/start') {
            $welcome = $this->settings->get('support_welcome');
            $hours = $this->settings->get('support_hours');
            $this->tg->sendMessage(
                $chatId,
                $welcome . "\n\nساعات پاسخگویی: <b>" .
                htmlspecialchars($hours, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>'
            );
            return;
        }

        if ($text === '') {
            $this->tg->sendMessage($chatId, 'فعلاً فقط متن پشتیبانی می‌شود.');
            return;
        }

        // Open/update ticket and notify staff
        $pdo = $this->db->pdo();
        $st = $pdo->prepare("SELECT id FROM support_tickets WHERE user_telegram_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
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
                // continue
            }
        }

        if ($sent === 0) {
            // Fallback: notify main bot admins
            foreach (($this->config['admin_ids'] ?? []) as $aid) {
                try {
                    $this->tg->sendMessage((int)$aid, $payload);
                    $sent++;
                } catch (Throwable $e) {
                }
            }
        }

        $this->tg->sendMessage($chatId, $sent > 0
            ? 'پیامت ثبت شد. به‌زودی پاسخ می‌دهیم ✅'
            : 'پیامت ثبت شد. فعلاً کارمند آنلاینی نیست؛ به‌زودی بررسی می‌شود.');
    }
}
