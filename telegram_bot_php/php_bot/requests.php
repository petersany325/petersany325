<?php
/**
 * Professional Support + Sales requests, media attachments, product links.
 */
declare(strict_types=1);

function ensure_requests_schema($pdo = null) {
    $pdo = $pdo ? $pdo : db();

    // Product link / media columns
    $cols = array(
        'buy_url' => "VARCHAR(512) NULL",
        'image_url' => "VARCHAR(512) NULL",
        'video_url' => "VARCHAR(512) NULL",
        'demo_url' => "VARCHAR(512) NULL",
        'link_label' => "VARCHAR(120) NULL",
    );
    foreach ($cols as $name => $def) {
        try {
            $c = $pdo->query("SHOW COLUMNS FROM products LIKE " . $pdo->quote($name))->fetch();
            if (!$c) {
                $pdo->exec("ALTER TABLE products ADD COLUMN `{$name}` {$def}");
            }
        } catch (Exception $e) {}
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS service_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        req_type VARCHAR(30) NOT NULL DEFAULT 'support',
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        contact_info VARCHAR(255) NULL,
        status VARCHAR(40) DEFAULT 'open',
        admin_note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (req_type),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS request_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        user_id BIGINT NOT NULL,
        media_type VARCHAR(30) NOT NULL,
        file_id VARCHAR(255) NOT NULL,
        file_unique_id VARCHAR(255) NULL,
        caption TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_states (
        user_id BIGINT PRIMARY KEY,
        state VARCHAR(80) NOT NULL,
        payload TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure dedicated menu entries exist (Support Center / Sales)
    try {
        $has = (int)$pdo->query("SELECT COUNT(*) FROM menus WHERE value_text IN ('reqhub','req:support','req:sales') OR menu_type IN ('request_hub','request_support','request_sales')")->fetchColumn();
        if ($has === 0) {
            $ins = $pdo->prepare('INSERT INTO menus (parent_id, category, title, menu_type, value_text, row_index, sort_order, is_active) VALUES (?,?,?,?,?,?,?,1)');
            $ins->execute(array(null, 'Support', '💼 Pro Desk', 'submenu', '', 4, 1));
            $st = $pdo->query("SELECT id FROM menus WHERE title='💼 Pro Desk' ORDER BY id DESC LIMIT 1");
            $pid = (int)$st->fetchColumn();
            if ($pid > 0) {
                $ins->execute(array($pid, 'Support', '🛠️ Technical Support', 'callback', 'req:support', 0, 1));
                $ins->execute(array($pid, 'Commerce', '💎 Software Sales', 'callback', 'req:sales', 0, 2));
                $ins->execute(array($pid, 'Support', '📎 Send Media Help', 'callback', 'req:mediahelp', 1, 1));
                $ins->execute(array($pid, 'System', '🏠 Main Menu', 'callback', 'main', 2, 1));
                // FA titles
                if (function_exists('save_menu_translation')) {
                    $rows = $pdo->query("SELECT id, title FROM menus WHERE parent_id={$pid} OR id={$pid}")->fetchAll();
                    $map = array(
                        '💼 Pro Desk' => '💼 میز حرفه‌ای',
                        '🛠️ Technical Support' => '🛠️ پشتیبانی فنی',
                        '💎 Software Sales' => '💎 فروش نرم‌افزار',
                        '📎 Send Media Help' => '📎 راهنمای ارسال فایل',
                        '🏠 Main Menu' => '🏠 منوی اصلی',
                    );
                    foreach ($rows as $r) {
                        if (isset($map[$r['title']])) {
                            save_menu_translation((int)$r['id'], 'fa', $map[$r['title']], null);
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {}
}

function set_user_state($userId, $state, $payload = null) {
    $pdo = db();
    $json = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE);
    $pdo->prepare('INSERT INTO user_states (user_id, state, payload) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE state=VALUES(state), payload=VALUES(payload), updated_at=CURRENT_TIMESTAMP')
        ->execute(array((int)$userId, $state, $json));
}

function get_user_state($userId) {
    $st = db()->prepare('SELECT state, payload FROM user_states WHERE user_id=?');
    $st->execute(array((int)$userId));
    $row = $st->fetch();
    if (!$row) {
        return null;
    }
    return array(
        'state' => $row['state'],
        'payload' => $row['payload'] ? json_decode($row['payload'], true) : array(),
    );
}

function clear_user_state($userId) {
    db()->prepare('DELETE FROM user_states WHERE user_id=?')->execute(array((int)$userId));
}

function request_hub_keyboard($lang = 'en') {
    if ($lang === 'fa') {
        return array('inline_keyboard' => array(
            array(
                array('text' => '🛠️ پشتیبانی فنی', 'callback_data' => 'req:support'),
                array('text' => '💎 فروش نرم‌افزار', 'callback_data' => 'req:sales'),
            ),
            array(array('text' => '📎 راهنمای ارسال عکس/فیلم', 'callback_data' => 'req:mediahelp')),
            array(array('text' => '🏠 منوی اصلی', 'callback_data' => 'main')),
        ));
    }
    return array('inline_keyboard' => array(
        array(
            array('text' => '🛠️ Technical Support', 'callback_data' => 'req:support'),
            array('text' => '💎 Software Sales', 'callback_data' => 'req:sales'),
        ),
        array(array('text' => '📎 How to send photo/video', 'callback_data' => 'req:mediahelp')),
        array(array('text' => '🏠 Main Menu', 'callback_data' => 'main')),
    ));
}

function show_request_hub($chatId, $msgId, $lang) {
    $text = $lang === 'fa'
        ? "💼 <b>میز حرفه‌ای HDD-Land</b>\n\nپشتیبانی فنی SeDiv و فروش لایسنس نرم‌افزار.\nیک گزینه را انتخاب کنید:"
        : "💼 <b>HDD-Land Professional Desk</b>\n\nTechnical support for SeDiv & professional software sales.\nChoose an option:";
    edit_or_send($chatId, $msgId, $text, request_hub_keyboard($lang));
}

function start_request_flow($chatId, $userId, $type, $lang) {
    set_user_state($userId, 'await_' . $type . '_text', array('type' => $type));
    if ($type === 'sales') {
        $text = $lang === 'fa'
            ? "💎 <b>درخواست فروش نرم‌افزار</b>\n\nلطفاً بنویسید به کدام محصول علاقه‌مندید (SeDiv 2026 / HITACHI ARM / HGST)، کشور و راه تماس.\n\nمی‌توانید بعداً عکس/فیلم هم بفرستید.\n\nبرای لغو: /cancel"
            : "💎 <b>Software Sales Request</b>\n\nPlease write which product you need (SeDiv 2026 / HITACHI ARM / HGST), your country and contact.\n\nYou can attach photo/video next.\n\nCancel: /cancel";
    } else {
        $text = $lang === 'fa'
            ? "🛠️ <b>درخواست پشتیبانی فنی</b>\n\nمشکل را دقیق بنویسید (مدل هارد، خطا، نسخه SeDiv).\n\nدر مرحله بعد می‌توانید عکس یا فیلم بفرستید.\n\nلغو: /cancel"
            : "🛠️ <b>Technical Support Request</b>\n\nDescribe the issue (drive model, error, SeDiv version).\n\nNext step you can send photo or video.\n\nCancel: /cancel";
    }
    send_message($chatId, $text);
}

function create_service_request($userId, $type, $message, $subject = null) {
    $subject = $subject ? $subject : (strtoupper($type) . ' request');
    $pdo = db();
    $pdo->prepare('INSERT INTO service_requests (user_id, req_type, subject, message, status) VALUES (?,?,?,?,?)')
        ->execute(array((int)$userId, $type, substr($subject, 0, 250), $message, 'open'));
    return (int)$pdo->lastInsertId();
}

function attach_request_media($requestId, $userId, $mediaType, $fileId, $fileUniqueId = null, $caption = null) {
    db()->prepare('INSERT INTO request_media (request_id, user_id, media_type, file_id, file_unique_id, caption) VALUES (?,?,?,?,?,?)')
        ->execute(array((int)$requestId, (int)$userId, $mediaType, $fileId, $fileUniqueId, $caption));
}

function notify_admins_request($rid, $userId, $type, $message) {
    $label = $type === 'sales' ? '💎 SALES' : '🛠️ SUPPORT';
    $text = "🆕 {$label} request <b>#{$rid}</b>\nFrom: <code>{$userId}</code>\n\n"
        . htmlspecialchars($message) . "\n\n"
        . "Admin: /telegram_bot/php_bot/admin/requests.php";
    if (function_exists('notify_staff')) {
        notify_staff($text, 'requests');
        return;
    }
    foreach (bot_config()['admin_ids'] as $adminId) {
        send_message($adminId, $text);
    }
}

function handle_request_text($chatId, $userId, $text, $lang) {
    $st = get_user_state($userId);
    if (!$st) {
        return false;
    }
    if ($text === '/cancel' || strcasecmp($text, 'cancel') === 0) {
        clear_user_state($userId);
        send_message($chatId, $lang === 'fa' ? '❌ لغو شد.' : '❌ Cancelled.', main_keyboard($lang));
        return true;
    }

    if ($st['state'] === 'await_support_text' || $st['state'] === 'await_sales_text') {
        $type = ($st['state'] === 'await_sales_text') ? 'sales' : 'support';
        $rid = create_service_request($userId, $type, $text, $type === 'sales' ? 'Sales inquiry' : 'Support issue');
        notify_admins_request($rid, $userId, $type, $text);
        set_user_state($userId, 'await_media', array('type' => $type, 'request_id' => $rid));
        $msg = $lang === 'fa'
            ? "✅ درخواست <b>#{$rid}</b> ثبت شد.\n\nاگر عکس یا فیلم دارید همین الان بفرستید.\nاگر تمام شد /done را بزنید."
            : "✅ Request <b>#{$rid}</b> submitted.\n\nSend photo/video now if you have evidence.\nWhen finished send /done.";
        send_message($chatId, $msg);
        return true;
    }

    if ($st['state'] === 'await_media') {
        if ($text === '/done' || strcasecmp($text, 'done') === 0) {
            clear_user_state($userId);
            send_message($chatId, $lang === 'fa' ? '✅ پرونده بسته شد. تیم ما پاسخ می‌دهد.' : '✅ Done. Our team will reply soon.', main_keyboard($lang));
            return true;
        }
        send_message($chatId, $lang === 'fa' ? 'عکس/فیلم بفرستید یا /done بزنید.' : 'Please send a photo/video or /done.');
        return true;
    }

    return false;
}

function handle_request_media_message($message, $lang) {
    $from = isset($message['from']) ? $message['from'] : array('id' => 0);
    $userId = (int)$from['id'];
    $chatId = (int)$message['chat']['id'];
    $st = get_user_state($userId);
    if (!$st || $st['state'] !== 'await_media') {
        // Allow free media → create quick support request
        if ($st) {
            return false;
        }
        // If user sends media without state, offer to attach to new support
        set_user_state($userId, 'await_media', array('type' => 'support', 'request_id' => 0, 'pending' => 1));
        // create request with caption
        $caption = isset($message['caption']) ? $message['caption'] : 'Media attachment';
        $rid = create_service_request($userId, 'support', $caption ?: 'User sent media', 'Media support');
        $payload = array('type' => 'support', 'request_id' => $rid);
        set_user_state($userId, 'await_media', $payload);
        $st = get_user_state($userId);
    }

    $rid = (int)$st['payload']['request_id'];
    if ($rid <= 0) {
        return false;
    }

    $caption = isset($message['caption']) ? $message['caption'] : null;
    $fileId = null;
    $unique = null;
    $type = null;

    if (isset($message['photo']) && is_array($message['photo'])) {
        $photo = end($message['photo']);
        $fileId = $photo['file_id'];
        $unique = isset($photo['file_unique_id']) ? $photo['file_unique_id'] : null;
        $type = 'photo';
    } elseif (isset($message['video'])) {
        $fileId = $message['video']['file_id'];
        $unique = isset($message['video']['file_unique_id']) ? $message['video']['file_unique_id'] : null;
        $type = 'video';
    } elseif (isset($message['document'])) {
        $fileId = $message['document']['file_id'];
        $unique = isset($message['document']['file_unique_id']) ? $message['document']['file_unique_id'] : null;
        $type = 'document';
    } else {
        return false;
    }

    attach_request_media($rid, $userId, $type, $fileId, $unique, $caption);

    // Forward copy to admins
    if (!function_exists('notify_on') || notify_on('media')) {
        $ids = function_exists('staff_notify_ids') ? staff_notify_ids() : (bot_config()['admin_ids'] ?? array());
        foreach ($ids as $adminId) {
            if ($type === 'photo') {
                tg_api('sendPhoto', array('chat_id' => $adminId, 'photo' => $fileId, 'caption' => "📎 {$type} for request #{$rid}\n" . ($caption ?: '')));
            } elseif ($type === 'video') {
                tg_api('sendVideo', array('chat_id' => $adminId, 'video' => $fileId, 'caption' => "📎 {$type} for request #{$rid}\n" . ($caption ?: '')));
            } else {
                tg_api('sendDocument', array('chat_id' => $adminId, 'document' => $fileId, 'caption' => "📎 {$type} for request #{$rid}\n" . ($caption ?: '')));
            }
        }
    }

    send_message($chatId, $lang === 'fa'
        ? "✅ فایل به درخواست #{$rid} اضافه شد. فایل بعدی یا /done"
        : "✅ Media attached to request #{$rid}. Send more or /done");
    return true;
}
