<?php /** @var array $rows */ ?>
<div class="table-wrap"><table>
  <thead><tr><th>مدل</th><th>قیمت شما</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <tr><td><?= e((string) $row['code']) ?> — <?= e((string) $row['name']) ?></td><td><?= e(money((int) $row['unit_price'])) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php if ($rows === []): ?><p>هنوز قیمتی برای مدل‌های مجاز شما ثبت نشده است.</p><?php endif; ?>
