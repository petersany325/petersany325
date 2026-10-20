<?php /** @var array $rows */ ?>
<div class="table-wrap"><table>
  <thead><tr><th>شماره</th><th>نماینده</th><th>جمع</th><th>نقد</th><th>چک</th><th>نسیه</th><th>وضعیت</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <tr>
      <td><a href="<?= e(url('/admin/dealers/orders/' . $row['id'])) ?>"><?= e((string) $row['number']) ?></a></td>
      <td><?= e((string) $row['name']) ?><div class="muted"><?= e((string) $row['city']) ?></div></td>
      <td><?= e(money((int) $row['total'])) ?></td>
      <td><?= e(money((int) $row['cash_amount'])) ?></td>
      <td><?= e(money((int) $row['check_amount'])) ?></td>
      <td><?= e(money((int) $row['credit_amount'])) ?></td>
      <td><?= e(dealer_order_statuses()[$row['status']] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
