<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
require_once dirname(__DIR__) . '/src/Autoload.php';

use HddLand\Bot\Repositories\UserRepository;

UserRepository::ensureVipColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'toggle_vip') {
            $tg = (int)($_POST['telegram_id'] ?? 0);
            $vip = !empty($_POST['is_vip']);
            if ($tg <= 0) {
                throw new RuntimeException('Invalid user.');
            }
            UserRepository::setVip($tg, $vip);
            flash('ok', $vip ? "User {$tg} marked VIP." : "VIP removed from {$tg}.");
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: users.php');
    exit;
}

$rows = db()->query('SELECT * FROM users ORDER BY id DESC LIMIT 300')->fetchAll();

$pageTitle = 'Users';
$active = 'users';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <p class="muted">Enable <b>VIP</b> to grant access to 💎 VIP Download (forum vbdlmanager).</p>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>Telegram</th><th>Name</th><th>Contact</th><th>Phone</th><th>Customer ID</th><th>VIP</th><th>Username</th><th>Joined</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><code><?= e((string)$u['telegram_id']) ?></code></td>
          <td><?= e((string)$u['full_name']) ?></td>
          <td><?= e((string)($u['contact_name'] ?? '')) ?: '<span class="muted">—</span>' ?></td>
          <td><?= e((string)($u['phone'] ?? '')) ?: '<span class="muted">—</span>' ?></td>
          <td><?= e((string)($u['customer_id'] ?? '')) ?: '<span class="muted">—</span>' ?></td>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="toggle_vip">
              <input type="hidden" name="telegram_id" value="<?= (int)$u['telegram_id'] ?>">
              <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer">
                <input type="checkbox" name="is_vip" value="1" <?= !empty($u['is_vip'])?'checked':'' ?> onchange="this.form.submit()">
                <span class="badge <?= !empty($u['is_vip'])?'open':'closed' ?>"><?= !empty($u['is_vip'])?'VIP':'—' ?></span>
              </label>
            </form>
          </td>
          <td><?= e($u['username'] ? '@'.$u['username'] : '-') ?></td>
          <td class="muted"><?= e((string)$u['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="9" class="muted">No users yet. Ask someone to /start the bot.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
