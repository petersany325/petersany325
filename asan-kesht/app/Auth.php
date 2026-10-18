<?php

declare(strict_types=1);

final class Auth
{
    public function __construct(private Database $db)
    {
    }

    public function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) && !is_string($id)) {
            return null;
        }
        $user = $this->db->one('SELECT * FROM ak_users WHERE id = ? AND active = 1', [(int) $id]);
        return $user ?: null;
    }

    public function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    public function attemptPassword(string $username, string $password): ?array
    {
        $user = $this->db->one(
            'SELECT * FROM ak_users WHERE username = ? AND active = 1',
            [$username]
        );
        if (!$user || empty($user['password_hash'])) {
            return null;
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            return null;
        }
        return $user;
    }

    public function byPhone(string $phone): ?array
    {
        return $this->db->one('SELECT * FROM ak_users WHERE phone = ? AND active = 1', [$phone]);
    }
}
