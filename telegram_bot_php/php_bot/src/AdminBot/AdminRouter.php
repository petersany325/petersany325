<?php
declare(strict_types=1);

namespace HddLand\Bot\AdminBot;

use HddLand\Bot\Services\LicenseFlowService;

final class AdminRouter
{
    public static function handleUpdate(array $update): void
    {
        AdminAuth::ensureSchema();

        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            self::callback($update['callback_query']);
            return;
        }
        if (isset($update['message']) && is_array($update['message'])) {
            self::message($update['message']);
        }
    }

    private static function message(array $message): void
    {
        $chatId = (int)($message['chat']['id'] ?? 0);
        $userId = (int)($message['from']['id'] ?? 0);
        $text = trim((string)($message['text'] ?? ''));
        if ($chatId <= 0 || $userId <= 0) {
            return;
        }

        if ($text === '/cancel') {
            AdminAuth::clearState($userId);
            admin_send_message($chatId, 'Cancelled.');
            if (AdminAuth::isLoggedIn($userId)) {
                AdminScreens::home($chatId, 0, AdminAuth::username($userId));
            }
            return;
        }

        if ($text === '/start' || $text === '/login') {
            if (AdminAuth::isLoggedIn($userId)) {
                AdminAuth::touch($userId);
                AdminScreens::home($chatId, 0, AdminAuth::username($userId));
                return;
            }
            if (AdminAuth::isLocked($userId)) {
                admin_send_message($chatId, 'Too many failed attempts. Try again in 15 minutes.');
                return;
            }
            AdminAuth::beginLogin($userId);
            admin_send_message($chatId, AdminUi::gateText() . "\n\n👤 Send your <b>username</b>:");
            return;
        }

        if ($text === '/logout') {
            AdminAuth::logout($userId);
            admin_send_message($chatId, 'Signed out. Send /login to continue.');
            return;
        }

        if ($text === '/help') {
            admin_send_message($chatId, AdminUi::helpText());
            return;
        }

        // Login state machine
        $state = AdminAuth::getState($userId);
        if ($state['name'] === 'await_username') {
            AdminAuth::setState($userId, 'await_password', array('username' => $text));
            admin_send_message($chatId, '🔒 Send your <b>password</b>:');
            return;
        }
        if ($state['name'] === 'await_password') {
            $user = (string)($state['payload']['username'] ?? '');
            $res = AdminAuth::attemptLogin($userId, $user, $text);
            if (!$res['ok']) {
                admin_send_message($chatId, '❌ ' . $res['msg'] . "\n\nSend /login to try again.");
                return;
            }
            admin_send_message($chatId, '✅ ' . $res['msg']);
            AdminScreens::home($chatId, 0, AdminAuth::username($userId));
            return;
        }

        if (!AdminAuth::isLoggedIn($userId)) {
            admin_send_message($chatId, "🛡 Access denied.\nPlease /login with your admin username and password.");
            return;
        }
        AdminAuth::touch($userId);

        if ($state['name'] === 'broadcast_wait') {
            self::doBroadcast($chatId, $userId, $text);
            return;
        }
        if ($state['name'] === 'ticket_reply') {
            $tid = (int)($state['payload']['ticket_id'] ?? 0);
            self::doTicketReply($chatId, $userId, $tid, $text);
            return;
        }

        if ($text === '/menu' || $text === 'menu') {
            AdminScreens::home($chatId, 0, AdminAuth::username($userId));
            return;
        }
        if ($text === '/dash' || $text === '/dashboard') {
            AdminScreens::dashboard($chatId, 0);
            return;
        }
        if ($text === '/tickets') {
            AdminScreens::tickets($chatId, 0);
            return;
        }
        if ($text === '/receipts') {
            AdminScreens::receipts($chatId, 0);
            return;
        }
        if ($text === '/broadcast') {
            AdminScreens::broadcastPrompt($chatId, $userId, 0);
            return;
        }
        if ($text === '/health') {
            AdminScreens::health($chatId, 0);
            return;
        }
        if (preg_match('/^\/user\s+(\d+)$/', $text, $m)) {
            self::showUser($chatId, (int)$m[1]);
            return;
        }

        admin_send_message($chatId, 'Unknown command. Use /menu or /help.', AdminUi::mainKeyboard());
    }

    private static function callback(array $cb): void
    {
        $id = (string)($cb['id'] ?? '');
        $data = (string)($cb['data'] ?? '');
        $msg = $cb['message'] ?? array();
        $chatId = (int)($msg['chat']['id'] ?? 0);
        $msgId = (int)($msg['message_id'] ?? 0);
        $userId = (int)($cb['from']['id'] ?? 0);
        if ($chatId <= 0 || $userId <= 0) {
            return;
        }

        if (!AdminAuth::isLoggedIn($userId)) {
            admin_answer_callback($id, 'Please /login first', true);
            admin_send_message($chatId, "🛡 Session required.\nSend /login");
            return;
        }
        AdminAuth::touch($userId);

        if (strpos($data, 'adm:') !== 0) {
            admin_answer_callback($id);
            return;
        }
        admin_answer_callback($id);

        $parts = explode(':', $data);
        $action = $parts[1] ?? '';
        $arg = $parts[2] ?? '';
        $arg2 = $parts[3] ?? '';

        switch ($action) {
            case 'home':
                AdminScreens::home($chatId, $msgId, AdminAuth::username($userId));
                break;
            case 'dash':
                AdminScreens::dashboard($chatId, $msgId);
                break;
            case 'tickets':
                AdminScreens::tickets($chatId, $msgId, $arg !== '' ? (int)$arg : 0);
                break;
            case 'ticket':
                AdminScreens::ticketView($chatId, $msgId, (int)$arg);
                break;
            case 'treply':
                AdminAuth::setState($userId, 'ticket_reply', array('ticket_id' => (int)$arg));
                admin_edit_or_send($chatId, $msgId, "✍️ Reply to ticket #" . (int)$arg . "\n\nSend the reply text now.\nCancel: /cancel", AdminUi::backHome());
                break;
            case 'tclose':
                self::closeTicket($chatId, $msgId, (int)$arg);
                break;
            case 'requests':
                AdminScreens::requests($chatId, $msgId);
                break;
            case 'reqclose':
                self::closeRequest($chatId, $msgId, (int)$arg);
                break;
            case 'tfields':
                AdminScreens::ticketFields($chatId, $msgId);
                break;
            case 'faqs':
                AdminScreens::faqs($chatId, $msgId);
                break;
            case 'menus':
                AdminScreens::menus($chatId, $msgId);
                break;
            case 'menusync':
                $n = function_exists('ensure_professional_menus') ? ensure_professional_menus() : 0;
                admin_edit_or_send($chatId, $msgId, "✅ Menu sync done. Added <b>{$n}</b> missing items. Customs preserved.", AdminUi::backHome());
                break;
            case 'products':
                AdminScreens::products($chatId, $msgId);
                break;
            case 'broadcast':
                AdminScreens::broadcastPrompt($chatId, $userId, $msgId);
                break;
            case 'langs':
                AdminScreens::languages($chatId, $msgId);
                break;
            case 'users':
                AdminScreens::users($chatId, $msgId);
                break;
            case 'uopt':
                AdminScreens::userOptions($chatId, $msgId);
                break;
            case 'receipts':
                AdminScreens::receipts($chatId, $msgId);
                break;
            case 'rapprove':
                self::receiptAction($chatId, $msgId, (int)$arg, true, $userId);
                break;
            case 'rreject':
                self::receiptAction($chatId, $msgId, (int)$arg, false, $userId);
                break;
            case 'admins':
                AdminScreens::admins($chatId, $msgId);
                break;
            case 'settings':
                AdminScreens::settings($chatId, $msgId);
                break;
            case 'maint':
                self::toggleMaintenance($chatId, $msgId, $arg === '1');
                break;
            case 'branding':
                AdminScreens::branding($chatId, $msgId);
                break;
            case 'health':
                AdminScreens::health($chatId, $msgId);
                break;
            case 'heal':
                try {
                    if (function_exists('ensure_schema')) {
                        ensure_schema();
                    }
                    admin_edit_or_send($chatId, $msgId, '✅ Schema heal completed.', AdminUi::backHome());
                } catch (\Throwable $e) {
                    admin_edit_or_send($chatId, $msgId, '❌ Heal failed: ' . htmlspecialchars($e->getMessage()), AdminUi::backHome());
                }
                break;
            case 'weblink':
                AdminScreens::webLink($chatId, $msgId);
                break;
            case 'logout':
                AdminAuth::logout($userId);
                admin_edit_or_send($chatId, $msgId, '🚪 Signed out.\nSend /login to sign in again.');
                break;
            default:
                admin_edit_or_send($chatId, $msgId, 'Unknown module.', AdminUi::mainKeyboard());
        }
    }

    private static function closeTicket(int $chatId, int $msgId, int $ticketId): void
    {
        try {
            db()->prepare("UPDATE tickets SET status='closed' WHERE id=?")->execute(array($ticketId));
            admin_edit_or_send($chatId, $msgId, "✅ Ticket #{$ticketId} closed.", AdminUi::kb(array(
                array(array('text' => '⬅️ Tickets', 'callback_data' => 'adm:tickets')),
            )));
        } catch (\Throwable $e) {
            admin_edit_or_send($chatId, $msgId, 'Failed: ' . htmlspecialchars($e->getMessage()), AdminUi::backHome());
        }
    }

    private static function closeRequest(int $chatId, int $msgId, int $reqId): void
    {
        try {
            db()->prepare("UPDATE service_requests SET status='closed' WHERE id=?")->execute(array($reqId));
            admin_edit_or_send($chatId, $msgId, "✅ Request #{$reqId} closed.", AdminUi::kb(array(
                array(array('text' => '⬅️ Requests', 'callback_data' => 'adm:requests')),
            )));
        } catch (\Throwable $e) {
            admin_edit_or_send($chatId, $msgId, 'Failed: ' . htmlspecialchars($e->getMessage()), AdminUi::backHome());
        }
    }

    private static function doTicketReply(int $chatId, int $adminTg, int $ticketId, string $text): void
    {
        AdminAuth::clearState($adminTg);
        if ($ticketId <= 0 || $text === '') {
            admin_send_message($chatId, 'Invalid reply.');
            return;
        }
        try {
            $st = db()->prepare('SELECT user_id, status FROM tickets WHERE id=? LIMIT 1');
            $st->execute(array($ticketId));
            $t = $st->fetch();
            if (!$t) {
                admin_send_message($chatId, 'Ticket not found.');
                return;
            }
            db()->prepare('INSERT INTO ticket_messages (ticket_id, sender_id, is_admin, text) VALUES (?,?,1,?)')
                ->execute(array($ticketId, $adminTg, $text));
            // Notify user on public bot
            if (function_exists('send_message')) {
                send_message((int)$t['user_id'], "💬 <b>Admin reply on ticket #{$ticketId}</b>\n\n" . htmlspecialchars($text));
            }
            admin_send_message($chatId, "✅ Reply sent on ticket #{$ticketId}.");
            AdminScreens::ticketView($chatId, 0, $ticketId);
        } catch (\Throwable $e) {
            admin_send_message($chatId, 'Failed: ' . htmlspecialchars($e->getMessage()));
        }
    }

    private static function doBroadcast(int $chatId, int $adminTg, string $text): void
    {
        AdminAuth::clearState($adminTg);
        if (trim($text) === '') {
            admin_send_message($chatId, 'Empty message cancelled.');
            return;
        }
        $users = array();
        try {
            $users = db()->query('SELECT telegram_id FROM users ORDER BY id ASC')->fetchAll();
        } catch (\Throwable $e) {
        }
        $ok = 0;
        $fail = 0;
        foreach ($users as $u) {
            $tid = (int)$u['telegram_id'];
            if ($tid <= 0) {
                continue;
            }
            $res = send_message($tid, "📢 <b>Announcement</b>\n\n" . $text);
            if (is_array($res) && !empty($res['ok'])) {
                $ok++;
            } else {
                $fail++;
            }
            usleep(35000);
        }
        admin_send_message($chatId, "📢 Broadcast finished.\n✅ Sent: <b>{$ok}</b>\n❌ Failed: <b>{$fail}</b>", AdminUi::mainKeyboard());
    }

    private static function receiptAction(int $chatId, int $msgId, int $receiptId, bool $approve, int $adminTg): void
    {
        if (!class_exists(LicenseFlowService::class, true)) {
            admin_edit_or_send($chatId, $msgId, 'LicenseFlowService missing on server.', AdminUi::backHome());
            return;
        }
        $res = $approve
            ? LicenseFlowService::approveReceipt($receiptId, $adminTg)
            : LicenseFlowService::rejectReceipt($receiptId, $adminTg);
        $icon = !empty($res['ok']) ? '✅' : '❌';
        admin_edit_or_send($chatId, $msgId, $icon . ' ' . htmlspecialchars((string)($res['msg'] ?? '')), AdminUi::kb(array(
            array(array('text' => '⬅️ Receipts', 'callback_data' => 'adm:receipts')),
        )));
    }

    private static function toggleMaintenance(int $chatId, int $msgId, bool $on): void
    {
        try {
            $cfg = bot_config();
            $cfg['maintenance_mode'] = $on ? 1 : 0;
            if (function_exists('save_bot_config')) {
                save_bot_config($cfg);
            }
            admin_edit_or_send(
                $chatId,
                $msgId,
                '⚙️ Maintenance is now <b>' . ($on ? 'ON' : 'OFF') . '</b>.',
                AdminUi::kb(array(array(array('text' => '⬅️ Settings', 'callback_data' => 'adm:settings'))))
            );
        } catch (\Throwable $e) {
            admin_edit_or_send($chatId, $msgId, 'Failed: ' . htmlspecialchars($e->getMessage()), AdminUi::backHome());
        }
    }

    private static function showUser(int $chatId, int $tgId): void
    {
        $st = db()->prepare('SELECT * FROM users WHERE telegram_id=? LIMIT 1');
        $st->execute(array($tgId));
        $u = $st->fetch();
        if (!$u) {
            admin_send_message($chatId, 'User not found.');
            return;
        }
        $text = "👤 <b>User</b>\n"
            . 'ID: <code>' . (int)$u['telegram_id'] . "</code>\n"
            . 'Username: @' . htmlspecialchars((string)($u['username'] ?? '')) . "\n"
            . 'Name: ' . htmlspecialchars(trim((string)($u['full_name'] ?? ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')))) . "\n"
            . 'Email: ' . htmlspecialchars((string)($u['email'] ?? '')) . "\n"
            . 'Lang: ' . htmlspecialchars((string)($u['lang'] ?? '')) . "\n"
            . 'Created: ' . htmlspecialchars((string)($u['created_at'] ?? ''));
        admin_send_message($chatId, $text, AdminUi::mainKeyboard());
    }
}
