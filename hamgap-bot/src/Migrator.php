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
        self::addColumn($pdo, 'users', 'bio', 'VARCHAR(180) NULL');
        self::addColumn($pdo, 'users', 'profile_visibility', "VARCHAR(16) NOT NULL DEFAULT 'public'");
        self::addColumn($pdo, 'users', 'show_age', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::addColumn($pdo, 'users', 'show_city', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::addColumn($pdo, 'users', 'show_province', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::addColumn($pdo, 'users', 'show_gender', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::addColumn($pdo, 'users', 'show_online', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::addColumn($pdo, 'users', 'show_avatar', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::addColumn($pdo, 'users', 'occupation', 'VARCHAR(32) NULL');
        self::addColumn($pdo, 'users', 'show_occupation', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::addColumn($pdo, 'users', 'browse_view', "VARCHAR(16) NOT NULL DEFAULT 'card'");
        self::addColumn($pdo, 'users', 'browse_cache', 'MEDIUMTEXT NULL');
        self::addColumn($pdo, 'users', 'active_room_id', 'BIGINT UNSIGNED NULL');
        self::widenFlowColumn($pdo);
        self::ensureSearchPrefFlexible($pdo);
        self::ensureGenderIncludesShemale($pdo);
        self::ensureReferralUnique($pdo);
        self::ensurePublicCodeUnique($pdo);
        self::ensureSettingsTable($pdo);
        self::ensureBlocksTable($pdo);
        self::ensureSupportStaffTable($pdo);
        self::ensureContactRequestsTable($pdo);
        self::ensureSupportTicketsTable($pdo);
        self::ensureAdminSessionsTable($pdo);
        self::ensureFriendshipsTable($pdo);
        self::ensureFriendRoomsTables($pdo);
        self::ensureStatusIncludesRoom($pdo);
        self::ensurePaymentInvoicesTable($pdo);
        self::ensureUserLikesTable($pdo);
        self::ensureContactRequestsKinds($pdo);
        self::ensureChatWaitNoticesTable($pdo);
        self::addColumn($pdo, 'users', 'ban_reason', 'VARCHAR(64) NULL');
        self::addColumn($pdo, 'users', 'likes_count', 'INT NOT NULL DEFAULT 0');
        $settings = new Settings($db);
        $settings->seedDefaults();
        // v10.8.1: auto-ban after 10 reports (migrate previous default of 5)
        $cur = $settings->get('report_ban_threshold', '10');
        if ($cur === '' || $cur === '5') {
            $settings->set('report_ban_threshold', '10');
        }
    }

    private static function ensureChatWaitNoticesTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS chat_wait_notices (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                waiter_id BIGINT NOT NULL,
                target_id BIGINT NOT NULL,
                status ENUM('pending','notified','cancelled') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                notified_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_waiter_target (waiter_id, target_id),
                KEY idx_target_pending (target_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureUserLikesTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_likes (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                from_id BIGINT NOT NULL,
                to_id BIGINT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_like (from_id, to_id),
                KEY idx_like_to (to_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureContactRequestsKinds(PDO $pdo): void
    {
        try {
            $pdo->exec(
                "ALTER TABLE contact_requests
                 MODIFY kind VARCHAR(16) NOT NULL DEFAULT 'request'"
            );
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec(
                "ALTER TABLE contact_requests
                 MODIFY status VARCHAR(16) NOT NULL DEFAULT 'pending'"
            );
        } catch (Throwable $e) {
        }
    }

    private static function ensurePaymentInvoicesTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS payment_invoices (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                invoice_no VARCHAR(16) NOT NULL,
                telegram_id BIGINT NOT NULL,
                pack_coins INT NOT NULL,
                base_amount INT NOT NULL,
                amount_toman INT NOT NULL,
                status ENUM('pending','awaiting_receipt','submitted','approved','rejected','expired') NOT NULL DEFAULT 'pending',
                receipt_file_id VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                reviewed_by BIGINT NULL,
                reviewed_at TIMESTAMP NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_invoice_no (invoice_no),
                KEY idx_amount_status (amount_toman, status),
                KEY idx_user_status (telegram_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureStatusIncludesRoom(PDO $pdo): void
    {
        $st = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
        $col = $st->fetch(PDO::FETCH_ASSOC);
        if (!$col) {
            return;
        }
        $type = strtolower((string)($col['Type'] ?? ''));
        if (str_contains($type, 'room')) {
            return;
        }
        try {
            $pdo->exec(
                "ALTER TABLE users MODIFY status ENUM('idle','searching','chatting','banned','room') NOT NULL DEFAULT 'idle'"
            );
        } catch (Throwable $e) {
            // non-fatal on restricted hosts
        }
    }

    private static function ensureFriendshipsTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS friendships (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_a BIGINT NOT NULL,
                user_b BIGINT NOT NULL,
                status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
                requested_by BIGINT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_friend_pair (user_a, user_b),
                KEY idx_friend_b_status (user_b, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureFriendRoomsTables(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS friend_rooms (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                code VARCHAR(10) NOT NULL,
                owner_id BIGINT NOT NULL,
                title VARCHAR(64) NOT NULL,
                is_open TINYINT(1) NOT NULL DEFAULT 1,
                max_members INT NOT NULL DEFAULT 50,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_room_code (code),
                KEY idx_room_owner (owner_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS friend_room_members (
                room_id BIGINT UNSIGNED NOT NULL,
                telegram_id BIGINT NOT NULL,
                role ENUM('owner','member') NOT NULL DEFAULT 'member',
                joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (room_id, telegram_id),
                KEY idx_member_user (telegram_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function ensureAdminSessionsTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_sessions (
                telegram_id BIGINT NOT NULL,
                logged_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                PRIMARY KEY (telegram_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
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

    private static function widenFlowColumn(PDO $pdo): void
    {
        $st = $pdo->query("SHOW COLUMNS FROM users LIKE 'flow'");
        $col = $st->fetch(PDO::FETCH_ASSOC);
        if (!$col) {
            return;
        }
        $type = strtolower((string)($col['Type'] ?? ''));
        if (str_contains($type, 'varchar(64)') || preg_match('/varchar\(([0-9]+)\)/', $type, $m) && (int)$m[1] < 255) {
            $pdo->exec('ALTER TABLE users MODIFY flow VARCHAR(255) NULL');
        }
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

    private static function ensureGenderIncludesShemale(PDO $pdo): void
    {
        $st = $pdo->query("SHOW COLUMNS FROM users LIKE 'gender'");
        $col = $st->fetch(PDO::FETCH_ASSOC);
        if (!$col) {
            return;
        }
        $type = strtolower((string)($col['Type'] ?? ''));
        if (str_contains($type, 'shemale')) {
            return;
        }
        try {
            if (str_contains($type, 'enum')) {
                $pdo->exec(
                    "ALTER TABLE users MODIFY gender ENUM('male','female','shemale') NULL"
                );
            } else {
                $pdo->exec('ALTER TABLE users MODIFY gender VARCHAR(16) NULL');
            }
        } catch (Throwable $e) {
            try {
                $pdo->exec('ALTER TABLE users MODIFY gender VARCHAR(16) NULL');
            } catch (Throwable $e2) {
                // non-fatal
            }
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
