<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

use HddLand\Bot\Repositories\TicketRepository;
use HddLand\Bot\Repositories\UserRepository;
use HddLand\Bot\Support\Presenter;

/**
 * Smart ticket intake: identity → questions → problem → admin notify.
 */
final class SupportFormService
{
    public static function ensureSchema(): void
    {
        $pdo = db();
        foreach (array(
            'contact_name' => 'VARCHAR(120) NULL',
            'phone' => 'VARCHAR(40) NULL',
            'customer_id' => 'VARCHAR(80) NULL',
        ) as $col => $def) {
            try {
                $c = $pdo->query('SHOW COLUMNS FROM users LIKE ' . $pdo->quote($col))->fetch();
                if (!$c) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN `{$col}` {$def}");
                }
            } catch (\Throwable $e) {
            }
        }
        foreach (array(
            'contact_name' => 'VARCHAR(120) NULL',
            'phone' => 'VARCHAR(40) NULL',
            'customer_id' => 'VARCHAR(80) NULL',
            'meta_json' => 'TEXT NULL',
            'updated_at' => 'TIMESTAMP NULL DEFAULT NULL',
        ) as $col => $def) {
            try {
                $c = $pdo->query('SHOW COLUMNS FROM tickets LIKE ' . $pdo->quote($col))->fetch();
                if (!$c) {
                    $pdo->exec("ALTER TABLE tickets ADD COLUMN `{$col}` {$def}");
                }
            } catch (\Throwable $e) {
            }
        }
        try {
            $c = $pdo->query("SHOW COLUMNS FROM service_requests LIKE 'meta_json'")->fetch();
            if (!$c) {
                $pdo->exec('ALTER TABLE service_requests ADD COLUMN meta_json TEXT NULL');
            }
        } catch (\Throwable $e) {
        }
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

