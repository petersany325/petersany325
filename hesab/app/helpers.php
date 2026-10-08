<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($n): string
{
    return number_format((float) $n, 0, '.', ',');
}

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    // When app lives in /hesab/index.php, SCRIPT_NAME is /hesab/index.php
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($dir === '/' || $dir === '.' || $dir === '') {
        $base = '';
    } else {
        $base = $dir;
    }
    return $base;
}

function url(string $path = '/'): string
{
    if (str_starts_with($path, 'http')) {
        return $path;
    }
    if ($path === '') {
        $path = '/';
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }
    return base_path() . $path;
}

function redirect(string $path): never
{
    if (str_starts_with($path, 'http')) {
        header('Location: ' . $path);
        exit;
    }
    // Always stay on the current host/path-base so DNS issues on a
    // subdomain cannot break the working /hesab path.
    header('Location: ' . url($path));
    exit;
}

function cfg(string $key, mixed $default = null): mixed
{
    global $CONFIG;
    $parts = explode('.', $key);
    $val = $CONFIG;
    foreach ($parts as $p) {
        if (!is_array($val) || !array_key_exists($p, $val)) {
            return $default;
        }
        $val = $val[$p];
    }
    return $val;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!$token || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
        http_response_code(419);
        exit('CSRF token mismatch');
    }
}

function wants_mobile_ui(): bool
{
    if (!empty($_GET['desktop'])) {
        $_SESSION['ui_mode'] = 'desktop';
    }
    if (!empty($_GET['mobile'])) {
        $_SESSION['ui_mode'] = 'mobile';
    }
    if (($_SESSION['ui_mode'] ?? '') === 'desktop') {
        return false;
    }
    if (($_SESSION['ui_mode'] ?? '') === 'mobile') {
        return true;
    }
    $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return (bool) preg_match('/android|iphone|ipad|ipod|mobile|opera mini|windows phone|webos|blackberry/i', $ua);
}

function is_mobile_route(): bool
{
    $path = request_path();
    return $path === '/m' || str_starts_with($path, '/m/');
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $flash = take_flash();
    $user = current_user();
    $appName = cfg('app_name', 'حساب');
    $mobile = !empty($force_mobile) || is_mobile_route();
    if ($mobile) {
        if ($name === 'login') {
            $name = 'login';
        }
        if (!is_file(__DIR__ . '/../views/mobile/' . $name . '.php') && $name !== 'login') {
            // fallback tiny page
            $name = 'placeholder';
        }
        require __DIR__ . '/../views/mobile/layout.php';
        return;
    }
    require __DIR__ . '/../views/layout.php';
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../views/' . $name . '.php';
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }
    // Also support /index.php/path
    if (str_starts_with($uri, '/index.php')) {
        $uri = substr($uri, strlen('/index.php')) ?: '/';
    }
    return rtrim($uri, '/') ?: '/';
}

function status_label(string $status): string
{
    return match ($status) {
        'draft' => 'پیش‌نویس',
        'operational' => 'عملیاتی',
        'reviewed' => 'بررسی‌شده',
        'locked', 'posted' => 'قطعی',
        'void' => 'باطل',
        default => $status,
    };
}

function qdate(?string $v = null): string
{
    return $v ?: date('Y-m-d');
}
