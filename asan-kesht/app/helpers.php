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
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
}

function hex_shift(string $hex, int $amount): string
{
    $hex = ltrim(hex_color($hex, '#000000'), '#');
    $parts = [];
    for ($i = 0; $i < 3; $i++) {
        $channel = hexdec(substr($hex, $i * 2, 2)) + $amount;
        $parts[] = str_pad(dechex(max(0, min(255, $channel))), 2, '0', STR_PAD_LEFT);
    }
    return '#' . implode('', $parts);
}

function hex_luma(string $hex): float
{
    $hex = ltrim(hex_color($hex, '#000000'), '#');
    $red = hexdec(substr($hex, 0, 2));
    $green = hexdec(substr($hex, 2, 2));
    $blue = hexdec(substr($hex, 4, 2));
    return (0.299 * $red + 0.587 * $green + 0.114 * $blue) / 255;
}

/** @return array<string, array<string, string>> */
function theme_palettes(): array
{
    return [
        'classic' => [
            'name' => 'قالب کارخانه',
            'note' => 'حالت اولیه',
            'oxide' => '#d24a1a',
            'on_oxide' => '#ffffff',
            'oxide_deep' => '#a33a14',
            'ink' => '#161814',
            'paper' => '#f3efe4',
            'paper_2' => '#e7e0d0',
            'dark' => '#121410',
            'dark_2' => '#1c211c',
            'brass' => '#c6a15a',
            'field' => '#e9f0e4',
            'white' => '#f7f4ee',
        ],
        'navy' => [
            'name' => 'سرمه‌ای و زرد',
            'note' => 'سرمه با زرد صنعتی',
            'oxide' => '#e2b000',
            'on_oxide' => '#172033',
            'oxide_deep' => '#b88a00',
            'ink' => '#172033',
            'paper' => '#f4f6f8',
            'paper_2' => '#e6ebf0',
            'dark' => '#0e1c33',
            'dark_2' => '#17304f',
            'brass' => '#e2b000',
            'field' => '#eef3f8',
            'white' => '#ffffff',
        ],
        'forest' => [
            'name' => 'سبز و طلایی',
            'note' => 'سبز جنگلی و طلایی',
            'oxide' => '#e0b84a',
            'on_oxide' => '#142116',
            'oxide_deep' => '#a07b0c',
            'ink' => '#163024',
            'paper' => '#f6f7f2',
            'paper_2' => '#e7eee4',
            'dark' => '#0f3d24',
            'dark_2' => '#145c32',
            'brass' => '#e0b84a',
            'field' => '#e7f2e4',
            'white' => '#fbfcf8',
        ],
        'graphite' => [
            'name' => 'خاکستری و نارنجی',
            'note' => 'گرافیت و نارنجی سوخته',
            'oxide' => '#e06a1f',
            'on_oxide' => '#ffffff',
            'oxide_deep' => '#b84e12',
            'ink' => '#2a2e33',
            'paper' => '#f3f4f6',
            'paper_2' => '#e5e7eb',
            'dark' => '#3a3f46',
            'dark_2' => '#4b515a',
            'brass' => '#c5cad1',
            'field' => '#f7f1ec',
            'white' => '#ffffff',
        ],
    ];
}

function theme_palette(string $id): ?array
{
    $palettes = theme_palettes();
    return $palettes[$id] ?? null;
}

/** @param array{oxide: string, ink: string, paper: string, dark: string} $colors */
function theme_from_colors(array $colors): array
{
    $oxide = hex_color($colors['oxide'], '#d24a1a');
    $ink = hex_color($colors['ink'], '#161814');
    $paper = hex_color($colors['paper'], '#f3efe4');
    $dark = hex_color($colors['dark'], '#121410');
    return [
        'name' => 'رنگ دلخواه',
        'note' => 'انتخاب مدیر',
        'oxide' => $oxide,
        'on_oxide' => hex_luma($oxide) > 0.62 ? $ink : '#ffffff',
        'oxide_deep' => hex_shift($oxide, -36),
        'ink' => $ink,
        'paper' => $paper,
        'paper_2' => hex_shift($paper, -14),
        'dark' => $dark,
        'dark_2' => hex_shift($dark, 18),
        'brass' => '#c6a15a',
        'field' => hex_shift($paper, -8),
        'white' => hex_shift($paper, 8),
    ];
}

