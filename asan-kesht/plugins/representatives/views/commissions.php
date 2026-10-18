<?php /** @var array $rows */ ?>
<div class="table-wrap"><table>
  <thead><tr><th>نماینده</th><th>سفارش</th><th>مبلغ</th><th>مبنا</th><th>قطعی شدن</th><th>وضعیت</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <tr>
      <td><?= e((string) $row['name']) ?></td>
      <td><?= e((string) $row['number']) ?></td>
      <td><?= e(money((int) $row['amount'])) ?></td>
      <td><?= e($row['basis'] === 'gross' ? 'سود ناخالص' : 'مبلغ فروش') ?></td>
      <td><?= e($row['settle_on'] === 'sale' ? 'هنگام فروش' : 'هنگام وصول') ?></td>
      <td><?= e((string) $row['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<p class="muted">پورسانت فروش نسیه تا وصول وجه در وضعیت pending می‌ماند. لغو سفارش یا تأیید مرجوعی، پورسانت قطعی‌شده را با سند برمی‌گرداند.</p>
