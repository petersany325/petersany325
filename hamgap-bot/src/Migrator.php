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
}
