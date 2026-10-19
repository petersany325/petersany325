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
    $section = access_section_for(request_path());
    if ($section !== null) {
        if (!access_can($app, $user, $section, $roles)) {
            access_forbid();
        }
        return $user;
    }
    if (!in_array($user['role'], $roles, true)) {
        access_forbid();
    }
    return $user;
}

function access_forbid(): void
{
    http_response_code(403);
    echo 'به این بخش دسترسی ندارید.';
    exit;
}

/** @return array<string, array{label:string, paths:list<string>, roles:list<string>}> */
function access_sections(): array
{
    return [
        'dashboard' => ['label' => 'پیشخوان', 'paths' => ['/admin'], 'roles' => ['admin', 'employee', 'accountant', 'region_manager']],
        'profile' => ['label' => 'نمایه', 'paths' => ['/admin/profile'], 'roles' => ['admin', 'employee', 'accountant', 'region_manager', 'representative']],
        'users' => ['label' => 'کاربران و دسترسی‌ها', 'paths' => ['/admin/users', '/admin/access'], 'roles' => ['admin']],
        'system' => ['label' => 'افزونه‌ها و تنظیمات کلی', 'paths' => ['/admin/plugins', '/admin/settings'], 'roles' => ['admin']],
        'maintain' => ['label' => 'نگهداری، کش، تعمیر و پشتیبان', 'paths' => ['/admin/maintain'], 'roles' => ['admin']],
        'accounting' => ['label' => 'حسابداری، فاکتور، ویزیتور و سود و زیان', 'paths' => ['/admin/accounting'], 'roles' => ['admin', 'employee', 'accountant']],
        'audit' => ['label' => 'حسابرسی', 'paths' => ['/admin/accounting/audit'], 'roles' => ['admin', 'accountant']],
        'dealers' => ['label' => 'پرونده و سفارش نماینده', 'paths' => ['/admin/dealers'], 'roles' => ['admin', 'employee', 'accountant', 'region_manager']],
        'dealer_edit' => ['label' => 'قیمت و تنظیم نمایندگی', 'paths' => ['/admin/dealers/prices', '/admin/dealers/settings', '/admin/dealers/rules'], 'roles' => ['admin', 'employee']],
        'dealer_money' => ['label' => 'حساب، چک و پورسانت نماینده', 'paths' => ['/admin/dealers/money', '/admin/dealers/payments', '/admin/dealers/checks', '/admin/dealers/adjust', '/admin/dealers/disputes', '/admin/dealers/commissions'], 'roles' => ['admin', 'accountant']],
        'shop' => ['label' => 'تنظیم فروشگاه و درگاه', 'paths' => ['/admin/shop'], 'roles' => ['admin']],
        'shop_orders' => ['label' => 'سفارش فروشگاه', 'paths' => ['/admin/shop/orders'], 'roles' => ['admin', 'employee']],
        'catalog' => ['label' => 'مدل ماشین‌ها و پیش‌فاکتور', 'paths' => ['/admin/machines', '/admin/quotes'], 'roles' => ['admin', 'employee']],
        'content' => ['label' => 'صفحه اول، فوتر، منو و قالب', 'paths' => ['/admin/pages', '/admin/menus', '/admin/theme', '/admin/layout'], 'roles' => ['admin']],
        'messages' => ['label' => 'تیکت و پیامک', 'paths' => ['/admin/tickets', '/admin/sms', '/admin/niaz'], 'roles' => ['admin', 'employee']],
        'register' => ['label' => 'ثبت‌نام و درخواست نمایندگی', 'paths' => ['/admin/register'], 'roles' => ['admin']],
        'rep' => ['label' => 'کارتابل نمایندگی', 'paths' => ['/rep'], 'roles' => ['representative']],
    ];
}

function access_section_for(string $path): ?string
{
    $path = rtrim($path, '/') ?: '/';
    $best = null;
    $bestLen = -1;
    foreach (access_sections() as $id => $section) {
        foreach ($section['paths'] as $prefix) {
            $exact = $prefix === '/admin';
            $hit = $path === $prefix || (!$exact && str_starts_with($path, $prefix . '/'));
            if ($prefix === '/admin/shop' && str_starts_with($path, '/admin/shop/orders')) {
                $hit = false;
            }
            if ($prefix === '/admin/accounting' && str_starts_with($path, '/admin/accounting/audit')) {
                $hit = false;
            }
            if ($prefix === '/admin/dealers' && access_dealer_specific($path)) {
                $hit = false;
            }
            if (!$hit) {
                continue;
            }
            $len = strlen($prefix);
            if ($len > $bestLen) {
                $best = $id;
                $bestLen = $len;
            }
        }
    }
    return $best;
}

