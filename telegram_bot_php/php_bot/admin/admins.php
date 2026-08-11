<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
try { ensure_schema(); } catch (Throwable $e) {}
try {
    if (function_exists('ensure_admins_schema')) {
        ensure_admins_schema();
    }
} catch (Throwable $e) {}
require_panel_perm('admins');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add_staff') {
            $tid = (int)($_POST['telegram_id'] ?? 0);
            $name = trim((string)($_POST['full_name'] ?? ''));
            $role = trim((string)($_POST['role'] ?? 'support'));
            if ($tid <= 0) throw new RuntimeException('Telegram ID required.');
            $pdo->prepare('INSERT INTO staff_admins (telegram_id, full_name, role, can_reply, can_sales, can_support, can_broadcast, can_ban, is_active) VALUES (?,?,?,?,?,?,?,?,1)
                ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), role=VALUES(role), is_active=1')
                ->execute(array(
                    $tid, $name ?: ('Admin '.$tid), $role,
                    isset($_POST['can_reply'])?1:0,
                    isset($_POST['can_sales'])?1:0,
                    isset($_POST['can_support'])?1:0,
                    isset($_POST['can_broadcast'])?1:0,
                    isset($_POST['can_ban'])?1:0,
                ));
            // sync config admin_ids
            $ids = $pdo->query('SELECT telegram_id FROM staff_admins WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN);
            $cfg = bot_config();
            $cfg['admin_ids'] = array_map('intval', $ids);
            save_bot_config($cfg);
            flash('ok', 'Telegram staff admin saved & synced.');
        } elseif ($action === 'toggle_staff') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE staff_admins SET is_active = 1 - is_active WHERE id=?')->execute(array($id));
            $ids = $pdo->query('SELECT telegram_id FROM staff_admins WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN);
            $cfg = bot_config();
            $cfg['admin_ids'] = array_map('intval', $ids);
            save_bot_config($cfg);
            flash('ok', 'Staff status updated.');
        } elseif ($action === 'delete_staff') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM staff_admins WHERE id=?')->execute(array($id));
            $ids = $pdo->query('SELECT telegram_id FROM staff_admins WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN);
            $cfg = bot_config();
            $cfg['admin_ids'] = array_map('intval', $ids);
            save_bot_config($cfg);
            flash('ok', 'Staff admin deleted.');
        } elseif ($action === 'add_panel') {
            $user = trim((string)($_POST['username'] ?? ''));
            $pass = (string)($_POST['password'] ?? '');
            $name = trim((string)($_POST['display_name'] ?? ''));
            if ($user === '' || strlen($pass) < 6) throw new RuntimeException('Username + password (6+) required.');
            $perms = array('tickets','requests','products','menus','faqs','users','languages','branding','settings','admins','health');
            $cols = array('username','password_hash','display_name','is_super');
            $vals = array($user, password_hash($pass, PASSWORD_DEFAULT), $name, isset($_POST['is_super'])?1:0);
            foreach ($perms as $p) {
                $cols[] = 'can_' . $p;
                $vals[] = isset($_POST['can_'.$p]) ? 1 : 0;
            }
            $cols[] = 'is_active';
            $vals[] = 1;
            $ph = implode(',', array_fill(0, count($vals), '?'));
            $pdo->prepare('INSERT INTO panel_users ('.implode(',', $cols).') VALUES ('.$ph.')')->execute($vals);
            flash('ok', 'Panel user created.');
        } elseif ($action === 'delete_panel') {
            $id = (int)($_POST['id'] ?? 0);
            $me = $_SESSION['panel_user']['id'] ?? 0;
            if ($id === (int)$me) throw new RuntimeException('Cannot delete yourself.');
            $pdo->prepare('DELETE FROM panel_users WHERE id=? AND is_super=0')->execute(array($id));
            flash('ok', 'Panel user deleted (super users protected).');
        } elseif ($action === 'toggle_panel') {
            $pdo->prepare('UPDATE panel_users SET is_active = 1 - is_active WHERE id=? AND is_super=0')->execute(array((int)($_POST['id'] ?? 0)));
            flash('ok', 'Panel user toggled.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: admins.php');
    exit;
}

$staff = $pdo->query('SELECT * FROM staff_admins ORDER BY id DESC')->fetchAll();
$panel = $pdo->query('SELECT * FROM panel_users ORDER BY id ASC')->fetchAll();

