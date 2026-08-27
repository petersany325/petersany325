<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

$pdo = db();
$stats = array(
    'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'open_tickets' => (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='open'")->fetchColumn(),
    'closed_tickets' => (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='closed'")->fetchColumn(),
    'products' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn(),
    'faqs' => 0,
    'menus' => 0,
    'open_requests' => 0,
    'sales_open' => 0,
);
try {
    ensure_schema();
    $stats['faqs'] = (int)$pdo->query('SELECT COUNT(*) FROM faqs WHERE is_active=1')->fetchColumn();
    $stats['menus'] = (int)$pdo->query('SELECT COUNT(*) FROM menus WHERE is_active=1')->fetchColumn();
} catch (Throwable $e) {}
try {
    $stats['open_requests'] = (int)$pdo->query("SELECT COUNT(*) FROM service_requests WHERE status='open'")->fetchColumn();
    $stats['sales_open'] = (int)$pdo->query("SELECT COUNT(*) FROM service_requests WHERE status='open' AND req_type='sales'")->fetchColumn();
} catch (Throwable $e) {}

$langRows = array();
try {
    $langRows = $pdo->query('SELECT lang, COUNT(*) AS c FROM users GROUP BY lang ORDER BY c DESC LIMIT 8')->fetchAll();
} catch (Throwable $e) {}

$latestTickets = $pdo->query("SELECT id, user_id, subject, status, created_at FROM tickets ORDER BY id DESC LIMIT 8")->fetchAll();
$latestUsers = $pdo->query("SELECT telegram_id, username, full_name, created_at FROM users ORDER BY id DESC LIMIT 8")->fetchAll();
$latestReq = array();
try {
    $latestReq = $pdo->query("SELECT id, user_id, req_type, subject, status, created_at FROM service_requests ORDER BY id DESC LIMIT 8")->fetchAll();
} catch (Throwable $e) {}

$cfg = function_exists('merge_bot_defaults_into_config') ? merge_bot_defaults_into_config(bot_config()) : bot_config();

$pageTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/layout_header.php';
?>
<div class="grid">
  <div class="card stat"><div class="label">Users</div><div class="value"><?= $stats['users'] ?></div><div class="hint">Telegram users</div></div>
  <div class="card stat"><div class="label">Open Tickets</div><div class="value"><?= $stats['open_tickets'] ?></div><div class="hint">Need reply</div></div>
  <div class="card stat"><div class="label">Open Requests</div><div class="value"><?= $stats['open_requests'] ?></div><div class="hint">Support & Sales</div></div>
  <div class="card stat"><div class="label">Sales Open</div><div class="value"><?= $stats['sales_open'] ?></div><div class="hint">Purchase inquiries</div></div>
  <div class="card stat"><div class="label">FAQs</div><div class="value"><?= $stats['faqs'] ?></div><div class="hint">Active questions</div></div>
  <div class="card stat"><div class="label">Menus</div><div class="value"><?= $stats['menus'] ?></div><div class="hint">Active buttons</div></div>
  <div class="card stat"><div class="label">Products</div><div class="value"><?= $stats['products'] ?></div><div class="hint">In shop</div></div>
  <div class="card stat"><div class="label">Closed Tickets</div><div class="value"><?= $stats['closed_tickets'] ?></div><div class="hint">Resolved</div></div>
</div>

<div class="card panel" style="margin:16px 0">
  <h2>Control center</h2>
  <p class="muted" style="margin:0 0 12px">All bot behaviour is controlled from Admin. Start here:</p>
  <div class="actions">
    <a class="btn sm" href="settings.php">Settings ★</a>
    <a class="btn sm secondary" href="menus.php">Menus</a>
    <a class="btn sm secondary" href="products.php">Products</a>
    <a class="btn sm secondary" href="faqs.php">FAQ</a>
    <a class="btn sm secondary" href="broadcast.php">Broadcast</a>
    <a class="btn sm secondary" href="settings.php?tab=webhook">Webhook</a>
  </div>
  <p class="muted" style="margin-top:12px">
    Bot: <b><?= e((string)($cfg['bot_title'] ?? 'HDD-Land Bot')) ?></b>
    · Site: <a href="<?= e((string)($cfg['site_url'] ?? '#')) ?>" target="_blank"><?= e((string)($cfg['site_url'] ?? '')) ?></a>
    <?php if (!empty($cfg['maintenance_mode'])): ?> · <span class="badge open">MAINTENANCE ON</span><?php endif; ?>
  </p>
</div>

<div class="row2">
  <div class="card panel">
    <h2>Latest Tickets</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>User</th><th>Subject</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($latestTickets as $t): ?>
          <tr>
            <td><a href="ticket_view.php?id=<?= (int)$t['id'] ?>">#<?= (int)$t['id'] ?></a></td>
            <td><code><?= e((string)$t['user_id']) ?></code></td>
            <td><?= e(substr((string)$t['subject'], 0, 60)) ?></td>
            <td><span class="badge <?= e($t['status']) ?>"><?= e($t['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$latestTickets): ?><tr><td colspan="4" class="muted">No tickets yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card panel">
    <h2>Latest Support / Sales</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>Type</th><th>Subject</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($latestReq as $r): ?>
          <tr>
            <td><a href="requests.php">#<?= (int)$r['id'] ?></a></td>
            <td><?= e((string)$r['req_type']) ?></td>
            <td><?= e(substr((string)$r['subject'], 0, 50)) ?></td>
            <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$latestReq): ?><tr><td colspan="4" class="muted">No requests yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="row2" style="margin-top:16px">
  <div class="card panel">
    <h2>Latest Users</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Telegram ID</th><th>Name</th><th>Username</th></tr></thead>
        <tbody>
        <?php foreach ($latestUsers as $u): ?>
          <tr>
            <td><code><?= e((string)$u['telegram_id']) ?></code></td>
            <td><?= e((string)$u['full_name']) ?></td>
            <td><?= e($u['username'] ? '@'.$u['username'] : '-') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$latestUsers): ?><tr><td colspan="3" class="muted">No users yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card panel">
    <h2>Languages in use</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Lang</th><th>Users</th></tr></thead>
        <tbody>
        <?php foreach ($langRows as $lr): ?>
          <tr><td><code><?= e((string)($lr['lang'] ?: 'en')) ?></code></td><td><?= (int)$lr['c'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$langRows): ?><tr><td colspan="2" class="muted">No data yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