    public static function start(int $chatId, int $userId, string $lang, string $mode = 'ticket'): void
    {
        self::ensureSchema();
        $intro = (string)cfg('support_intro_' . ($lang === 'fa' ? 'fa' : 'en'), '');
        if ($intro === '') {
            $intro = $lang === 'fa'
                ? "🛠️ <b>ثبت تیکت هوشمند</b>\n\nابتدا مشخصات شما، بعد سؤالات فنی، سپس شرح مشکل.\nلغو: /cancel"
                : "🛠️ <b>Smart ticket</b>\n\nFirst your identity, then technical questions, then the problem.\nCancel: /cancel";
        }

        $kb = array('inline_keyboard' => array());
        foreach (self::links() as $link) {
            $kb['inline_keyboard'][] = array(array('text' => '🔗 ' . $link['label'], 'url' => $link['url']));
        }
        $kb['inline_keyboard'][] = array(array('text' => $lang === 'fa' ? '❌ لغو' : '❌ Cancel', 'callback_data' => 'support_cancel'));

        set_user_state($userId, 'support_form', array(
            'mode' => $mode,
            'step' => 'boot',
            'values' => array(),
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
        $values = isset($p['values']) && is_array($p['values']) ? $p['values'] : array();
        $profile = UserRepository::profile($userId);

        foreach (TicketFieldsService::all() as $field) {
            $key = $field['key'];
            if (array_key_exists($key, $values)) {
                continue;
            }

            // Reuse saved profile for identity fields unless ask_always
            if (empty($field['ask_always'])) {
                $existing = '';
                if ($field['type'] === 'name') {
                    $existing = trim((string)($profile['contact_name'] ?? ''));
                } elseif ($field['type'] === 'phone') {
                    $existing = trim((string)($profile['phone'] ?? ''));
                } elseif ($field['type'] === 'id') {
                    $existing = trim((string)($profile['customer_id'] ?? ''));
                }
                if ($existing !== '') {
                    $values[$key] = $existing;
                    $p['values'] = $values;
                    set_user_state($userId, 'support_form', $p);
                    continue;
                }
            }

            $p['step'] = 'f:' . $key;
            $p['values'] = $values;
            set_user_state($userId, 'support_form', $p);
            $label = $lang === 'fa' ? $field['fa'] : $field['en'];
            $icon = self::iconFor($field['type']);
            $req = !empty($field['required'])
                ? ($lang === 'fa' ? ' (الزامی)' : ' (required)')
                : ($lang === 'fa' ? ' (اختیاری — برای رد شدن - بفرستید)' : ' (optional — send - to skip)');
            send_message($chatId, $icon . ' ' . $label . $req);
            return;
        }

        // Should not reach — message field is always last
        clear_user_state($userId);
        send_message($chatId, $lang === 'fa' ? 'فرم ناقص است. دوباره /ticket بزنید.' : 'Form incomplete. Try /ticket again.');
    }

    public static function handleText(int $chatId, int $userId, string $text, string $lang): bool
    {
        // User follow-up reply to an open/answered ticket
        if (self::handleTicketReplyText($chatId, $userId, $text, $lang)) {
            return true;
        }

        $st = get_user_state($userId);
        if (!$st || $st['state'] !== 'support_form') {
            return false;
        }
        if ($text === '/cancel' || strcasecmp($text, 'cancel') === 0) {
            clear_user_state($userId);
            send_message($chatId, $lang === 'fa' ? '❌ لغو شد.' : '❌ Cancelled.', function_exists('main_reply_keyboard') ? main_reply_keyboard($lang) : main_keyboard($lang));
            return true;
        }

        $p = $st['payload'] ?: array();
        $step = (string)($p['step'] ?? '');
        if (strpos($step, 'f:') !== 0) {
            self::askNext($chatId, $userId, $lang);
            return true;
        }

        $key = substr($step, 2);
        $field = null;
        foreach (TicketFieldsService::all() as $f) {
            if ($f['key'] === $key) {
                $field = $f;
                break;
            }
        }
        if (!$field) {
            self::askNext($chatId, $userId, $lang);
            return true;
        }

        $val = trim($text);
        if ($val === '-' || strcasecmp($val, 'skip') === 0) {
            if (!empty($field['required'])) {
                send_message($chatId, $lang === 'fa' ? 'این مورد الزامی است.' : 'This field is required.');
                return true;
            }
            $val = '';
        } else {
            $err = self::validate($field, $val, $lang);
            if ($err !== null) {
                send_message($chatId, $err);
                return true;
            }
            if ($field['type'] === 'phone') {
                $val = preg_replace('/[^\d\+]/', '', $val) ?: $val;
            }
        }

        $values = isset($p['values']) && is_array($p['values']) ? $p['values'] : array();
        $values[$key] = $val;
        $p['values'] = $values;

        if ($field['type'] === 'message') {
            self::finish($chatId, $userId, $lang, $p, $val);
            return true;
        }

        set_user_state($userId, 'support_form', $p);
        self::askNext($chatId, $userId, $lang);
        return true;
    }

    /** @param array<string,mixed> $field */
    private static function validate(array $field, string $val, string $lang): ?string
    {
        if ($val === '' && !empty($field['required'])) {
            return $lang === 'fa' ? 'این مورد الزامی است.' : 'This field is required.';
        }
        if ($val === '') {
            return null;
        }
        switch ($field['type']) {
            case 'name':
                if (mb_strlen($val) < 2) {
                    return $lang === 'fa' ? 'نام معتبر وارد کنید.' : 'Please enter a valid name.';
                }
                break;
            case 'phone':
                $phone = preg_replace('/[^\d\+]/', '', $val);
                if ($phone === null || strlen($phone) < 7) {
                    return $lang === 'fa' ? 'شماره موبایل معتبر وارد کنید.' : 'Please enter a valid mobile number.';
                }
                break;
            case 'id':
                if (mb_strlen($val) < 3) {
                    return $lang === 'fa' ? 'کد/شناسه معتبر وارد کنید.' : 'Please enter a valid ID.';
                }
                break;
            case 'message':
                if (mb_strlen($val) < 5) {
                    return $lang === 'fa' ? 'لطفاً مشکل را کامل‌تر توضیح دهید.' : 'Please describe the problem in more detail.';
                }
                break;
        }
        return null;
    }

    private static function iconFor(string $type): string
    {
        $map = array('name' => '👤', 'phone' => '📞', 'id' => '🆔', 'text' => '❓', 'message' => '📝');
        return $map[$type] ?? '❓';
    }

    /** @param array<string,mixed> $p */
    private static function finish(int $chatId, int $userId, string $lang, array $p, string $message): void
    {
        self::ensureSchema();
        $values = isset($p['values']) && is_array($p['values']) ? $p['values'] : array();
        $mode = (string)($p['mode'] ?? 'ticket');

        $name = '';
        $phone = '';
        $customerId = '';
        $answers = array();
        $metaLines = array();
        foreach (TicketFieldsService::all() as $f) {
            $v = trim((string)($values[$f['key']] ?? ''));
            if ($f['type'] === 'name') {
                $name = $v;
            } elseif ($f['type'] === 'phone') {
                $phone = $v;
            } elseif ($f['type'] === 'id') {
                $customerId = $v;
            } elseif ($f['type'] === 'text' && $v !== '') {
                $answers[$f['key']] = $v;
            }
            if ($v === '' || $f['type'] === 'message') {
                continue;
            }
            $label = $lang === 'fa' ? $f['fa'] : $f['en'];
            $metaLines[] = $label . ': ' . $v;
        }

        UserRepository::saveContact($userId, $name, $phone, $customerId);

        $full = $message;
        if ($metaLines) {
            $full = $message . "\n\n——\n" . implode("\n", $metaLines);
        }
        $subject = mb_substr($message, 0, 80);
        $metaJson = json_encode(array(
            'contact_name' => $name,
            'phone' => $phone,
            'customer_id' => $customerId,
            'answers' => $answers,
            'values' => $values,
            'mode' => $mode,
            'lang' => $lang,
        ), JSON_UNESCAPED_UNICODE);

        $tid = TicketRepository::createAdvanced($userId, $subject, $full, $name, $phone, (string)$metaJson, $customerId);

        try {
            if (function_exists('create_service_request')) {
                $rid = create_service_request($userId, 'support', $full, 'Support: ' . $subject);
                db()->prepare('UPDATE service_requests SET contact_info=?, meta_json=? WHERE id=?')
                    ->execute(array(trim($name . ' / ' . $phone . ' / ' . $customerId, ' /'), $metaJson, $rid));
                set_user_state($userId, 'await_media', array('type' => 'support', 'request_id' => $rid, 'ticket_id' => $tid));
            } else {
                clear_user_state($userId);
            }
        } catch (\Throwable $e) {
            clear_user_state($userId);
        }

        if (function_exists('notify_staff')) {
            notify_staff(
                "🆕 <b>تیکت جدید #{$tid}</b>\n"
                . ($name !== '' ? "👤 {$name}\n" : '')
                . ($phone !== '' ? "📞 {$phone}\n" : '')
                . ($customerId !== '' ? "🆔 {$customerId}\n" : '')
                . "TG: <code>{$userId}</code>\n\n"
                . htmlspecialchars($message) . "\n\n"
                . 'Admin: reply in panel or /replyticket ' . $tid . ' …',
                'tickets'
            );
        }

        $msg = $lang === 'fa'
            ? "✅ تیکت <b>#{$tid}</b> ثبت شد و برای پشتیبانی ارسال شد.\n\nاگر عکس/فیلم دارید همین الان بفرستید.\nپایان: /done"
            : "✅ Ticket <b>#{$tid}</b> created and sent to support.\n\nSend photo/video now if needed.\nFinish: /done";
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
                set_user_state($userId, 'mytickets_phone', array());
                $text = $lang === 'fa'
                    ? '🔐 برای مشاهده تیکت‌ها، شماره موبایلی که هنگام ثبت وارد کردید را بفرستید:'
                    : '🔐 To view tickets, send the mobile number used when creating the ticket:';
                Presenter::editOrSend($chatId, $msgId, $text);
                return;
            }
            $norm = preg_replace('/[^\d\+]/', '', $phoneGate);
            if ($saved === '' || $norm === '' || ($norm !== $saved && substr((string)$norm, -9) !== substr($saved, -9))) {
                send_message($chatId, $lang === 'fa' ? '❌ شماره مطابقت ندارد.' : '❌ Phone number does not match.');
                return;
            }
            clear_user_state($userId);
        }

        $rows = TicketRepository::forUserDetailed($userId, 12);
        if (!$rows) {
            Presenter::editOrSend($chatId, $msgId, $lang === 'fa' ? '🎫 تیکتی ندارید.' : '🎫 You have no tickets.', main_keyboard($lang));
            return;
        }

        $kb = array('inline_keyboard' => array());
        $lines = array($lang === 'fa' ? '🎫 <b>تیکت‌های من</b>' : '🎫 <b>My Tickets</b>', '');
        foreach ($rows as $t) {
            $lines[] = self::statusEmoji((string)$t['status']) . " #{$t['id']} — " . htmlspecialchars(mb_substr((string)$t['subject'], 0, 40));
            $kb['inline_keyboard'][] = array(array(
                'text' => "#{$t['id']} " . ($lang === 'fa' ? 'مشاهده / پاسخ' : 'View / reply'),
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
            ($lang === 'fa' ? '🎫 <b>تیکت' : '🎫 <b>Ticket') . " #{$ticketId}</b> — " . self::statusLabel((string)$t['status'], $lang),
            '',
            '<b>' . htmlspecialchars((string)$t['subject']) . '</b>',
            '',
        );
        $idBits = array();
        if (!empty($t['contact_name'])) {
            $idBits[] = '👤 ' . htmlspecialchars((string)$t['contact_name']);
        }
        if (!empty($t['phone'])) {
            $idBits[] = '📞 ' . htmlspecialchars((string)$t['phone']);
        }
        if (!empty($t['customer_id'])) {
            $idBits[] = '🆔 ' . htmlspecialchars((string)$t['customer_id']);
        }
        if ($idBits) {
            $lines[] = implode(' · ', $idBits);
            $lines[] = '';
        }
        foreach ($msgs as $m) {
            $who = !empty($m['is_admin']) ? ($lang === 'fa' ? '🛡️ پشتیبانی' : '🛡️ Support') : ($lang === 'fa' ? '👤 شما' : '👤 You');
            $lines[] = '<b>' . $who . '</b>';
            $lines[] = htmlspecialchars((string)$m['text']);
            $lines[] = '';
        }

        $kbRows = array();
        if ((string)$t['status'] !== 'closed') {
            $kbRows[] = array(array(
                'text' => $lang === 'fa' ? '💬 پاسخ به پشتیبانی' : '💬 Reply to support',
                'callback_data' => 'ticket_reply:' . $ticketId,
            ));
        }
        $kbRows[] = array(array('text' => $lang === 'fa' ? '⬅️ تیکت‌ها' : '⬅️ My Tickets', 'callback_data' => 'mytickets'));
        $kbRows[] = array(array('text' => $lang === 'fa' ? '🏠 منو' : '🏠 Menu', 'callback_data' => 'main'));
        Presenter::editOrSend($chatId, $msgId, implode("\n", $lines), array('inline_keyboard' => $kbRows));
    }

    public static function beginUserReply(int $chatId, int $userId, int $ticketId, string $lang, int $msgId = 0): void
    {
        $t = TicketRepository::find($ticketId);
        if (!$t || (int)$t['user_id'] !== $userId || (string)$t['status'] === 'closed') {
            Presenter::editOrSend($chatId, $msgId, $lang === 'fa' ? 'امکان پاسخ نیست.' : 'Cannot reply.');
            return;
        }
        set_user_state($userId, 'ticket_reply', array('ticket_id' => $ticketId));
        Presenter::editOrSend(
            $chatId,
            $msgId,
            $lang === 'fa'
                ? "💬 پاسخ خود برای تیکت #{$ticketId} را بنویسید:\nلغو: /cancel"
                : "💬 Type your reply for ticket #{$ticketId}:\nCancel: /cancel"
        );
    }

    public static function handleTicketReplyText(int $chatId, int $userId, string $text, string $lang): bool
    {
        $st = get_user_state($userId);
        if (!$st || $st['state'] !== 'ticket_reply') {
            return false;
        }
        if ($text === '/cancel' || strcasecmp($text, 'cancel') === 0) {
            clear_user_state($userId);
            send_message($chatId, $lang === 'fa' ? 'لغو شد.' : 'Cancelled.');
            return true;
        }
        $tid = (int)($st['payload']['ticket_id'] ?? 0);
        $t = TicketRepository::find($tid);
        if (!$t || (int)$t['user_id'] !== $userId || (string)$t['status'] === 'closed') {
            clear_user_state($userId);
            send_message($chatId, $lang === 'fa' ? 'تیکت معتبر نیست.' : 'Invalid ticket.');
            return true;
        }
        $body = trim($text);
        if ($body === '') {
            send_message($chatId, $lang === 'fa' ? 'متن پاسخ خالی است.' : 'Reply cannot be empty.');
            return true;
        }
        TicketRepository::addUserReply($tid, $userId, $body);
        TicketRepository::setStatus($tid, 'open');
        clear_user_state($userId);
        if (function_exists('notify_staff')) {
            notify_staff(
                "💬 پاسخ مشتری روی تیکت <b>#{$tid}</b>\n"
                . 'TG: <code>' . $userId . "</code>\n\n"
                . htmlspecialchars($body),
                'tickets'
            );
        }
        send_message(
            $chatId,
            $lang === 'fa' ? "✅ پاسخ شما برای تیکت #{$tid} ارسال شد." : "✅ Your reply for ticket #{$tid} was sent.",
            array('inline_keyboard' => array(
                array(array('text' => $lang === 'fa' ? '🎫 مشاهده تیکت' : '🎫 View ticket', 'callback_data' => 'ticket:' . $tid)),
            ))
        );
        return true;
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

    public static function statusEmoji(string $status): string
    {
        $map = array('open' => '🟢', 'answered' => '🔵', 'waiting' => '🟡', 'closed' => '🔴');
        return $map[$status] ?? '⚪';
    }

    public static function statusLabel(string $status, string $lang = 'en'): string
    {
        if ($lang === 'fa') {
            $map = array('open' => 'باز', 'answered' => 'پاسخ‌داده‌شده', 'waiting' => 'منتظر مشتری', 'closed' => 'بسته‌شده');
            return $map[$status] ?? $status;
        }
        return $status;
    }
}
