<?php
declare(strict_types=1);

final class Permission
{
    public static function can(?array $user, string $code): bool
    {
        if (!$user) {
            return false;
        }
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }
        $st = Database::query('SELECT allowed FROM user_permissions WHERE user_id=? AND permission_code=?', [$user['id'], $code]);
        $row = $st->fetch();
        if ($row) {
            return (int) $row['allowed'] === 1;
        }
        $st = Database::query('SELECT 1 FROM role_permissions WHERE role=? AND permission_code=?', [$user['role'], $code]);
        return (bool) $st->fetch();
    }

    public static function require(string $code): void
    {
        require_login();
        if (!self::can(current_user(), $code)) {
            http_response_code(403);
            flash('err', 'دسترسی لازم را ندارید: ' . $code);
            redirect('/');
        }
    }
}
