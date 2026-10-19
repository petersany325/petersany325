<?php /** @var array $rows */ ?>
<?php require __DIR__ . '/rep-nav.php'; ?>
<div class="table-wrap"><table>
  <thead><tr><th>مدل</th><th>قیمت شما</th><?php if ($app->pluginEnabled('cooperation')): ?><th>خرده</th><th>حاشیه از خرده</th><th>نسبت به همکاری</th><?php endif; ?></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <tr>
      <td><?= e((string) $row['code']) ?> — <?= e((string) $row['name']) ?></td>
      <td><?= e(money((int) $row['unit_price'])) ?></td>
      <?php if ($app->pluginEnabled('cooperation')): ?>
        <td><?= isset($row['retail_price']) && (int) $row['retail_price'] > 0 ? e(money((int) $row['retail_price'])) : 'ثبت نشده' ?></td>
        <td><?= e((string) ($row['margin_label'] ?? '') !== '' ? (string) $row['margin_label'] : 'ثبت نشده') ?></td>
        <td><?= e((string) ($row['markup_label'] ?? '') !== '' ? (string) $row['markup_label'] : 'ثبت نشده') ?></td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php if ($rows === []): ?><p>هنوز قیمتی برای مدل‌های مجاز شما ثبت نشده است.</p><?php endif; ?>
