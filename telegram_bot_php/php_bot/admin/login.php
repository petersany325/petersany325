<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/auth.php';

$error = '';
$cfg = bot_config();
$needsSetup = empty($cfg['admin_password_hash']);
// if panel user exists, setup done
try {
    if (function_exists('ensure_admins_schema')) ensure_admins_schema();
    $c = (int)db()->query('SELECT COUNT(*) FROM panel_users')->fetchColumn();
    if ($c > 0) $needsSetup = false;
} catch (Exception $e) {}

if (admin_logged_in() && !$needsSetup) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$needsSetup) {
    $user = isset($_POST['username']) ? trim($_POST['username']) : '';
    $pass = isset($_POST['password']) ? $_POST['password'] : '';
    if (admin_login($user, $pass)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Login — HDD-Land Admin</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:Segoe UI,Tahoma,sans-serif;background:#0b1220;color:#e8eef8;padding:16px}
.card{width:min(420px,100%);background:#121a2b;border:1px solid #243044;border-radius:20px;padding:28px;box-shadow:0 18px 50px rgba(0,0,0,.35)}
h1{margin:0 0 6px;font-size:1.4rem}p{color:#93a0b8;margin:0 0 16px}
label{display:block;margin:12px 0 6px;color:#93a0b8}
input{width:100%;box-sizing:border-box;padding:14px;border-radius:12px;border:1px solid #243044;background:#0d1524;color:#fff;font:inherit;font-size:16px}
button{width:100%;margin-top:18px;border:0;cursor:pointer;font-weight:700;background:linear-gradient(135deg,#2f80ed,#1f6fd4);color:#fff;padding:14px;border-radius:10px;min-height:48px}
.err{background:rgba(235,87,87,.15);border:1px solid rgba(235,87,87,.35);color:#ffb4b4;padding:12px;border-radius:12px;margin-bottom:12px}
.logo{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;font-weight:800;background:linear-gradient(135deg,#2f80ed,#56ccf2);color:#06101f;margin-bottom:14px}
a{color:#56ccf2}
</style>
</head>
<body>
<div class="card">
  <div class="logo">HL</div>
  <h1>HDD-Land Admin</h1>
  <p>Professional control panel · staff & permissions</p>
  <?php if ($needsSetup): ?>
    <div class="err">Setup password first: <a href="../setup_admin.php">setup_admin.php</a></div>
  <?php endif; ?>
  <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
  <?php if (!$needsSetup): ?>
  <form method="post">
    <label>Username</label>
    <input name="username" required value="peter_sany" autocomplete="username">
    <label>Password</label>
    <input type="password" name="password" required autocomplete="current-password">
    <button type="submit">Login</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
