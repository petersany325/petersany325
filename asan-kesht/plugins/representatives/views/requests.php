<?php /** @var array $rows */ /** @var string $role */ ?>
<div class="table-wrap"><table>
  <thead><tr><th>نماینده</th><th>نوع</th><th>شرح</th><th>وضعیت</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <?php $flow = dealer_request_flow((string) $row['type']); $expected = $flow[(int) $row['step']] ?? 'admin'; ?>
    <tr>
      <td><?= e((string) $row['name']) ?></td>
      <td><?= e(dealer_request_types()[$row['type']] ?? '') ?></td>
      <td><?= e((string) $row['body']) ?><?php if ((int) $row['amount'] > 0): ?><div class="muted"><?= e(money((int) $row['amount'])) ?></div><?php endif; ?>
        <?php if ($app->pluginEnabled('attachments') && (string) ($row['file'] ?? '') !== ''): ?><div><a href="<?= e(url('/files/' . (string) $row['file'])) ?>">پیوست</a></div><?php endif; ?>
      </td>
      <td><?= e((string) $row['status']) ?><?php if ($row['status'] === 'open'): ?><div class="muted">منتظر <?= e(ak_role_labels()[$expected] ?? $expected) ?></div><?php endif; ?></td>
      <td>
        <?php if ($row['status'] === 'open' && ($role === 'admin' || $role === $expected)): ?>
          <form method="post" action="<?= e(url('/admin/dealers/requests/' . $row['id'])) ?>">
            <?= csrf_field() ?>
            <input name="response" placeholder="یادداشت">
            <button name="decision" value="approve">تأیید مرحله</button>
            <button name="decision" value="reject">رد</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
