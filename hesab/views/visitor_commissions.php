<?php
/** @var array $rows */
/** @var array $sum */
$statusLabels = [
    'accrued' => 'ذخیره‌شده',
    'approved' => 'تأیید شده',
    'paid' => 'پرداخت‌شده',
    'void' => 'باطل',
];
?>
<div class="grid stats" style="margin-bottom:12px;grid-template-columns:repeat(3,minmax(0,1fr))">
  <div class="stat"><div class="label">ذخیره‌شده</div><div class="value num"><?= money($sum['accrued'] ?? 0) ?></div></div>
  <div class="stat"><div class="label">تأیید شده</div><div class="value num"><?= money($sum['approved'] ?? 0) ?></div></div>
  <div class="stat"><div class="label">پرداخت‌شده</div><div class="value num"><?= money($sum['paid'] ?? 0) ?></div></div>
</div>

<div class="panel">
  <div class="hd">
    <strong>پورسانت ویزیتورها</strong>
    <a class="btn ghost" href="<?= e(url('/invoices')) ?>">فاکتور فروش</a>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>#</th><th>ویزیتور</th><th>فاکتور</th><th>تاریخ</th>
          <th>مبلغ پایه</th><th>درصد</th><th>پورسانت</th><th>وضعیت</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="9">پورسانتی ثبت نشده — هنگام صدور فاکتور با ویزیتور محاسبه می‌شود</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['visitor_name']) ?></td>
          <td class="num">#<?= e((string)$r['invoice_number']) ?></td>
          <td class="num"><?= e($r['invoice_date'] ?: '—') ?></td>
          <td class="num"><?= money($r['base_amount']) ?></td>
          <td class="num"><?= e((string)$r['percent']) ?>٪</td>
          <td class="num"><?= money($r['commission_amount']) ?></td>
          <td><?= e($statusLabels[$r['status']] ?? $r['status']) ?></td>
          <td>
            <?php if ($r['status'] === 'accrued'): ?>
              <form method="post" action="<?= e(url('/visitors/commissions')) ?>" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn ghost" name="status" value="approved" type="submit">تأیید</button>
                <button class="btn ghost" name="status" value="void" type="submit">ابطال</button>
              </form>
            <?php elseif ($r['status'] === 'approved'): ?>
              <form method="post" action="<?= e(url('/visitors/commissions')) ?>" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn" name="status" value="paid" type="submit">پرداخت / تسویه</button>
              </form>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
