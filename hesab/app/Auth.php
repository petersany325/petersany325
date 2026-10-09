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

function login_session_from_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? null,
        'role' => $user['role'],
    ];
    try {
        Database::query('UPDATE users SET last_login_at=NOW() WHERE id=?', [(int) $user['id']]);
    } catch (Throwable $e) {
        // column may not exist on very old schemas mid-migrate
    }
}

function attempt_login(string $email, string $password): bool
{
    $st = Database::query('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
    $user = $st->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    if (isset($user['is_active']) && (int) $user['is_active'] !== 1) {
        return false;
    }
    login_session_from_user($user);
    return true;
}

function attempt_login_phone(string $phone, string $password): bool
{
    $phone = Sms::normalizeMobile($phone);
    if ($phone === '') {
        return false;
    }
    $st = Database::query('SELECT * FROM users WHERE phone = ? LIMIT 1', [$phone]);
    $user = $st->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    if (isset($user['is_active']) && (int) $user['is_active'] !== 1) {
        return false;
    }
    login_session_from_user($user);
    return true;
}

function create_login_otp(string $phone): array
{
    $phone = Sms::normalizeMobile($phone);
    if ($phone === '') {
        return ['ok' => false, 'message' => 'شماره موبایل نامعتبر است'];
    }
    $user = Database::query('SELECT * FROM users WHERE phone=? AND is_active=1 LIMIT 1', [$phone])->fetch();
    if (!$user) {
        // fallback if is_active column missing
        $user = Database::query('SELECT * FROM users WHERE phone=? LIMIT 1', [$phone])->fetch();
    }
    if (!$user) {
        return ['ok' => false, 'message' => 'کاربری با این موبایل یافت نشد'];
    }
    $code = (string) random_int(100000, 999999);
    $expires = date('Y-m-d H:i:s', time() + 300);
    Database::query('DELETE FROM otp_codes WHERE phone=? OR expires_at < NOW()', [$phone]);
    Database::query(
        'INSERT INTO otp_codes (phone, code, purpose, expires_at, created_at) VALUES (?,?,?,?,NOW())',
        [$phone, password_hash($code, PASSWORD_DEFAULT), 'login', $expires]
    );
    $send = Sms::sendOtp($phone, $code);
    if (!$send['ok']) {
        // In disabled/dev mode keep OTP in session for testing
        if (!Sms::enabled()) {
            $_SESSION['otp_debug_' . $phone] = $code;
            return ['ok' => true, 'message' => 'حالت آزمایشی: کد ' . $code . ' (SMS خاموش است)', 'debug' => $code];
        }
        return ['ok' => false, 'message' => 'ارسال پیامک ناموفق: ' . $send['message']];
    }
    return ['ok' => true, 'message' => 'کد تأیید ارسال شد'];
}

function verify_login_otp(string $phone, string $code): bool
{
    $phone = Sms::normalizeMobile($phone);
    $code = trim($code);
    if ($phone === '' || $code === '') {
        return false;
    }
    $row = Database::query(
        'SELECT * FROM otp_codes WHERE phone=? AND purpose=? ORDER BY id DESC LIMIT 1',
        [$phone, 'login']
    )->fetch();
    if (!$row) {
        return false;
    }
    if (strtotime((string) $row['expires_at']) < time()) {
        return false;
    }
    if ((int) ($row['attempts'] ?? 0) >= 5) {
        return false;
    }
    Database::query('UPDATE otp_codes SET attempts=attempts+1 WHERE id=?', [(int) $row['id']]);
    $ok = password_verify($code, (string) $row['code']);
    if (!$ok && !empty($_SESSION['otp_debug_' . $phone]) && hash_equals((string) $_SESSION['otp_debug_' . $phone], $code)) {
        $ok = true;
    }
    if (!$ok) {
        return false;
    }
    $user = Database::query('SELECT * FROM users WHERE phone=? LIMIT 1', [$phone])->fetch();
    if (!$user) {
        return false;
    }
    Database::query('DELETE FROM otp_codes WHERE phone=?', [$phone]);
    unset($_SESSION['otp_debug_' . $phone]);
    login_session_from_user($user);
    return true;
}

function logout_user(): void
{
    unset($_SESSION['user']);
}
