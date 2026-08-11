<?php
declare(strict_types=1);

function bot_config() {
    static $cfg = null;
    static $loaded = false;
    // Allow forced reload after admin saves
    if (!empty($GLOBALS['hdd_reload_config'])) {
        $cfg = null;
        $loaded = false;
        $GLOBALS['hdd_reload_config'] = false;
    }
    if ($cfg === null) {
        $file = __DIR__ . '/config.local.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('Bot not installed. Open /install.php first. Missing config.local.php');
        }
        $cfg = require $file;
        $loaded = true;
    }
    return $cfg;
}

function db() {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = bot_config()['db'];
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
    $pdo = new PDO($dsn, $c['user'], $c['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
    return $pdo;
}

/**
 * Call Telegram Bot API. Prefer cURL (file_get_contents often fails when
 * allow_url_fopen is off or OpenSSL streams are blocked on shared hosts).
 * Errors are logged to error.log without the bot token.
 */
function tg_api($method, $params = array()) {
    $token = (string)(bot_config()['bot_token'] ?? '');
    if ($token === '') {
        tg_api_log($method, 'empty bot_token');
        return array('ok' => false, 'description' => 'empty bot_token');
    }
    $url = 'https://api.telegram.org/bot' . $token . '/' . $method;
    $payload = json_encode($params, JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        tg_api_log($method, 'json_encode failed');
        return array('ok' => false, 'description' => 'json_encode failed');
    }

    $raw = null;
    $httpCode = 0;
    $err = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ));
        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($raw === false) {
            $err = curl_error($ch) ?: 'curl_exec failed';
        }
        curl_close($ch);
    } else {
        $ctx = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 45,
                'ignore_errors' => true,
            ),
        ));
        $raw = @file_get_contents($url, false, $ctx);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $httpCode = (int)$m[1];
        }
        if ($raw === false) {
            $err = 'file_get_contents failed (allow_url_fopen=' . (ini_get('allow_url_fopen') ? '1' : '0') . ')';
        }
    }

    if ($raw === false || $raw === null || $raw === '') {
        tg_api_log($method, $err !== '' ? $err : ('empty response http=' . $httpCode));
        return array(
            'ok' => false,
            'description' => $err !== '' ? $err : ('empty response http=' . $httpCode),
            'http_code' => $httpCode,
        );
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        tg_api_log($method, 'invalid json http=' . $httpCode . ' body=' . substr($raw, 0, 180));
        return array('ok' => false, 'description' => 'invalid json', 'http_code' => $httpCode);
    }
    if (empty($decoded['ok'])) {
        $desc = (string)($decoded['description'] ?? 'telegram error');
        tg_api_log($method, 'api=' . $desc . ' http=' . $httpCode);
    }
    return $decoded;
}

function tg_api_log($method, $message) {
    $safeMethod = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$method);
    @file_put_contents(
        __DIR__ . '/error.log',
        date('c') . ' tg_api ' . $safeMethod . ': ' . $message . "\n",
        FILE_APPEND
    );
}

function send_message($chatId, $text, $replyMarkup = null) {
    $params = array(
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    );
    if ($replyMarkup) {
        $params['reply_markup'] = $replyMarkup;
    }
    $res = tg_api('sendMessage', $params);
    // HTML parse errors: retry as plain text so language picker / menus still arrive
    if (is_array($res) && empty($res['ok'])) {
        $desc = (string)($res['description'] ?? '');
        if (stripos($desc, 'parse') !== false || stripos($desc, "can't parse") !== false) {
            unset($params['parse_mode']);
            $res = tg_api('sendMessage', $params);
        }
    }
    return $res;
}

function edit_message($chatId, $messageId, $text, $replyMarkup = null) {
    $params = array(
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    );
    if ($replyMarkup) {
        $params['reply_markup'] = $replyMarkup;
    }
    return tg_api('editMessageText', $params);
}

/**
 * Edit an existing Telegram message, or send a new one (plugins/legacy helper).
 */
function edit_or_send($chatId, $msgId, $text, $replyMarkup = null) {
    $chatId = (int)$chatId;
    $msgId = (int)$msgId;
    if ($msgId > 0) {
        $res = edit_message($chatId, $msgId, $text, $replyMarkup);
        // If edit fails (message too old / not modified), fall back to send
        if (is_array($res) && empty($res['ok'])) {
            send_message($chatId, $text, $replyMarkup);
        }
        return;
    }
    send_message($chatId, $text, $replyMarkup);
}

function answer_callback($id, $text = '', $alert = false) {
    tg_api('answerCallbackQuery', array(
        'callback_query_id' => $id,
        'text' => $text,
        'show_alert' => $alert,
    ));
}

function is_admin($userId) {
    return in_array((int)$userId, bot_config()['admin_ids'], true);
}

