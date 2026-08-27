<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
try { ensure_schema(); } catch (Throwable $e) {}
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $code = strtolower(trim((string)($_POST['code'] ?? '')));
            $name = trim((string)($_POST['name'] ?? ''));
            $native = trim((string)($_POST['native_name'] ?? ''));
            $flag = trim((string)($_POST['flag'] ?? ''));
            $sort = (int)($_POST['sort_order'] ?? 0);
            if (!preg_match('/^[a-z]{2,5}$/', $code)) {
                throw new RuntimeException('Language code must be 2-5 letters (en, fa, ru...)');
            }
            if ($name === '' || $native === '') {
                throw new RuntimeException('Name fields required.');
            }
            $pdo->prepare('INSERT INTO languages (code, name, native_name, flag, is_default, is_active, sort_order) VALUES (?,?,?,?,0,1,?)')
                ->execute(array($code, $name, $native, $flag, $sort));
            flash('ok', 'Language added.');
        } elseif ($action === 'toggle') {
            $code = $_POST['code'] ?? '';
            $pdo->prepare('UPDATE languages SET is_active = 1 - is_active WHERE code=?')->execute(array($code));
            flash('ok', 'Language status updated.');
        } elseif ($action === 'default') {
            $code = $_POST['code'] ?? '';
            $pdo->exec('UPDATE languages SET is_default=0');
            $pdo->prepare('UPDATE languages SET is_default=1, is_active=1 WHERE code=?')->execute(array($code));
            flash('ok', 'Default language set.');
        } elseif ($action === 'delete') {
            $code = $_POST['code'] ?? '';
            if ($code === 'en') {
                throw new RuntimeException('Cannot delete English base language.');
            }
            $pdo->prepare('DELETE FROM languages WHERE code=?')->execute(array($code));
            flash('ok', 'Language removed (translations remain in DB).');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: languages.php');
    exit;
}

$rows = get_languages(false);
$pageTitle = 'Languages';
$active = 'languages';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel" style="margin-bottom:16px">
  <p class="muted" style="margin:0">
    Enable languages for the Telegram bot. Users choose with <code>/lang</code> or the Language menu button.
    Translate menus in <b>Menus → i18n</b> and FAQs in <b>FAQ → Translations</b>.
  </p>
</div>

<div class="row2">
  <div class="card panel">
    <h2>Add Language</h2>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="add">
      <label>Code *</label>
      <input name="code" required placeholder="fa / ru / zh / ar" maxlength="5">
      <label>English name *</label>
      <input name="name" required placeholder="Persian">
      <label>Native name *</label>
      <input name="native_name" required placeholder="فارسی">
      <label>Flag emoji</label>
      <input name="flag" placeholder="🇮🇷">
      <label>Sort</label>
      <input type="number" name="sort_order" value="10">
      <button class="btn" type="submit" style="margin-top:14px">Add Language</button>
    </form>
  </div>

  <div class="card panel">
    <h2>Active Languages</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Code</th><th>Name</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $l): ?>
          <tr>
            <td><?= e($l['flag']) ?> <code><?= e($l['code']) ?></code>
              <?php if ((int)$l['is_default']): ?><span class="badge open">default</span><?php endif; ?>
            </td>
            <td><b><?= e($l['native_name']) ?></b><br><span class="muted"><?= e($l['name']) ?></span></td>
            <td><span class="badge <?= $l['is_active']?'open':'closed' ?>"><?= $l['is_active']?'on':'off' ?></span></td>
            <td class="actions">
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="code" value="<?= e($l['code']) ?>">
                <button class="btn sm secondary" type="submit">Toggle</button>
              </form>
              <?php if (!(int)$l['is_default']): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="default">
                <input type="hidden" name="code" value="<?= e($l['code']) ?>">
                <button class="btn sm secondary" type="submit">Set Default</button>
              </form>
              <?php endif; ?>
              <?php if ($l['code'] !== 'en'): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete language?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="code" value="<?= e($l['code']) ?>">
                <button class="btn sm danger" type="submit">Del</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
