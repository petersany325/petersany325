<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
require_once dirname(__DIR__) . '/src/Autoload.php';

use HddLand\Bot\Services\UserOptionsService;

UserOptionsService::ensureSchema();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'add_option') {
            UserOptionsService::addOption(
                (string)($_POST['code'] ?? ''),
                trim((string)($_POST['title_en'] ?? '')),
                trim((string)($_POST['title_fa'] ?? '')),
                !empty($_POST['default_open']) ? 1 : 0,
                (int)($_POST['sort_order'] ?? 100)
            );
            flash('ok', 'Option saved.');
        } elseif ($action === 'toggle_option') {
            UserOptionsService::setOptionActive((string)($_POST['code'] ?? ''), !empty($_POST['is_active']));
            flash('ok', 'Option updated.');
        } elseif ($action === 'set_access') {
            $tg = (int)($_POST['telegram_id'] ?? 0);
            $code = (string)($_POST['code'] ?? '');
            if ($tg <= 0 || $code === '') {
                throw new RuntimeException('Invalid user/option');
            }
            UserOptionsService::setUserAccess($tg, $code, !empty($_POST['is_open']));
            flash('ok', "Access for {$tg} / {$code} updated.");
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: user_options.php' . (!empty($_POST['telegram_id']) ? ('?tg=' . (int)$_POST['telegram_id']) : ''));
    exit;
}

$options = UserOptionsService::allOptions(false);
$tgFilter = (int)($_GET['tg'] ?? 0);
$users = db()->query('SELECT telegram_id, username, full_name, email, first_name, last_name FROM users ORDER BY id DESC LIMIT 200')->fetchAll();

$pageTitle = 'User Options';
$active = 'user_options';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <h2>Option catalog</h2>
  <p class="muted">اپشن‌های پنل کاربر. می‌توانید اپشن جدید اضافه کنید و برای هر کاربر دسترسی باز/بسته کنید.</p>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Code</th><th>EN</th><th>FA</th><th>Default open</th><th>Active</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($options as $o): ?>
        <tr>
          <td><code><?= e((string)$o['code']) ?></code></td>
          <td><?= e((string)$o['title_en']) ?></td>
          <td><?= e((string)$o['title_fa']) ?></td>
          <td><?= !empty($o['default_open']) ? 'yes' : 'no' ?></td>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="toggle_option">
              <input type="hidden" name="code" value="<?= e((string)$o['code']) ?>">
              <label><input type="checkbox" name="is_active" value="1" <?= !empty($o['is_active'])?'checked':'' ?> onchange="this.form.submit()"> active</label>
            </form>
          </td>
          <td class="muted"><?= e((string)($o['description_en'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h3 style="margin-top:20px">Add / update option</h3>
  <form method="post" class="stack" style="max-width:520px">
    <input type="hidden" name="action" value="add_option">
    <label>Code (a-z0-9_)</label>
    <input name="code" required placeholder="vip_pack">
    <label>Title EN</label>
    <input name="title_en" required>
    <label>Title FA</label>
    <input name="title_fa" required>
    <label>Sort</label>
    <input type="number" name="sort_order" value="100">
    <label><input type="checkbox" name="default_open" value="1"> Open by default for all users</label>
    <button class="btn" type="submit">Save option</button>
  </form>
</div>

<div class="card panel" style="margin-top:16px">
  <h2>Per-user access</h2>
  <form method="get" class="actions" style="margin-bottom:12px">
    <label>Telegram ID
      <input name="tg" value="<?= $tgFilter ?: '' ?>" placeholder="6478...">
    </label>
    <button class="btn secondary" type="submit">Load</button>
  </form>

  <?php if ($tgFilter > 0): ?>
    <p>User <code><?= (int)$tgFilter ?></code></p>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Option</th><th>Open?</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($options as $o): if (empty($o['is_active'])) continue; $code=(string)$o['code']; $open=UserOptionsService::isOpen($tgFilter,$code); ?>
          <tr>
            <td><?= e($code) ?> — <?= e((string)$o['title_en']) ?></td>
            <td><span class="badge <?= $open?'open':'closed' ?>"><?= $open?'OPEN':'CLOSED' ?></span></td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="set_access">
                <input type="hidden" name="telegram_id" value="<?= (int)$tgFilter ?>">
                <input type="hidden" name="code" value="<?= e($code) ?>">
                <label><input type="checkbox" name="is_open" value="1" <?= $open?'checked':'' ?> onchange="this.form.submit()"> grant</label>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <h3 style="margin-top:16px">Recent users</h3>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Telegram</th><th>Name</th><th>Email</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><code><?= e((string)$u['telegram_id']) ?></code> <?= $u['username'] ? '@'.e((string)$u['username']) : '' ?></td>
          <td><?= e(trim((string)(($u['first_name'] ?? '').' '.($u['last_name'] ?? ''))) ?: (string)$u['full_name']) ?></td>
          <td><?= e((string)($u['email'] ?? '')) ?: '<span class="muted">—</span>' ?></td>
          <td><a class="btn sm secondary" href="user_options.php?tg=<?= (int)$u['telegram_id'] ?>">Options</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
