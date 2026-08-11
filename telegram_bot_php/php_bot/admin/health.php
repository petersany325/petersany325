<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
try { ensure_schema(); } catch (Throwable $e) {}

$report = null;
$actionDone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if (!class_exists('HealthRepairPlugin')) {
            $plugin = dirname(__DIR__) . '/plugins/HealthRepair/plugin.php';
            if (is_file($plugin)) {
                require_once $plugin;
            }
        }
        if ($action === 'repair') {
            if (!class_exists('HealthRepairPlugin')) {
                throw new RuntimeException('HealthRepair plugin not loaded. Upload plugins/HealthRepair/');
            }
            $report = HealthRepairPlugin::run_full_repair();
            $actionDone = 'Full repair completed';
            flash('ok', 'System repair finished. See report below.');
        } elseif ($action === 'cache') {
            if (!class_exists('HealthRepairPlugin')) {
                throw new RuntimeException('HealthRepair plugin not loaded.');
            }
            $report = HealthRepairPlugin::clear_all_caches();
            $actionDone = 'Caches cleared';
            flash('ok', 'Caches cleared.');
        } elseif ($action === 'schema') {
            ensure_schema();
            $report = array(array('ok', 'ensure_schema() executed'));
            flash('ok', 'Schema refreshed.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
        $report = array(array('err', $e->getMessage()));
    }
}

// Live status snapshot
$status = array();
try {
    $pdo = db();
    $status['db'] = 'connected';
    $status['users'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $status['menus'] = (int)$pdo->query('SELECT COUNT(*) FROM menus')->fetchColumn();
    $status['faqs'] = (int)$pdo->query('SELECT COUNT(*) FROM faqs')->fetchColumn();
    $status['tickets_open'] = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='open'")->fetchColumn();
    try {
        $status['cache'] = (int)$pdo->query('SELECT COUNT(*) FROM i18n_cache')->fetchColumn();
    } catch (Exception $e) {
        $status['cache'] = 0;
    }
} catch (Exception $e) {
    $status['db'] = 'error: ' . $e->getMessage();
}

$root = dirname(__DIR__);
$pluginOk = is_file($root . '/plugins/HealthRepair/plugin.php');
$smartOk = is_file($root . '/plugins/SmartI18n/plugin.php');

$pageTitle = 'Health & Repair';
$active = 'health';
require __DIR__ . '/layout_header.php';
?>
<div class="grid">
  <div class="card stat"><div class="label">Database</div><div class="value" style="font-size:1rem"><?= e($status['db'] ?? '-') ?></div></div>
  <div class="card stat"><div class="label">Users</div><div class="value"><?= (int)($status['users'] ?? 0) ?></div></div>
  <div class="card stat"><div class="label">Menus / FAQs</div><div class="value"><?= (int)($status['menus'] ?? 0) ?> / <?= (int)($status['faqs'] ?? 0) ?></div></div>
  <div class="card stat"><div class="label">i18n Cache</div><div class="value"><?= (int)($status['cache'] ?? 0) ?></div></div>
</div>

<div class="row2">
  <div class="card panel">
    <h2>Automatic Repair</h2>
    <p class="muted">Checks database tables, reseeds missing data, fixes user language, clears broken cache, verifies bot files & permissions.</p>
    <div class="actions" style="margin-top:12px">
      <form method="post">
        <input type="hidden" name="action" value="repair">
        <button class="btn" type="submit">🔧 Run Full Repair</button>
      </form>
      <form method="post">
        <input type="hidden" name="action" value="cache">
        <button class="btn secondary" type="submit">🧹 Clear Caches</button>
      </form>
      <form method="post">
        <input type="hidden" name="action" value="schema">
        <button class="btn secondary" type="submit">🗄 Refresh Schema</button>
      </form>
    </div>
    <p class="muted" style="margin-top:14px">
      Plugins:
      HealthRepair <?= $pluginOk ? '<span class="badge open">ON</span>' : '<span class="badge closed">OFF</span>' ?>
      · SmartI18n <?= $smartOk ? '<span class="badge open">ON</span>' : '<span class="badge closed">OFF</span>' ?>
    </p>
  </div>

  <div class="card panel">
    <h2>Quick Links</h2>
    <div class="actions">
      <a class="btn secondary" href="branding.php">✏️ Bot Title / Branding</a>
      <a class="btn secondary" href="menus.php">Menus</a>
      <a class="btn secondary" href="languages.php">Languages</a>
      <a class="btn secondary" href="settings.php">Settings</a>
      <a class="btn secondary" href="check.php" target="_blank">Diagnostics</a>
    </div>
    <p class="muted" style="margin-top:16px">Safe to run after every update. Does not delete your products, tickets, or custom menus (only reseeds if empty).</p>
  </div>
</div>

<?php if ($report): ?>
<div class="card panel" style="margin-top:16px">
  <h2>Repair Report <?= $actionDone ? '— ' . e($actionDone) : '' ?></h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Status</th><th>Message</th></tr></thead>
      <tbody>
      <?php foreach ($report as $r): ?>
        <tr>
          <td><span class="badge <?= $r[0]==='ok'?'open':'closed' ?>"><?= e($r[0]) ?></span></td>
          <td><?= e($r[1]) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/layout_footer.php'; ?>
