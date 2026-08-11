<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

$status = $_GET['status'] ?? 'open';
if (!in_array($status, ['open', 'closed', 'all'], true)) {
    $status = 'open';
}

$sql = 'SELECT * FROM tickets';
if ($status !== 'all') {
    $sql .= " WHERE status = " . db()->quote($status);
}
$sql .= ' ORDER BY id DESC LIMIT 200';
$rows = db()->query($sql)->fetchAll();

$pageTitle = 'Tickets';
$active = 'tickets';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <div class="actions" style="margin-bottom:14px">
    <a class="btn sm <?= $status==='open'?'':'secondary' ?>" href="?status=open">Open</a>
    <a class="btn sm <?= $status==='closed'?'':'secondary' ?>" href="?status=closed">Closed</a>
    <a class="btn sm <?= $status==='all'?'':'secondary' ?>" href="?status=all">All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>User</th><th>Contact</th><th>Subject</th><th>Status</th><th>Created</th><th></th></tr>
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
              echo e($cn !== '' ? $cn : '—');
              if ($ph !== '') {
                  echo '<br>📞 ' . e($ph);
              }
            ?>
          </td>
          <td><?= e(substr((string)$t['subject'], 0, 80)) ?></td>
          <td><span class="badge <?= e($t['status']) ?>"><?= e($t['status']) ?></span></td>
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
