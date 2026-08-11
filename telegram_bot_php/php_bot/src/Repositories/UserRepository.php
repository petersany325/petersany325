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

    public static function saveContact(int $userId, string $name, string $phone): void
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
            if (!$sets) {
                return;
            }
            $vals[] = $userId;
            db()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE telegram_id = ?')->execute($vals);
        } catch (\Throwable $e) {
            // columns may not exist yet — SupportFormService::ensureSchema runs first
        }
    }
}