$pageTitle = 'Admins & Permissions';
$active = 'admins';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel" style="margin-bottom:14px">
  <p class="muted" style="margin:0">Manage <b>Telegram staff</b> (receive requests/replies) and <b>web panel users</b> with granular permissions (edit/delete access).</p>
</div>

<div class="row2">
  <div class="card panel">
    <h2>➕ Add Telegram Staff Admin</h2>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="add_staff">
      <label>Telegram ID *</label>
      <input name="telegram_id" required placeholder="123456789" dir="ltr">
      <label>Full name</label>
      <input name="full_name" placeholder="Support Agent">
      <label>Role</label>
      <select name="role">
        <option value="super">Super</option>
        <option value="manager">Manager</option>
        <option value="sales">Sales</option>
        <option value="support" selected>Support</option>
      </select>
      <label><input type="checkbox" name="can_reply" checked> Can reply</label>
      <label><input type="checkbox" name="can_support" checked> Support requests</label>
      <label><input type="checkbox" name="can_sales" checked> Sales requests</label>
      <label><input type="checkbox" name="can_broadcast"> Broadcast</label>
      <label><input type="checkbox" name="can_ban"> Ban tools</label>
      <button class="btn" type="submit" style="margin-top:12px">Save Staff Admin</button>
    </form>
  </div>

  <div class="card panel">
    <h2>➕ Add Web Panel User</h2>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="add_panel">
      <label>Username *</label>
      <input name="username" required>
      <label>Password *</label>
      <input type="password" name="password" required minlength="6">
      <label>Display name</label>
      <input name="display_name">
      <label><input type="checkbox" name="is_super"> Super admin (all access)</label>
      <div class="row2">
        <?php foreach (array('tickets','requests','products','menus','faqs','users','languages','branding','settings','admins','health') as $p): ?>
          <label><input type="checkbox" name="can_<?= $p ?>" <?= in_array($p,array('tickets','requests'),true)?'checked':'' ?>> <?= e($p) ?></label>
        <?php endforeach; ?>
      </div>
      <button class="btn" type="submit" style="margin-top:12px">Create Panel User</button>
    </form>
  </div>
</div>

<div class="card panel" style="margin-top:16px">
  <h2>Telegram Staff</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>Telegram</th><th>Name</th><th>Role</th><th>Perms</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($staff as $s): ?>
        <tr>
          <td>#<?= (int)$s['id'] ?></td>
          <td><code><?= e((string)$s['telegram_id']) ?></code></td>
          <td><?= e((string)$s['full_name']) ?></td>
          <td><?= e($s['role']) ?></td>
          <td class="muted" style="font-size:.8rem">
            <?= $s['can_reply']?'Reply ':'' ?>
            <?= $s['can_support']?'Support ':'' ?>
            <?= $s['can_sales']?'Sales ':'' ?>
            <?= $s['can_ban']?'Ban':'' ?>
          </td>
          <td><span class="badge <?= $s['is_active']?'open':'closed' ?>"><?= $s['is_active']?'on':'off' ?></span></td>
          <td class="actions">
            <form method="post" style="display:inline"><input type="hidden" name="action" value="toggle_staff"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn sm secondary" type="submit">Toggle</button></form>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete staff?')"><input type="hidden" name="action" value="delete_staff"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn sm danger" type="submit">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$staff): ?><tr><td colspan="7" class="muted">No staff yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card panel" style="margin-top:16px">
  <h2>Web Panel Users</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>User</th><th>Access</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($panel as $u): ?>
        <tr>
          <td><b><?= e($u['username']) ?></b><br><span class="muted"><?= e((string)$u['display_name']) ?></span>
            <?php if ($u['is_super']): ?><span class="badge open">super</span><?php endif; ?>
          </td>
          <td class="muted" style="font-size:.75rem">
            <?php foreach (array('tickets','requests','products','menus','faqs','admins','settings','health') as $p): ?>
              <?php if (!empty($u['can_'.$p]) || $u['is_super']): ?><?= e($p) ?> <?php endif; ?>
            <?php endforeach; ?>
          </td>
          <td><span class="badge <?= $u['is_active']?'open':'closed' ?>"><?= $u['is_active']?'on':'off' ?></span></td>
          <td class="actions">
            <?php if (!(int)$u['is_super']): ?>
            <form method="post" style="display:inline"><input type="hidden" name="action" value="toggle_panel"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn sm secondary" type="submit">Toggle</button></form>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete user?')"><input type="hidden" name="action" value="delete_panel"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn sm danger" type="submit">Delete</button></form>
            <?php else: ?><span class="muted">protected</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
