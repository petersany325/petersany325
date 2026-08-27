<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (function_exists('do_action')) {
    do_action('bot_boot');
}

// Health check in browser
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "HDD-Land PHP webhook is online.\n";
    exit;
}

$cfg = bot_config();
$secretHeader = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
if (!empty($cfg['webhook_secret']) && !hash_equals($cfg['webhook_secret'], $secretHeader)) {
    http_response_code(403);
    exit('Forbidden');
}

$raw = file_get_contents('php://input');
$update = json_decode($raw ?: '[]', true);
if (!$update) {
    exit('no update');
}

try {
    if (isset($update['callback_query'])) {
        handle_callback($update['callback_query']);
    } elseif (isset($update['message'])) {
        handle_message($update['message']);
    }
} catch (Throwable $e) {
    // Avoid Telegram retries storm; log locally if possible
    @file_put_contents(__DIR__ . '/error.log', date('c') . ' ' . $e->getMessage() . "\n", FILE_APPEND);
}

echo 'ok';

function handle_message(array $message): void {
    $chatId = (int)$message['chat']['id'];
    $from = $message['from'] ?? ['id' => 0];
    $text = trim((string)($message['text'] ?? ''));
    $userId = (int)($from['id'] ?? 0);

    if ($userId) {
        ensure_user($from);
    }
    $lang = user_lang($userId);

    if (function_exists('cfg') && (int)cfg('maintenance_mode', 0) === 1 && !is_admin($userId)) {
        send_message($chatId, (string)cfg('maintenance_text', 'Bot is under maintenance.'));
        return;
    }

    // Media (photo/video/document) for support/sales requests
    if (isset($message['photo']) || isset($message['video']) || isset($message['document'])) {
        if (!function_exists('feature_on') || feature_on('prodesk')) {
            if (handle_request_media_message($message, $lang)) {
                return;
            }
        }
    }

    if ($text === '' || $text[0] !== '/') {
        if ($text !== '') {
            if (function_exists('feature_on') && feature_on('prodesk') && handle_request_text($chatId, $userId, $text, $lang)) {
                return;
            }
            if (!function_exists('feature_on') || feature_on('auto_faq_search')) {
                $hits = search_faqs($text, $lang);
                if ($hits) {
                    $lines = ["❓ <b>Related FAQ:</b>\n"];
                    foreach ($hits as $f) {
                        $lines[] = '<b>' . htmlspecialchars($f['question']) . "</b>\n" . htmlspecialchars($f['answer']) . "\n";
                    }
                    send_message($chatId, implode("\n", $lines), faq_keyboard(null, $lang));
                    return;
                }
            }
            send_message($chatId, $lang === 'fa' ? 'از /menu یا /faq استفاده کنید.' : 'Send /menu or /faq — or /help for commands.', main_keyboard($lang));
        }
        return;
    }

    // Cancel / done while in request flow
    if (in_array($text, array('/cancel', '/done'), true) || strpos($text, '/cancel') === 0 || strpos($text, '/done') === 0) {
        if (handle_request_text($chatId, $userId, explode(' ', $text)[0], $lang)) {
            return;
        }
    }

    $parts = preg_split('/\s+/', $text, 2);
    $cmd = strtolower(explode('@', ltrim($parts[0], '/'))[0]);
    $arg = $parts[1] ?? '';

    switch ($cmd) {
        case 'start':
            if (function_exists('feature_on') && feature_on('language_gate') && empty(cfg('start_with_menu', 0))) {
                $detected = function_exists('detect_telegram_lang') ? detect_telegram_lang($from) : 'en';
                send_message($chatId, language_gate_text(), function_exists('lang_keyboard_world') ? lang_keyboard_world(true, 0, $detected) : lang_keyboard(true));
            } else {
                send_message($chatId, welcome_text($lang), main_keyboard($lang));
            }
            break;

        case 'menu':
            send_message($chatId, $lang === 'fa' ? '📑 <b>منوی پیشرفته</b>' : '📑 <b>Main Menu</b>', main_keyboard($lang));
            break;

        case 'lang':
        case 'language':
            $detected = function_exists('detect_telegram_lang') ? detect_telegram_lang($from) : 'en';
            send_message($chatId, language_gate_text(), function_exists('lang_keyboard_world') ? lang_keyboard_world(false, 0, $detected) : lang_keyboard(false));
            break;

        case 'faq':
            if (function_exists('feature_on') && !feature_on('faq')) {
                send_message($chatId, $lang === 'fa' ? 'FAQ فعلاً غیرفعال است.' : 'FAQ is disabled.', main_keyboard($lang));
                break;
            }
            if ($arg !== '') {
                $hits = search_faqs($arg, $lang);
                if (!$hits) {
                    send_message($chatId, 'No FAQ found for: ' . htmlspecialchars($arg), faq_keyboard(null, $lang));
                } else {
                    $lines = ["❓ <b>FAQ:</b>\n"];
                    foreach ($hits as $f) {
                        $lines[] = '<b>' . htmlspecialchars($f['question']) . "</b>\n" . htmlspecialchars($f['answer']) . "\n";
                    }
                    send_message($chatId, implode("\n", $lines), faq_keyboard(null, $lang));
                }
            } else {
                send_message($chatId, $lang === 'fa' ? '❓ <b>سوالات متداول</b>' : '❓ <b>Frequently Asked Questions</b>', faq_keyboard(null, $lang));
            }
            break;

        case 'help':
            send_message($chatId, help_text($lang), main_keyboard($lang));
            break;

        case 'support':
        case 'sales':
        case 'pro':
        case 'desk':
            if (function_exists('feature_on') && !feature_on('prodesk')) {
                send_message($chatId, $lang === 'fa' ? 'میز حرفه‌ای غیرفعال است.' : 'Pro Desk is disabled.', main_keyboard($lang));
                break;
            }
            show_request_hub($chatId, 0, $lang);
            break;

        case 'website':
            $custom = function_exists('content_text') ? content_text('website', $lang) : null;
            if ($custom) {
                send_message($chatId, $custom, main_keyboard($lang));
            } else {
                $c = bot_config();
                $email = function_exists('cfg') ? trim((string)cfg('support_email', '')) : '';
                send_message(
                    $chatId,
                    "🌐 <b>" . htmlspecialchars((string)cfg('bot_title', 'HDD-Land')) . "</b>\n\n"
                    . "• SeDiv 2026 — WD, Seagate, Toshiba, Samsung, Fujitsu\n"
                    . "• SeDiv HITACHI ARM\n"
                    . "• SeDiv HGST\n\n"
                    . "Website: {$c['site_url']}\n"
                    . "Forum: {$c['forum_url']}"
                    . ($email !== '' ? "\nEmail: {$email}" : ''),
                    main_keyboard($lang)
                );
            }
            break;

        case 'training':
            $custom = function_exists('content_text') ? content_text('training', $lang) : null;
            if ($custom) {
                send_message($chatId, $custom, main_keyboard($lang));
            } else {
                $url = function_exists('cfg') ? cfg('training_url', bot_config()['forum_url']) : bot_config()['forum_url'];
                send_message(
                    $chatId,
                    "🎓 <b>SeDiv Training Center</b>\n\n"
                    . "• SeDiv WD Training\n"
                    . "• SeDiv Seagate (F3) Training\n"
                    . "• SeDiv Toshiba / Samsung / Hitachi\n\n"
                    . "Link: " . $url,
                    main_keyboard($lang)
                );
            }
            break;

        case 'shop':
            if (function_exists('feature_on') && !feature_on('shop')) {
                send_message($chatId, $lang === 'fa' ? 'فروشگاه غیرفعال است.' : 'Shop is disabled.', main_keyboard($lang));
                break;
            }
            show_shop($chatId, 0, $lang);
            break;

        case 'forum':
            if (function_exists('feature_on') && !feature_on('forum')) {
                send_message($chatId, $lang === 'fa' ? 'فروم غیرفعال است.' : 'Forum is disabled.', main_keyboard($lang));
                break;
            }
            show_forum($chatId, 0, $lang);
            break;

        case 'ticket':
            if (function_exists('feature_on') && !feature_on('tickets')) {
                send_message($chatId, $lang === 'fa' ? 'تیکت غیرفعال است.' : 'Tickets are disabled.', main_keyboard($lang));
                break;
            }
            if ($arg === '') {
                send_message($chatId, "Usage: /ticket your issue description");
                break;
            }
            create_ticket($chatId, $userId, $arg);
            break;

        case 'mytickets':
            show_my_tickets($chatId, $userId);
            break;

        case 'tickets':
            if (!is_admin($userId)) {
                send_message($chatId, "🔒 Admins only.");
                break;
            }
            show_all_tickets($chatId);
            break;

        case 'replyticket':
            if (!is_admin($userId)) {
                send_message($chatId, "🔒 Admins only.");
                break;
            }
            $bits = preg_split('/\s+/', $arg, 2);
            if (count($bits) < 2 || !ctype_digit($bits[0])) {
                send_message($chatId, "Usage: /replyticket ID message");
                break;
            }
            reply_ticket($chatId, $userId, (int)$bits[0], $bits[1]);
            break;

        case 'closeticket':
            if (!is_admin($userId)) {
                send_message($chatId, "🔒 Admins only.");
                break;
            }
            if (!ctype_digit(trim($arg))) {
                send_message($chatId, "Usage: /closeticket ID");
                break;
            }
            close_ticket($chatId, (int)$arg);
            break;

        case 'ask':
            if (function_exists('feature_on') && !feature_on('ai')) {
                send_message($chatId, $lang === 'fa' ? 'دستیار هوشمند غیرفعال است.' : 'AI is disabled.', main_keyboard($lang));
                break;
            }
            ask_ai($chatId, $arg);
            break;

        default:
            send_message($chatId, "Unknown command. Try /help", main_keyboard($lang));
    }
}