function ensure_user($from) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE telegram_id = ?');
    $stmt->execute(array((int)$from['id']));
    if ($stmt->fetch()) {
        return;
    }
    $lang = function_exists('default_lang') ? default_lang() : 'en';
    try {
        $ins = $pdo->prepare('INSERT INTO users (telegram_id, username, full_name, is_admin, lang) VALUES (?,?,?,?,?)');
        $ins->execute(array(
            (int)$from['id'],
            isset($from['username']) ? $from['username'] : null,
            trim((isset($from['first_name']) ? $from['first_name'] : '') . ' ' . (isset($from['last_name']) ? $from['last_name'] : '')),
            is_admin((int)$from['id']) ? 1 : 0,
            $lang,
        ));
    } catch (Exception $e) {
        $ins = $pdo->prepare('INSERT INTO users (telegram_id, username, full_name, is_admin) VALUES (?,?,?,?)');
        $ins->execute(array(
            (int)$from['id'],
            isset($from['username']) ? $from['username'] : null,
            trim((isset($from['first_name']) ? $from['first_name'] : '') . ' ' . (isset($from['last_name']) ? $from['last_name'] : '')),
            is_admin((int)$from['id']) ? 1 : 0,
        ));
    }
}

function main_keyboard($lang = null) {
    if (function_exists('graphical_main_hub')) {
        return graphical_main_hub($lang ? $lang : 'en');
    }
    if (function_exists('build_menu_keyboard')) {
        $kb = build_menu_keyboard(null, $lang);
        if (!empty($kb['inline_keyboard'])) {
            return $kb;
        }
    }
    $cfg = bot_config();
    return array(
        'inline_keyboard' => array(
            array(
                array('text' => '🛒 Shop', 'callback_data' => 'shop'),
                array('text' => '📋 Forum', 'callback_data' => 'forum'),
            ),
            array(
                array('text' => '❓ FAQ', 'callback_data' => 'faqcat:all'),
                array('text' => '🎫 Support', 'callback_data' => 'support'),
            ),
            array(
                array('text' => '🌍 Language', 'callback_data' => 'lang'),
                array('text' => '🌐 Website', 'url' => $cfg['site_url']),
            ),
        ),
    );
}

function help_text($lang = 'en') {
    if (function_exists('content_text')) {
        $custom = content_text('help', $lang);
        if ($custom) {
            return $custom;
        }
    }
    $title = function_exists('cfg') ? cfg('bot_title', 'HDD-Land Bot') : 'HDD-Land Bot';
    if ($lang === 'fa') {
        return "<b>{$title} — راهنما</b>\n\n"
            . "/start — شروع / انتخاب زبان\n"
            . "/menu — منوی اصلی\n"
            . "/faq — سوالات متداول\n"
            . "/shop — محصولات SeDiv\n"
            . "/forum — انجمن\n"
            . "/support — پشتیبانی و فروش\n"
            . "/ticket متن — ثبت تیکت\n"
            . "/mytickets — تیکت‌های من\n"
            . "/website — وب‌سایت\n"
            . "/training — آموزش\n"
            . "/ask سوال — دستیار هوشمند\n"
            . "/lang — تغییر زبان";
    }
    return "<b>{$title} — Commands</b>\n\n"
        . "/start — Language & welcome\n"
        . "/menu — Main menu\n"
        . "/faq — Frequently asked questions\n"
        . "/shop — SeDiv products\n"
        . "/forum — Community forum\n"
        . "/support — Support & sales desk\n"
        . "/ticket &lt;text&gt; — Create ticket\n"
        . "/mytickets — Your tickets\n"
        . "/website — Website\n"
        . "/training — Training\n"
        . "/ask &lt;question&gt; — AI assistant\n"
        . "/lang — Change language\n\n"
        . "<b>Admin</b>\n"
        . "/tickets — Open tickets\n"
        . "/replyticket &lt;id&gt; &lt;msg&gt;\n"
        . "/closeticket &lt;id&gt;";
}

require_once __DIR__ . '/menu_faq.php';

// Optional modules — missing file must never 500 the whole admin
foreach (array('settings_lib.php', 'i18n_world.php', 'admins_schema.php', 'requests.php', 'reply_buttons.php') as $opt) {
    $path = __DIR__ . '/' . $opt;
    if (is_file($path)) {
        try {
            require_once $path;
        } catch (Throwable $e) {
            @file_put_contents(__DIR__ . '/error.log', date('c') . ' require ' . $opt . ': ' . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

$loader = __DIR__ . '/plugins/loader.php';
if (is_file($loader)) {
    try {
        require_once $loader;
    } catch (Throwable $e) {
        @file_put_contents(__DIR__ . '/error.log', date('c') . ' plugins: ' . $e->getMessage() . "\n", FILE_APPEND);
    }
}
