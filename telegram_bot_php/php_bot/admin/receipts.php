<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_admin();
require_once dirname(__DIR__) . '/src/Autoload.php';

use HddLand\Bot\Services\LicenseFlowService;

LicenseFlowService::ensureSchema();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    try {
        $adminTg = 0;
        $ids = bot_config()['admin_ids'] ?? array();
        if ($ids) {
            $adminTg = (int)$ids[0];
        }
        if ($action === 'approve') {
            $res = LicenseFlowService::approveReceipt((int)($_POST['id'] ?? 0), $adminTg ?: 1);
            flash($res['ok'] ? 'ok' : 'err', $res['msg']);
        } elseif ($action === 'reject') {
            $res = LicenseFlowService::rejectReceipt((int)($_POST['id'] ?? 0), $adminTg ?: 1);
            flash($res['ok'] ? 'ok' : 'err', $res['msg']);
        } elseif ($action === 'mark_ready') {
            $res = LicenseFlowService::markActivationReady((int)($_POST['order_id'] ?? 0), '');
            flash($res['ok'] ? 'ok' : 'err', $res['msg']);
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }
    header('Location: receipts.php');
    exit;
}

$receipts = db()->query('SELECT * FROM payment_receipts ORDER BY id DESC LIMIT 100')->fetchAll();
$orders = db()->query('SELECT * FROM license_orders ORDER BY id DESC LIMIT 100')->fetchAll();

$pageTitle = 'Receipts & Licenses';
$active = 'receipts';
require __DIR__ . '/layout_header.php';
?>
<div class="card panel">
  <h2>Payment receipts</h2>
  <p class="muted">فیش‌های PayPal / Western Union — تایید ⇒ لایسنس TXT برای کاربر + باز شدن اپشن لایسنس/اکتیو.</p>
  <p class="muted">Mailbox: <code><?= e(LicenseFlowService::licenseMailbox()) ?></code></p>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>User</th><th>Method</th><th>Order</th><th>Status</th><th>Note</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($receipts as $r): ?>
        <tr>
          <td>#<?= (int)$r['id'] ?></td>
          <td><code><?= e((string)$r['telegram_id']) ?></code></td>
          <td><?= e((string)$r['method']) ?></td>
          <td><code><?= e((string)($r['order_code'] ?? '')) ?></code></td>
          <td><span class="badge <?= e((string)$r['status']) ?>"><?= e((string)$r['status']) ?></span></td>
          <td><?= e(mb_substr((string)($r['note'] ?? ''), 0, 80)) ?></td>
          <td class="actions">
            <?php if ($r['status'] === 'pending'): ?>
              <form method="post" style="display:inline"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm" type="submit">Approve</button></form>
              <form method="post" style="display:inline" onsubmit="return confirm('Reject?')"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm danger" type="submit">Reject</button></form>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$receipts): ?><tr><td colspan="7" class="muted">No receipts yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card panel" style="margin-top:16px">
  <h2>License orders</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>User</th><th>Code</th><th>Status</th><th>License</th><th>Activation</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td>#<?= (int)$o['id'] ?></td>
          <td><code><?= e((string)$o['telegram_id']) ?></code></td>
          <td><code><?= e((string)$o['order_code']) ?></code></td>
          <td><span class="badge"><?= e((string)$o['status']) ?></span></td>
          <td class="muted"><?= e(basename((string)($o['license_path'] ?? ''))) ?></td>
          <td class="muted"><?= e(basename((string)($o['activation_path'] ?? ''))) ?></td>
          <td>
            <?php if (in_array($o['status'], array('activation_uploaded','activation_emailed','license_sent'), true)): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="mark_ready">
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <button class="btn sm secondary" type="submit">Notify activation ready</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?><tr><td colspan="7" class="muted">No orders yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