function handle_callback(array $cb): void {
    $id = $cb['id'];
    $data = (string)($cb['data'] ?? '');
    $message = $cb['message'] ?? null;
    $from = $cb['from'] ?? ['id' => 0];
    $chatId = (int)($message['chat']['id'] ?? 0);
    $msgId = (int)($message['message_id'] ?? 0);
    $userId = (int)($from['id'] ?? 0);

    ensure_user($from);
    ensure_schema();
    $lang = user_lang($userId);

    if ($data === 'shop') {
        answer_callback($id);
        if (function_exists('feature_on') && !feature_on('shop')) {
            edit_or_send($chatId, $msgId, $lang === 'fa' ? 'فروشگاه غیرفعال است.' : 'Shop is disabled.', main_keyboard($lang));
        } else {
            show_shop($chatId, $msgId, $lang);
        }
    } elseif ($data === 'forum') {
        answer_callback($id);
        if (function_exists('feature_on') && !feature_on('forum')) {
            edit_or_send($chatId, $msgId, $lang === 'fa' ? 'فروم غیرفعال است.' : 'Forum is disabled.', main_keyboard($lang));
        } else {
            show_forum($chatId, $msgId, $lang);
        }
    } elseif ($data === 'support' || $data === 'reqhub') {
        answer_callback($id);
        if (function_exists('feature_on') && !feature_on('prodesk')) {
            edit_or_send($chatId, $msgId, $lang === 'fa' ? 'میز حرفه‌ای غیرفعال است.' : 'Pro Desk is disabled.', main_keyboard($lang));
        } else {
            show_request_hub($chatId, $msgId, $lang);
        }
    } elseif ($data === 'req:support') {
        answer_callback($id);
        start_request_flow($chatId, $userId, 'support', $lang);
    } elseif ($data === 'req:sales') {
        answer_callback($id);
        start_request_flow($chatId, $userId, 'sales', $lang);
    } elseif ($data === 'req:mediahelp') {
        answer_callback($id);
        $t = $lang === 'fa'
            ? "📎 <b>ارسال عکس و فیلم</b>\n\n1) از منوی 💼 میز حرفه‌ای پشتیبانی یا فروش را شروع کنید\n2) متن را بفرستید\n3) بعد عکس یا فیلم را ارسال کنید\n4) در پایان /done بزنید\n\nادمین‌ها فایل را مستقیم دریافت می‌کنند."
            : "📎 <b>Send photo & video</b>\n\n1) Open 💼 Pro Desk → Support or Sales\n2) Send your text\n3) Then send photo or video\n4) Finish with /done\n\nAdmins receive media instantly.";
        edit_or_send($chatId, $msgId, $t, request_hub_keyboard($lang));
    } elseif ($data === 'help') {
        answer_callback($id);
        edit_or_send($chatId, $msgId, help_text($lang), main_keyboard($lang));
    } elseif ($data === 'lang') {
        answer_callback($id);
        $detected = detect_telegram_lang($from);
        edit_or_send($chatId, $msgId, language_gate_text(), lang_keyboard_world(false, 0, $detected));
    } elseif (strpos($data, 'langpage:') === 0) {
        $page = (int)substr($data, 9);
        answer_callback($id);
        $detected = detect_telegram_lang($from);
        edit_or_send($chatId, $msgId, language_gate_text(), lang_keyboard_world(true, $page, $detected));
    } elseif (strpos($data, 'startlang:') === 0 || strpos($data, 'setlang:') === 0) {
        $fromStart = (strpos($data, 'startlang:') === 0);
        $code = $fromStart ? substr($data, 10) : substr($data, 8);
        answer_callback($id, $code === 'fa' ? 'فارسی' : 'English');
        set_user_lang($userId, $code);
        $lang = $code;
        if (function_exists('do_action')) {
            do_action('after_language_selected', $chatId, $msgId, $userId, $lang);
        } else {
            edit_or_send($chatId, $msgId, welcome_text($lang), function_exists('graphical_main_hub') ? graphical_main_hub($lang) : main_keyboard($lang));
        }
    } elseif ($data === 'main' || $data === 'menu:root') {
        answer_callback($id);
        edit_or_send($chatId, $msgId, $lang === 'fa' ? '🏠 <b>منوی اصلی</b>' : '🏠 <b>Main Menu</b>', function_exists('graphical_main_hub') ? graphical_main_hub($lang) : main_keyboard($lang));
    } elseif (strpos($data, 'menu:') === 0) {
        $mid = (int)substr($data, 5);
        answer_callback($id);
        if ($mid <= 0) {
            edit_or_send($chatId, $msgId, $lang === 'fa' ? '📑 <b>منو</b>' : '📑 <b>Menu</b>', main_keyboard($lang));
        } else {
            $st = db()->prepare('SELECT * FROM menus WHERE id=?');
            $st->execute([$mid]);
            $m = $st->fetch();
            if ($m) {
                $m = localize_menu_row($m, $lang);
            }
            $title = $m ? $m['title'] : 'Menu';
            edit_or_send($chatId, $msgId, '📑 <b>' . htmlspecialchars((string)$title) . '</b>', build_menu_keyboard($mid, $lang));
        }
    } elseif (strpos($data, 'menutxt:') === 0) {
        $mid = (int)substr($data, 8);
        answer_callback($id);
        $st = db()->prepare('SELECT * FROM menus WHERE id=? AND is_active=1');
        $st->execute([$mid]);
        $row = $st->fetch();
        if ($row) {
            $row = localize_menu_row($row, $lang);
        }
        $text = $row ? ('<b>' . htmlspecialchars($row['title']) . "</b>\n\n" . htmlspecialchars((string)$row['value_text'])) : 'Empty.';
        edit_or_send($chatId, $msgId, $text, main_keyboard($lang));
    } elseif (strpos($data, 'cmd:') === 0) {
        $cmd = substr($data, 4);
        answer_callback($id);
        if ($cmd === 'training') {
            $custom = function_exists('content_text') ? content_text('training', $lang) : null;
            $url = function_exists('cfg') ? cfg('training_url', bot_config()['forum_url']) : bot_config()['forum_url'];
            $text = $custom ? $custom : ("🎓 <b>SeDiv Training Center</b>\n\n"
                . "• SeDiv WD Training\n"
                . "• SeDiv Seagate (F3) Training\n"
                . "• SeDiv Toshiba / Samsung / Hitachi\n\n"
                . "Link: " . $url);
            edit_or_send($chatId, $msgId, $text, main_keyboard($lang));
        } elseif ($cmd === 'website') {
            $custom = function_exists('content_text') ? content_text('website', $lang) : null;
            $c = bot_config();
            $text = $custom ? $custom : ("🌐 {$c['site_url']}");
            edit_or_send($chatId, $msgId, $text, main_keyboard($lang));
        } else {
            edit_or_send($chatId, $msgId, help_text($lang), main_keyboard($lang));
        }
    } elseif (strpos($data, 'faqcat:') === 0) {
        $cat = rawurldecode(substr($data, 7));
        answer_callback($id);
        $title = ($cat === 'all') ? ($lang === 'fa' ? 'همه سوالات' : 'All FAQs') : $cat;
        edit_or_send($chatId, $msgId, "❓ <b>{$title}</b>", faq_keyboard($cat === 'all' ? null : $cat, $lang));
    } elseif (strpos($data, 'faq:') === 0) {
        $fid = (int)substr($data, 4);
        answer_callback($id);
        $st = db()->prepare('SELECT * FROM faqs WHERE id=? AND is_active=1');
        $st->execute([$fid]);
        $f = $st->fetch();
        if (!$f) {
            edit_or_send($chatId, $msgId, 'FAQ not found.', faq_keyboard(null, $lang));
        } else {
            $f = localize_faq_row($f, $lang);
            $text = '❓ <b>' . htmlspecialchars($f['question']) . "</b>\n\n" . htmlspecialchars($f['answer']);
            $back = $lang === 'fa' ? '⬅️ بازگشت به FAQ' : '⬅️ Back to FAQ';
            $main = $lang === 'fa' ? '🏠 منوی اصلی' : '🏠 Main Menu';
            $kb = [
                'inline_keyboard' => [
                    [['text' => $back, 'callback_data' => 'faqcat:all']],
                    [['text' => $main, 'callback_data' => 'main']],
                ],
            ];
            edit_or_send($chatId, $msgId, $text, $kb);
        }
    } elseif (strpos($data, 'product:') === 0) {
        $pid = (int)substr($data, 8);
        answer_callback($id);
        show_product($chatId, $msgId, $pid);
    } else {
        answer_callback($id, 'OK');
    }
}

