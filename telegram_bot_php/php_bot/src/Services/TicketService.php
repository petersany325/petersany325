<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

use HddLand\Bot\Repositories\TicketRepository;

final class TicketService
{
    public static function create(int $chatId, int $userId, string $subject): void
    {
        $tid = TicketRepository::create($userId, $subject);
        send_message($chatId, "🎫 Ticket <b>#{$tid}</b> created!\n\n📝 " . htmlspecialchars($subject));

        if (function_exists('notify_staff')) {
            notify_staff(
                "🆕 New ticket <b>#{$tid}</b> from <code>{$userId}</code>\n\n"
                . htmlspecialchars($subject) . "\n\n"
                . "Reply: <code>/replyticket {$tid} your answer</code>",
                'tickets'
            );
            return;
        }
        foreach (bot_config()['admin_ids'] as $adminId) {
            send_message(
                (int)$adminId,
                "🆕 New ticket <b>#{$tid}</b> from <code>{$userId}</code>\n\n"
                . htmlspecialchars($subject) . "\n\n"
                . "Reply: <code>/replyticket {$tid} your answer</code>"
            );
        }
    }

    public static function showMine(int $chatId, int $userId): void
    {
        $rows = TicketRepository::forUser($userId);
        if (!$rows) {
            send_message($chatId, '🎫 You have no tickets.');
            return;
        }
        $lines = ["🎫 <b>Your Tickets:</b>\n"];
        foreach ($rows as $t) {
            $st = $t['status'] === 'open' ? '🟢 Open' : '🔴 Closed';
            $lines[] = "#{$t['id']} — {$st} — " . htmlspecialchars(substr((string)$t['subject'], 0, 50));
        }
        send_message($chatId, implode("\n", $lines));
    }

    public static function showOpen(int $chatId): void
    {
        $rows = TicketRepository::openTickets();
        if (!$rows) {
            send_message($chatId, '🎫 No open tickets.');
            return;
        }
        $lines = ["🎫 <b>Open Tickets:</b>\n"];
        foreach ($rows as $t) {
            $lines[] = "#{$t['id']} — user {$t['user_id']} — " . htmlspecialchars(substr((string)$t['subject'], 0, 50));
        }
        send_message($chatId, implode("\n", $lines));
    }

    public static function reply(int $adminChatId, int $adminId, int $tid, string $text): void
    {
        $ticket = TicketRepository::find($tid);
        if (!$ticket) {
            send_message($adminChatId, 'Ticket not found.');
            return;
        }
        TicketRepository::addAdminReply($tid, $adminId, $text);
        send_message(
            (int)$ticket['user_id'],
            "💬 <b>Reply to Ticket #{$tid}:</b>\n\n" . htmlspecialchars($text),
            array('inline_keyboard' => array(
                array(array('text' => '💬 Reply', 'callback_data' => 'ticket_reply:' . $tid)),
                array(array('text' => '🎫 View', 'callback_data' => 'ticket:' . $tid)),
            ))
        );
        send_message($adminChatId, "✅ Reply sent to ticket #{$tid}.");
    }

    public static function close(int $adminChatId, int $tid): void
    {
        $ticket = TicketRepository::find($tid);
        if (!$ticket) {
            send_message($adminChatId, 'Ticket not found.');
            return;
        }
        TicketRepository::close($tid);
        send_message((int)$ticket['user_id'], "🔒 Your ticket #{$tid} has been closed.");
        send_message($adminChatId, "✅ Ticket #{$tid} closed.");
    }
}
