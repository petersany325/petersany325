<?php /** @var array $rows */ /** @var array $regions */ /** @var array $managers */ /** @var bool $can_edit */ ?>
<?php if ($can_edit): ?>
  <p><a class="btn" href="<?= e(url('/admin/dealers/new')) ?>">پرونده تازه</a></p>
  <h2>منطقه</h2>
  <form method="post" action="<?= e(url('/admin/dealers/regions')) ?>" class="grid">
    <?= csrf_field() ?>
    <label>نام منطقه <input name="name" required></label>
    <label>مدیر منطقه
      <select name="manager_user_id">
        <option value="0">—</option>
        <?php foreach ($managers as $manager): ?><option value="<?= e((string) $manager['id']) ?>"><?= e((string) $manager['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <button type="submit">ذخیره منطقه</button>
  </form>
<?php endif; ?>
<div class="table-wrap"><table>
  <thead><tr><th>کد</th><th>نام</th><th>شهر</th><th>مدل</th><th>درصد سود</th><th>وضعیت</th><th>قرارداد</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <tr>
      <td><?= e((string) $row['code']) ?></td>
      <td><?= e((string) $row['name']) ?><?php if ((int) $row['exclusive_city'] === 1): ?><div class="muted">انحصاری شهر</div><?php endif; ?></td>
      <td><?= e((string) $row['province']) ?> — <?= e((string) $row['city']) ?><div class="muted"><?= e((string) ($row['region_name'] ?? '')) ?></div></td>
      <td><?= e(dealer_sale_models()[$row['sale_model']] ?? '') ?></td>
      <td><?= $row['commission_percent'] === null ? 'پیش‌فرض' : e(fa_digits((string) $row['commission_percent'])) . '٪' ?></td>
      <td><?= e(dealer_statuses()[$row['status']] ?? '') ?></td>
      <td><?= e((string) ($row['contract_end'] ? jalali_date((string) $row['contract_end']) : '—')) ?></td>
      <td><a href="<?= e(url('/admin/dealers/' . $row['id'])) ?>">پرونده</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php if ($regions !== []): ?><p class="muted">منطقه‌ها: <?php foreach ($regions as $region): ?><?= e((string) $region['name']) ?>، <?php endforeach; ?></p><?php endif; ?>