function edit_or_send(int $chatId, int $msgId, string $text, ?array $kb = null): void {
    if ($msgId > 0) {
        edit_message($chatId, $msgId, $text, $kb);
    } else {
        send_message($chatId, $text, $kb);
    }
}

function show_shop(int $chatId, int $msgId = 0, string $lang = 'en'): void {
    $rows = db()->query('SELECT id, name, price, buy_url FROM products WHERE is_active = 1 ORDER BY id')->fetchAll();
    $kb = ['inline_keyboard' => []];
    foreach ($rows as $p) {
        $kb['inline_keyboard'][] = [[
            'text' => $p['name'] . ' — $' . (int)$p['price'],
            'callback_data' => 'product:' . $p['id'],
        ]];
    }
    $kb['inline_keyboard'][] = [
        ['text' => $lang === 'fa' ? '💎 درخواست خرید' : '💎 Sales Request', 'callback_data' => 'req:sales'],
        ['text' => $lang === 'fa' ? '🛠️ پشتیبانی' : '🛠️ Support', 'callback_data' => 'req:support'],
    ];
    $kb['inline_keyboard'][] = [['text' => $lang === 'fa' ? '⬅️ بازگشت' : '⬅️ Back', 'callback_data' => 'main']];
    $custom = function_exists('content_text') ? content_text('shop', $lang) : null;
    $text = $custom ? $custom : ($lang === 'fa'
        ? "🛒 <b>فروشگاه SeDiv — HDD-Land</b>\n\nیک محصول را انتخاب کنید:"
        : "🛒 <b>SeDiv Shop — HDD-Land</b>\n\nSelect a product:");
    edit_or_send($chatId, $msgId, $text, $kb);
}

