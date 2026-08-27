<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
try { ensure_schema(); } catch (Throwable $e) {}

$pdo = db();
$types = array(
    'submenu' => 'Submenu (nested children)',
    'text' => 'Text message',
    'url' => 'Open URL (https link)',
    'callback' => 'Built-in (shop/forum/help/main/lang/reqhub/req:support/req:sales)',
    'command' => 'Command (training/website/help/support)',
    'faq_list' => 'Open FAQ list',
);
$categories = menu_categories();
$langs = get_languages(false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add' || $action === 'edit') {
            $title = trim((string)($_POST['title'] ?? ''));
            $menuType = (string)($_POST['menu_type'] ?? 'text');
            $value = trim((string)($_POST['value_text'] ?? ''));
            $category = trim((string)($_POST['category'] ?? 'Main')) ?: 'Main';
            $parent = $_POST['parent_id'] ?? '';
            $parentId = ($parent === '' || $parent === '0') ? null : (int)$parent;
            $rowIndex = (int)($_POST['row_index'] ?? 0);
            $sort = (int)($_POST['sort_order'] ?? 0);
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($title === '') {
                throw new RuntimeException('Title is required.');
            }
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO menus (parent_id, category, title, menu_type, value_text, row_index, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute(array($parentId, $category, $title, $menuType, $value, $rowIndex, $sort, $active));
                $newId = (int)$pdo->lastInsertId();
                flash('ok', 'Menu item #' . $newId . ' added.');
            } else {
                $id = (int)($_POST['id'] ?? 0);
                if ($parentId === $id) {
                    throw new RuntimeException('Menu cannot be parent of itself.');
                }
                $pdo->prepare('UPDATE menus SET parent_id=?, category=?, title=?, menu_type=?, value_text=?, row_index=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute(array($parentId, $category, $title, $menuType, $value, $rowIndex, $sort, $active, $id));
                flash('ok', 'Menu updated.');
            }
        } elseif ($action === 'i18n') {
            $id = (int)($_POST['id'] ?? 0);
            $lang = trim((string)($_POST['lang'] ?? ''));
            $title = trim((string)($_POST['i18n_title'] ?? ''));
            $value = trim((string)($_POST['i18n_value'] ?? ''));
            if ($id <= 0 || $lang === '' || $title === '') {
                throw new RuntimeException('Language translation needs menu, lang and title.');
            }
            save_menu_translation($id, $lang, $title, $value !== '' ? $value : null);
            flash('ok', 'Translation saved for ' . $lang);
            header('Location: menus.php?edit=' . $id . '&tab=i18n');
            exit;
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE menus SET parent_id=NULL WHERE parent_id=?')->execute(array($id));
            $pdo->prepare('DELETE FROM menu_i18n WHERE menu_id=?')->execute(array($id));
            $pdo->prepare('DELETE FROM menus WHERE id=?')->execute(array($id));
            flash('ok', 'Menu deleted.');
        } elseif ($action === 'toggle') {
            $pdo->prepare('UPDATE menus SET is_active = 1 - is_active WHERE id=?')->execute(array((int)($_POST['id'] ?? 0)));
            flash('ok', 'Status updated.');
        } elseif ($action === 'reset') {
            $pdo->exec('DELETE FROM menu_i18n');
            $pdo->exec('DELETE FROM menus');
            seed_default_menus($pdo);
            flash('ok', 'Professional default menu (with categories + FA) restored.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    if (($action ?? '') !== 'i18n') {
        header('Location: menus.php' . (!empty($_POST['id']) && $action === 'edit' ? ('?edit=' . (int)$_POST['id']) : ''));
        exit;
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$tab = $_GET['tab'] ?? 'edit';
$edit = null;
$editTr = array();
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM menus WHERE id=?');
    $st->execute(array($editId));
    $edit = $st->fetch() ?: null;
    if ($edit) {
        foreach ($langs as $l) {
            $editTr[$l['code']] = get_menu_translation($editId, $l['code']);
        }
    }
}

$tree = build_menu_tree();
$filterCat = $_GET['cat'] ?? '';

$pageTitle = 'Menus & Categories';
$active = 'menus';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel" style="margin-bottom:16px">
  <p class="muted" style="margin:0">
    Professional nested menus with <b>categories</b> (Commerce / Support / Resources…) and <b>multi-language titles</b>.
    Submenus can contain other submenus. Back button returns to parent automatically.
  </p>
</div>

<div class="actions" style="margin-bottom:14px">
  <a class="btn sm <?= $filterCat===''?'':'secondary' ?>" href="menus.php">All</a>
  <?php foreach ($categories as $c): ?>
    <a class="btn sm <?= $filterCat===$c?'':'secondary' ?>" href="?cat=<?= urlencode($c) ?>"><?= e($c) ?></a>
  <?php endforeach; ?>
  <form method="post" style="display:inline;margin-left:auto" onsubmit="return confirm('Reset menus to professional default?')">
    <input type="hidden" name="action" value="reset">
    <button class="btn sm danger" type="submit">Reset Default Menu</button>
  </form>
</div>

<div class="row2">
  <div class="card panel">
    <h2><?= $edit ? 'Edit Menu #' . (int)$edit['id'] : 'Add Menu / Submenu' ?></h2>
    <?php if ($edit): ?>
      <div class="actions" style="margin-bottom:12px">
        <a class="btn sm <?= $tab!=='i18n'?'':'secondary' ?>" href="?edit=<?= $editId ?>">Settings</a>
        <a class="btn sm <?= $tab==='i18n'?'':'secondary' ?>" href="?edit=<?= $editId ?>&tab=i18n">Translations</a>
        <a class="btn sm secondary" href="menus.php">New item</a>
      </div>
    <?php endif; ?>

    <?php if ($edit && $tab === 'i18n'): ?>
      <form method="post" class="stack">
        <input type="hidden" name="action" value="i18n">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <label>Language</label>
        <select name="lang" required>
          <?php foreach ($langs as $l): if ($l['code']==='en') continue; ?>
            <option value="<?= e($l['code']) ?>"><?= e($l['flag'] . ' ' . $l['native_name'] . ' (' . $l['code'] . ')') ?></option>
          <?php endforeach; ?>
        </select>
        <label>Translated title *</label>
        <input name="i18n_title" required placeholder="مثلاً: فروشگاه">
        <label>Translated value (optional — for text/url)</label>
        <textarea name="i18n_value" placeholder="اختیاری"></textarea>
        <button class="btn" type="submit" style="margin-top:12px">Save Translation</button>
      </form>
      <h2 style="margin-top:22px">Existing translations</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Lang</th><th>Title</th><th>Value</th></tr></thead>
          <tbody>
          <?php foreach ($editTr as $code => $tr): if (!$tr) continue; ?>
            <tr>
              <td><code><?= e($code) ?></code></td>
              <td><?= e($tr['title']) ?></td>
              <td class="muted"><?= e(substr((string)$tr['value_text'], 0, 60)) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="muted">Base/default language uses the main Title field (usually English).</p>
    <?php else: ?>
      <form method="post" class="stack">
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <label>Title (default language) *</label>
        <input name="title" required value="<?= e((string)($edit['title'] ?? '')) ?>" placeholder="🛒 Shop">
        <label>Category</label>
        <select name="category">
          <?php foreach ($categories as $c): ?>
            <option value="<?= e($c) ?>" <?= (($edit['category'] ?? 'Main') === $c) ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Parent (submenu under…)</label>
        <select name="parent_id">
          <option value="">— Root / Main Menu —</option>
          <?php foreach ($tree as $p): if ($edit && (int)$edit['id']===(int)$p['id']) continue; ?>
            <option value="<?= (int)$p['id'] ?>" <?= isset($edit['parent_id']) && (int)$edit['parent_id']===(int)$p['id'] ? 'selected' : '' ?>>
              <?= str_repeat('— ', (int)$p['_depth']) ?>#<?= (int)$p['id'] ?> <?= e($p['title']) ?> (<?= e($p['category']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <label>Type</label>
        <select name="menu_type">
          <?php foreach ($types as $k=>$label): ?>
            <option value="<?= e($k) ?>" <?= (($edit['menu_type'] ?? 'text')===$k)?'selected':'' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Value (url / callback / text / command)</label>
        <textarea name="value_text"><?= e((string)($edit['value_text'] ?? '')) ?></textarea>
        <div class="row2">
          <div>
            <label>Row index</label>
            <input type="number" name="row_index" value="<?= e((string)($edit['row_index'] ?? '0')) ?>">
          </div>
          <div>
            <label>Sort in row</label>
            <input type="number" name="sort_order" value="<?= e((string)($edit['sort_order'] ?? '0')) ?>">
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;margin-top:12px">
          <input type="checkbox" name="is_active" <?= !isset($edit['is_active']) || (int)$edit['is_active']===1 ? 'checked' : '' ?>> Active
        </label>
        <div class="actions" style="margin-top:14px">
          <button class="btn" type="submit"><?= $edit ? 'Save' : 'Add Menu' ?></button>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <div class="card panel">
    <h2>Menu Tree</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Structure</th><th>Category</th><th>Type</th><th>Row</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tree as $m):
          if ($filterCat !== '' && $m['category'] !== $filterCat) continue;
        ?>
          <tr>
            <td>
              <span class="muted"><?= str_repeat('↳ ', (int)$m['_depth']) ?></span>
              <b><?= e($m['title']) ?></b>
              <span class="muted">#<?= (int)$m['id'] ?></span>
              <?php if (!(int)$m['is_active']): ?><span class="badge closed">off</span><?php endif; ?>
            </td>
            <td><span class="badge open"><?= e($m['category']) ?></span></td>
            <td><code><?= e($m['menu_type']) ?></code></td>
            <td><?= (int)$m['row_index'] ?>/<?= (int)$m['sort_order'] ?></td>
            <td class="actions">
              <a class="btn sm secondary" href="?edit=<?= (int)$m['id'] ?>">Edit</a>
              <a class="btn sm secondary" href="?edit=<?= (int)$m['id'] ?>&tab=i18n">i18n</a>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button class="btn sm secondary" type="submit">Toggle</button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button class="btn sm danger" type="submit">Del</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
