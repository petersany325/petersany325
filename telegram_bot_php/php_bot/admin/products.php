<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
try { ensure_schema(); } catch (Throwable $e) {}
try {
    if (function_exists('ensure_requests_schema')) {
        ensure_requests_schema();
    }
} catch (Throwable $e) {}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add' || $action === 'edit') {
            $name = trim((string)($_POST['name'] ?? ''));
            $desc = trim((string)($_POST['description'] ?? ''));
            $price = (float)($_POST['price'] ?? 0);
            $buy = trim((string)($_POST['buy_url'] ?? ''));
            $img = trim((string)($_POST['image_url'] ?? ''));
            $vid = trim((string)($_POST['video_url'] ?? ''));
            $demo = trim((string)($_POST['demo_url'] ?? ''));
            $label = trim((string)($_POST['link_label'] ?? ''));
            if ($name === '' || $price <= 0) {
                throw new RuntimeException('Name and valid price are required.');
            }
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO products (name, description, price, buy_url, image_url, video_url, demo_url, link_label, is_active) VALUES (?,?,?,?,?,?,?,?,1)')
                    ->execute(array($name, $desc, $price, $buy ?: null, $img ?: null, $vid ?: null, $demo ?: null, $label ?: null));
                flash('ok', 'Product added with links/media.');
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $pdo->prepare('UPDATE products SET name=?, description=?, price=?, buy_url=?, image_url=?, video_url=?, demo_url=?, link_label=? WHERE id=?')
                    ->execute(array($name, $desc, $price, $buy ?: null, $img ?: null, $vid ?: null, $demo ?: null, $label ?: null, $id));
                flash('ok', 'Product updated.');
            }
        } elseif ($action === 'toggle') {
            $pdo->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?')->execute(array((int)($_POST['id'] ?? 0)));
            flash('ok', 'Product status updated.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute(array((int)($_POST['id'] ?? 0)));
            flash('ok', 'Product deleted.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: products.php');
    exit;
}

$rows = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id=?');
    $st->execute(array($editId));
    $edit = $st->fetch() ?: null;
}

$pageTitle = 'Products';
$active = 'products';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel" style="margin-bottom:14px">
  <p class="muted" style="margin:0">Add <b>Buy link</b>, <b>Image URL</b>, <b>Video URL</b> (direct https links or Telegram CDN). Users see media + buttons in the bot shop.</p>
</div>
<div class="row2">
  <div class="card panel">
    <h2><?= $edit ? 'Edit Product #' . (int)$edit['id'] : 'Add Product' ?></h2>
    <form method="post" class="stack">
      <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
      <label>Name *</label>
      <input name="name" required value="<?= e((string)($edit['name'] ?? '')) ?>">
      <label>Description</label>
      <textarea name="description"><?= e((string)($edit['description'] ?? '')) ?></textarea>
      <label>Price (USD) *</label>
      <input name="price" type="number" step="0.01" min="1" required value="<?= e((string)($edit['price'] ?? '')) ?>">
      <label>Buy / Product Link (URL)</label>
      <input name="buy_url" dir="ltr" placeholder="https://hdd-land.com/..." value="<?= e((string)($edit['buy_url'] ?? '')) ?>">
      <label>Link button label</label>
      <input name="link_label" placeholder="🌐 Buy on Website" value="<?= e((string)($edit['link_label'] ?? '')) ?>">
      <label>Image URL</label>
      <input name="image_url" dir="ltr" placeholder="https://.../product.jpg" value="<?= e((string)($edit['image_url'] ?? '')) ?>">
      <label>Video URL</label>
      <input name="video_url" dir="ltr" placeholder="https://.../demo.mp4" value="<?= e((string)($edit['video_url'] ?? '')) ?>">
      <label>Demo / Extra Link</label>
      <input name="demo_url" dir="ltr" placeholder="https://..." value="<?= e((string)($edit['demo_url'] ?? '')) ?>">
      <div class="actions" style="margin-top:14px">
        <button class="btn" type="submit"><?= $edit ? 'Save Changes' : 'Add Product' ?></button>
        <?php if ($edit): ?><a class="btn secondary" href="products.php">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card panel">
    <h2>Catalog</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Product</th><th>Price</th><th>Links</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
          <tr>
            <td>
              <b><?= e($p['name']) ?></b>
              <?php if (!(int)$p['is_active']): ?><span class="badge closed">off</span><?php endif; ?>
              <br><span class="muted"><?= e(substr((string)$p['description'], 0, 70)) ?></span>
            </td>
            <td>$<?= e((string)$p['price']) ?></td>
            <td class="muted" style="font-size:.8rem">
              <?= !empty($p['buy_url']) ? '🔗' : '' ?>
              <?= !empty($p['image_url']) ? '🖼' : '' ?>
              <?= !empty($p['video_url']) ? '🎬' : '' ?>
            </td>
            <td class="actions">
              <a class="btn sm secondary" href="?edit=<?= (int)$p['id'] ?>">Edit</a>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="btn sm secondary" type="submit">Toggle</button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
