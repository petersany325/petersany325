<?php
declare(strict_types=1);

namespace HddLand\Bot\AdminBot;

use HddLand\Bot\Services\LicenseFlowService;

/**
 * Screen renderers for each Admin panel module (English).
 */
final class AdminScreens
{
    public static function home(int $chatId, int $msgId, string $username): void
    {
        admin_edit_or_send($chatId, $msgId, AdminUi::mainText($username), AdminUi::mainKeyboard());
    }

    public static function dashboard(int $chatId, int $msgId): void
    {
        $pdo = db();
        $stats = array(
            'users' => 0,
            'open_tickets' => 0,
            'closed_tickets' => 0,
            'products' => 0,
            'faqs' => 0,
            'menus' => 0,
            'open_requests' => 0,
            'sales_open' => 0,
            'pending_receipts' => 0,
        );
        try {
            $stats['users'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $stats['open_tickets'] = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='open'")->fetchColumn();
            $stats['closed_tickets'] = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='closed'")->fetchColumn();
            $stats['products'] = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn();
            $stats['faqs'] = (int)$pdo->query('SELECT COUNT(*) FROM faqs WHERE is_active=1')->fetchColumn();
            $stats['menus'] = (int)$pdo->query('SELECT COUNT(*) FROM menus WHERE is_active=1')->fetchColumn();
        } catch (\Throwable $e) {
        }
        try {
            $stats['open_requests'] = (int)$pdo->query("SELECT COUNT(*) FROM service_requests WHERE status='open'")->fetchColumn();
            $stats['sales_open'] = (int)$pdo->query("SELECT COUNT(*) FROM service_requests WHERE status='open' AND req_type='sales'")->fetchColumn();
        } catch (\Throwable $e) {
        }
        try {
            $stats['pending_receipts'] = (int)$pdo->query("SELECT COUNT(*) FROM payment_receipts WHERE status='pending'")->fetchColumn();
        } catch (\Throwable $e) {
        }

        $text = "📊 <b>Dashboard</b>\n\n"
            . "👥 Users: <b>{$stats['users']}</b>\n"
            . "🎫 Open tickets: <b>{$stats['open_tickets']}</b>\n"
            . "✅ Closed tickets: <b>{$stats['closed_tickets']}</b>\n"
            . "🛠 Open requests: <b>{$stats['open_requests']}</b>\n"
            . "💎 Sales open: <b>{$stats['sales_open']}</b>\n"
            . "💵 Pending receipts: <b>{$stats['pending_receipts']}</b>\n"
            . "❓ FAQs: <b>{$stats['faqs']}</b>\n"
            . "📋 Menus: <b>{$stats['menus']}</b>\n"
            . "🛒 Products: <b>{$stats['products']}</b>";

        admin_edit_or_send($chatId, $msgId, $text, AdminUi::kb(array(
            array(
                array('text' => '🎫 Tickets', 'callback_data' => 'adm:tickets'),
                array('text' => '💵 Receipts', 'callback_data' => 'adm:receipts'),
            ),
            array(
                array('text' => '🛠 Requests', 'callback_data' => 'adm:requests'),
                array('text' => '❤️ Health', 'callback_data' => 'adm:health'),
            ),
        )));
    }

    public static function tickets(int $chatId, int $msgId, int $page = 0): void
    {
        $page = max(0, $page);
        $limit = 8;
        $offset = $page * $limit;
        $rows = array();
        try {
            $st = db()->prepare("SELECT id, user_id, subject, status, created_at FROM tickets WHERE status='open' ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}");
            $st->execute();
            $rows = $st->fetchAll();
        } catch (\Throwable $e) {
        }

        $text = "🎫 <b>Open Tickets</b>\nPage " . ($page + 1) . "\n\n";
        $kb = array();
        if (!$rows) {
            $text .= $page === 0 ? 'No open tickets.' : 'No more tickets.';
        } else {
            foreach ($rows as $t) {
                $text .= '#' . (int)$t['id'] . ' · <code>' . (int)$t['user_id'] . "</code>\n"
                    . htmlspecialchars(mb_substr((string)$t['subject'], 0, 70)) . "\n\n";
                $kb[] = array(array(
                    'text' => '#' . (int)$t['id'] . ' — view',
                    'callback_data' => 'adm:ticket:' . (int)$t['id'],
                ));
            }
        }
        $nav = array();
        if ($page > 0) {
            $nav[] = array('text' => '◀️ Prev', 'callback_data' => 'adm:tickets:' . ($page - 1));
        }
        if (count($rows) >= $limit) {
            $nav[] = array('text' => 'Next ▶️', 'callback_data' => 'adm:tickets:' . ($page + 1));
        }
        if ($nav) {
            $kb[] = $nav;
        }
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::kb($kb));
    }

