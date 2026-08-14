<?php
declare(strict_types=1);

/**
 * Safe, idempotent schema upgrades so deploys don't break old DBs.
 */
final class Migrator
{
    public static function ensure(Database $db): void
    {
        $pdo = $db->pdo();
        self::addColumn($pdo, 'users', 'flow', 'VARCHAR(64) NULL');
        self::addColumn($pdo, 'users', 'ui_messages', 'TEXT NULL');
        self::addColumn($pdo, 'users', 'province', 'VARCHAR(64) NULL');
        self::addColumn($pdo, 'users', 'display_name', 'VARCHAR(32) NULL');
        self::addColumn($pdo, 'users', 'avatar_file_id', 'VARCHAR(255) NULL');
        self::addColumn($pdo, 'users', 'referral_code', 'VARCHAR(16) NULL');
        self::addColumn($pdo, 'users', 'referred_by', 'BIGINT NULL');
        self::addColumn($pdo, 'users', 'public_code', 'VARCHAR(16) NULL');
        self::addColumn($pdo, 'users', 'last_seen_at', 'TIMESTAMP NULL');
        self::addColumn($pdo, 'users', 'browse_cursor', 'BIGINT NULL');
        self::ensureSearchPrefFlexible($pdo);
        self::ensureReferralUnique($pdo);
        self::ensurePublicCodeUnique($pdo);
        self::ensureSettingsTable($pdo);
        self::ensureBlocksTable($pdo);
        self::ensureSupportStaffTable($pdo);
        self::ensureContactRequestsTable($pdo);
        self::ensureSupportTicketsTable($pdo);
        (new Settings($db))->seedDefaults();
    }

    private static function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $st->execute([$table, $column]);
        if ((int)$st->fetchColumn() > 0) {
            return;
        }
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }

    private static function ensureSearchPrefFlexible(PDO $pdo): void
    {
        $st = $pdo->query("SHOW COLUMNS FROM users LIKE 'search_pref'");
        $col = $st->fetch(PDO::FETCH_ASSOC);
        if (!$col) {
            return;
        }
        $type = strtolower((string)($col['Type'] ?? ''));
        if (str_contains($type, 'enum')) {
            $pdo->exec('ALTER TABLE users MODIFY search_pref VARCHAR(32) NULL');
        }
    }

    private static function ensureReferralUnique(PDO $pdo): void
    {
        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_referral_code'"
        );
        $st->execute();
        if ((int)$st->fetchColumn() > 0) {
            return;
        }
        try {
            $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_referral_code (referral_code)');
        } catch (Throwable $e) {
            // non-fatal
        }
    }

    private static function ensurePublicCodeUnique(PDO $pdo): void
    {
        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_public_code'"
        );
        $st->execute();
        if ((int)$st->fetchColumn() > 0) {
            return;
        }
        try {
            $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_public_code (public_code)');
        } catch (Throwable $e) {
            // non-fatal
        }
    }

    private static function ensureSettingsTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS bot_settings (
                setting_key VARCHAR(64) NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureBlocksTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_blocks (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                blocker_id BIGINT NOT NULL,
                blocked_id BIGINT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_block (blocker_id, blocked_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureSupportStaffTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS support_staff (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                telegram_id BIGINT NOT NULL,
                display_label VARCHAR(64) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_staff (telegram_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureContactRequestsTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS contact_requests (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                from_id BIGINT NOT NULL,
                to_id BIGINT NOT NULL,
                kind ENUM('request','message') NOT NULL DEFAULT 'request',
                payload TEXT NULL,
                status ENUM('pending','accepted','declined','expired') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_to_status (to_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureSupportTicketsTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS support_tickets (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_telegram_id BIGINT NOT NULL,
                staff_telegram_id BIGINT NULL,
                status ENUM('open','closed') NOT NULL DEFAULT 'open',
                last_message TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_user_open (user_telegram_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}