function access_dealer_specific(string $path): bool
{
    foreach (['/prices', '/settings', '/rules', '/money', '/payments', '/checks', '/adjust', '/disputes', '/commissions'] as $tail) {
        if ($path === '/admin/dealers' . $tail || str_starts_with($path, '/admin/dealers' . $tail . '/')) {
            return true;
        }
    }
    return false;
}

/** @return array<string, list<string>> */
function access_matrix(App $app): array
{
    $raw = setting($app, 'access', 'matrix', '');
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    $known = access_sections();
    $roles = array_keys(ak_role_labels());
    $out = [];
    foreach ($decoded as $section => $list) {
        if (!is_string($section) || !isset($known[$section]) || !is_array($list)) {
            continue;
        }
        $picked = ['admin'];
        foreach ($list as $role) {
            if (is_string($role) && in_array($role, $roles, true) && !in_array($role, $picked, true)) {
                $picked[] = $role;
            }
        }
        $out[$section] = $picked;
    }
    return $out;
}

/** @return array<string, string> */
function access_user_flags(App $app, int $userId): array
{
    if ($userId < 1) {
        return [];
    }
    $decoded = json_decode(setting($app, 'access', 'user-' . $userId, ''), true);
    if (!is_array($decoded)) {
        return [];
    }
    $out = [];
    foreach (access_sections() as $id => $section) {
        $flag = $decoded[$id] ?? '';
        if ($flag === 'allow' || $flag === 'deny') {
            $out[$id] = $flag;
        }
    }
    return $out;
}

function access_can(App $app, array $user, string $section, array $codeRoles): bool
{
    if (!isset(access_sections()[$section])) {
        return false;
    }
    $role = (string) ($user['role'] ?? '');
    if ($role === 'admin') {
        return true;
    }
    if ($role === '' || !array_key_exists($role, ak_role_labels())) {
        return false;
    }
    $flag = access_user_flags($app, (int) ($user['id'] ?? 0))[$section] ?? '';
    if ($flag === 'deny') {
        return false;
    }
    if ($flag === 'allow') {
        return true;
    }
    if (!in_array($role, $codeRoles, true)) {
        return false;
    }
    $matrix = access_matrix($app);
    if (array_key_exists($section, $matrix)) {
        return in_array($role, $matrix[$section], true);
    }
    return true;
}

function access_save_matrix(App $app): void
{
    $posted = $_POST['sections'] ?? [];
    if (!is_array($posted)) {
        $posted = [];
    }
    $matrix = [];
    foreach (access_sections() as $id => $section) {
        $picked = ['admin'];
        $row = $posted[$id] ?? [];
        if (is_array($row)) {
            foreach (ak_role_labels() as $role => $label) {
                if ($role !== 'admin' && isset($row[$role])) {
                    $picked[] = $role;
                }
            }
        }
        $matrix[$id] = $picked;
    }
    save_setting($app, 'access', 'matrix', json_encode($matrix, JSON_UNESCAPED_UNICODE));
    ak_audit($app, 'دسترسی', 'نقش‌ها', 'جدول دسترسی نقش‌ها ذخیره شد');
    flash('success', 'دسترسی نقش‌ها ذخیره شد. مدیر همیشه به همه بخش‌ها دسترسی دارد.');
    redirect('/admin/access');
}

function access_save_user(App $app): void
{
    $userId = (int) post_string('user_id', 12);
    $user = $app->db->one('SELECT id, name, role FROM ak_users WHERE id = ?', [$userId]);
    if (!$user || (string) $user['role'] === 'admin') {
        flash('error', 'برای مدیر استثنا ثبت نمی‌شود؛ مدیر به همه بخش‌ها دسترسی دارد.');
        redirect('/admin/access');
    }
    $posted = $_POST['flag'] ?? [];
    if (!is_array($posted)) {
        $posted = [];
    }
    $flags = [];
    foreach (access_sections() as $id => $section) {
        $flag = is_string($posted[$id] ?? null) ? $posted[$id] : '';
        if ($flag === 'allow' || $flag === 'deny') {
            $flags[$id] = $flag;
        }
    }
    save_setting($app, 'access', 'user-' . $userId, json_encode($flags, JSON_UNESCAPED_UNICODE));
    ak_audit($app, 'دسترسی', (string) $user['name'], 'استثنای دسترسی کاربر ذخیره شد');
    flash('success', 'دسترسی این کاربر ذخیره شد.');
    redirect('/admin/access');
}