function show_product(int $chatId, int $msgId, int $pid): void {
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
    $stmt->execute([$pid]);
    $p = $stmt->fetch();
    if (!$p) {
        edit_or_send($chatId, $msgId, 'Product not found.', main_keyboard());
        return;
    }
    $site = bot_config()['site_url'];
    $buy = !empty($p['buy_url']) ? $p['buy_url'] : $site;
    $label = !empty($p['link_label']) ? $p['link_label'] : '🌐 Buy / Details';
    $text = "📦 <b>" . htmlspecialchars($p['name']) . "</b>\n\n"
        . htmlspecialchars((string)$p['description']) . "\n\n"
        . "💰 Price: <b>$" . number_format((float)$p['price'], 0) . "</b> / year";

    $kbRows = array();
    $kbRows[] = array(array('text' => $label, 'url' => $buy));
    if (!empty($p['demo_url'])) {
        $kbRows[] = array(array('text' => '▶️ Demo / Info', 'url' => $p['demo_url']));
    }
    $kbRows[] = array(
        array('text' => '💎 Request Purchase', 'callback_data' => 'req:sales'),
        array('text' => '🛠️ Ask Support', 'callback_data' => 'req:support'),
    );
    $kbRows[] = array(array('text' => '⬅️ Back to Shop', 'callback_data' => 'shop'));
    $kb = array('inline_keyboard' => $kbRows);

    // Send media if configured
    if (!empty($p['image_url'])) {
        tg_api('sendPhoto', array(
            'chat_id' => $chatId,
            'photo' => $p['image_url'],
            'caption' => strip_tags($text),
            'parse_mode' => 'HTML',
            'reply_markup' => $kb,
        ));
        if (!empty($p['video_url'])) {
            tg_api('sendVideo', array('chat_id' => $chatId, 'video' => $p['video_url'], 'caption' => $p['name'] . ' video'));
        }
        return;
    }
    if (!empty($p['video_url'])) {
        tg_api('sendVideo', array(
            'chat_id' => $chatId,
            'video' => $p['video_url'],
            'caption' => strip_tags($text),
            'reply_markup' => $kb,
        ));
        return;
    }
    edit_or_send($chatId, $msgId, $text, $kb);
}

