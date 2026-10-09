<?php
/** @var array $perf */
/** @var string $from */
/** @var string $to */
?>
<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>گزارش عملکرد ویزیتورها</strong>
    <form method="get" action="<?= e(url('/visitors/reports')) ?>" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
      <label style="margin:0">از<input type="date" name="from" value="<?= e($from) ?>"></label>
      <label style="margin:0">تا<input type="date" name="to" value="<?= e($to) ?>"></label>
      <button class="btn" type="submit">اعمال</button>
      <a class="btn ghost" href="<?= e(url('/visitors/reports?from=' . urlencode($from) . '&to=' . urlencode($to) . '&excel=1')) ?>">Excel</a>
    </form>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>کد</th><th>ویزیتور</th><th>درصد</th>
          <th>بازدید</th><th>انجام‌شده</th><th>فروش فاکتور</th><th>پورسانت</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$perf): ?><tr><td colspan="7">داده‌ای در بازه انتخابی نیست</td></tr><?php endif; ?>
      <?php foreach ($perf as $r): ?>
        <tr>
          <td><?= e($r['code']) ?></td>
          <td><a href="<?= e(url('/visitors?edit=' . (int)$r['id'])) ?>"><?= e($r['name']) ?></a></td>
          <td class="num"><?= e((string)$r['commission_percent']) ?>٪</td>
          <td class="num"><?= (int)$r['visits_total'] ?></td>
          <td class="num"><?= (int)$r['visits_done'] ?></td>
          <td class="num"><?= money($r['sales_total']) ?></td>
          <td class="num"><?= money($r['commission_total']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