function admin_user_other_admins(App $app, int $exceptId): int
{
    $row = $app->db->one(
        'SELECT COUNT(*) AS c FROM ak_users WHERE role = "admin" AND active = 1 AND id <> ?',
        [$exceptId]
    );
    return (int) ($row['c'] ?? 0);
}

/** @return list<string> */
function admin_user_blocks(App $app, int $id): array
{
    $checks = [
        ['ak_dealers', 'user_id', 'پرونده نمایندگی'],
        ['ak_tickets', 'user_id', 'تیکت'],
        ['ak_ticket_messages', 'user_id', 'پیام تیکت'],
        ['ak_shop_orders', 'user_id', 'سفارش فروشگاه'],
        ['ak_registrations', 'user_id', 'درخواست ثبت‌نام'],
        ['ak_dealer_regions', 'manager_user_id', 'مدیریت منطقه'],
    ];
    $found = [];
    foreach ($checks as [$table, $column, $label]) {
        if (!preg_match('/^ak_[a-z_]+$/', $table) || !preg_match('/^[a-z_]+$/', $column)) {
            continue;
        }
        try {
            $row = $app->db->one('SELECT id FROM ' . $table . ' WHERE ' . $column . ' = ? LIMIT 1', [$id]);
        } catch (Throwable) {
            continue;
        }
        if ($row) {
            $found[] = $label;
        }
    }
    return $found;
}