function show_forum(int $chatId, int $msgId = 0, string $lang = 'en'): void {
    $forum = bot_config()['forum_url'];
    $custom = function_exists('content_text') ? content_text('forum', $lang) : null;
    $text = $custom ? $custom : ($lang === 'fa'
        ? "📋 <b>انجمن HDD-Land</b>\n\nجامعه حرفه‌ای ریکاوری دیتا.\n\nبر اساس برند در فروم جستجو کنید:\n• Western Digital\n• Seagate\n• Toshiba\n• Samsung\n• Hitachi\n• Fujitsu"
        : "📋 <b>HDD-Land Forum</b>\n\nProfessional Data Recovery community.\n\nBrowse by brand on the forum:\n• Western Digital\n• Seagate\n• Toshiba\n• Samsung\n• Hitachi\n• Fujitsu");
    $kb = [
        'inline_keyboard' => [
            [['text' => $lang === 'fa' ? '🌐 باز کردن فروم' : '🌐 Open Forum', 'url' => $forum]],
            [['text' => $lang === 'fa' ? '⬅️ بازگشت' : '⬅️ Back', 'callback_data' => 'main']],
        ],
    ];
    edit_or_send($chatId, $msgId, $text, $kb);
}

function create_ticket(int $chatId, int $userId, string $subject): void {
    $pdo = db();
    $pdo->prepare('INSERT INTO tickets (user_id, subject, status) VALUES (?,?,?)')
        ->execute([$userId, $subject, 'open']);
    $tid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO ticket_messages (ticket_id, sender_id, is_admin, text) VALUES (?,?,0,?)')
        ->execute([$tid, $userId, $subject]);

    send_message($chatId, "🎫 Ticket <b>#{$tid}</b> created!\n\n📝 " . htmlspecialchars($subject));

    if (function_exists('notify_staff')) {
        notify_staff(
            "🆕 New ticket <b>#{$tid}</b> from <code>{$userId}</code>\n\n"
            . htmlspecialchars($subject) . "\n\n"
            . "Reply: <code>/replyticket {$tid} your answer</code>",
            'tickets'
        );
    } else {
        foreach (bot_config()['admin_ids'] as $adminId) {
            send_message(
                $adminId,
                "🆕 New ticket <b>#{$tid}</b> from <code>{$userId}</code>\n\n"
                . htmlspecialchars($subject) . "\n\n"
                . "Reply: <code>/replyticket {$tid} your answer</code>"
            );
        }
    }
}

