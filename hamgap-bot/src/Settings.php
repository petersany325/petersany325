<?php
declare(strict_types=1);

/**
 * Runtime bot settings stored in DB so the admin Telegram bot can edit them
 * without touching config.php / a website panel.
 */
final class Settings
{
    private static array $cache = [];

    public const DEFAULTS = [
        'invite_reward' => '30',
        'invite_milestone_3' => '50',
        'invite_milestone_10' => '150',
        'invite_milestone_25' => '400',
        'invitee_bonus' => '10',
        'message_cost' => '2',
        'request_cost' => '1',
        'like_cost' => '0',
        'room_create_cost' => '5',
        'room_join_cost' => '1',
        'report_ban_threshold' => '10',
        'notify_free_cost' => '1',
        'search_free' => '1',
        'connect_any_cost' => '0',
        'connect_gender_cost' => '0',
        'connect_province_cost' => '0',
        'connect_age_cost' => '0',
        'welcome_coins' => '35',
        'support_bot_username' => '',
        'support_welcome' => "سلام 👋\nپیامت را بنویس؛ همکاران پشتیبانی به‌زودی جواب می‌دهند.",
        'support_hours' => '۹ تا ۲۴',
        'main_bot_username' => 'HamGapXBot',
        'brand_name' => 'هم‌گپ',
        'admin_username' => 'hamgap_admin',
        // password hash is set on first deploy; never store plaintext here
        'admin_password_hash' => '',
        'admin_session_hours' => '12',
        // Card-to-card top-up
        'pay_card_number' => '',
        'pay_card_holder' => '',
        'pay_bank_name' => '',
        'pay_trust_channel' => '',
        'pay_invoice_minutes' => '30',
        'pack_100_price' => '50000',
        'pack_300_price' => '120000',
        'pack_1000_price' => '350000',
        // Star Club (VIP)
        'vip_price_30' => '99000',
        'vip_days' => '30',
        'vip_min_account_days' => '3',
        'vip_min_likes' => '3',
        'vip_max_reports' => '2',
        'vip_require_occupation' => '1',
        'vip_require_avatar' => '1',
    ];

    public function __construct(private Database $db)
    {
    }

    public function get(string $key, ?string $default = null): string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $st = $this->db->pdo()->prepare('SELECT setting_value FROM bot_settings WHERE setting_key = ? LIMIT 1');
        $st->execute([$key]);
        $row = $st->fetchColumn();
        if ($row === false) {
            $val = $default ?? (self::DEFAULTS[$key] ?? '');
            self::$cache[$key] = $val;
            return $val;
        }
        self::$cache[$key] = (string)$row;
        return self::$cache[$key];
    }

    public function getInt(string $key, ?int $default = null): int
    {
        $raw = $this->get($key, $default !== null ? (string)$default : null);
        return (int)$raw;
    }

    public function set(string $key, string $value): void
    {
        $pdo = $this->db->pdo();
        $st = $pdo->prepare(
            'INSERT INTO bot_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $st->execute([$key, $value]);
        self::$cache[$key] = $value;
    }

    public function all(): array
    {
        $out = self::DEFAULTS;
        $rows = $this->db->pdo()->query('SELECT setting_key, setting_value FROM bot_settings')->fetchAll();
        foreach ($rows as $row) {
            $out[(string)$row['setting_key']] = (string)$row['setting_value'];
        }
        return $out;
    }

    public function seedDefaults(): void
    {
        foreach (self::DEFAULTS as $k => $v) {
            $st = $this->db->pdo()->prepare('SELECT 1 FROM bot_settings WHERE setting_key = ? LIMIT 1');
            $st->execute([$k]);
            if (!$st->fetchColumn()) {
                $this->set($k, $v);
            }
        }
    }
}
