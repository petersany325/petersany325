<?php
/**
 * Professional admin users + Telegram staff permissions.
 */
declare(strict_types=1);

function ensure_admins_schema($pdo = null) {
    $pdo = $pdo ? $pdo : db();

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

    $pdo->exec("CREATE TABLE IF NOT EXISTS staff_admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        telegram_id BIGINT NOT NULL UNIQUE,
        full_name VARCHAR(120) NULL,
        role VARCHAR(40) DEFAULT 'support',
        can_reply TINYINT(1) DEFAULT 1,
        can_sales TINYINT(1) DEFAULT 1,
        can_support TINYINT(1) DEFAULT 1,
        can_broadcast TINYINT(1) DEFAULT 0,
        can_ban TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migrate config admin into panel_users if empty
    $c = (int)$pdo->query('SELECT COUNT(*) FROM panel_users')->fetchColumn();
    if ($c === 0) {
        $cfg = bot_config();
        $user = !empty($cfg['admin_username']) ? $cfg['admin_username'] : 'admin';
        $hash = !empty($cfg['admin_password_hash']) ? $cfg['admin_password_hash'] : password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO panel_users (username, password_hash, display_name, is_super, can_tickets, can_requests, can_products, can_menus, can_faqs, can_users, can_languages, can_branding, can_settings, can_admins, can_health, is_active) VALUES (?,?,?,1,1,1,1,1,1,1,1,1,1,1,1,1)')
            ->execute(array($user, $hash, 'Super Admin'));
    }

    // Sync telegram admin_ids into staff_admins
    $cfg = bot_config();
    if (!empty($cfg['admin_ids']) && is_array($cfg['admin_ids'])) {
        // 3 placeholders + literal permission flags (fixes SQLSTATE[HY093])
        $ins = $pdo->prepare('INSERT IGNORE INTO staff_admins (telegram_id, full_name, role, can_reply, can_sales, can_support, is_active) VALUES (?,?,?,1,1,1,1)');
        foreach ($cfg['admin_ids'] as $tid) {
            $ins->execute(array((int)$tid, 'Admin ' . $tid, 'super'));
        }
    }
}

function sync_config_admin_ids_from_staff() {
    $rows = db()->query('SELECT telegram_id FROM staff_admins WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN);
    $cfg = bot_config();
    $cfg['admin_ids'] = array_map('intval', $rows);
    if (function_exists('save_bot_config')) {
        // only from admin context
    }
    return $cfg['admin_ids'];
}

function staff_is_active_admin($telegramId) {
    try {
        $st = db()->prepare('SELECT * FROM staff_admins WHERE telegram_id=? AND is_active=1');
        $st->execute(array((int)$telegramId));
        $row = $st->fetch();
        if ($row) return $row;
    } catch (Exception $e) {}
    return in_array((int)$telegramId, bot_config()['admin_ids'] ?? array(), true) ? array('role' => 'legacy') : null;
}

function panel_user_can($perm) {
    if (empty($_SESSION['panel_user'])) {
        // legacy session
        return !empty($_SESSION['hddland_admin']);
    }
    $u = $_SESSION['panel_user'];
    if (!empty($u['is_super'])) return true;
    $key = 'can_' . $perm;
    return !empty($u[$key]);
}

function require_panel_perm($perm) {
    if (!panel_user_can($perm)) {
        flash('err', 'Access denied for this section.');
        header('Location: index.php');
        exit;
    }
}
