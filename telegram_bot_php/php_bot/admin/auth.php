<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

try {
    if (function_exists('ensure_schema')) {
        ensure_schema();
    }
    if (function_exists('ensure_admins_schema')) {
        ensure_admins_schema();
    }
    if (function_exists('ensure_world_languages')) {
        ensure_world_languages();
    }
} catch (Throwable $e) {
    @file_put_contents(dirname(__DIR__) . '/error.log', date('c') . ' auth ensure: ' . $e->getMessage() . "\n", FILE_APPEND);
}

function admin_cfg() {
    $c = bot_config();
    return array(
        'username' => isset($c['admin_username']) ? $c['admin_username'] : 'admin',
        'password_hash' => isset($c['admin_password_hash']) ? $c['admin_password_hash'] : '',
    );
}

function admin_logged_in() {
    return !empty($_SESSION['hddland_admin']) && $_SESSION['hddland_admin'] === true;
}

function require_admin() {
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_login($user, $pass) {
    $ok = verify_admin_credentials($user, $pass);
    if (!$ok) {
        return false;
    }
    $_SESSION['hddland_admin'] = true;
    $_SESSION['hddland_admin_user'] = $ok['username'];
    $_SESSION['panel_user'] = $ok;
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
    return true;
}

/**
 * Verify panel username/password without starting a web session (Admin Telegram bot).
 * @return array|null user row-like array on success
 */
function verify_admin_credentials($user, $pass) {
    $user = trim((string)$user);
    $pass = (string)$pass;
    if ($user === '' || $pass === '') {
        return null;
    }
    try {
        if (function_exists('ensure_admins_schema')) {
            ensure_admins_schema();
        }
        $st = db()->prepare('SELECT * FROM panel_users WHERE username=? AND is_active=1 LIMIT 1');
        $st->execute(array($user));
        $row = $st->fetch();
        if ($row && password_verify($pass, (string)$row['password_hash'])) {
            return array(
                'username' => (string)$row['username'],
                'is_super' => (int)($row['is_super'] ?? 0),
                'id' => (int)($row['id'] ?? 0),
            );
        }
    } catch (Exception $e) {
    }

    $cfg = admin_cfg();
    if ($cfg['password_hash'] === '' || $cfg['username'] !== $user) {
        return null;
    }
    if (!password_verify($pass, $cfg['password_hash'])) {
        return null;
    }
    return array(
        'username' => $user,
        'is_super' => 1,
        'id' => 0,
    );
}

function admin_logout() {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function flash($type, $msg) {
    $_SESSION['flash'] = array('type' => $type, 'msg' => $msg);
}

function take_flash() {
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function save_bot_config($cfg) {
    $file = dirname(__DIR__) . '/config.local.php';
    if (!is_array($cfg)) {
        throw new RuntimeException('Invalid config payload');
    }
    // Hard lock: never wipe/replace bot_token with empty/masked/short values
    if (is_file($file)) {
        $prev = (static function ($path) {
            /** @var mixed $v */
            $v = include $path;
            return is_array($v) ? $v : array();
        })($file);
        $prevToken = trim((string)($prev['bot_token'] ?? ''));
        $newToken = trim((string)($cfg['bot_token'] ?? ''));
        $tokenLooksValid = $newToken !== ''
            && strpos($newToken, '…') === false
            && strpos($newToken, '...') === false
            && strlen($newToken) > 20
            && strpos($newToken, ':') !== false;
        if ($prevToken !== '' && !$tokenLooksValid) {
            $cfg['bot_token'] = $prevToken;
        }
        // Do not clear webhook_secret unless explicitly provided as non-empty string key intent
        if (!array_key_exists('webhook_secret', $cfg) && array_key_exists('webhook_secret', $prev)) {
            $cfg['webhook_secret'] = $prev['webhook_secret'];
        }
        // Protect admin bot token the same way
        $prevAdmin = trim((string)($prev['admin_bot_token'] ?? ''));
        $newAdmin = trim((string)($cfg['admin_bot_token'] ?? ''));
        $adminLooksValid = $newAdmin !== ''
            && strpos($newAdmin, '…') === false
            && strpos($newAdmin, '...') === false
            && strlen($newAdmin) > 20
            && strpos($newAdmin, ':') !== false;
        if ($prevAdmin !== '' && !$adminLooksValid) {
            $cfg['admin_bot_token'] = $prevAdmin;
        }
        if (!array_key_exists('admin_bot_webhook_secret', $cfg) && array_key_exists('admin_bot_webhook_secret', $prev)) {
            $cfg['admin_bot_webhook_secret'] = $prev['admin_bot_webhook_secret'];
        }
    }
    $code = "<?php\nreturn " . var_export($cfg, true) . ";\n";
    if (file_put_contents($file, $code) === false) {
        throw new RuntimeException('Cannot write config.local.php');
    }
    $GLOBALS['hdd_reload_config'] = true;
}

// Load helpers
if (is_file(dirname(__DIR__) . '/admins_schema.php')) {
    require_once dirname(__DIR__) . '/admins_schema.php';
}
if (!function_exists('panel_user_can')) {
    function panel_user_can($perm) {
        return !empty($_SESSION['hddland_admin']);
    }
    function require_panel_perm($perm) {}
}