    public static function ticketView(int $chatId, int $msgId, int $ticketId): void
    {
        $st = db()->prepare('SELECT * FROM tickets WHERE id=? LIMIT 1');
        $st->execute(array($ticketId));
        $t = $st->fetch();
        if (!$t) {
            admin_edit_or_send($chatId, $msgId, 'Ticket not found.', AdminUi::backHome());
            return;
        }
        $msgs = array();
        try {
            $m = db()->prepare('SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY id DESC LIMIT 6');
            $m->execute(array($ticketId));
            $msgs = array_reverse($m->fetchAll());
        } catch (\Throwable $e) {
        }

        $text = "🎫 <b>Ticket #{$ticketId}</b>\n"
            . 'Status: <code>' . htmlspecialchars((string)$t['status']) . "</code>\n"
            . 'User: <code>' . (int)$t['user_id'] . "</code>\n"
            . 'Subject: ' . htmlspecialchars((string)$t['subject']) . "\n\n";
        foreach ($msgs as $row) {
            $who = !empty($row['is_admin']) ? 'Admin' : 'User';
            $body = (string)($row['text'] ?? $row['message'] ?? '');
            $text .= '<b>' . $who . ':</b> ' . htmlspecialchars(mb_substr($body, 0, 180)) . "\n";
        }

        $kb = array();
        if ((string)$t['status'] === 'open') {
            $kb[] = array(array('text' => '✍️ Reply', 'callback_data' => 'adm:treply:' . $ticketId));
            $kb[] = array(array('text' => '✅ Close ticket', 'callback_data' => 'adm:tclose:' . $ticketId));
        }
        $kb[] = array(array('text' => '⬅️ Tickets', 'callback_data' => 'adm:tickets'));
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::kb($kb));
    }

    public static function requests(int $chatId, int $msgId): void
    {
        $rows = array();
        try {
            $rows = db()->query("SELECT id, user_id, req_type, subject, status, created_at FROM service_requests WHERE status='open' ORDER BY id DESC LIMIT 12")->fetchAll();
        } catch (\Throwable $e) {
        }
        $text = "🛠 <b>Support & Sales — Open</b>\n\n";
        $kb = array();
        if (!$rows) {
            $text .= 'No open requests.';
        } else {
            foreach ($rows as $r) {
                $text .= '#' . (int)$r['id'] . ' · ' . htmlspecialchars((string)$r['req_type'])
                    . ' · <code>' . (int)$r['user_id'] . "</code>\n"
                    . htmlspecialchars(mb_substr((string)$r['subject'], 0, 70)) . "\n\n";
                $kb[] = array(array(
                    'text' => '#' . (int)$r['id'] . ' close',
                    'callback_data' => 'adm:reqclose:' . (int)$r['id'],
                ));
            }
        }
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::kb($kb));
    }

    public static function ticketFields(int $chatId, int $msgId): void
    {
        $raw = (string)cfg('ticket_fields', '');
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: array())));
        $text = "🧩 <b>Ticket Fields</b>\nConfigured smart fields:\n\n";
        if (!$lines) {
            $text .= 'No fields configured. Edit in Web Admin → Ticket Fields / Settings.';
        } else {
            foreach ($lines as $i => $line) {
                $parts = explode('|', $line);
                $text .= ($i + 1) . '. <code>' . htmlspecialchars($parts[0] ?? '') . '</code> · '
                    . htmlspecialchars($parts[1] ?? 'text') . ' · '
                    . htmlspecialchars($parts[2] ?? '') . "\n";
            }
        }
        $text .= "\nWeb panel: Ticket Fields for full editor.";
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::backHome());
    }

    public static function faqs(int $chatId, int $msgId): void
    {
        $rows = array();
        try {
            $rows = db()->query('SELECT id, question, category, is_active FROM faqs ORDER BY sort_order ASC, id DESC LIMIT 15')->fetchAll();
        } catch (\Throwable $e) {
        }
        $text = "❓ <b>FAQ / Questions</b>\n\n";
        if (!$rows) {
            $text .= 'No FAQs yet.';
        } else {
            foreach ($rows as $f) {
                $on = !empty($f['is_active']) ? 'ON' : 'OFF';
                $text .= '#' . (int)$f['id'] . " [{$on}] "
                    . htmlspecialchars(mb_substr((string)$f['question'], 0, 60)) . "\n";
            }
        }
        $text .= "\nManage full content in Web Admin → FAQ.";
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::backHome());
    }

    public static function menus(int $chatId, int $msgId): void
    {
        $rows = array();
        try {
            $rows = db()->query('SELECT category, COUNT(*) c FROM menus WHERE is_active=1 GROUP BY category ORDER BY category')->fetchAll();
        } catch (\Throwable $e) {
        }
        $total = 0;
        $text = "📋 <b>Menus & Categories</b>\nActive buttons by category:\n\n";
        foreach ($rows as $r) {
            $c = (int)$r['c'];
            $total += $c;
            $text .= '• ' . htmlspecialchars((string)$r['category']) . ": <b>{$c}</b>\n";
        }
        $text .= "\nTotal active: <b>{$total}</b>\n"
            . "Custom labels are preserved on updates.\n"
            . 'Edit structure in Web Admin → Menus.';
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::kb(array(
            array(array('text' => '➕ Sync missing pro menus', 'callback_data' => 'adm:menusync')),
        )));
    }

    public static function products(int $chatId, int $msgId): void
    {
        $rows = array();
        try {
            $rows = db()->query('SELECT id, title, price, is_active FROM products ORDER BY id DESC LIMIT 15')->fetchAll();
        } catch (\Throwable $e) {
        }
        $text = "🛒 <b>Products</b>\n\n";
        if (!$rows) {
            $text .= 'No products yet.';
        } else {
            foreach ($rows as $p) {
                $on = !empty($p['is_active']) ? 'ON' : 'OFF';
                $text .= '#' . (int)$p['id'] . " [{$on}] "
                    . htmlspecialchars((string)$p['title'])
                    . ' — ' . htmlspecialchars((string)$p['price']) . "\n";
            }
        }
        $text .= "\nEdit in Web Admin → Products.";
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::backHome());
    }

    public static function languages(int $chatId, int $msgId): void
    {
        $rows = array();
        try {
            $rows = db()->query('SELECT code, name, is_active, sort_order FROM languages ORDER BY sort_order ASC, code ASC LIMIT 30')->fetchAll();
        } catch (\Throwable $e) {
        }
        $text = "🌍 <b>Languages</b>\n\n";
        foreach ($rows as $l) {
            $on = !empty($l['is_active']) ? 'ON' : 'OFF';
            $text .= '<code>' . htmlspecialchars((string)$l['code']) . "</code> {$on} — "
                . htmlspecialchars((string)$l['name']) . "\n";
        }
        if (!$rows) {
            $text .= 'No languages loaded.';
        }
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::backHome());
    }

    public static function users(int $chatId, int $msgId): void
    {
        $rows = array();
        try {
            $rows = db()->query('SELECT telegram_id, username, full_name, lang, created_at FROM users ORDER BY id DESC LIMIT 12')->fetchAll();
        } catch (\Throwable $e) {
        }
        $text = "👥 <b>Latest Users</b>\n\n";
        foreach ($rows as $u) {
            $name = trim((string)($u['full_name'] ?? ''));
            $un = (string)($u['username'] ?? '');
            $text .= '<code>' . (int)$u['telegram_id'] . '</code> '
                . ($un !== '' ? '@' . htmlspecialchars($un) : htmlspecialchars($name !== '' ? $name : '-'))
                . ' · ' . htmlspecialchars((string)($u['lang'] ?? 'en')) . "\n";
        }
        if (!$rows) {
            $text .= 'No users yet.';
        }
        $text .= "\nSend <code>/user TELEGRAM_ID</code> for details.";
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::backHome());
    }

    public static function userOptions(int $chatId, int $msgId): void
    {
        $opts = array();
        try {
            if (class_exists('\\HddLand\\Bot\\Services\\UserOptionsService', true)) {
                \HddLand\Bot\Services\UserOptionsService::ensureSchema();
            }
            $opts = db()->query('SELECT code, title_en, default_open FROM option_catalog ORDER BY sort_order ASC, id ASC')->fetchAll();
        } catch (\Throwable $e) {
        }
        $text = "🎛 <b>User Options Catalog</b>\nPer-user access is managed in Web Admin → User Options.\n\n";
        foreach ($opts as $o) {
            $open = !empty($o['default_open']) ? 'default OPEN' : 'default locked';
            $text .= '• <code>' . htmlspecialchars((string)$o['code']) . '</code> — '
                . htmlspecialchars((string)$o['title_en']) . " ({$open})\n";
        }
        if (!$opts) {
            $text .= 'Catalog empty — open License sample / User Options once.';
        }
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::backHome());
    }

    public static function receipts(int $chatId, int $msgId): void
    {
        if (class_exists(LicenseFlowService::class, true)) {
            LicenseFlowService::ensureSchema();
        }
        $rows = array();
        try {
            $rows = db()->query("SELECT * FROM payment_receipts WHERE status='pending' ORDER BY id DESC LIMIT 10")->fetchAll();
        } catch (\Throwable $e) {
        }
        $mailbox = class_exists(LicenseFlowService::class, true)
            ? LicenseFlowService::licenseMailbox()
            : (string)cfg('license_mailbox', 'sedivlic@list.ru');
        $text = "💵 <b>Receipts & Licenses</b>\nMailbox: <code>" . htmlspecialchars($mailbox) . "</code>\n\n";
        $kb = array();
        if (!$rows) {
            $text .= 'No pending receipts.';
        } else {
            foreach ($rows as $r) {
                $id = (int)$r['id'];
                $text .= "#{$id} · " . htmlspecialchars((string)$r['method'])
                    . ' · user <code>' . (int)$r['telegram_id'] . '</code>'
                    . ' · ' . htmlspecialchars((string)($r['order_code'] ?? '')) . "\n";
                $kb[] = array(
                    array('text' => "✅ Approve #{$id}", 'callback_data' => 'adm:rapprove:' . $id),
                    array('text' => "❌ Reject #{$id}", 'callback_data' => 'adm:rreject:' . $id),
                );
            }
        }
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::kb($kb));
    }

    public static function admins(int $chatId, int $msgId): void
    {
        $rows = array();
        try {
            $rows = db()->query('SELECT id, username, is_super, is_active FROM panel_users ORDER BY id ASC LIMIT 30')->fetchAll();
        } catch (\Throwable $e) {
        }
        $ids = bot_config()['admin_ids'] ?? array();
        $text = "🔐 <b>Admins & Access</b>\n\nTelegram admin IDs:\n";
        if ($ids) {
            foreach ($ids as $id) {
                $text .= '• <code>' . (int)$id . "</code>\n";
            }
        } else {
            $text .= "• (none configured)\n";
        }
        $text .= "\nPanel users:\n";
        foreach ($rows as $a) {
            $flags = array();
            if (!empty($a['is_super'])) {
                $flags[] = 'super';
            }
            $flags[] = !empty($a['is_active']) ? 'active' : 'disabled';
            $text .= '• <b>' . htmlspecialchars((string)$a['username']) . '</b> (' . implode(', ', $flags) . ")\n";
        }
        if (!$rows) {
            $text .= "• legacy config admin only\n";
        }
        $text .= "\nPasswords are managed in Web Admin.";
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::backHome());
    }

    public static function settings(int $chatId, int $msgId): void
    {
        $cfg = function_exists('merge_bot_defaults_into_config')
            ? merge_bot_defaults_into_config(bot_config())
            : bot_config();
        $features = array('shop','forum','faq','tickets','prodesk','ai','license','renewal','vip_download');
        $text = "⚙️ <b>Settings</b>\n\n"
            . 'Site: ' . htmlspecialchars((string)($cfg['site_url'] ?? '')) . "\n"
            . 'Forum: ' . htmlspecialchars((string)($cfg['forum_url'] ?? '')) . "\n"
            . 'PayPal: <code>' . htmlspecialchars((string)cfg('paypal_email', 'sedivlic@list.ru')) . "</code>\n"
            . 'License mailbox: <code>' . htmlspecialchars((string)cfg('license_mailbox', 'sedivlic@list.ru')) . "</code>\n"
            . 'Maintenance: <code>' . (!empty($cfg['maintenance_mode']) ? 'ON' : 'OFF') . "</code>\n\n"
            . "<b>Features</b>\n";
        foreach ($features as $f) {
            $on = function_exists('feature_on') ? (feature_on($f) ? 'ON' : 'OFF') : '?';
            $text .= "• {$f}: <code>{$on}</code>\n";
        }
        $text .= "\nFull editor: Web Admin → Settings ★";
        $maintOn = !empty($cfg['maintenance_mode']);
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::kb(array(
            array(array(
                'text' => $maintOn ? '🟢 Turn Maintenance OFF' : '🔴 Turn Maintenance ON',
                'callback_data' => 'adm:maint:' . ($maintOn ? '0' : '1'),
            )),
        )));
    }

    public static function branding(int $chatId, int $msgId): void
    {
        $text = "🏷 <b>Branding / Bot Title</b>\n\n"
            . 'Title: <b>' . htmlspecialchars((string)cfg('bot_title', 'HDD-Land Bot')) . "</b>\n"
            . 'Subtitle: ' . htmlspecialchars((string)cfg('bot_subtitle', '')) . "\n"
            . 'Public bot username: @' . htmlspecialchars(ltrim((string)cfg('bot_username', ''), '@')) . "\n"
            . 'Admin bot: @' . htmlspecialchars(ltrim((string)cfg('admin_bot_username', 'SedivSupport_bot'), '@')) . "\n\n"
            . 'Edit texts in Web Admin → Settings → Branding.';
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::backHome());
    }

    public static function health(int $chatId, int $msgId): void
    {
        $root = dirname(__DIR__, 2);
        $checks = array(
            'BotKernel' => is_file($root . '/src/BotKernel.php'),
            'AdminBotKernel' => is_file($root . '/src/AdminBot/AdminBotKernel.php'),
            'LicenseFlow' => is_file($root . '/src/Services/LicenseFlowService.php'),
            'config.local.php' => is_file($root . '/config.local.php'),
            'HealthRepair plugin' => is_file($root . '/plugins/HealthRepair/plugin.php'),
            'SmartI18n plugin' => is_file($root . '/plugins/SmartI18n/plugin.php'),
        );
        $dbOk = false;
        try {
            db()->query('SELECT 1');
            $dbOk = true;
        } catch (\Throwable $e) {
        }
        $text = "❤️ <b>Health & Repair</b>\n\nDatabase: <code>" . ($dbOk ? 'OK' : 'FAIL') . "</code>\n\n";
        foreach ($checks as $name => $ok) {
            $text .= ($ok ? '✅' : '❌') . ' ' . $name . "\n";
        }
        $tokenPub = trim((string)(bot_config()['bot_token'] ?? ''));
        $tokenAdm = admin_bot_token();
        $text .= "\nPublic bot token: <code>" . ($tokenPub !== '' ? 'set' : 'MISSING') . "</code>\n"
            . 'Admin bot token: <code>' . ($tokenAdm !== '' ? 'set' : 'MISSING') . "</code>";
        admin_edit_or_send($chatId, $msgId, $text, AdminUi::kb(array(
            array(array('text' => '🩺 Run schema heal', 'callback_data' => 'adm:heal')),
        )));
    }

    public static function webLink(int $chatId, int $msgId): void
    {
        $base = rtrim((string)cfg('site_url', 'https://hdd-land.com'), '/');
        // Prefer known live admin path
        $url = 'https://hdd-land.com/telegram_bot/php_bot/admin/';
        $text = "🌐 <b>Web Admin Panel</b>\n\n"
            . "Open the full desktop/mobile control panel:\n"
            . "<code>{$url}</code>\n\n"
            . 'Use the same username & password as this bot.';
        admin_edit_or_send($chatId, $msgId, $text, array(
            'inline_keyboard' => array(
                array(array('text' => '🚀 Open Web Admin', 'url' => $url)),
                array(array('text' => '⬅️ Main Menu', 'callback_data' => 'adm:home')),
            ),
        ));
    }

    public static function broadcastPrompt(int $chatId, int $userId, int $msgId): void
    {
        AdminAuth::setState($userId, 'broadcast_wait', array());
        admin_edit_or_send(
            $chatId,
            $msgId,
            "📢 <b>Broadcast</b>\n\nSend the message text now (HTML allowed).\nIt will go to all bot users.\n\nCancel: /cancel",
            AdminUi::backHome()
        );
    }
}
