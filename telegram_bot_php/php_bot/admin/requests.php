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
        $id = (int)($_POST['id'] ?? 0);
        if ($action === 'close') {
            $pdo->prepare("UPDATE service_requests SET status='closed' WHERE id=?")->execute(array($id));
            flash('ok', "Request #{$id} closed.");
        } elseif ($action === 'open') {
            $pdo->prepare("UPDATE service_requests SET status='open' WHERE id=?")->execute(array($id));
            flash('ok', "Request #{$id} reopened.");
        } elseif ($action === 'reply') {
            $text = trim((string)($_POST['text'] ?? ''));
            if ($text === '' || $id <= 0) {
                throw new RuntimeException('Reply text required.');
            }
            $st = $pdo->prepare('SELECT * FROM service_requests WHERE id=?');
            $st->execute(array($id));
            $req = $st->fetch();
            if (!$req) {
                throw new RuntimeException('Request not found.');
            }
            send_message((int)$req['user_id'], "💬 <b>Reply to your " . htmlspecialchars($req['req_type']) . " request #{$id}:</b>\n\n" . htmlspecialchars($text));
            $pdo->prepare('UPDATE service_requests SET admin_note=? WHERE id=?')->execute(array($text, $id));
            flash('ok', 'Reply sent on Telegram.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM request_media WHERE request_id=?')->execute(array($id));
            $pdo->prepare('DELETE FROM service_requests WHERE id=?')->execute(array($id));
            flash('ok', 'Request deleted.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    $redir = 'requests.php';
    if (!empty($_POST['id']) && in_array($action, array('reply', 'close', 'open'), true)) {
        $redir .= '?id=' . (int)$_POST['id'];
    }
    header('Location: ' . $redir);
    exit;
}

$viewId = (int)($_GET['id'] ?? 0);
$typeFilter = $_GET['type'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'open';

if ($viewId > 0) {
    $st = $pdo->prepare('SELECT * FROM service_requests WHERE id=?');
    $st->execute(array($viewId));
    $req = $st->fetch();
    $media = array();
    if ($req) {
        $ms = $pdo->prepare('SELECT * FROM request_media WHERE request_id=? ORDER BY id');
        $ms->execute(array($viewId));
        $media = $ms->fetchAll();
    }
    $pageTitle = 'Request #' . $viewId;
    $active = 'requests';
    require __DIR__ . '/layout_header.php';
    if (!$req) {
        echo '<div class="alert err">Not found.</div>';
        require __DIR__ . '/layout_footer.php';
        exit;
    }
    ?>
    <div class="card panel">
      <div class="actions" style="margin-bottom:12px">
        <a class="btn sm secondary" href="requests.php">← Back</a>
        <span class="badge <?= $req['req_type']==='sales'?'open':'closed' ?>"><?= e($req['req_type']) ?></span>
        <span class="badge <?= $req['status']==='open'?'open':'closed' ?>"><?= e($req['status']) ?></span>
      </div>
      <p class="muted">User: <code><?= e((string)$req['user_id']) ?></code> · <?= e((string)$req['created_at']) ?></p>
      <h2><?= e($req['subject']) ?></h2>
      <div style="white-space:pre-wrap;margin:12px 0;padding:12px;border:1px solid var(--line);border-radius:12px"><?= e($req['message']) ?></div>

      <h2>Media (<?= count($media) ?>)</h2>
      <?php if (!$media): ?>
        <p class="muted">No photo/video attached.</p>
      <?php else: ?>
        <div class="table-wrap"><table>
          <thead><tr><th>Type</th><th>File ID</th><th>Caption</th></tr></thead>
          <tbody>
          <?php foreach ($media as $m): ?>
            <tr>
              <td><?= e($m['media_type']) ?></td>
              <td><code style="font-size:.7rem"><?= e(substr($m['file_id'],0,40)) ?>…</code></td>
              <td><?= e((string)$m['caption']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
        <p class="muted">Media is also forwarded live to Telegram admin accounts when users upload.</p>
      <?php endif; ?>

      <form method="post" class="stack" style="margin-top:18px">
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
        <label>Reply to user on Telegram</label>
        <textarea name="text" required placeholder="Write professional reply..."></textarea>
        <div class="actions">
          <button class="btn" type="submit">Send Reply</button>
        </div>
      </form>
      <div class="actions" style="margin-top:12px">
        <?php if ($req['status']==='open'): ?>
          <form method="post"><input type="hidden" name="action" value="close"><input type="hidden" name="id" value="<?= (int)$req['id'] ?>"><button class="btn danger" type="submit">Close</button></form>
        <?php else: ?>
          <form method="post"><input type="hidden" name="action" value="open"><input type="hidden" name="id" value="<?= (int)$req['id'] ?>"><button class="btn secondary" type="submit">Reopen</button></form>
        <?php endif; ?>
      </div>
    </div>
    <?php
    require __DIR__ . '/layout_footer.php';
    exit;
}

$sql = 'SELECT * FROM service_requests WHERE 1=1';
if ($typeFilter !== 'all') {
    $sql .= ' AND req_type=' . $pdo->quote($typeFilter);
}
if ($statusFilter !== 'all') {
    $sql .= ' AND status=' . $pdo->quote($statusFilter);
}
$sql .= ' ORDER BY id DESC LIMIT 200';
$rows = $pdo->query($sql)->fetchAll();

$pageTitle = 'Support & Sales Requests';
$active = 'requests';
require __DIR__ . '/layout_header.php';
?>
<div class="actions" style="margin-bottom:14px">
  <a class="btn sm <?= $typeFilter==='all'?'':'secondary' ?>" href="?type=all&status=<?= e($statusFilter) ?>">All types</a>
  <a class="btn sm <?= $typeFilter==='support'?'':'secondary' ?>" href="?type=support&status=<?= e($statusFilter) ?>">Support</a>
  <a class="btn sm <?= $typeFilter==='sales'?'':'secondary' ?>" href="?type=sales&status=<?= e($statusFilter) ?>">Sales</a>
  <a class="btn sm <?= $statusFilter==='open'?'':'secondary' ?>" href="?type=<?= e($typeFilter) ?>&status=open">Open</a>
  <a class="btn sm <?= $statusFilter==='closed'?'':'secondary' ?>" href="?type=<?= e($typeFilter) ?>&status=closed">Closed</a>
  <a class="btn sm <?= $statusFilter==='all'?'':'secondary' ?>" href="?type=<?= e($typeFilter) ?>&status=all">All status</a>
</div>
<div class="card panel">
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>Type</th><th>User</th><th>Message</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['id'] ?></td>
          <td><span class="badge <?= $r['req_type']==='sales'?'open':'closed' ?>"><?= e($r['req_type']) ?></span></td>
          <td><code><?= e((string)$r['user_id']) ?></code></td>
          <td><?= e(substr($r['message'], 0, 80)) ?></td>
          <td><span class="badge <?= $r['status']==='open'?'open':'closed' ?>"><?= e($r['status']) ?></span></td>
          <td><a class="btn sm secondary" href="?id=<?= (int)$r['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="muted">No requests yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
