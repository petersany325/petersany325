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
}
