<?php /** @var array $machines */ /** @var array $prices */ /** @var array $dealers */ ?>
<p class="muted">قیمت پایه برای همه است. سطح یا شناسه نماینده، قیمت مخصوص را جلوتر از قیمت پایه می‌نشاند. بهای تمام‌شده فقط برای محاسبه سود ناخالص پورسانت است.</p>
<form method="post" action="<?= e(url('/admin/dealers/prices')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>مدل
    <select name="machine_id"><?php foreach ($machines as $machine): ?><option value="<?= e((string) $machine['id']) ?>"><?= e((string) $machine['code']) ?> — <?= e((string) $machine['name']) ?></option><?php endforeach; ?></select>
  </label>
  <label>نماینده، صفر یعنی سطح یا پایه
    <select name="dealer_id"><option value="0">پایه یا سطح</option><?php foreach ($dealers as $dealer): ?><option value="<?= e((string) $dealer['id']) ?>"><?= e((string) $dealer['name']) ?></option><?php endforeach; ?></select>
  </label>
  <label>سطح، اگر نماینده صفر است <input name="level" placeholder="خالی = قیمت پایه"></label>
  <label>قیمت ریال <input name="price" required></label>
  <label>بهای تمام‌شده ریال <input name="cost" value="0"></label>
  <button type="submit">ثبت قیمت</button>
</form>
<div class="table-wrap"><table>
  <thead><tr><th>مدل</th><th>نماینده</th><th>سطح</th><th>قیمت</th><th>تمام‌شده</th></tr></thead>
  <tbody>
  <?php foreach ($prices as $price): ?>
    <tr>
      <td><?= e((string) $price['machine_name']) ?></td>
      <td><?= e((string) ($price['dealer_name'] ?: 'عمومی')) ?></td>
      <td><?= e((string) ($price['level'] !== '' ? $price['level'] : '—')) ?></td>
      <td><?= e(money((int) $price['price'])) ?></td>
      <td><?= e(money((int) $price['cost'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