function show_my_tickets(int $chatId, int $userId): void {
    $stmt = db()->prepare('SELECT id, subject, status FROM tickets WHERE user_id = ? ORDER BY id DESC LIMIT 10');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();
    if (!$rows) {
        send_message($chatId, '🎫 You have no tickets.');
        return;
    }
    $lines = ["🎫 <b>Your Tickets:</b>\n"];
    foreach ($rows as $t) {
        $st = $t['status'] === 'open' ? '🟢 Open' : '🔴 Closed';
        $lines[] = "#{$t['id']} — {$st} — " . htmlspecialchars(substr($t['subject'], 0, 50));
    }
    send_message($chatId, implode("\n", $lines));
}

function show_all_tickets(int $chatId): void {
    $rows = db()->query("SELECT id, user_id, subject FROM tickets WHERE status='open' ORDER BY id DESC LIMIT 20")->fetchAll();
    if (!$rows) {
        send_message($chatId, '🎫 No open tickets.');
        return;
    }
    $lines = ["🎫 <b>Open Tickets:</b>\n"];
    foreach ($rows as $t) {
        $lines[] = "#{$t['id']} — user {$t['user_id']} — " . htmlspecialchars(substr($t['subject'], 0, 50));
    }
    send_message($chatId, implode("\n", $lines));
}

