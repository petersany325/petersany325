<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = rtrim(dirname($script), '/');
    if ($dir === '' || $dir === '.' || $dir === '/') {
        return '';
    }
    return $dir;
}

function url(string $path = '/'): string
{
    if ($path === '') {
        $path = '/';
    }
    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }
    $base = base_path();
    if ($path === '/') {
        return ($base === '' ? '' : $base) . '/';
    }
    return $base . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function request_path(): string
{
    $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $uri = is_string($uri) ? $uri : '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = '/' . trim($uri, '/');
    if ($uri !== '/') {
        $uri = rtrim($uri, '/') ?: '/';
    }
    return $uri === '//' ? '/' : $uri;
}

function fa_digits(int|string $value): string
{
    return strtr((string) $value, [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ]);
}

function en_digits(string $value): string
{
    return strtr($value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

function money(int $rials): string
{
    return fa_digits(number_format($rials)) . ' ریال';
}

function normalize_phone(string $phone): string
{
    $phone = en_digits(trim($phone));
    $phone = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($phone, '0098')) {
        $phone = '0' . substr($phone, 4);
    } elseif (str_starts_with($phone, '98') && strlen($phone) === 12) {
        $phone = '0' . substr($phone, 2);
    } elseif (strlen($phone) === 10 && str_starts_with($phone, '9')) {
        $phone = '0' . $phone;
    }
    return $phone;
}

function is_mobile(string $phone): bool
{
    return (bool) preg_match('/^09\d{9}$/', $phone);
}

function gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function jalali_date(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    [$jy, $jm, $jd] = gregorian_to_jalali((int) date('Y', $ts), (int) date('n', $ts), (int) date('j', $ts));
    return fa_digits(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
}

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $items = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return is_array($items) ? $items : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    $known = (string) ($_SESSION['csrf'] ?? '');
    if ($known === '' || $sent === '' || !hash_equals($known, $sent)) {
        flash('error', 'نشست منقضی شده. صفحه را تازه کنید و دوباره بفرستید.');
        $back = (string) ($_SERVER['HTTP_REFERER'] ?? url('/'));
        header('Location: ' . $back);
        exit;
    }
}

function post_string(string $key, int $max = 5000): string
{
    $value = trim(en_digits((string) ($_POST[$key] ?? '')));
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }
    return $value;
}

function setting(App $app, string $plugin, string $key, string $default = ''): string
{
    $row = $app->db->one(
        'SELECT svalue FROM ak_settings WHERE plugin = ? AND skey = ?',
        [$plugin, $key]
    );
    if (!$row || $row['svalue'] === null) {
        return $default;
    }
    return (string) $row['svalue'];
}

function save_setting(App $app, string $plugin, string $key, string $value): void
{
    $app->db->run(
        'INSERT INTO ak_settings (plugin, skey, svalue) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)',
        [$plugin, $key, $value]
    );
}

function require_user(App $app): array
{
    $user = $app->auth->user();
    if (!$user) {
        flash('error', 'برای ادامه وارد شوید.');
        redirect('/login');
    }
    return $user;
}

function require_role(App $app, array $roles): array
{
    $user = require_user($app);
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'به این بخش دسترسی ندارید.';
        exit;
    }
    return $user;
}

function log_line(string $root, string $message): void
{
    $dir = $root . '/storage/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($dir . '/app.log', date('c') . ' ' . $message . "\n", FILE_APPEND);
}

function hex_color(string $value, string $fallback): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

/** @return array<string, string> */
function ak_role_labels(): array
{
    return [
        'admin' => 'مدیر شرکت',
        'employee' => 'کارمند',
        'accountant' => 'حسابدار',
        'region_manager' => 'مدیر منطقه',
        'representative' => 'نماینده',
        'customer' => 'مشتری',
    ];
}

function home_for_role(string $role): string
{
    return match ($role) {
        'customer' => '/account',
        'representative' => '/rep',
        default => '/admin',
    };
}

function reserved_slug(string $slug): bool
{
    return in_array($slug, [
        'admin', 'account', 'login', 'logout', 'machines', 'quote', 'assets',
        'install', 'p', 'index.php', 'api', 'estelam', 'rep',
    ], true);
}
