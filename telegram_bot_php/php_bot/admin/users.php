<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();

$rows = db()->query('SELECT * FROM users ORDER BY id DESC LIMIT 300')->fetchAll();

$pageTitle = 'Users';
$active = 'users';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>Telegram</th><th>Name</th><th>Username</th><th>Warns</th><th>Joined</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><code><?= e((string)$u['telegram_id']) ?></code></td>
          <td><?= e((string)$u['full_name']) ?></td>
          <td><?= e($u['username'] ? '@'.$u['username'] : '-') ?></td>
          <td><?= (int)$u['warns'] ?></td>
          <td class="muted"><?= e((string)$u['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="muted">No users yet. Ask someone to /start the bot.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
