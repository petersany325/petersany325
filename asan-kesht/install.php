<?php

declare(strict_types=1);

const INSTALL_TOKEN = '%%INSTALL_TOKEN%%';

if (is_file(__DIR__ . '/storage/installed.lock')) {
    http_response_code(410);
    exit('نصب قبلاً انجام شده است.');
}

if (INSTALL_TOKEN === '%' . '%INSTALL_TOKEN%%') {
    http_response_code(403);
    exit('توکن نصب تنظیم نشده است.');
}

require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/Database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
if (!hash_equals(INSTALL_TOKEN, $token)) {
    http_response_code(403);
    exit('دسترسی نصب مجاز نیست.');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $name = trim((string) ($_POST['db_name'] ?? ''));
    $user = trim((string) ($_POST['db_user'] ?? ''));
    $pass = (string) ($_POST['db_pass'] ?? '');
    $adminName = trim((string) ($_POST['admin_name'] ?? ''));
    $adminUser = trim((string) ($_POST['admin_user'] ?? ''));
    $adminPass = (string) ($_POST['admin_pass'] ?? '');
    $adminPhone = normalize_phone((string) ($_POST['admin_phone'] ?? ''));
    if ($name === '' || $user === '' || $adminName === '' || !preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $adminUser) || strlen($adminPass) < 8) {
        $error = 'اطلاعات پایگاه و مدیر را کامل کنید. رمز حداقل ۸ نویسه.';
    } elseif ($adminPhone !== '' && !is_mobile($adminPhone)) {
        $error = 'موبایل مدیر معتبر نیست.';
    } else {
        try {
            $config = [
                'db' => ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass],
                'debug' => false,
            ];
            $exported = var_export($config, true);
            file_put_contents(__DIR__ . '/config.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn {$exported};\n");
            $db = Database::connect($config['db']);
            $pdo = $db->pdo();
            foreach (['settings' => 'skey', 'plugins' => 'enabled', 'schema_migrations' => 'applied_at'] as $table => $column) {
                $found = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table))->fetch();
                if (!$found) {
                    continue;
                }
                $columns = [];
                foreach ($pdo->query('SHOW COLUMNS FROM `' . $table . '`') as $col) {
                    $columns[] = $col['Field'];
                }
                if (!in_array($column, $columns, true)) {
                    continue;
                }
                $count = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
                if ($count === 0) {
                    $pdo->exec('DROP TABLE `' . $table . '`');
                }
            }
            $db->exec(
                'CREATE TABLE IF NOT EXISTS ak_users (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    username VARCHAR(60) NULL,
                    phone VARCHAR(20) NULL,
                    password_hash VARCHAR(255) NULL,
                    role VARCHAR(20) NOT NULL,
                    active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL,
                    UNIQUE KEY username (username),
                    UNIQUE KEY phone (phone)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $db->exec(
                'CREATE TABLE IF NOT EXISTS ak_settings (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    plugin VARCHAR(50) NOT NULL,
                    skey VARCHAR(80) NOT NULL,
                    svalue MEDIUMTEXT NULL,
                    UNIQUE KEY plugin_key (plugin, skey)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $db->exec(
                'CREATE TABLE IF NOT EXISTS ak_plugins (
                    id VARCHAR(50) PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    description VARCHAR(255) NOT NULL,
                    enabled TINYINT(1) NOT NULL DEFAULT 1
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $db->exec(
                'CREATE TABLE IF NOT EXISTS ak_schema_migrations (
                    id VARCHAR(80) PRIMARY KEY,
                    applied_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $phoneValue = $adminPhone === '' ? null : $adminPhone;
            $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);
            $existingAdmin = $db->one('SELECT id FROM ak_users WHERE username = ?', [$adminUser]);
            if ($existingAdmin) {
                $db->run(
                    'UPDATE ak_users SET name = ?, phone = ?, password_hash = ?, role = "admin", active = 1 WHERE id = ?',
                    [$adminName, $phoneValue, $passwordHash, $existingAdmin['id']]
                );
            } else {
                $db->run(
                    'INSERT INTO ak_users (name, username, phone, password_hash, role, active, created_at) VALUES (?, ?, ?, ?, "admin", 1, NOW())',
                    [$adminName, $adminUser, $phoneValue, $passwordHash]
                );
            }
            require __DIR__ . '/app/bootstrap.php';
            $application = App::boot(__DIR__);
            $application->loadPlugins();
            file_put_contents(__DIR__ . '/storage/installed.lock', date('c'));
            @unlink(__FILE__);
            echo 'نصب انجام شد. نام کاربری مدیر: ' . e($adminUser);
            exit;
        } catch (Throwable $e) {
            @unlink(__DIR__ . '/config.php');
            $error = 'نصب ناموفق: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>نصب آسان‌کشت</title>
  <link rel="stylesheet" href="<?= e(url('/assets/css/admin.css')) ?>">
</head>
<body>
  <main class="main">
    <h1>نصب آسان‌کشت</h1>
    <?php if ($error !== ''): ?><p class="flash flash-error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack">
      <input type="hidden" name="token" value="<?= e(INSTALL_TOKEN) ?>">
      <label>میزبان پایگاه <input name="db_host" value="localhost"></label>
      <label>نام پایگاه <input name="db_name" required></label>
      <label>کاربر پایگاه <input name="db_user" required></label>
      <label>رمز پایگاه <input name="db_pass" type="password" required></label>
      <label>نام مدیر <input name="admin_name" required></label>
      <label>نام کاربری مدیر <input name="admin_user" required value="admin"></label>
      <label>رمز مدیر <input name="admin_pass" type="password" required></label>
      <label>موبایل مدیر <input name="admin_phone"></label>
      <button type="submit">نصب افزونه‌ها</button>
    </form>
  </main>
</body>
</html>
