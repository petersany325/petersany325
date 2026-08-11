<?php
/**
 * Change panel login password (current user).
 */
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

function password_verify_current(string $pass): bool
{
    $user = (string)($_SESSION['hddland_admin_user'] ?? '');
    if ($user === '' || $pass === '') {
        return false;
    }

    try {
        $st = db()->prepare('SELECT password_hash FROM panel_users WHERE username=? AND is_active=1 LIMIT 1');
        $st->execute(array($user));
        $row = $st->fetch();
        if ($row && !empty($row['password_hash']) && password_verify($pass, (string)$row['password_hash'])) {
            return true;
        }
    } catch (Throwable $e) {
        // fall through to legacy config
    }

    $cfg = admin_cfg();
    if ($cfg['username'] === $user && $cfg['password_hash'] !== '' && password_verify($pass, $cfg['password_hash'])) {
        return true;
    }

    return false;
}

function password_sync_hashes(string $username, string $hash): void
{
    $cfg = bot_config();
    $cfg['admin_username'] = $username;
    $cfg['admin_password_hash'] = $hash;
    save_bot_config($cfg);

    try {
        // Avoid broken staff sync throwing before we update the user row.
        $pdo = db();
        $pdo->exec("CREATE TABLE IF NOT EXISTS panel_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(120) NULL,
            is_super TINYINT(1) DEFAULT 0,
            can_tickets TINYINT(1) DEFAULT 1,
            can_requests TINYINT(1) DEFAULT 1,
            can_products TINYINT(1) DEFAULT 0,
            can_menus TINYINT(1) DEFAULT 0,
            can_faqs TINYINT(1) DEFAULT 0,
            can_users TINYINT(1) DEFAULT 0,
            can_languages TINYINT(1) DEFAULT 0,
            can_branding TINYINT(1) DEFAULT 0,
            can_settings TINYINT(1) DEFAULT 0,
            can_admins TINYINT(1) DEFAULT 0,
            can_health TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $st = $pdo->prepare('SELECT id FROM panel_users WHERE username=? LIMIT 1');
        $st->execute(array($username));
        $row = $st->fetch();
        if ($row) {
            $pdo->prepare('UPDATE panel_users SET password_hash=?, is_active=1 WHERE id=?')
                ->execute(array($hash, (int)$row['id']));
        } else {
            $pdo->prepare('INSERT INTO panel_users (username, password_hash, display_name, is_super, can_tickets, can_requests, can_products, can_menus, can_faqs, can_users, can_languages, can_branding, can_settings, can_admins, can_health, is_active) VALUES (?,?,?,1,1,1,1,1,1,1,1,1,1,1,1,1)')
                ->execute(array($username, $hash, 'Super Admin'));
        }
    } catch (Throwable $e) {
        // Config hash is already saved; DB sync is best-effort.
        @file_put_contents(dirname(__DIR__) . '/error.log', date('c') . ' password sync: ' . $e->getMessage() . "\n", FILE_APPEND);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string)($_POST['current_password'] ?? '');
    $pass = (string)($_POST['new_password'] ?? '');
    $pass2 = (string)($_POST['new_password2'] ?? '');
    $user = trim((string)($_SESSION['hddland_admin_user'] ?? 'admin'));

    try {
        if (!password_verify_current($current)) {
            throw new RuntimeException('Current password is incorrect.');
        }
        if (strlen($pass) < 8) {
            throw new RuntimeException('New password must be at least 8 characters.');
        }
        if ($pass !== $pass2) {
            throw new RuntimeException('New passwords do not match.');
        }
        if ($pass === $current) {
            throw new RuntimeException('New password must be different from the current password.');
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        password_sync_hashes($user, $hash);

        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }

        flash('ok', 'Password changed successfully. Use the new password next login.');
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }

    header('Location: password.php');
    exit;
}

$pageTitle = 'Change Password';
$active = 'password';
require __DIR__ . '/layout_header.php';
$who = (string)($_SESSION['hddland_admin_user'] ?? 'admin');
?>
<div class="card panel" style="max-width:520px">
  <h2>Change Password</h2>
  <p class="muted">Update the web panel password for <b><?= e($who) ?></b>. This updates both config and database login.</p>
  <form method="post" class="stack" autocomplete="off">
    <label>Current password</label>
    <input type="password" name="current_password" required autocomplete="current-password">

    <label>New password</label>
    <input type="password" name="new_password" required minlength="8" autocomplete="new-password">

    <label>Confirm new password</label>
    <input type="password" name="new_password2" required minlength="8" autocomplete="new-password">

    <button class="btn" type="submit" style="margin-top:12px">Save New Password</button>
  </form>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
