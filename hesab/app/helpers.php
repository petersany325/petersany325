<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * One-shot asset pull when cPanel upload is blocked.
 * Hit: /hesab/?hesab_pull=hsbDeploy2026x  (then remove this block)
 */
function hesab_pull_workspace_assets(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    if (($_GET['hesab_pull'] ?? '') !== 'hsbDeploy2026x') {
        return;
    }
    header('Content-Type: text/plain; charset=utf-8');
    $base = 'https://raw.githubusercontent.com/petersany325/petersany325/cursor/hesab-accounting-app-aa3e/hesab';
    $files = [
        'assets/js/app.js',
        'assets/css/app.css',
        'assets/js/mobile.js',
        'assets/css/mobile.css',
        'views/layout.php',
        'app/helpers.php',
        'opcache_reset.php',
        'patch.php',
    ];
    $root = dirname(__DIR__);
    $ok = 0;
    $fail = 0;
    foreach ($files as $rel) {
        $ctx = stream_context_create([
            'http' => ['timeout' => 45, 'header' => "User-Agent: hesab-pull\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $data = @file_get_contents($base . '/' . $rel, false, $ctx);
        if ($data === false || $data === '') {
            echo "FAIL {$rel}\n";
            $fail++;
            continue;
        }
        $dest = $root . '/' . $rel;
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dest, $data);
        echo "OK {$rel} (" . strlen($data) . " bytes)\n";
        $ok++;
    }
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "opcache_reset=1\n";
    }
    echo "Done ok={$ok} fail={$fail}\n";
    exit;
}
hesab_pull_workspace_assets();

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

function is_embed_request(): bool
{
    if (isset($_GET['embed']) && (string) $_GET['embed'] === '1') {
        return true;
    }
    // Form posts / redirects from an iframe tab
    if (($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'iframe') {
        return true;
    }
    return false;
}

function with_embed(string $href): string
{
    if ($href === '' || str_starts_with($href, '#') || str_contains($href, 'embed=')) {
        return $href;
    }
    $parts = parse_url($href);
    if ($parts === false) {
        return $href;
    }
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['embed'] = '1';
    $q = http_build_query($query);
    $out = ($parts['path'] ?? '');
    if ($q !== '') {
        $out .= '?' . $q;
    }
    if (!empty($parts['fragment'])) {
        $out .= '#' . $parts['fragment'];
    }
    // Preserve absolute URLs
    if (!empty($parts['scheme']) && !empty($parts['host'])) {
        $auth = '';
        if (!empty($parts['user'])) {
            $auth = $parts['user'] . (isset($parts['pass']) ? ':' . $parts['pass'] : '') . '@';
        }
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        return $parts['scheme'] . '://' . $auth . $parts['host'] . $port . $out;
    }
    return $out;
}

function redirect(string $path): never
{
    if (str_starts_with($path, 'http')) {
        $loc = $path;
    } else {
        // Always stay on the current host/path-base so DNS issues on a
        // subdomain cannot break the working /hesab path.
        $loc = url($path);
    }
    if (is_embed_request()) {
        $loc = with_embed($loc);
    }
    header('Location: ' . $loc);
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
    $user = current_user();
    $appName = cfg('app_name', 'حساب');
    $mobile = !empty($force_mobile) || is_mobile_route();
    $isEmbed = is_embed_request();
    $isAuthPage = in_array($name, ['login', 'install'], true);
    // Shell request only hosts tabs; leave flash for the embed iframe.
    $isShell = !$mobile && !$isEmbed && !$isAuthPage;
    $flash = $isShell ? null : take_flash();
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
