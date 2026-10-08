<?php
$td = 0; $tc = 0;
foreach ($rows as $r) { $td += (float)$r['debit']; $tc += (float)$r['credit']; }
?>
<div class="panel">
  <div class="hd">
    <strong>تراز آزمایشی (اسناد قطعی)</strong>
    <span class="badge <?= abs($td-$tc)<0.001 ? 'posted' : 'void' ?>">
      <?= abs($td-$tc)<0.001 ? 'متوازن' : 'نامتوازن' ?>
    </span>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>کد</th>
          <th>حساب</th>
          <th>جمع بدهکار</th>
          <th>جمع بستانکار</th>
          <th>مانده بدهکار</th>
          <th>مانده بستانکار</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="6">داده‌ای نیست</td></tr><?php endif; ?>
      <?php foreach ($rows as $r):
        $d = (float)$r['debit']; $c = (float)$r['credit'];
        $bd = max($d - $c, 0); $bc = max($c - $d, 0);
      ?>
        <tr>
          <td class="num"><?= e($r['code']) ?></td>
          <td><?= e($r['title']) ?></td>
          <td class="num"><?= money($d) ?></td>
          <td class="num"><?= money($c) ?></td>
          <td class="num"><?= money($bd) ?></td>
          <td class="num"><?= money($bc) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2">جمع</th>
          <th class="num"><?= money($td) ?></th>
          <th class="num"><?= money($tc) ?></th>
          <th colspan="2"></th>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
