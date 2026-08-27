<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
try { ensure_schema(); } catch (Throwable $e) {}

$pdo = db();
$langs = get_languages(false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add' || $action === 'edit') {
            $question = trim((string)($_POST['question'] ?? ''));
            $answer = trim((string)($_POST['answer'] ?? ''));
            $category = trim((string)($_POST['category'] ?? 'General')) ?: 'General';
            $keywords = trim((string)($_POST['keywords'] ?? ''));
            $sort = (int)($_POST['sort_order'] ?? 0);
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($question === '' || $answer === '') {
                throw new RuntimeException('Question and answer are required.');
            }
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO faqs (question, answer, category, keywords, sort_order, is_active) VALUES (?,?,?,?,?,?)')
                    ->execute(array($question, $answer, $category, $keywords, $sort, $active));
                flash('ok', 'FAQ added.');
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $pdo->prepare('UPDATE faqs SET question=?, answer=?, category=?, keywords=?, sort_order=?, is_active=? WHERE id=?')
                    ->execute(array($question, $answer, $category, $keywords, $sort, $active, $id));
                flash('ok', 'FAQ updated.');
            }
        } elseif ($action === 'i18n') {
            $id = (int)($_POST['id'] ?? 0);
            $lang = trim((string)($_POST['lang'] ?? ''));
            $q = trim((string)($_POST['i18n_question'] ?? ''));
            $a = trim((string)($_POST['i18n_answer'] ?? ''));
            $c = trim((string)($_POST['i18n_category'] ?? ''));
            if ($id <= 0 || $lang === '' || $q === '' || $a === '') {
                throw new RuntimeException('Translation needs language, question and answer.');
            }
            save_faq_translation($id, $lang, $q, $a, $c !== '' ? $c : null);
            flash('ok', 'FAQ translation saved for ' . $lang);
            header('Location: faqs.php?edit=' . $id . '&tab=i18n');
            exit;
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM faq_i18n WHERE faq_id=?')->execute(array($id));
            $pdo->prepare('DELETE FROM faqs WHERE id=?')->execute(array($id));
            flash('ok', 'FAQ deleted.');
        } elseif ($action === 'toggle') {
            $pdo->prepare('UPDATE faqs SET is_active = 1 - is_active WHERE id=?')->execute(array((int)($_POST['id'] ?? 0)));
            flash('ok', 'FAQ status updated.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    if (($action ?? '') !== 'i18n') {
        header('Location: faqs.php');
        exit;
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$tab = $_GET['tab'] ?? 'edit';
$edit = null;
$editTr = array();
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM faqs WHERE id=?');
    $st->execute(array($editId));
    $edit = $st->fetch() ?: null;
    if ($edit) {
        foreach ($langs as $l) {
            $editTr[$l['code']] = get_faq_translation($editId, $l['code']);
        }
    }
}
$rows = $pdo->query('SELECT * FROM faqs ORDER BY category ASC, sort_order ASC, id DESC')->fetchAll();

$pageTitle = 'FAQ / Questions';
$active = 'faqs';
require __DIR__ . '/layout_header.php';
?>
<div class="row2">
  <div class="card panel">
    <h2><?= $edit ? 'Edit FAQ #' . (int)$edit['id'] : 'Add FAQ Question' ?></h2>
    <?php if ($edit): ?>
      <div class="actions" style="margin-bottom:12px">
        <a class="btn sm <?= $tab!=='i18n'?'':'secondary' ?>" href="?edit=<?= $editId ?>">Base</a>
        <a class="btn sm <?= $tab==='i18n'?'':'secondary' ?>" href="?edit=<?= $editId ?>&tab=i18n">Translations</a>
        <a class="btn sm secondary" href="faqs.php">New</a>
      </div>
    <?php endif; ?>

    <?php if ($edit && $tab === 'i18n'): ?>
      <form method="post" class="stack">
        <input type="hidden" name="action" value="i18n">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <label>Language</label>
        <select name="lang">
          <?php foreach ($langs as $l): if ($l['code']==='en') continue; ?>
            <option value="<?= e($l['code']) ?>"><?= e($l['flag'].' '.$l['native_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Translated question *</label>
        <input name="i18n_question" required>
        <label>Translated answer *</label>
        <textarea name="i18n_answer" required></textarea>
        <label>Translated category</label>
        <input name="i18n_category" placeholder="مثلاً: پشتیبانی">
        <button class="btn" type="submit" style="margin-top:12px">Save Translation</button>
      </form>
      <h2 style="margin-top:20px">Saved translations</h2>
      <?php foreach ($editTr as $code=>$tr): if (!$tr) continue; ?>
        <div style="border:1px solid var(--line);border-radius:12px;padding:12px;margin-bottom:10px">
          <b><?= e($code) ?></b> — <?= e($tr['question']) ?><br>
          <span class="muted"><?= e(substr($tr['answer'],0,120)) ?></span>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <form method="post" class="stack">
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <label>Question * (default language)</label>
        <input name="question" required value="<?= e((string)($edit['question'] ?? '')) ?>">
        <label>Answer *</label>
        <textarea name="answer" required><?= e((string)($edit['answer'] ?? '')) ?></textarea>
        <div class="row2">
          <div>
            <label>Category</label>
            <input name="category" value="<?= e((string)($edit['category'] ?? 'General')) ?>">
          </div>
          <div>
            <label>Sort order</label>
            <input type="number" name="sort_order" value="<?= e((string)($edit['sort_order'] ?? '0')) ?>">
          </div>
        </div>
        <label>Keywords</label>
        <input name="keywords" value="<?= e((string)($edit['keywords'] ?? '')) ?>">
        <label style="display:flex;align-items:center;gap:8px;margin-top:12px">
          <input type="checkbox" name="is_active" <?= !isset($edit['is_active']) || (int)$edit['is_active']===1?'checked':'' ?>> Active
        </label>
        <div class="actions" style="margin-top:14px">
          <button class="btn" type="submit"><?= $edit ? 'Save FAQ' : 'Add FAQ' ?></button>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <div class="card panel">
    <h2>All Questions (<?= count($rows) ?>)</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Q</th><th>Category</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $f): ?>
          <tr>
            <td><b><?= e($f['question']) ?></b><br><span class="muted"><?= e(substr($f['answer'],0,80)) ?></span></td>
            <td><?= e($f['category']) ?></td>
            <td><span class="badge <?= $f['is_active']?'open':'closed' ?>"><?= $f['is_active']?'on':'off' ?></span></td>
            <td class="actions">
              <a class="btn sm secondary" href="?edit=<?= (int)$f['id'] ?>">Edit</a>
              <a class="btn sm secondary" href="?edit=<?= (int)$f['id'] ?>&tab=i18n">i18n</a>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                <button class="btn sm secondary" type="submit">Toggle</button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
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
