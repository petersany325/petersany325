<?php /** @var int $paid */ /** @var int $expense */ /** @var array $open */ /** @var array $months */ ?>
<div class="cards">
  <div class="card"><span>دریافت‌ها</span><strong><?= e(money($paid)) ?></strong></div>
  <div class="card"><span>هزینه‌ها</span><strong><?= e(money($expense)) ?></strong></div>
  <div class="card"><span>مابه‌التفاوت ثبت‌شده</span><strong><?= e(money($paid - $expense)) ?></strong></div>
</div>
<p><button type="button" onclick="window.print()">چاپ گزارش</button></p>
<h2>مانده فاکتورها</h2>
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
<h2>دریافت شش ماه اخیر</h2>
<?php $max = 1; foreach ($months as $month) { $max = max($max, (int) $month['total']); } ?>
<ul class="bars">
  <?php foreach ($months as $month): ?>
    <li>
      <span><?= e((string) $month['ym']) ?></span>
      <span class="bar" style="width: <?= e((string) max(4, (int) round(((int) $month['total'] / $max) * 100))) ?>%"></span>
      <span><?= e(money((int) $month['total'])) ?></span>
    </li>
  <?php endforeach; ?>
</ul>
