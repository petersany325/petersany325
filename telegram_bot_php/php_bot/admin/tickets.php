<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
require_once dirname(__DIR__) . '/src/Autoload.php';

use HddLand\Bot\Services\SupportFormService;

$status = $_GET['status'] ?? 'active';
$q = trim((string)($_GET['q'] ?? ''));
$allowed = array('active', 'open', 'answered', 'waiting', 'closed', 'all');
if (!in_array($status, $allowed, true)) {
    $status = 'active';
}

$sql = 'SELECT * FROM tickets WHERE 1=1';
$params = array();
if ($status === 'active') {
    $sql .= " AND status IN ('open','answered','waiting')";
} elseif ($status !== 'all') {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $sql .= ' AND (subject LIKE ? OR contact_name LIKE ? OR phone LIKE ? OR customer_id LIKE ? OR CAST(user_id AS CHAR) LIKE ? OR CAST(id AS CHAR) = ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $q);
}
$sql .= ' ORDER BY id DESC LIMIT 250';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Tickets';
$active = 'tickets';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <div class="actions" style="margin-bottom:14px;flex-wrap:wrap;align-items:center;gap:8px">
    <?php
    $tabs = array(
      'active' => 'Active',
      'open' => 'Open',
      'answered' => 'Answered',
      'waiting' => 'Waiting',
      'closed' => 'Closed',
      'all' => 'All',
    );
    foreach ($tabs as $k => $label): ?>
      <a class="btn sm <?= $status===$k?'':'secondary' ?>" href="?status=<?= e($k) ?>&q=<?= urlencode($q) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a class="btn sm secondary" href="ticket_fields.php">🧠 Ticket Fields</a>
  </div>
  <form method="get" class="actions" style="margin-bottom:14px;gap:8px">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input name="q" value="<?= e($q) ?>" placeholder="Search name / phone / ID / telegram / #id" style="min-width:240px;flex:1">
    <button class="btn sm" type="submit">Search</button>
  </form>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>User</th><th>Identity</th><th>Subject</th><th>Status</th><th>Created</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $t): ?>
        <tr>
          <td>#<?= (int)$t['id'] ?></td>
          <td><code><?= e((string)$t['user_id']) ?></code></td>
          <td class="muted" style="font-size:.85rem">
            <?php
              $cn = trim((string)($t['contact_name'] ?? ''));
              $ph = trim((string)($t['phone'] ?? ''));
              $cid = trim((string)($t['customer_id'] ?? ''));
              echo e($cn !== '' ? $cn : '—');
              if ($ph !== '') echo '<br>📞 ' . e($ph);
              if ($cid !== '') echo '<br>🆔 ' . e($cid);
            ?>
          </td>
          <td><?= e(substr((string)$t['subject'], 0, 80)) ?></td>
          <td>
            <span class="badge <?= e($t['status']) ?>"><?= e(SupportFormService::statusLabel((string)$t['status'], 'en')) ?></span>
          </td>
          <td class="muted"><?= e((string)$t['created_at']) ?></td>
          <td><a class="btn sm secondary" href="ticket_view.php?id=<?= (int)$t['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="7" class="muted">No tickets found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