function reply_ticket(int $adminChatId, int $adminId, int $tid, string $text): void {
    $stmt = db()->prepare('SELECT * FROM tickets WHERE id = ?');
    $stmt->execute([$tid]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        send_message($adminChatId, 'Ticket not found.');
        return;
    }
    db()->prepare('INSERT INTO ticket_messages (ticket_id, sender_id, is_admin, text) VALUES (?,?,1,?)')
        ->execute([$tid, $adminId, $text]);
    send_message((int)$ticket['user_id'], "💬 <b>Reply to Ticket #{$tid}:</b>\n\n" . htmlspecialchars($text));
    send_message($adminChatId, "✅ Reply sent to ticket #{$tid}.");
}

function close_ticket(int $adminChatId, int $tid): void {
    $stmt = db()->prepare('SELECT * FROM tickets WHERE id = ?');
    $stmt->execute([$tid]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        send_message($adminChatId, 'Ticket not found.');
        return;
    }
    db()->prepare("UPDATE tickets SET status='closed' WHERE id=?")->execute([$tid]);
    send_message((int)$ticket['user_id'], "🔒 Your ticket #{$tid} has been closed.");
    send_message($adminChatId, "✅ Ticket #{$tid} closed.");
}

function ask_ai(int $chatId, string $question): void {
    if ($question === '') {
        send_message($chatId, 'Usage: /ask your question');
        return;
    }
    $key = function_exists('cfg') ? (string)cfg('openai_api_key', '') : (bot_config()['openai_api_key'] ?? '');
    if ($key === '') {
        send_message($chatId, '⚠️ OpenAI API key is not configured. Set it in Admin → Settings → AI / API.');
        return;
    }

    $payload = [
        'model' => function_exists('cfg') ? (string)cfg('ai_model', 'gpt-4o-mini') : 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => function_exists('cfg')
                    ? (string)cfg('ai_system_prompt', 'You are an expert HDD repair assistant for HDD-Land / SeDiv.')
                    : 'You are an expert HDD repair and data recovery assistant for HDD-Land.com / SeDiv. Be concise and professional.',
            ],
            ['role' => 'user', 'content' => $question],
        ],
        'max_tokens' => 800,
    ];
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$key}\r\n",
            'content' => json_encode($payload),
            'timeout' => 60,
        ],
    ]);
    $raw = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $ctx);
    if ($raw === false) {
        send_message($chatId, '❌ AI request failed.');
        return;
    }
    $json = json_decode($raw, true);
    $answer = $json['choices'][0]['message']['content'] ?? 'No response.';
    send_message($chatId, "🤖 <b>AI Answer:</b>\n\n" . htmlspecialchars($answer));
}
