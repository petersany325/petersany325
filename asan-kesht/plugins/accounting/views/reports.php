<?php
/** @var array<string, int> $figures */
/** @var list<array<string, mixed>> $open */
/** @var list<array<string, mixed>> $checks */
require __DIR__ . '/nav.php';
?>
<div class="cards">
  <div class="card"><span>فروش</span><strong><?= e(money($figures['sales'])) ?></strong></div>
  <div class="card"><span>بهای تمام‌شده</span><strong><?= e(money($figures['cogs'])) ?></strong></div>
  <div class="card"><span>سود ناخالص</span><strong><?= e(money($figures['gross'])) ?></strong></div>
  <div class="card"><span>خرید</span><strong><?= e(money($figures['purchases'])) ?></strong></div>
  <div class="card"><span>هزینه</span><strong><?= e(money($figures['expenses'])) ?></strong></div>
  <div class="card"><span>حقوق</span><strong><?= e(money($figures['payroll'])) ?></strong></div>
  <div class="card"><span>سود عملیاتی</span><strong><?= e(money($figures['operating'])) ?></strong></div>
</div>
<p><button type="button" onclick="window.print()">چاپ گزارش</button></p>
<p class="muted">اگر بهای تمام‌شده ردیف فروش صفر باشد، سود ناخالص با فروش یکی دیده می‌شود. مبلغ خرید جداست و داخل سود تکرار نمی‌شود.</p>
<h2>مانده فاکتورهای فروش</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>شماره</th><th>طرف</th><th>جمع</th><th>دریافت</th><th>مانده</th></tr></thead>
    <tbody>
    <?php foreach ($open as $row): ?>
      <tr>
        <td><?= e((string) $row['number']) ?></td>
        <td><?= e((string) $row['name']) ?></td>
        <td><?= e(money((int) $row['total'])) ?></td>
        <td><?= e(money((int) $row['paid'])) ?></td>
        <td><?= e(money((int) $row['total'] - (int) $row['paid'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<h2>چک‌های در جریان</h2>
<ul>
  <?php foreach ($checks as $check): ?>
    <li><?= e((string) $check['due_on']) ?> — <?= e((string) $check['number']) ?> — <?= e((string) $check['party_name']) ?> — <?= e(money((int) $check['amount'])) ?></li>
  <?php endforeach; ?>
</ul>
