<?php
declare(strict_types=1);

namespace HddLand\Bot\Repositories;

final class UserRepository
{
    /** @param array<string,mixed> $from */
    public static function ensure(array $from): void
    {
        ensure_user($from);
    }

    public static function lang(int $userId): string
    {
        return function_exists('user_lang') ? user_lang($userId) : 'en';
    }

    public static function setLang(int $userId, string $code): void
    {
        if (function_exists('set_user_lang')) {
            set_user_lang($userId, $code);
        }
    }

    /** @return array<string,mixed> */
    public static function profile(int $userId): array
    {
        try {
            $st = db()->prepare('SELECT * FROM users WHERE telegram_id = ? LIMIT 1');
            $st->execute([$userId]);
            $row = $st->fetch();
            return $row ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function saveContact(int $userId, string $name, string $phone, string $customerId = ''): void
    {
        try {
            $sets = [];
            $vals = [];
            if ($name !== '') {
                $sets[] = 'contact_name = ?';
                $vals[] = mb_substr($name, 0, 120);
            }
            if ($phone !== '') {
                $sets[] = 'phone = ?';
                $vals[] = mb_substr($phone, 0, 40);
            }
            if ($customerId !== '') {
                $sets[] = 'customer_id = ?';
                $vals[] = mb_substr($customerId, 0, 80);
            }
            if (!$sets) {
                return;
            }
            $vals[] = $userId;
            db()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE telegram_id = ?')->execute($vals);
        } catch (\Throwable $e) {
            // columns may not exist yet — SupportFormService::ensureSchema runs first
        }
    }

    public static function ensureVipColumn(): void
    {
        try {
            $pdo = db();
            $c = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_vip'")->fetch();
            if (!$c) {
                $pdo->exec('ALTER TABLE users ADD COLUMN is_vip TINYINT(1) NOT NULL DEFAULT 0');
            }
        } catch (\Throwable $e) {
        }
    }

    public static function isVip(int $userId): bool
    {
        self::ensureVipColumn();
        try {
            $st = db()->prepare('SELECT is_vip FROM users WHERE telegram_id = ? LIMIT 1');
            $st->execute([$userId]);
            return (int)$st->fetchColumn() === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function setVip(int $userId, bool $vip): void
    {
        self::ensureVipColumn();
        db()->prepare('UPDATE users SET is_vip = ? WHERE telegram_id = ?')->execute([$vip ? 1 : 0, $userId]);
    }
}
