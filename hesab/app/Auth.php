<?php
declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('/login');
    }
}

function attempt_login(string $email, string $password): bool
{
    $st = Database::query('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
    $user = $st->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
    return true;
}

function logout_user(): void
{
    unset($_SESSION['user']);
}