function admin_user_save(App $app, int $id): void
{
    $name = post_string('name', 120);
    $username = trim(post_string('username', 60));
    if ($username !== '' && !preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
        flash('error', 'نام کاربری فقط حروف انگلیسی، عدد، نقطه، خط تیره و زیرخط، حداقل ۳ نویسه.');
        redirect($id > 0 ? '/admin/users/' . $id : '/admin/users');
    }
    $phone = normalize_phone(post_string('phone', 20));
    $role = post_string('role', 20);
    $active = isset($_POST['active']) ? 1 : 0;
    $password = (string) ($_POST['password'] ?? '');
    if ($name === '' || !array_key_exists($role, ak_role_labels())) {
        flash('error', 'نام و نقش را درست وارد کنید.');
        redirect($id > 0 ? '/admin/users/' . $id : '/admin/users');
    }
    if ($phone !== '' && !is_mobile($phone)) {
        flash('error', 'موبایل را با ۱۱ رقم و شروع ۰۹ بنویسید.');
        redirect($id > 0 ? '/admin/users/' . $id : '/admin/users');
    }
    $phoneValue = $phone === '' ? null : $phone;
    $usernameValue = $username === '' ? null : $username;
    $back = $id > 0 ? '/admin/users/' . $id : '/admin/users';
    try {
        if ($id > 0) {
            $existing = $app->db->one('SELECT id, role FROM ak_users WHERE id = ?', [$id]);
            if (!$existing) {
                flash('error', 'این کاربر پیدا نشد.');
                redirect('/admin/users');
            }
            $current = $app->auth->user();
            if ($current && (int) $current['id'] === $id && $active === 0) {
                flash('error', 'نمی‌توانید حساب خودتان را غیرفعال کنید.');
                redirect($back);
            }
            $wasAdmin = (string) $existing['role'] === 'admin';
            if ($wasAdmin && ($role !== 'admin' || $active === 0) && admin_user_other_admins($app, $id) === 0) {
                flash('error', 'آخرین مدیر فعال را نمی‌توان غیرفعال کرد یا نقشش را عوض کرد.');
                redirect($back);
            }
            $app->db->run(
                'UPDATE ak_users SET name = ?, username = ?, phone = ?, role = ?, active = ? WHERE id = ?',
                [$name, $usernameValue, $phoneValue, $role, $active, $id]
            );
            if ($password !== '') {
                if (mb_strlen($password) < 8) {
                    flash('error', 'رمز جدید حداقل ۸ نویسه باشد. بقیه مشخصات ذخیره شد.');
                    redirect($back);
                }
                $app->db->run('UPDATE ak_users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
            }
            ak_audit($app, 'کاربر', $name, 'ویرایش، نقش ' . (ak_role_labels()[$role] ?? $role) . ($active === 1 ? '، فعال' : '، غیرفعال'));
            flash('success', 'کاربر ذخیره شد.');
            redirect('/admin/users/' . $id);
        }
        if ($password !== '' && mb_strlen($password) < 8) {
            flash('error', 'رمز حداقل ۸ نویسه باشد.');
            redirect('/admin/users');
        }
        $app->db->run(
            'INSERT INTO ak_users (name, username, phone, password_hash, role, active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$name, $usernameValue, $phoneValue, $password === '' ? null : password_hash($password, PASSWORD_DEFAULT), $role, $active]
        );
        ak_audit($app, 'کاربر', $name, 'کاربر تازه با نقش ' . (ak_role_labels()[$role] ?? $role));
        flash('success', 'کاربر ساخته شد.');
    } catch (PDOException) {
        flash('error', 'نام کاربری یا موبایل تکراری است.');
        redirect($back);
    }
    redirect('/admin/users');
}

function admin_user_delete(App $app, int $id): void
{
    if (!isset($_POST['confirm'])) {
        flash('error', 'حذف کاربر را تأیید کنید.');
        redirect('/admin/users');
    }
    $existing = $app->db->one('SELECT id, name, role FROM ak_users WHERE id = ?', [$id]);
    if (!$existing) {
        flash('error', 'این کاربر پیدا نشد.');
        redirect('/admin/users');
    }
    $current = $app->auth->user();
    if ($current && (int) $current['id'] === $id) {
        flash('error', 'حساب خودتان حذف نمی‌شود.');
        redirect('/admin/users');
    }
    if ((string) $existing['role'] === 'admin' && admin_user_other_admins($app, $id) === 0) {
        flash('error', 'آخرین مدیر حذف نمی‌شود.');
        redirect('/admin/users');
    }
    $blocks = admin_user_blocks($app, $id);
    if ($blocks !== []) {
        flash('error', 'این کاربر حذف نمی‌شود چون ' . implode('، ', $blocks) . ' دارد. از ویرایش می‌توانید او را غیرفعال کنید.');
        redirect('/admin/users');
    }
    $app->db->run('DELETE FROM ak_settings WHERE plugin = "access" AND skey = ?', ['user-' . $id]);
    $app->db->run('DELETE FROM ak_users WHERE id = ?', [$id]);
    ak_audit($app, 'کاربر', (string) $existing['name'], 'حذف شد');
    flash('success', 'کاربر حذف شد.');
    redirect('/admin/users');
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

/** @return array{0:int,1:int,2:int} */
function hex_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return [23, 32, 51];
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function shade_hex(string $hex, float $factor): string
{
    $out = '#';
    foreach (hex_rgb($hex) as $channel) {
        $out .= sprintf('%02x', (int) max(0, min(255, round($channel * $factor))));
    }
    return $out;
}

function mix_hex(string $from, string $to, float $amount): string
{
    $a = hex_rgb($from);
    $b = hex_rgb($to);
    $out = '#';
    foreach ($a as $index => $channel) {
        $mixed = $channel + ($b[$index] - $channel) * $amount;
        $out .= sprintf('%02x', (int) max(0, min(255, round($mixed))));
    }
    return $out;
}

function contrast_on(string $hex): string
{
    [$red, $green, $blue] = hex_rgb($hex);
    $luma = (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue) / 255;
    return $luma > 0.62 ? '#172033' : '#ffffff';
}

/** @return list<array{name:string, items:list<array{href:string, label:string}>}> */
function admin_nav_groups(App $app): array
{
    $user = $app->auth->user();
    $items = [
        ['href' => '/admin', 'label' => 'پیشخوان', 'roles' => ['admin', 'employee', 'accountant', 'region_manager']],
        ['href' => '/admin/profile', 'label' => 'نمایه', 'roles' => ['admin', 'employee', 'accountant', 'region_manager', 'representative']],
        ['href' => '/admin/users', 'label' => 'کاربران', 'roles' => ['admin']],
        ['href' => '/admin/access', 'label' => 'دسترسی‌ها', 'roles' => ['admin']],
        ['href' => '/admin/plugins', 'label' => 'افزونه‌ها', 'roles' => ['admin']],
        ['href' => '/admin/settings', 'label' => 'تنظیمات', 'roles' => ['admin']],
    ];
    $extra = $app->filter('admin.menu', []);
    if (is_array($extra)) {
        $items = array_merge($items, $extra);
    }
    $role = is_array($user) ? (string) ($user['role'] ?? '') : '';
    $groups = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $itemRoles = $item['roles'] ?? [];
        if (!is_array($itemRoles)) {
            continue;
        }
        $href = (string) ($item['href'] ?? '');
        $section = access_section_for($href);
        $allowed = $section !== null
            ? access_can($app, is_array($user) ? $user : [], $section, $itemRoles)
            : in_array($role, $itemRoles, true);
        if (!$allowed || $href === '') {
            continue;
        }
        $bucket = admin_bucket($href);
        $groups[$bucket][] = ['href' => $href, 'label' => (string) ($item['label'] ?? '')];
    }
    $ordered = [];
    foreach (['مدیریت', 'کاربران', 'فروشگاه', 'نمایندگان', 'حسابداری', 'محتوا', 'پیام‌ها'] as $name) {
        if (!empty($groups[$name])) {
            $ordered[] = ['name' => $name, 'items' => $groups[$name]];
        }
    }
    return $ordered;
}

function admin_bucket(string $href): string
{
    if (str_starts_with($href, '/admin/accounting')) {
        return 'حسابداری';
    }
    if (str_contains($href, '/dealers') || $href === '/rep' || str_starts_with($href, '/rep/')) {
        return 'نمایندگان';
    }
    if (str_contains($href, '/shop') || str_contains($href, 'machines') || str_contains($href, 'quotes')) {
        return 'فروشگاه';
    }
    if (str_contains($href, '/pages') || str_contains($href, '/menus') || str_contains($href, '/theme') || str_contains($href, '/layout')) {
        return 'محتوا';
    }
    if (str_contains($href, 'ticket') || str_contains($href, 'sms') || str_contains($href, 'niaz')) {
        return 'پیام‌ها';
    }
    if ($href === '/admin/access' || str_contains($href, 'register') || str_contains($href, '/users')) {
        return 'کاربران';
    }
    return 'مدیریت';
}

function admin_glyph(string $href): string
{
    if (str_contains($href, '/maintain')) {
        return 'wrench';
    }
    return match (admin_bucket($href)) {
        'حسابداری' => 'file',
        'نمایندگان' => 'pin',
        'فروشگاه' => 'box',
        'محتوا' => 'home',
        'پیام‌ها' => 'phone',
        'کاربران' => 'user',
        'مدیریت' => 'key',
        default => 'chevron',
    };
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

function menu_mark(mixed $item): string
{
    if (!is_array($item) || !function_exists('menu_icon_labels')) {
        return '';
    }
    $icon = (string) ($item['icon'] ?? '');
    if ($icon === '' || !array_key_exists($icon, menu_icon_labels())) {
        return '';
    }
    return '<span class="nav-ico">' . ui_icon($icon) . '</span>';
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
        'home' => '<path d="M4 11.5 12 5l8 6.5"/><path d="M7 10.5V19h10v-8.5"/>',
        'box' => '<path d="M4 8l8-4 8 4-8 4-8-4z"/><path d="M4 8v8l8 4 8-4V8"/><path d="M12 12v8"/>',
        'factory' => '<path d="M3 20h18"/><path d="M5 20V10l5 3V10l5 3V8l5 3v9"/>',
        'pin' => '<path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="1.6"/>',
        'file' => '<path d="M7 3.5h7l4 4V20H7z"/><path d="M14 3.5V8h4"/>',
        'wrench' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.3 2.3-2-2z"/>',
        default => '',
    };
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function ak_audit(App $app, string $action, string $subject, string $detail = ''): void
{
    static $ready = false;
    try {
        if (!$ready) {
            $app->db->exec(
                'CREATE TABLE IF NOT EXISTS ak_audit (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL DEFAULT 0,
                    user_name VARCHAR(120) NOT NULL DEFAULT "",
                    action VARCHAR(80) NOT NULL,
                    subject VARCHAR(160) NOT NULL,
                    detail VARCHAR(400) NOT NULL DEFAULT "",
                    created_at DATETIME NOT NULL,
                    INDEX created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $ready = true;
        }
        if ($action === '') {
            return;
        }
        $user = $app->auth->user();
        $app->db->run(
            'INSERT INTO ak_audit (user_id, user_name, action, subject, detail, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [
                (int) ($user['id'] ?? 0),
                mb_substr((string) ($user['name'] ?? ''), 0, 120),
                mb_substr($action, 0, 80),
                mb_substr($subject, 0, 160),
                mb_substr($detail, 0, 400),
            ]
        );
    } catch (Throwable) {
        return;
    }
}

function reserved_slug(string $slug): bool
{
    return in_array($slug, [
        'admin', 'account', 'login', 'logout', 'machines', 'quote', 'assets',
        'install', 'p', 'index.php', 'api', 'estelam', 'rep', 'register', 'cart', 'checkout',
    ], true);
}
