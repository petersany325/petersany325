<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $active */
$pageTitle = $pageTitle ?? 'Admin';
$active = $active ?? '';
$flash = take_flash();
$user = $_SESSION['hddland_admin_user'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b1220">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<title><?= e($pageTitle) ?> — HDD-Land Admin</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="mobile-bar">
  <button type="button" class="menu-btn" id="menuToggle" aria-label="Open menu">☰</button>
  <div class="logo">HL</div>
  <div class="title"><?= e($pageTitle) ?></div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="logo">HL</div>
    <div>
      <strong>HDD-Land</strong>
      <small>Admin Panel · Mobile Ready</small>
    </div>
  </div>
  <nav>
    <a class="<?= $active === 'dashboard' ? 'on' : '' ?>" href="index.php">Dashboard</a>
    <a class="<?= $active === 'tickets' ? 'on' : '' ?>" href="tickets.php">Tickets</a>
    <a class="<?= $active === 'ticket_fields' ? 'on' : '' ?>" href="ticket_fields.php">Ticket Fields</a>
    <a class="<?= $active === 'requests' ? 'on' : '' ?>" href="requests.php">Support & Sales</a>
    <a class="<?= $active === 'faqs' ? 'on' : '' ?>" href="faqs.php">FAQ / Questions</a>
    <a class="<?= $active === 'menus' ? 'on' : '' ?>" href="menus.php">Menus & Categories</a>
    <a class="<?= $active === 'products' ? 'on' : '' ?>" href="products.php">Products</a>
    <a class="<?= $active === 'broadcast' ? 'on' : '' ?>" href="broadcast.php">Broadcast</a>
    <a class="<?= $active === 'languages' ? 'on' : '' ?>" href="languages.php">Languages</a>
    <a class="<?= $active === 'users' ? 'on' : '' ?>" href="users.php">Users</a>
    <a class="<?= $active === 'user_options' ? 'on' : '' ?>" href="user_options.php">User Options</a>
    <a class="<?= $active === 'receipts' ? 'on' : '' ?>" href="receipts.php">Receipts & Licenses</a>
    <a class="<?= $active === 'admins' ? 'on' : '' ?>" href="admins.php">Admins & Access</a>
    <a class="<?= $active === 'settings' ? 'on' : '' ?>" href="settings.php">Settings ★</a>
    <a class="<?= $active === 'password' ? 'on' : '' ?>" href="password.php">Change Password</a>
    <a class="<?= $active === 'branding' ? 'on' : '' ?>" href="settings.php?tab=branding">Bot Title</a>
    <a class="<?= $active === 'health' ? 'on' : '' ?>" href="health.php">Health & Repair</a>
  </nav>
  <div class="side-foot">
    <div class="who">Signed in as <b><?= e($user) ?></b></div>
    <a class="logout" href="logout.php">Logout</a>
  </div>
</aside>
<main class="main">
  <header class="top">
    <h1><?= e($pageTitle) ?></h1>
    <a class="ghost" href="<?= e(bot_config()['site_url'] ?? 'https://hdd-land.com') ?>" target="_blank" rel="noopener">Open Website</a>
  </header>
  <?php if ($flash): ?>
    <div class="alert <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>
  <div class="content">
