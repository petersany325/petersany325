<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

use HddLand\Bot\Support\Presenter;

/**
 * Sample SeDiv account flow:
 * register → payment receipt → admin approve → license TXT → activation file → history
 * License mailbox: sedivlic@list.ru (configurable).
 */
final class LicenseFlowService
{
    public static function ensureSchema(): void
    {
        UserOptionsService::ensureSchema();
        $pdo = db();
        foreach (array(
            'first_name' => 'VARCHAR(80) NULL',
            'last_name' => 'VARCHAR(80) NULL',
            'email' => 'VARCHAR(160) NULL',
            'profile_registered_at' => 'TIMESTAMP NULL DEFAULT NULL',
        ) as $col => $def) {
            try {
                $c = $pdo->query('SHOW COLUMNS FROM users LIKE ' . $pdo->quote($col))->fetch();
                if (!$c) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN `{$col}` {$def}");
                }
            } catch (\Throwable $e) {
            }
        }

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS payment_receipts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                telegram_id BIGINT NOT NULL,
                method VARCHAR(20) NOT NULL,
                note TEXT NULL,
                file_id VARCHAR(255) NULL,
                file_unique_id VARCHAR(120) NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                admin_note VARCHAR(255) NULL,
                order_code VARCHAR(40) NULL,
                reviewed_by BIGINT NULL,
                reviewed_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_tg (telegram_id),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS license_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                telegram_id BIGINT NOT NULL,
                receipt_id INT NULL,
                order_code VARCHAR(40) NOT NULL,
                status VARCHAR(40) NOT NULL DEFAULT 'registered',
                license_path VARCHAR(255) NULL,
                activation_path VARCHAR(255) NULL,
                meta_json TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                UNIQUE KEY uniq_order (order_code),
                KEY idx_tg (telegram_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                telegram_id BIGINT NOT NULL,
                order_id INT NULL,
                event_code VARCHAR(60) NOT NULL,
                detail TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_tg (telegram_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $dir = self::storageDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    public static function storageDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/licenses';
    }

    public static function licenseMailbox(): string
    {
        $m = trim((string)cfg('license_mailbox', 'sedivlic@list.ru'));
        return $m !== '' ? $m : 'sedivlic@list.ru';
    }

    public static function logEvent(int $telegramId, string $code, string $detail = '', ?int $orderId = null): void
    {
        self::ensureSchema();
        try {
            db()->prepare(
                'INSERT INTO user_events (telegram_id, order_id, event_code, detail) VALUES (?,?,?,?)'
            )->execute(array($telegramId, $orderId, $code, mb_substr($detail, 0, 2000)));
        } catch (\Throwable $e) {
        }
    }

    public static function isRegistered(int $userId): bool
    {
        self::ensureSchema();
        try {
            $st = db()->prepare('SELECT email, first_name FROM users WHERE telegram_id=? LIMIT 1');
            $st->execute(array($userId));
            $row = $st->fetch();
            return $row && trim((string)($row['email'] ?? '')) !== '' && trim((string)($row['first_name'] ?? '')) !== '';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function deny(int $chatId, string $lang, string $optCode): void
    {
        $msg = $lang === 'fa'
            ? "⛔ دسترسی به این اپشن باز نیست.\nکد: <code>{$optCode}</code>\nاز ادمین بخواهید دسترسی را باز کند."
            : "⛔ This option is closed for your account.\nCode: <code>{$optCode}</code>\nAsk admin to open access.";
        send_message($chatId, $msg, self::accountKeyboard($lang, $chatId > 0 ? $chatId : 0));
    }

    /** @param int $userId telegram id for option filtering */
    public static function accountKeyboard(string $lang, int $userId = 0): array
    {
        $kb = array('inline_keyboard' => array());
        if ($userId > 0) {
            foreach (UserOptionsService::openOptionsFor($userId) as $opt) {
                $code = (string)$opt['code'];
                $title = UserOptionsService::title($opt, $lang);
                $cb = 'acct:' . $code;
                $kb['inline_keyboard'][] = array(array('text' => $title, 'callback_data' => $cb));
            }
        } else {
            $kb['inline_keyboard'][] = array(array(
                'text' => $lang === 'fa' ? '👤 حساب من' : '👤 My Account',
                'callback_data' => 'acct',
            ));
        }
        $kb['inline_keyboard'][] = array(array(
            'text' => $lang === 'fa' ? '🏠 منوی اصلی' : '🏠 Main Menu',
            'callback_data' => 'main',
        ));
        return $kb;
    }

    /**
     * Entry from Main Menu → 🔑 License
     * Continues the sample pipeline: register → receipt → license → activation.
     */
    public static function showLicenseEntry(int $chatId, int $msgId, int $userId, string $lang): void
    {
        self::ensureSchema();
        UserOptionsService::ensureSchema();

        // Always keep the entry option path open for the License menu itself
        if (!UserOptionsService::isOpen($userId, 'register')) {
            UserOptionsService::setUserAccess($userId, 'register', true);
        }
        if (!UserOptionsService::isOpen($userId, 'pay_receipt')) {
            UserOptionsService::setUserAccess($userId, 'pay_receipt', true);
        }
        if (!UserOptionsService::isOpen($userId, 'history')) {
            UserOptionsService::setUserAccess($userId, 'history', true);
        }

        if (!self::isRegistered($userId)) {
            $text = $lang === 'fa'
                ? "🔑 <b>مرکز لایسنس SeDiv</b>\n\nبرای ادامه باید ثبت‌نام کنید (نام، نام‌خانوادگی، ایمیل).\nبعد می‌توانید فیش PayPal / Western Union بفرستید."
                : "🔑 <b>SeDiv License Center</b>\n\nPlease register first (name, family name, email).\nThen you can submit a PayPal / Western Union receipt.";
            $kb = array('inline_keyboard' => array(
                array(array('text' => $lang === 'fa' ? '📝 شروع ثبت‌نام' : '📝 Start registration', 'callback_data' => 'acct:register')),
                array(array('text' => $lang === 'fa' ? '🏠 منوی اصلی' : '🏠 Main Menu', 'callback_data' => 'main')),
            ));
            if ($msgId > 0) {
                Presenter::editOrSend($chatId, $msgId, $text, $kb);
            } else {
                send_message($chatId, $text, $kb);
            }
            return;
        }

        // Registered: check latest order status
        $st = db()->prepare('SELECT * FROM license_orders WHERE telegram_id=? ORDER BY id DESC LIMIT 1');
        $st->execute(array($userId));
        $order = $st->fetch() ?: null;
        $status = $order ? (string)$order['status'] : '';

        $pending = false;
        try {
            $p = db()->prepare("SELECT id FROM payment_receipts WHERE telegram_id=? AND status='pending' ORDER BY id DESC LIMIT 1");
            $p->execute(array($userId));
            $pending = (bool)$p->fetchColumn();
        } catch (\Throwable $e) {
        }

        if ($pending || $status === 'receipt_submitted') {
            $text = $lang === 'fa'
                ? "🔑 <b>مرکز لایسنس</b>\n\n⏳ فیش شما در صف تایید ادمین است.\nبعد از تایید، فایل لایسنس برایتان ارسال می‌شود."
                : "🔑 <b>License Center</b>\n\n⏳ Your receipt is waiting for admin approval.\nAfter approval, the license file will be sent.";
            $kb = array('inline_keyboard' => array(
                array(array('text' => $lang === 'fa' ? '📋 گزارش مراحل' : '📋 My reports', 'callback_data' => 'acct:history')),
                array(array('text' => $lang === 'fa' ? '🏠 منوی اصلی' : '🏠 Main Menu', 'callback_data' => 'main')),
            ));
            if ($msgId > 0) {
                Presenter::editOrSend($chatId, $msgId, $text, $kb);
            } else {
                send_message($chatId, $text, $kb);
            }
            return;
        }

        $hasLicense = $order && in_array($status, array('license_sent', 'activation_uploaded', 'activation_emailed', 'activation_ready', 'downloaded'), true);
        if (!$hasLicense) {
            $text = $lang === 'fa'
                ? "🔑 <b>مرکز لایسنس SeDiv</b>\n\nثبت‌نام شما کامل است.\nمرحله بعد: ارسال فیش واریزی PayPal یا Western Union."
                : "🔑 <b>SeDiv License Center</b>\n\nRegistration is complete.\nNext: submit a PayPal or Western Union payment receipt.";
            $kb = array('inline_keyboard' => array(
                array(array('text' => $lang === 'fa' ? '💵 ارسال فیش واریزی' : '💵 Submit payment receipt', 'callback_data' => 'acct:pay_receipt')),
                array(array('text' => $lang === 'fa' ? '📋 گزارش مراحل' : '📋 My reports', 'callback_data' => 'acct:history')),
                array(array('text' => $lang === 'fa' ? '🏠 منوی اصلی' : '🏠 Main Menu', 'callback_data' => 'main')),
            ));
            if ($msgId > 0) {
                Presenter::editOrSend($chatId, $msgId, $text, $kb);
            } else {
                send_message($chatId, $text, $kb);
            }
            return;
        }

        // Has license — show license + activation options
        $text = $lang === 'fa'
            ? "🔑 <b>مرکز لایسنس</b>\n\nوضعیت سفارش: <code>" . htmlspecialchars($status) . "</code>\nکد: <code>" . htmlspecialchars((string)$order['order_code']) . "</code>\n\nلایسنس را دوباره بگیرید یا فایل اکتیو (۲۰۰/۳۰۰KB) بفرستید."
            : "🔑 <b>License Center</b>\n\nOrder status: <code>" . htmlspecialchars($status) . "</code>\nCode: <code>" . htmlspecialchars((string)$order['order_code']) . "</code>\n\nRe-download license or upload activation file (200/300KB).";
        $kb = array('inline_keyboard' => array(
            array(array('text' => $lang === 'fa' ? '⬇️ دریافت لایسنس TXT' : '⬇️ Get license TXT', 'callback_data' => 'acct:license')),
            array(array('text' => $lang === 'fa' ? '📦 ارسال فایل اکتیو' : '📦 Send activation file', 'callback_data' => 'acct:activation')),
            array(array('text' => $lang === 'fa' ? '💵 فیش جدید' : '💵 New receipt', 'callback_data' => 'acct:pay_receipt')),
            array(array('text' => $lang === 'fa' ? '📋 گزارش مراحل' : '📋 My reports', 'callback_data' => 'acct:history')),
            array(array('text' => $lang === 'fa' ? '🏠 منوی اصلی' : '🏠 Main Menu', 'callback_data' => 'main')),
        ));
        if ($msgId > 0) {
            Presenter::editOrSend($chatId, $msgId, $text, $kb);
        } else {
            send_message($chatId, $text, $kb);
        }
    }

    public static function showAccount(int $chatId, int $msgId, int $userId, string $lang): void
    {
        // My Account / License menu both open the guided license center
        self::showLicenseEntry($chatId, $msgId, $userId, $lang);
    }

    public static function handleCallback(string $data, string $cbId, int $chatId, int $msgId, int $userId, string $lang): bool
    {
        if ($data !== 'acct' && strpos($data, 'acct:') !== 0 && strpos($data, 'rcpt:') !== 0) {
            return false;
        }
        self::ensureSchema();

        if (strpos($data, 'rcpt:') === 0) {
            return self::handleAdminReceiptCallback($data, $cbId, $chatId, $userId, $lang);
        }

        answer_callback($cbId);
        if ($data === 'acct') {
            self::showAccount($chatId, $msgId, $userId, $lang);
            return true;
        }

        $code = substr($data, 5);
        if ($code === 'pay_paypal' || $code === 'pay_wu') {
            if (!UserOptionsService::isOpen($userId, 'pay_receipt')) {
                self::deny($chatId, $lang, 'pay_receipt');
                return true;
            }
            self::beginReceiptUpload($chatId, $userId, $lang, $code === 'pay_paypal' ? 'paypal' : 'wu');
            return true;
        }

        if (!UserOptionsService::isOpen($userId, $code)) {
            self::deny($chatId, $lang, $code);
            return true;
        }

        switch ($code) {
            case 'register':
                self::startRegister($chatId, $userId, $lang);
                break;
            case 'support':
                SupportFormService::start($chatId, $userId, $lang, 'support');
                break;
            case 'pay_receipt':
                self::startPayReceipt($chatId, $msgId, $userId, $lang);
                break;
            case 'license':
                self::showLicense($chatId, $userId, $lang);
                break;
            case 'activation':
                self::startActivation($chatId, $userId, $lang);
                break;
            case 'history':
                self::showHistory($chatId, $userId, $lang);
                break;
            default:
                send_message(
                    $chatId,
                    $lang === 'fa' ? 'این اپشن هنوز به فلو وصل نشده (نمونه).' : 'This option is not wired yet (sample).',
                    self::accountKeyboard($lang, $userId)
                );
        }
        return true;
    }

    public static function startRegister(int $chatId, int $userId, string $lang): void
    {
        if (self::isRegistered($userId)) {
            send_message(
                $chatId,
                $lang === 'fa' ? '✅ قبلاً ثبت‌نام کرده‌اید.' : '✅ You are already registered.',
                self::accountKeyboard($lang, $userId)
            );
            return;
        }
        set_user_state($userId, 'license_register', array('step' => 'first_name'));
        send_message(
            $chatId,
            $lang === 'fa' ? "📝 <b>ثبت‌نام</b>\n\nنام کوچک را بفرستید:\nلغو: /cancel" : "📝 <b>Registration</b>\n\nSend your first name:\nCancel: /cancel"
        );
    }

    public static function startPayReceipt(int $chatId, int $msgId, int $userId, string $lang): void
    {
        if (!self::isRegistered($userId)) {
            send_message(
                $chatId,
                $lang === 'fa' ? 'اول ثبت‌نام کنید (نام + ایمیل).' : 'Please register first (name + email).',
                self::accountKeyboard($lang, $userId)
            );
            return;
        }
        $kb = array('inline_keyboard' => array(
            array(array('text' => '💳 PayPal', 'callback_data' => 'acct:pay_paypal')),
            array(array('text' => '🏦 Western Union', 'callback_data' => 'acct:pay_wu')),
            array(array('text' => $lang === 'fa' ? '⬅️ بازگشت' : '⬅️ Back', 'callback_data' => 'acct')),
        ));
        $text = $lang === 'fa'
            ? "💵 <b>ثبت فیش واریزی</b>\n\nروش پرداخت را انتخاب کنید، بعد عکس/فایل فیش را بفرستید.\nصندوق لایسنس: <code>" . htmlspecialchars(self::licenseMailbox()) . '</code>'
            : "💵 <b>Payment receipt</b>\n\nChoose method, then send photo/PDF of the receipt.\nLicense mailbox: <code>" . htmlspecialchars(self::licenseMailbox()) . '</code>';
        Presenter::editOrSend($chatId, $msgId, $text, $kb);
    }

    public static function beginReceiptUpload(int $chatId, int $userId, string $lang, string $method): void
    {
        set_user_state($userId, 'license_receipt', array('method' => $method));
        $label = $method === 'paypal' ? 'PayPal' : 'Western Union';
        send_message(
            $chatId,
            $lang === 'fa'
                ? "📎 فیش <b>{$label}</b> را به‌صورت عکس یا فایل بفرستید.\nمی‌توانید یک توضیح کوتاه هم در کپشن بنویسید.\nلغو: /cancel"
                : "📎 Send your <b>{$label}</b> receipt as photo or document.\nOptional caption note is OK.\nCancel: /cancel"
        );
    }

    public static function showLicense(int $chatId, int $userId, string $lang): void
    {
        $st = db()->prepare(
            "SELECT * FROM license_orders WHERE telegram_id=? AND status IN ('license_sent','activation_uploaded','activation_emailed','activation_ready','downloaded') ORDER BY id DESC LIMIT 1"
        );
        $st->execute(array($userId));
        $order = $st->fetch();
        if (!$order) {
            send_message(
                $chatId,
                $lang === 'fa' ? 'هنوز لایسنس تایید‌شده‌ای ندارید.' : 'No approved license yet.',
                self::accountKeyboard($lang, $userId)
            );
            return;
        }
        $path = (string)($order['license_path'] ?? '');
        if ($path !== '' && is_file($path)) {
            self::sendLocalDocument($chatId, $path, $lang === 'fa' ? '🔑 لایسنس SeDiv شما' : '🔑 Your SeDiv license');
            self::logEvent($userId, 'license_resent', 'user requested license', (int)$order['id']);
        } else {
            send_message($chatId, $lang === 'fa' ? 'فایل لایسنس پیدا نشد — با ادمین تماس بگیرید.' : 'License file missing — contact admin.');
        }
        send_message($chatId, $lang === 'fa' ? 'وضعیت سفارش: <code>' . htmlspecialchars((string)$order['status']) . '</code>' : 'Order status: <code>' . htmlspecialchars((string)$order['status']) . '</code>', self::accountKeyboard($lang, $userId));
    }

    public static function startActivation(int $chatId, int $userId, string $lang): void
    {
        set_user_state($userId, 'license_activation', array());
        send_message(
            $chatId,
            $lang === 'fa'
                ? "📦 فایل اکتیو را بفرستید (حدود ۲۰۰ یا ۳۰۰ کیلوبایت).\nبات آن را به <code>" . htmlspecialchars(self::licenseMailbox()) . "</code> ایمیل می‌کند.\nلغو: /cancel"
                : "📦 Send activation file (~200 or 300 KB).\nBot will email it to <code>" . htmlspecialchars(self::licenseMailbox()) . "</code>.\nCancel: /cancel"
        );
    }

    public static function showHistory(int $chatId, int $userId, string $lang): void
    {
        $st = db()->prepare('SELECT event_code, detail, created_at FROM user_events WHERE telegram_id=? ORDER BY id DESC LIMIT 15');
        $st->execute(array($userId));
        $rows = $st->fetchAll() ?: array();
        if (!$rows) {
            send_message($chatId, $lang === 'fa' ? 'گزارشی ثبت نشده.' : 'No reports yet.', self::accountKeyboard($lang, $userId));
            return;
        }
        $lines = array($lang === 'fa' ? '📋 <b>گزارش مراحل</b>' : '📋 <b>Activity report</b>');
        foreach ($rows as $r) {
            $lines[] = '• <code>' . htmlspecialchars((string)$r['created_at']) . '</code> — <b>' . htmlspecialchars((string)$r['event_code']) . '</b>'
                . ($r['detail'] ? "\n  " . htmlspecialchars(mb_substr((string)$r['detail'], 0, 120)) : '');
        }
        send_message($chatId, implode("\n", $lines), self::accountKeyboard($lang, $userId));
    }

    /** Handle text steps for register / cancel */
    public static function handleText(int $chatId, int $userId, string $text, string $lang): bool
    {
        $state = get_user_state($userId);
        if (!$state) {
            return false;
        }
        $name = (string)($state['state'] ?? '');
        if ($name === 'license_register') {
            return self::handleRegisterText($chatId, $userId, $text, $lang, $state);
        }
        if ($text === '/cancel' || strpos($text, '/cancel') === 0) {
            if (in_array($name, array('license_receipt', 'license_activation', 'license_register'), true)) {
                clear_user_state($userId);
                send_message($chatId, $lang === 'fa' ? '❌ لغو شد.' : '❌ Cancelled.', self::accountKeyboard($lang, $userId));
                return true;
            }
        }
        return false;
    }

    private static function handleRegisterText(int $chatId, int $userId, string $text, string $lang, array $state): bool
    {
        $text = trim($text);
        if ($text === '' || $text[0] === '/') {
            if ($text === '/cancel' || strpos($text, '/cancel') === 0) {
                clear_user_state($userId);
                send_message($chatId, $lang === 'fa' ? '❌ لغو شد.' : '❌ Cancelled.', self::accountKeyboard($lang, $userId));
                return true;
            }
            return false;
        }
        $payload = is_array($state['payload'] ?? null) ? $state['payload'] : array();
        $step = (string)($payload['step'] ?? 'first_name');
        if ($step === 'first_name') {
            $payload['first_name'] = mb_substr($text, 0, 80);
            $payload['step'] = 'last_name';
            set_user_state($userId, 'license_register', $payload);
            send_message($chatId, $lang === 'fa' ? 'نام‌خانوادگی را بفرستید:' : 'Send your last name:');
            return true;
        }
        if ($step === 'last_name') {
            $payload['last_name'] = mb_substr($text, 0, 80);
            $payload['step'] = 'email';
            set_user_state($userId, 'license_register', $payload);
            send_message($chatId, $lang === 'fa' ? 'ایمیل را بفرستید:' : 'Send your email:');
            return true;
        }
        if ($step === 'email') {
            if (!filter_var($text, FILTER_VALIDATE_EMAIL)) {
                send_message($chatId, $lang === 'fa' ? 'ایمیل نامعتبر است. دوباره بفرستید:' : 'Invalid email. Try again:');
                return true;
            }
            self::ensureSchema();
            db()->prepare(
                'UPDATE users SET first_name=?, last_name=?, email=?, contact_name=?, profile_registered_at=NOW() WHERE telegram_id=?'
            )->execute(array(
                $payload['first_name'] ?? '',
                $payload['last_name'] ?? '',
                $text,
                trim(($payload['first_name'] ?? '') . ' ' . ($payload['last_name'] ?? '')),
                $userId,
            ));
            clear_user_state($userId);
            self::logEvent($userId, 'registered', $text);
            // Ensure pay_receipt stays open after register
            UserOptionsService::setUserAccess($userId, 'pay_receipt', true);
            UserOptionsService::setUserAccess($userId, 'history', true);
            send_message(
                $chatId,
                $lang === 'fa'
                    ? "✅ ثبت‌نام شد.\nحالا می‌توانید فیش واریزی را از پنل حساب ارسال کنید."
                    : "✅ Registered.\nYou can now submit a payment receipt from My Account.",
                self::accountKeyboard($lang, $userId)
            );
            return true;
        }
        return true;
    }

    /** Handle photo/document for receipt or activation */
    public static function handleMedia(array $message, string $lang): bool
    {
        $userId = (int)($message['from']['id'] ?? 0);
        $chatId = (int)($message['chat']['id'] ?? 0);
        if ($userId <= 0 || $chatId <= 0) {
            return false;
        }
        $state = get_user_state($userId);
        if (!$state) {
            return false;
        }
        $name = (string)($state['state'] ?? '');
        if ($name === 'license_receipt') {
            return self::saveReceiptMedia($message, $userId, $chatId, $lang, $state);
        }
        if ($name === 'license_activation') {
            return self::saveActivationMedia($message, $userId, $chatId, $lang);
        }
        return false;
    }

    private static function extractFile(array $message): ?array
    {
        if (!empty($message['document'])) {
            $d = $message['document'];
            return array(
                'file_id' => (string)$d['file_id'],
                'file_unique_id' => (string)($d['file_unique_id'] ?? ''),
                'file_size' => (int)($d['file_size'] ?? 0),
                'file_name' => (string)($d['file_name'] ?? 'file.bin'),
                'kind' => 'document',
            );
        }
        if (!empty($message['photo']) && is_array($message['photo'])) {
            $photos = $message['photo'];
            $best = $photos[count($photos) - 1];
            return array(
                'file_id' => (string)$best['file_id'],
                'file_unique_id' => (string)($best['file_unique_id'] ?? ''),
                'file_size' => (int)($best['file_size'] ?? 0),
                'file_name' => 'receipt.jpg',
                'kind' => 'photo',
            );
        }
        return null;
    }

    private static function saveReceiptMedia(array $message, int $userId, int $chatId, string $lang, array $state): bool
    {
        if (!UserOptionsService::isOpen($userId, 'pay_receipt')) {
            clear_user_state($userId);
            self::deny($chatId, $lang, 'pay_receipt');
            return true;
        }
        $file = self::extractFile($message);
        if (!$file) {
            send_message($chatId, $lang === 'fa' ? 'فایل/عکس معتبر بفرستید.' : 'Send a valid photo or file.');
            return true;
        }
        $method = (string)(($state['payload']['method'] ?? 'paypal'));
        $note = trim((string)($message['caption'] ?? ''));
        $orderCode = 'ORD' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        self::ensureSchema();
        db()->prepare(
            'INSERT INTO payment_receipts (telegram_id, method, note, file_id, file_unique_id, status, order_code) VALUES (?,?,?,?,?,?,?)'
        )->execute(array($userId, $method, $note, $file['file_id'], $file['file_unique_id'], 'pending', $orderCode));
        $rid = (int)db()->lastInsertId();
        db()->prepare(
            'INSERT INTO license_orders (telegram_id, receipt_id, order_code, status) VALUES (?,?,?,?)'
        )->execute(array($userId, $rid, $orderCode, 'receipt_submitted'));
        $oid = (int)db()->lastInsertId();
        self::logEvent($userId, 'receipt_submitted', $method . ' #' . $rid, $oid);
        clear_user_state($userId);

        send_message(
            $chatId,
            $lang === 'fa'
                ? "✅ فیش ثبت شد.\nکد سفارش: <code>{$orderCode}</code>\nپس از تایید ادمین، لایسنس برایتان ارسال می‌شود."
                : "✅ Receipt submitted.\nOrder: <code>{$orderCode}</code>\nAfter admin approval, your license will be sent.",
            self::accountKeyboard($lang, $userId)
        );

        // Notify admins + forward file
        $caption = "🧾 Receipt #{$rid}\nOrder: {$orderCode}\nUser: {$userId}\nMethod: {$method}\n" . ($note !== '' ? "Note: {$note}\n" : '')
            . "Approve: /approvereceipt {$rid}\nReject: /rejectreceipt {$rid}";
        $kb = array('inline_keyboard' => array(
            array(
                array('text' => '✅ Approve', 'callback_data' => 'rcpt:ok:' . $rid),
                array('text' => '❌ Reject', 'callback_data' => 'rcpt:no:' . $rid),
            ),
        ));
        foreach ((array)(bot_config()['admin_ids'] ?? array()) as $adminId) {
            $adminId = (int)$adminId;
            if ($adminId <= 0) {
                continue;
            }
            if ($file['kind'] === 'photo') {
                tg_api('sendPhoto', array('chat_id' => $adminId, 'photo' => $file['file_id'], 'caption' => $caption, 'reply_markup' => $kb));
            } else {
                tg_api('sendDocument', array('chat_id' => $adminId, 'document' => $file['file_id'], 'caption' => $caption, 'reply_markup' => $kb));
            }
        }
        return true;
    }

    private static function saveActivationMedia(array $message, int $userId, int $chatId, string $lang): bool
    {
        if (!UserOptionsService::isOpen($userId, 'activation')) {
            clear_user_state($userId);
            self::deny($chatId, $lang, 'activation');
            return true;
        }
        $file = self::extractFile($message);
        if (!$file || $file['kind'] !== 'document') {
            send_message($chatId, $lang === 'fa' ? 'لطفاً فایل (document) بفرستید، نه فقط عکس.' : 'Please send a document file (not only a photo).');
            return true;
        }
        $size = (int)$file['file_size'];
        // Allow ~150–350 KB for sample flexibility around 200/300KB
        if ($size > 0 && ($size < 150000 || $size > 360000)) {
            send_message(
                $chatId,
                $lang === 'fa'
                    ? 'حجم فایل باید حدود ۲۰۰ یا ۳۰۰ کیلوبایت باشد (الان: ' . round($size / 1024) . ' KB).'
                    : 'File size should be about 200 or 300 KB (now: ' . round($size / 1024) . ' KB).'
            );
            return true;
        }

        $st = db()->prepare("SELECT * FROM license_orders WHERE telegram_id=? AND status IN ('license_sent','activation_uploaded','activation_emailed') ORDER BY id DESC LIMIT 1");
        $st->execute(array($userId));
        $order = $st->fetch();
        if (!$order) {
            send_message($chatId, $lang === 'fa' ? 'سفارش لایسنس فعال پیدا نشد.' : 'No active license order found.');
            clear_user_state($userId);
            return true;
        }

        $saved = self::downloadTelegramFile($file['file_id'], self::storageDir() . '/act_' . $order['order_code'] . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['file_name']));
        $path = $saved ?: '';
        db()->prepare('UPDATE license_orders SET status=?, activation_path=?, updated_at=NOW() WHERE id=?')
            ->execute(array('activation_uploaded', $path, (int)$order['id']));
        self::logEvent($userId, 'activation_uploaded', $file['file_name'] . ' size=' . $size, (int)$order['id']);

        $mailed = self::emailToLicenseMailbox(
            'SEDIV-ACT|' . $userId . '|' . $order['order_code'] . '|REQ',
            "Activation upload from Telegram user {$userId}\nOrder: {$order['order_code']}\nFile: {$file['file_name']}\n",
            $path !== '' ? $path : null
        );
        if ($mailed) {
            db()->prepare('UPDATE license_orders SET status=?, updated_at=NOW() WHERE id=?')
                ->execute(array('activation_emailed', (int)$order['id']));
            self::logEvent($userId, 'activation_emailed', self::licenseMailbox(), (int)$order['id']);
        }

        clear_user_state($userId);
        send_message(
            $chatId,
            $lang === 'fa'
                ? "✅ فایل اکتیو دریافت شد.\n" . ($mailed ? 'به صندوق لایسنس ایمیل شد: ' . self::licenseMailbox() : 'ایمیل فعلاً لاگ شد (SMTP را در Settings تنظیم کنید).') . "\nوقتی فایل اکتیو آماده شود از همین بات خبر می‌گیرید."
                : "✅ Activation file received.\n" . ($mailed ? 'Emailed to: ' . self::licenseMailbox() : 'Email logged (configure SMTP in Settings).') . "\nYou will be notified here when activation is ready.",
            self::accountKeyboard($lang, $userId)
        );

        foreach ((array)(bot_config()['admin_ids'] ?? array()) as $adminId) {
            $adminId = (int)$adminId;
            if ($adminId > 0) {
                tg_api('sendDocument', array(
                    'chat_id' => $adminId,
                    'document' => $file['file_id'],
                    'caption' => "⚙️ Activation file\nUser: {$userId}\nOrder: {$order['order_code']}\nMailed: " . ($mailed ? 'yes' : 'no'),
                ));
            }
        }
        return true;
    }

    private static function handleAdminReceiptCallback(string $data, string $cbId, int $chatId, int $userId, string $lang): bool
    {
        if (!is_admin($userId)) {
            answer_callback($cbId, 'Admin only', true);
            return true;
        }
        $parts = explode(':', $data);
        // rcpt:ok:ID / rcpt:no:ID
        if (count($parts) < 3) {
            answer_callback($cbId);
            return true;
        }
        $action = $parts[1];
        $rid = (int)$parts[2];
        if ($action === 'ok') {
            $res = self::approveReceipt($rid, $userId);
            answer_callback($cbId, $res['ok'] ? 'Approved' : 'Failed', !$res['ok']);
            send_message($chatId, $res['msg']);
        } else {
            $res = self::rejectReceipt($rid, $userId);
            answer_callback($cbId, $res['ok'] ? 'Rejected' : 'Failed', !$res['ok']);
            send_message($chatId, $res['msg']);
        }
        return true;
    }

    /** @return array{ok:bool,msg:string} */
    public static function approveReceipt(int $receiptId, int $adminId): array
    {
        self::ensureSchema();
        $st = db()->prepare('SELECT * FROM payment_receipts WHERE id=? LIMIT 1');
        $st->execute(array($receiptId));
        $r = $st->fetch();
        if (!$r) {
            return array('ok' => false, 'msg' => 'Receipt not found');
        }
        if ((string)$r['status'] === 'approved') {
            return array('ok' => true, 'msg' => 'Already approved');
        }
        db()->prepare('UPDATE payment_receipts SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?')
            ->execute(array('approved', $adminId, $receiptId));

        $tg = (int)$r['telegram_id'];
        $orderCode = (string)($r['order_code'] ?: ('ORD' . $receiptId));
        $st2 = db()->prepare('SELECT * FROM license_orders WHERE receipt_id=? LIMIT 1');
        $st2->execute(array($receiptId));
        $order = $st2->fetch();
        if (!$order) {
            db()->prepare('INSERT INTO license_orders (telegram_id, receipt_id, order_code, status) VALUES (?,?,?,?)')
                ->execute(array($tg, $receiptId, $orderCode, 'receipt_approved'));
            $orderId = (int)db()->lastInsertId();
        } else {
            $orderId = (int)$order['id'];
            $orderCode = (string)$order['order_code'];
            db()->prepare('UPDATE license_orders SET status=?, updated_at=NOW() WHERE id=?')
                ->execute(array('receipt_approved', $orderId));
        }

        // Open license + activation options for this user
        UserOptionsService::setUserAccess($tg, 'license', true);
        UserOptionsService::setUserAccess($tg, 'activation', true);
        UserOptionsService::setUserAccess($tg, 'history', true);

        $licPath = self::storageDir() . '/license_' . $orderCode . '.txt';
        $body = "SeDiv License\n"
            . "Order: {$orderCode}\n"
            . "Telegram ID: {$tg}\n"
            . "Issued: " . date('c') . "\n"
            . "Mailbox: " . self::licenseMailbox() . "\n"
            . "Status: APPROVED (sample)\n"
            . "----\n"
            . "Keep this file safe. Upload your activation file from My Account → Activation.\n";
        @file_put_contents($licPath, $body);

        db()->prepare('UPDATE license_orders SET status=?, license_path=?, updated_at=NOW() WHERE id=?')
            ->execute(array('license_sent', $licPath, $orderId));
        self::logEvent($tg, 'receipt_approved', 'by admin ' . $adminId, $orderId);
        self::logEvent($tg, 'license_sent', $licPath, $orderId);

        $uLang = function_exists('user_lang') ? user_lang($tg) : 'en';
        send_message(
            $tg,
            $uLang === 'fa'
                ? "✅ فیش شما تایید شد.\n🔑 لایسنس ارسال می‌شود.\nاپشن‌های لایسنس و اکتیو برای شما باز شد."
                : "✅ Your receipt was approved.\n🔑 License is being sent.\nLicense & Activation options are now open."
        );
        self::sendLocalDocument($tg, $licPath, $uLang === 'fa' ? '🔑 لایسنس SeDiv' : '🔑 SeDiv license');

        // Also email copy to license mailbox (sample)
        self::emailToLicenseMailbox(
            'SEDIV-LIC|' . $tg . '|' . $orderCode . '|SENT',
            $body,
            $licPath
        );

        return array('ok' => true, 'msg' => "Receipt #{$receiptId} approved — license sent to {$tg}");
    }

    /** @return array{ok:bool,msg:string} */
    public static function rejectReceipt(int $receiptId, int $adminId): array
    {
        self::ensureSchema();
        $st = db()->prepare('SELECT * FROM payment_receipts WHERE id=? LIMIT 1');
        $st->execute(array($receiptId));
        $r = $st->fetch();
        if (!$r) {
            return array('ok' => false, 'msg' => 'Receipt not found');
        }
        db()->prepare('UPDATE payment_receipts SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?')
            ->execute(array('rejected', $adminId, $receiptId));
        db()->prepare("UPDATE license_orders SET status='rejected', updated_at=NOW() WHERE receipt_id=?")
            ->execute(array($receiptId));
        $tg = (int)$r['telegram_id'];
        self::logEvent($tg, 'receipt_rejected', 'by admin ' . $adminId);
        $uLang = function_exists('user_lang') ? user_lang($tg) : 'en';
        send_message(
            $tg,
            $uLang === 'fa' ? '❌ فیش شما رد شد. لطفاً فیش معتبر دوباره ارسال کنید.' : '❌ Your receipt was rejected. Please submit a valid receipt again.'
        );
        return array('ok' => true, 'msg' => "Receipt #{$receiptId} rejected");
    }

    public static function sendLocalDocument(int $chatId, string $path, string $caption = ''): void
    {
        if (!is_file($path) || !function_exists('curl_init')) {
            send_message($chatId, $caption !== '' ? $caption : 'File ready.');
            return;
        }
        $token = (string)(bot_config()['bot_token'] ?? '');
        $url = 'https://api.telegram.org/bot' . $token . '/sendDocument';
        $post = array(
            'chat_id' => $chatId,
            'caption' => $caption,
            'document' => new \CURLFile($path, 'text/plain', basename($path)),
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ));
        curl_exec($ch);
        curl_close($ch);
    }

    public static function downloadTelegramFile(string $fileId, string $destPath): ?string
    {
        $info = tg_api('getFile', array('file_id' => $fileId));
        $filePath = (string)(($info['result']['file_path'] ?? ''));
        if ($filePath === '') {
            return null;
        }
        $token = (string)(bot_config()['bot_token'] ?? '');
        $url = 'https://api.telegram.org/file/bot' . $token . '/' . $filePath;
        $data = @file_get_contents($url);
        if ($data === false && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60));
            $data = curl_exec($ch);
            curl_close($ch);
        }
        if ($data === false || $data === null || $data === '') {
            return null;
        }
        if (@file_put_contents($destPath, $data) === false) {
            return null;
        }
        return $destPath;
    }

    /**
     * Sample mailer: uses PHP mail() or logs to storage if mail unavailable.
     * Production: configure SMTP for sedivlic@list.ru in hosting.
     */
    public static function emailToLicenseMailbox(string $subject, string $body, ?string $attachPath = null): bool
    {
        $to = self::licenseMailbox();
        $from = $to;
        $headers = 'From: ' . $from . "\r\n";
        if ($attachPath && is_file($attachPath)) {
            $boundary = 'b_' . md5((string)microtime(true));
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . "\"\r\n";
            $fileData = chunk_split(base64_encode((string)file_get_contents($attachPath)));
            $filename = basename($attachPath);
            $body = "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=utf-8\r\n\r\n"
                . $body . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n"
                . $fileData . "\r\n"
                . "--{$boundary}--";
        } else {
            $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        }

        $ok = @mail($to, $subject, $body, $headers);
        // Always keep a local copy for the sample
        $log = self::storageDir() . '/mail_log_' . date('Ymd_His') . '.txt';
        @file_put_contents($log, "TO: {$to}\nSUBJECT: {$subject}\nOK: " . ($ok ? '1' : '0') . "\n\n{$body}\n");
        self::logEvent(1, 'mail_attempt', $subject . ' -> ' . $to . ' ok=' . ($ok ? '1' : '0'));
        return (bool)$ok;
    }

    /** Mark activation ready and notify user (admin/tooling helper for sample) */
    public static function markActivationReady(int $orderId, string $activationPath = ''): array
    {
        self::ensureSchema();
        $st = db()->prepare('SELECT * FROM license_orders WHERE id=? LIMIT 1');
        $st->execute(array($orderId));
        $order = $st->fetch();
        if (!$order) {
            return array('ok' => false, 'msg' => 'Order not found');
        }
        if ($activationPath !== '') {
            db()->prepare('UPDATE license_orders SET status=?, activation_path=?, updated_at=NOW() WHERE id=?')
                ->execute(array('activation_ready', $activationPath, $orderId));
        } else {
            db()->prepare('UPDATE license_orders SET status=?, updated_at=NOW() WHERE id=?')
                ->execute(array('activation_ready', $orderId));
        }
        $tg = (int)$order['telegram_id'];
        self::logEvent($tg, 'activation_ready', 'order ' . $order['order_code'], $orderId);
        $uLang = function_exists('user_lang') ? user_lang($tg) : 'en';
        send_message(
            $tg,
            $uLang === 'fa'
                ? "🎉 فایل اکتیو آماده است.\nاز پنل حساب → اکتیوسازی / لایسنس اقدام کنید یا منتظر فایل ارسالی باشید."
                : "🎉 Activation file is ready.\nOpen My Account → Activation / License, or wait for the file."
        );
        if ($activationPath !== '' && is_file($activationPath)) {
            self::sendLocalDocument($tg, $activationPath, $uLang === 'fa' ? '⬇️ دانلود اکتیو' : '⬇️ Download activation');
            self::logEvent($tg, 'activation_downloaded', basename($activationPath), $orderId);
            db()->prepare('UPDATE license_orders SET status=?, updated_at=NOW() WHERE id=?')
                ->execute(array('downloaded', $orderId));
        }
        return array('ok' => true, 'msg' => 'User notified');
    }
}
