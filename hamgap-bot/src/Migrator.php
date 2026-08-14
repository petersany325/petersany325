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
        self::ensureSearchPrefFlexible($pdo);
        self::ensureReferralUnique($pdo);
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

    /** Allow prefs like province/age without ENUM truncation bugs. */
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
        // Ignore if duplicates somehow exist
        try {
            $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_referral_code (referral_code)');
        } catch (Throwable $e) {
            // non-fatal
        }
    }
}
