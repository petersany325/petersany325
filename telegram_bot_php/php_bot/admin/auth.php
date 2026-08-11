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
    // 1) panel_users table
    try {
        if (function_exists('ensure_admins_schema')) {
            ensure_admins_schema();
        }
        $st = db()->prepare('SELECT * FROM panel_users WHERE username=? AND is_active=1 LIMIT 1');
        $st->execute(array($user));
        $row = $st->fetch();
        if ($row && password_verify($pass, $row['password_hash'])) {
            $_SESSION['hddland_admin'] = true;
            $_SESSION['hddland_admin_user'] = $row['username'];
            $_SESSION['panel_user'] = $row;
            if (function_exists('session_regenerate_id')) {
                session_regenerate_id(true);
            }
            return true;
        }
    } catch (Exception $e) {}

    // 2) legacy config
    $cfg = admin_cfg();
    if ($cfg['password_hash'] === '') {
        return false;
    }
    if ($cfg['username'] !== $user) {
        return false;
    }
    if (!password_verify($pass, $cfg['password_hash'])) {
        return false;
    }
    $_SESSION['hddland_admin'] = true;
    $_SESSION['hddland_admin_user'] = $user;
    $_SESSION['panel_user'] = array('username' => $user, 'is_super' => 1);
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
    return true;
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