function theme_id_for_colors(string $oxide, string $ink, string $paper, string $dark): string
{
    foreach (theme_palettes() as $id => $palette) {
        if ($palette['oxide'] === $oxide && $palette['ink'] === $ink && $palette['paper'] === $paper && $palette['dark'] === $dark) {
            return $id;
        }
    }
    return 'custom';
}

function safe_return_path(string $value): string
{
    $value = trim($value);
    if ($value === '' || !str_starts_with($value, '/') || str_starts_with($value, '//') || str_contains($value, '\\') || str_contains($value, '://')) {
        return '/';
    }
    return $value;
}

function preference_cookie(string $name, string $value): void
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $path = base_path() === '' ? '/' : base_path();
    $expires = $value === '' ? time() - 3600 : time() + 86400 * 180;
    setcookie($name, $value, [
        'expires' => $expires,
        'path' => $path,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if ($value === '') {
        unset($_COOKIE[$name]);
        return;
    }
    $_COOKIE[$name] = $value;
}

function theme_choice(App $app): array
{
    $cookie = (string) ($_COOKIE['ak_theme'] ?? '');
    $cookiePalette = theme_palette($cookie);
    if ($cookiePalette) {
        $cookiePalette['id'] = $cookie;
        $cookiePalette['source'] = 'visitor';
        return $cookiePalette;
    }
    $oxide = '#d24a1a';
    $ink = '#161814';
    $paper = '#f3efe4';
    $dark = '#121410';
    $savedId = 'classic';
    if ($app->pluginEnabled('theme-settings')) {
        $oxide = hex_color(setting($app, 'theme-settings', 'color_oxide', $oxide), $oxide);
        $ink = hex_color(setting($app, 'theme-settings', 'color_ink', $ink), $ink);
        $paper = hex_color(setting($app, 'theme-settings', 'color_paper', $paper), $paper);
        $dark = hex_color(setting($app, 'theme-settings', 'color_dark', $dark), $dark);
        $savedId = setting($app, 'theme-settings', 'theme_id', '');
        if ($savedId === '' || ($savedId !== 'custom' && !theme_palette($savedId))) {
            $savedId = theme_id_for_colors($oxide, $ink, $paper, $dark);
        }
    }
    $savedPalette = theme_palette($savedId);
    if ($savedId !== 'custom' && $savedPalette) {
        $savedPalette['id'] = $savedId;
        $savedPalette['source'] = 'site';
        return $savedPalette;
    }
    $custom = theme_from_colors([
        'oxide' => $oxide,
        'ink' => $ink,
        'paper' => $paper,
        'dark' => $dark,
    ]);
    $custom['id'] = 'custom';
    $custom['source'] = 'site';
    return $custom;
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

function landing_for_role(string $role): string
{
    return $role === 'admin' ? '/' : home_for_role($role);
}

function nav_href(string $href): string
{
    if (preg_match('#^https?://#i', $href) === 1) {
        return $href;
    }
    if ($href === '' || $href === '#') {
        return '#';
    }
    return url(str_starts_with($href, '/') ? $href : '/' . $href);
}

function ui_icon(string $name): string
{
    $path = match ($name) {
        'phone' => '<rect x="8" y="2.5" width="8" height="19" rx="2"/><path d="M11 17.5h2"/>',
        'code' => '<rect x="2.5" y="7" width="3.5" height="10" rx="0.8"/><rect x="7.7" y="7" width="3.5" height="10" rx="0.8"/><rect x="12.8" y="7" width="3.5" height="10" rx="0.8"/><rect x="18" y="7" width="3.5" height="10" rx="0.8"/>',
        'key' => '<circle cx="8" cy="12" r="3.4"/><path d="M11.2 12H21M16.2 12v3M19.2 12v2"/>',
        'user' => '<circle cx="12" cy="8" r="3"/><path d="M5.5 19.5c1.3-3.2 3.5-4.7 6.5-4.7s5.2 1.5 6.5 4.7"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'chevron' => '<path d="M7 10l5 5 5-5"/>',
        default => '',
    };
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function reserved_slug(string $slug): bool
{
    return in_array($slug, [
        'admin', 'account', 'login', 'logout', 'machines', 'quote', 'assets',
        'install', 'p', 'index.php', 'api', 'estelam', 'rep', 'register', 'cart', 'checkout', 'theme',
    ], true);
}
