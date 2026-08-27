<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
require_once dirname(__DIR__) . '/src/Autoload.php';

try { ensure_schema(); } catch (Throwable $e) {}

$report = null;
$menuHealth = null;
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
        } elseif ($action === 'sync_menus') {
            $n = function_exists('ensure_professional_menus') ? ensure_professional_menus(db()) : 0;
            $report = array(array('ok', 'Professional menus synced (+' . (int)$n . ')'));
            flash('ok', 'Menus synced.');
        } elseif ($action === 'menu_health') {
            $menuHealth = \HddLand\Bot\Services\MenuHealthService::run();
            flash('ok', 'Menu health check finished.');
        } elseif ($action === 'init_settings') {
            $cfg = merge_bot_defaults_into_config(bot_config());
            save_bot_config($cfg);
            $report = array(array('ok', 'All default settings keys written to config.local.php'));
            flash('ok', 'Settings defaults initialized.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
        $report = array(array('err', $e->getMessage()));
    }
}

// Auto-run menu health on GET for online visibility
if ($menuHealth === null) {
    try {
        $menuHealth = \HddLand\Bot\Services\MenuHealthService::run();
    } catch (Throwable $e) {
        $menuHealth = array(array(
            'ok' => false,
            'level' => 'err',
            'code' => 'fatal',
            'title' => 'Menu health',
            'detail' => $e->getMessage(),
        ));
    }
}

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
$archOk = is_file($root . '/src/BotKernel.php');

$brokenMenus = 0;
foreach ($menuHealth as $mh) {
    if (isset($mh['level']) && $mh['level'] === 'err') {
        $brokenMenus++;
    }
}

$pageTitle = 'Health & Repair';
$active = 'health';
require __DIR__ . '/layout_header.php';
?>
<div class="grid">
  <div class="card stat"><div class="label">Database</div><div class="value" style="font-size:1rem"><?= e($status['db'] ?? '-') ?></div></div>
  <div class="card stat"><div class="label">Users</div><div class="value"><?= (int)($status['users'] ?? 0) ?></div></div>
  <div class="card stat"><div class="label">Menus / FAQs</div><div class="value"><?= (int)($status['menus'] ?? 0) ?> / <?= (int)($status['faqs'] ?? 0) ?></div></div>
  <div class="card stat"><div class="label">Menu issues</div><div class="value" style="color:<?= $brokenMenus ? '#fca5a5' : '#86efac' ?>"><?= (int)$brokenMenus ?></div></div>
</div>

<div class="row2">
  <div class="card panel">
    <h2>Automatic Repair</h2>
    <p class="muted">Checks database tables, reseeds missing data, fixes user language, clears broken cache, verifies bot files & permissions.</p>
    <div class="actions" style="margin-top:12px;flex-wrap:wrap">
      <form method="post"><input type="hidden" name="action" value="repair"><button class="btn" type="submit">🔧 Run Full Repair</button></form>
      <form method="post"><input type="hidden" name="action" value="cache"><button class="btn secondary" type="submit">🧹 Clear Caches</button></form>
      <form method="post"><input type="hidden" name="action" value="schema"><button class="btn secondary" type="submit">🗄 Refresh Schema</button></form>
      <form method="post"><input type="hidden" name="action" value="sync_menus"><button class="btn secondary" type="submit">📑 Sync Professional Menus</button></form>
      <form method="post"><input type="hidden" name="action" value="init_settings"><button class="btn secondary" type="submit">⚙️ Init Settings Defaults</button></form>
      <form method="post"><input type="hidden" name="action" value="menu_health"><button class="btn secondary" type="submit">🩺 Re-check Menus Online</button></form>
    </div>
    <p class="muted" style="margin-top:14px">
      Plugins: HealthRepair <?= $pluginOk ? '<span class="badge open">ON</span>' : '<span class="badge closed">OFF</span>' ?>
      · SmartI18n <?= $smartOk ? '<span class="badge open">ON</span>' : '<span class="badge closed">OFF</span>' ?>
      · Layered core <?= $archOk ? '<span class="badge open">ON</span>' : '<span class="badge closed">OFF</span>' ?>
    </p>
  </div>

  <div class="card panel">
    <h2>Quick Links</h2>
    <div class="actions" style="flex-wrap:wrap">
      <a class="btn secondary" href="settings.php?tab=features">Features</a>
      <a class="btn secondary" href="settings.php?tab=commerce">Commerce</a>
      <a class="btn secondary" href="settings.php?tab=license">License</a>
      <a class="btn secondary" href="settings.php?tab=growth">Growth</a>
      <a class="btn secondary" href="menus.php">Menus</a>
      <a class="btn secondary" href="check.php" target="_blank">Diagnostics</a>
    </div>
  </div>
</div>

<div class="card panel" style="margin-top:16px">
  <h2>Menu Health (online)</h2>
  <p class="muted">Live validation of every menu row + built-in professional callbacks.</p>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Status</th><th>Item</th><th>Detail</th></tr></thead>
      <tbody>
      <?php foreach ($menuHealth as $r):
        $level = $r['level'] ?? ($r['ok'] ? 'ok' : 'err');
        $badge = $level === 'ok' ? 'open' : ($level === 'warn' ? 'warn' : 'closed');
        ?>
        <tr>
          <td><span class="badge <?= e($badge) ?>"><?= e($level) ?></span></td>
          <td><b><?= e((string)($r['title'] ?? '')) ?></b><div class="muted" style="font-size:.8rem"><?= e((string)($r['code'] ?? '')) ?></div></td>
          <td><?= e((string)($r['detail'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
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
