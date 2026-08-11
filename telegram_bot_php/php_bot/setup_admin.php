<?php
/**
 * One-time setup for admin panel password (for already-installed bots)
 * Open: https://yourdomain.com/telegram_bot/php_bot/setup_admin.php
 * Delete this file after setup.
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
session_start();

$cfg = bot_config();
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string)($_POST['username'] ?? 'admin'));
    $pass = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');
    try {
        if ($user === '') {
            $user = 'admin';
        }
        if (strlen($pass) < 6) {
            throw new RuntimeException('Password must be at least 6 characters.');
        }
        if ($pass !== $pass2) {
            throw new RuntimeException('Passwords do not match.');
        }
        $cfg['admin_username'] = $user;
        $cfg['admin_password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        $file = __DIR__ . '/config.local.php';
        $code = "<?php\nreturn " . var_export($cfg, true) . ";\n";
        if (file_put_contents($file, $code) === false) {
            throw new RuntimeException('Cannot write config.local.php — check permissions.');
        }
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup Admin Panel</title>
<style>
body{font-family:Tahoma,sans-serif;background:#0f172a;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0}
.card{background:#1e293b;padding:28px;border-radius:16px;width:min(420px,92vw)}
input{width:100%;padding:12px;margin:8px 0 14px;border-radius:10px;border:1px solid #334155;background:#0f172a;color:#fff;box-sizing:border-box}
button{width:100%;padding:12px;border:0;border-radius:10px;background:#38bdf8;font-weight:700;cursor:pointer}
.err{background:#7f1d1d;padding:10px;border-radius:8px;margin-bottom:12px}
.ok{background:#14532d;padding:10px;border-radius:8px;margin-bottom:12px}
a{color:#7dd3fc}
</style>
</head>
<body>
<div class="card">
  <h2>ساخت رمز پنل ادمین</h2>
  <?php if ($done): ?>
    <div class="ok">رمز ذخیره شد.</div>
    <p>الان وارد شوید:</p>
    <p><a href="admin/login.php"><b>ورود به پنل ادمین</b></a></p>
    <p style="color:#94a3b8;font-size:.9rem">برای امنیت، همین فایل <code>setup_admin.php</code> را حذف کنید.</p>
  <?php else: ?>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <label>نام کاربری</label>
      <input name="username" value="admin" required>
      <label>رمز عبور</label>
      <input type="password" name="password" required>
      <label>تکرار رمز</label>
      <input type="password" name="password2" required>
      <button type="submit">ذخیره و فعال‌سازی</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
