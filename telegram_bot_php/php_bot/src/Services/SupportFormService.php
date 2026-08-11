<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

use HddLand\Bot\Repositories\TicketRepository;
use HddLand\Bot\Repositories\UserRepository;
use HddLand\Bot\Support\Presenter;

/**
 * Advanced Technical Support + Ticket intake (admin-configurable questions/links).
 */
final class SupportFormService
{
    public static function ensureSchema(): void
    {
        $pdo = db();
        foreach (array(
            'contact_name' => "VARCHAR(120) NULL",
            'phone' => "VARCHAR(40) NULL",
        ) as $col => $def) {
            try {
                $c = $pdo->query("SHOW COLUMNS FROM users LIKE " . $pdo->quote($col))->fetch();
                if (!$c) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN `{$col}` {$def}");
                }
            } catch (\Throwable $e) {}
        }
        foreach (array(
            'contact_name' => "VARCHAR(120) NULL",
            'phone' => "VARCHAR(40) NULL",
            'meta_json' => "TEXT NULL",
        ) as $col => $def) {
            try {
                $c = $pdo->query("SHOW COLUMNS FROM tickets LIKE " . $pdo->quote($col))->fetch();
                if (!$c) {
                    $pdo->exec("ALTER TABLE tickets ADD COLUMN `{$col}` {$def}");
                }
            } catch (\Throwable $e) {}
        }
        try {
            $c = $pdo->query("SHOW COLUMNS FROM service_requests LIKE 'meta_json'")->fetch();
            if (!$c) {
                $pdo->exec("ALTER TABLE service_requests ADD COLUMN meta_json TEXT NULL");
            }
        } catch (\Throwable $e) {}
    }

    /** @return list<array{label:string,url:string}> */
    public static function links(): array
    {
        $raw = trim((string)cfg('support_links', ''));
        $out = array();
        if ($raw === '') {
            return $out;
        }
        foreach (preg_split('/\r\n|\n|\r/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }
            list($label, $url) = array_map('trim', explode('|', $line, 2));
            if ($label !== '' && preg_match('#^https?://#i', $url)) {
                $out[] = array('label' => $label, 'url' => $url);
            }
        }
        return $out;
    }

    /** @return list<array{key:string,en:string,fa:string,required:bool}> */
    public static function questions(): array
    {
        $raw = trim((string)cfg('support_questions', ''));
        $out = array();
        if ($raw === '') {
            // Sensible SeDiv defaults
            return array(
                array('key' => 'drive_model', 'en' => 'Hard drive model (e.g. WD20EFRX)', 'fa' => 'مدل هارد (مثلاً WD20EFRX)', 'required' => true),
                array('key' => 'error', 'en' => 'Error / symptom', 'fa' => 'خطا / علائم مشکل', 'required' => true),
                array('key' => 'sediv_version', 'en' => 'SeDiv version (if any)', 'fa' => 'نسخه SeDiv (اگر دارید)', 'required' => false),
            );
        }
        foreach (preg_split('/\r\n|\n|\r/', $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 3) {
                continue;
            }
            $out[] = array(
                'key' => preg_replace('/[^a-z0-9_]/i', '_', $parts[0]) ?: ('q' . (count($out) + 1)),
                'en' => $parts[1],
                'fa' => $parts[2] !== '' ? $parts[2] : $parts[1],
                'required' => !isset($parts[3]) || strtolower($parts[3]) !== '0',
            );
        }
        return $out;
    }

    public static function start(int $chatId, int $userId, string $lang, string $mode = 'ticket'): void
    {
        self::ensureSchema();
        $intro = (string)cfg('support_intro_' . ($lang === 'fa' ? 'fa' : 'en'), '');
        if ($intro === '') {
            $intro = $lang === 'fa'
                ? "🛠️ <b>پشتیبانی فنی پیشرفته</b>\n\nچند سؤال کوتاه می‌پرسیم تا تیم دقیق‌تر کمک کند.\nلغو: /cancel"
                : "🛠️ <b>Advanced Technical Support</b>\n\nA few short questions help our team respond faster.\nCancel: /cancel";
        }

        $kb = array('inline_keyboard' => array());
        foreach (self::links() as $link) {
            $kb['inline_keyboard'][] = array(array('text' => '🔗 ' . $link['label'], 'url' => $link['url']));
        }
        $kb['inline_keyboard'][] = array(array('text' => $lang === 'fa' ? '❌ لغو' : '❌ Cancel', 'callback_data' => 'support_cancel'));

        set_user_state($userId, 'support_form', array(
            'mode' => $mode, // ticket | support
            'step' => 'boot',
            'answers' => array(),
            'contact_name' => '',
            'phone' => '',
        ));
        send_message($chatId, $intro, $kb);
        self::askNext($chatId, $userId, $lang);
    }

    public static function askNext(int $chatId, int $userId, string $lang): void
    {
        $st = get_user_state($userId);
        if (!$st || $st['state'] !== 'support_form') {
            return;
        }
        $p = $st['payload'] ?: array();
        $profile = UserRepository::profile($userId);

        if (!empty(cfg('ticket_ask_name', 1)) && trim((string)($p['contact_name'] ?? '')) === '') {
            $existing = trim((string)($profile['contact_name'] ?? ''));
            if ($existing !== '' && empty(cfg('ticket_always_ask_name', 0))) {
                $p['contact_name'] = $existing;
                set_user_state($userId, 'support_form', $p);
            } else {
                $p['step'] = 'name';
                set_user_state($userId, 'support_form', $p);
                send_message($chatId, $lang === 'fa' ? '👤 نام و نام خانوادگی خود را بنویسید:' : '👤 Please type your full name:');
                return;
            }
        }

        if (!empty(cfg('ticket_ask_phone', 1)) && trim((string)($p['phone'] ?? '')) === '') {
            $existing = trim((string)($profile['phone'] ?? ''));
            if ($existing !== '' && empty(cfg('ticket_always_ask_phone', 0))) {
                $p['phone'] = $existing;
                set_user_state($userId, 'support_form', $p);
            } else {
                $p['step'] = 'phone';
                set_user_state($userId, 'support_form', $p);
                send_message($chatId, $lang === 'fa' ? '📞 شماره تلفن خود را با کد کشور بنویسید:' : '📞 Please type your phone number (with country code):');
                return;
            }
        }

        $questions = self::questions();
        $answers = isset($p['answers']) && is_array($p['answers']) ? $p['answers'] : array();
        foreach ($questions as $q) {
            if (!array_key_exists($q['key'], $answers)) {
                $p['step'] = 'q:' . $q['key'];
                set_user_state($userId, 'support_form', $p);
                $label = $lang === 'fa' ? $q['fa'] : $q['en'];
                $req = !empty($q['required']) ? ($lang === 'fa' ? ' (الزامی)' : ' (required)') : ($lang === 'fa' ? ' (اختیاری — برای رد شدن - بفرستید)' : ' (optional — send - to skip)');
                send_message($chatId, '❓ ' . $label . $req);
                return;
            }
        }

        $p['step'] = 'message';
        set_user_state($userId, 'support_form', $p);
        send_message($chatId, $lang === 'fa'
            ? '📝 شرح کامل مشکل / درخواست را بنویسید:'
            : '📝 Describe the full issue / request:');
    }

    public static function handleText(int $chatId, int $userId, string $text, string $lang): bool
    {
        $st = get_user_state($userId);
        if (!$st || $st['state'] !== 'support_form') {
            return false;
        }
        if ($text === '/cancel' || strcasecmp($text, 'cancel') === 0) {
            clear_user_state($userId);
            send_message($chatId, $lang === 'fa' ? '❌ لغو شد.' : '❌ Cancelled.', main_keyboard($lang));
            return true;
        }

        $p = $st['payload'] ?: array();
        $step = (string)($p['step'] ?? '');

        if ($step === 'name') {
            if (mb_strlen(trim($text)) < 2) {
                send_message($chatId, $lang === 'fa' ? 'نام معتبر وارد کنید.' : 'Please enter a valid name.');
                return true;
            }
            $p['contact_name'] = trim($text);
            set_user_state($userId, 'support_form', $p);
            self::askNext($chatId, $userId, $lang);
            return true;
        }

        if ($step === 'phone') {
            $phone = preg_replace('/[^\d\+]/', '', $text);
            if ($phone === null || strlen($phone) < 7) {
                send_message($chatId, $lang === 'fa' ? 'شماره تلفن معتبر وارد کنید.' : 'Please enter a valid phone number.');
                return true;
            }
            $p['phone'] = $phone;
            set_user_state($userId, 'support_form', $p);
            self::askNext($chatId, $userId, $lang);
            return true;
        }

        if (strpos($step, 'q:') === 0) {
            $key = substr($step, 2);
            $q = null;
            foreach (self::questions() as $item) {
                if ($item['key'] === $key) {
                    $q = $item;
                    break;
                }
            }
            $val = trim($text);
            if ($val === '-' || strcasecmp($val, 'skip') === 0) {
                if ($q && !empty($q['required'])) {
                    send_message($chatId, $lang === 'fa' ? 'این سؤال الزامی است.' : 'This question is required.');
                    return true;
                }
                $val = '';
            } elseif ($q && !empty($q['required']) && $val === '') {
                send_message($chatId, $lang === 'fa' ? 'این سؤال الزامی است.' : 'This question is required.');
                return true;
            }
            $p['answers'][$key] = $val;
            set_user_state($userId, 'support_form', $p);
            self::askNext($chatId, $userId, $lang);
            return true;
        }

        if ($step === 'message') {
            if (trim($text) === '') {
                send_message($chatId, $lang === 'fa' ? 'متن مشکل را بنویسید.' : 'Please describe the issue.');
                return true;
            }
            self::finish($chatId, $userId, $lang, $p, trim($text));
            return true;
        }

        // boot / unknown → restart questions
        self::askNext($chatId, $userId, $lang);
        return true;
    }

    /** @param array<string,mixed> $p */
    private static function finish(int $chatId, int $userId, string $lang, array $p, string $message): void
    {
        self::ensureSchema();
        $name = trim((string)($p['contact_name'] ?? ''));
        $phone = trim((string)($p['phone'] ?? ''));
        $answers = isset($p['answers']) && is_array($p['answers']) ? $p['answers'] : array();
        $mode = (string)($p['mode'] ?? 'ticket');

        UserRepository::saveContact($userId, $name, $phone);

        $metaLines = array();
        if ($name !== '') {
            $metaLines[] = 'Name: ' . $name;
        }
        if ($phone !== '') {
            $metaLines[] = 'Phone: ' . $phone;
        }
        $qmap = array();
        foreach (self::questions() as $q) {
            $qmap[$q['key']] = $lang === 'fa' ? $q['fa'] : $q['en'];
        }
        foreach ($answers as $k => $v) {
            if ((string)$v === '') {
                continue;
            }
            $label = $qmap[$k] ?? $k;
            $metaLines[] = $label . ': ' . $v;
        }
        $full = $message;
        if ($metaLines) {
            $full = $message . "\n\n——\n" . implode("\n", $metaLines);
        }
        $subject = mb_substr($message, 0, 80);
        $metaJson = json_encode(array(
            'contact_name' => $name,
            'phone' => $phone,
            'answers' => $answers,
            'mode' => $mode,
        ), JSON_UNESCAPED_UNICODE);

        $tid = TicketRepository::createAdvanced($userId, $subject, $full, $name, $phone, (string)$metaJson);

        // Also mirror into Pro Desk requests for Support & Sales inbox
        try {
            if (function_exists('create_service_request')) {
                $rid = create_service_request($userId, 'support', $full, 'Support: ' . $subject);
                if ($phone !== '' || $name !== '') {
                    db()->prepare('UPDATE service_requests SET contact_info=?, meta_json=? WHERE id=?')
                        ->execute(array(trim($name . ' / ' . $phone, ' /'), $metaJson, $rid));
                }
                set_user_state($userId, 'await_media', array('type' => 'support', 'request_id' => $rid, 'ticket_id' => $tid));
            } else {
                clear_user_state($userId);
            }
        } catch (\Throwable $e) {
            clear_user_state($userId);
        }

        if (function_exists('notify_staff')) {
            notify_staff(
                "🆕 Support/Ticket <b>#{$tid}</b>\n"
                . ($name !== '' ? "👤 {$name}\n" : '')
                . ($phone !== '' ? "📞 {$phone}\n" : '')
                . "From: <code>{$userId}</code>\n\n"
                . htmlspecialchars($message),
                'tickets'
            );
        }

        $msg = $lang === 'fa'
            ? "✅ تیکت <b>#{$tid}</b> ثبت شد.\n\nاگر عکس/فیلم دارید همین الان بفرستید.\nپایان: /done\nمشاهده: /mytickets"
            : "✅ Ticket <b>#{$tid}</b> created.\n\nSend photo/video now if needed.\nFinish: /done\nView: /mytickets";
        send_message($chatId, $msg, array('inline_keyboard' => array(
            array(array('text' => $lang === 'fa' ? '🎫 تیکت‌های من' : '🎫 My Tickets', 'callback_data' => 'mytickets')),
            array(array('text' => $lang === 'fa' ? '🏠 منو' : '🏠 Menu', 'callback_data' => 'main')),
        )));
    }

    public static function showMyTickets(int $chatId, int $userId, string $lang, int $msgId = 0, ?string $phoneGate = null): void
    {
        self::ensureSchema();
        if (!empty(cfg('ticket_phone_for_view', 0))) {
            $profile = UserRepository::profile($userId);
            $saved = trim((string)($profile['phone'] ?? ''));
            if ($phoneGate === null) {
                // ask
                set_user_state($userId, 'mytickets_phone', array());
                $text = $lang === 'fa'
                    ? '🔐 برای مشاهده تیکت‌ها، شماره تلفنی که هنگام ثبت وارد کردید را بفرستید:'
                    : '🔐 To view tickets, send the phone number used when creating the ticket:';
                Presenter::editOrSend($chatId, $msgId, $text);
                return;
            }
            $norm = preg_replace('/[^\d\+]/', '', $phoneGate);
            if ($saved === '' || $norm === '' || ($norm !== $saved && substr($norm, -9) !== substr($saved, -9))) {
                send_message($chatId, $lang === 'fa' ? '❌ شماره مطابقت ندارد.' : '❌ Phone number does not match.');
                return;
            }
            clear_user_state($userId);
        }

        $rows = TicketRepository::forUserDetailed($userId, 10);
        if (!$rows) {
            Presenter::editOrSend($chatId, $msgId, $lang === 'fa' ? '🎫 تیکتی ندارید.' : '🎫 You have no tickets.', main_keyboard($lang));
            return;
        }

        $kb = array('inline_keyboard' => array());
        $lines = array($lang === 'fa' ? '🎫 <b>تیکت‌های من</b>' : '🎫 <b>My Tickets</b>', '');
        foreach ($rows as $t) {
            $st = $t['status'] === 'open' ? '🟢' : '🔴';
            $lines[] = "{$st} #{$t['id']} — " . htmlspecialchars(mb_substr((string)$t['subject'], 0, 40));
            $kb['inline_keyboard'][] = array(array(
                'text' => "#{$t['id']} " . ($lang === 'fa' ? 'مشاهده پاسخ' : 'View replies'),
                'callback_data' => 'ticket:' . $t['id'],
            ));
        }
        $kb['inline_keyboard'][] = array(array('text' => $lang === 'fa' ? '🏠 منو' : '🏠 Menu', 'callback_data' => 'main'));
        Presenter::editOrSend($chatId, $msgId, implode("\n", $lines), $kb);
    }

    public static function showTicketThread(int $chatId, int $userId, int $ticketId, string $lang, int $msgId = 0): void
    {
        $t = TicketRepository::find($ticketId);
        if (!$t || (int)$t['user_id'] !== $userId) {
            Presenter::editOrSend($chatId, $msgId, $lang === 'fa' ? 'تیکت پیدا نشد.' : 'Ticket not found.');
            return;
        }
        $msgs = TicketRepository::messages($ticketId);
        $lines = array(
            ($lang === 'fa' ? '🎫 <b>تیکت' : '🎫 <b>Ticket') . " #{$ticketId}</b> — " . htmlspecialchars((string)$t['status']),
            '',
            '<b>' . htmlspecialchars((string)$t['subject']) . '</b>',
            '',
        );
        if (!empty($t['contact_name']) || !empty($t['phone'])) {
            $lines[] = '👤 ' . htmlspecialchars((string)($t['contact_name'] ?? '-')) . ' · 📞 ' . htmlspecialchars((string)($t['phone'] ?? '-'));
            $lines[] = '';
        }
        foreach ($msgs as $m) {
            $who = !empty($m['is_admin']) ? ($lang === 'fa' ? '🛡️ ادمین' : '🛡️ Admin') : ($lang === 'fa' ? '👤 شما' : '👤 You');
            $lines[] = '<b>' . $who . '</b>';
            $lines[] = htmlspecialchars((string)$m['text']);
            $lines[] = '';
        }
        $kb = array('inline_keyboard' => array(
            array(array('text' => $lang === 'fa' ? '⬅️ تیکت‌ها' : '⬅️ My Tickets', 'callback_data' => 'mytickets')),
            array(array('text' => $lang === 'fa' ? '🏠 منو' : '🏠 Menu', 'callback_data' => 'main')),
        ));
        Presenter::editOrSend($chatId, $msgId, implode("\n", $lines), $kb);
    }

    public static function handleMyTicketsPhone(int $chatId, int $userId, string $text, string $lang): bool
    {
        $st = get_user_state($userId);
        if (!$st || $st['state'] !== 'mytickets_phone') {
            return false;
        }
        if ($text === '/cancel') {
            clear_user_state($userId);
            send_message($chatId, $lang === 'fa' ? 'لغو شد.' : 'Cancelled.', main_keyboard($lang));
            return true;
        }
        self::showMyTickets($chatId, $userId, $lang, 0, $text);
        return true;
    }
}
