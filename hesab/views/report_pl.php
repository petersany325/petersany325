<?php
$inc = 0; $exp = 0;
foreach ($income as $r) { $inc += (float)$r['credit'] - (float)$r['debit']; }
foreach ($expense as $r) { $exp += (float)$r['debit'] - (float)$r['credit']; }
$net = $inc - $exp;
?>
<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>صورت سود و زیان جامع</strong>
    <a class="btn ghost" href="?excel=1">Excel</a>
  </div>
</div>
<div class="grid" style="grid-template-columns:1fr 1fr;gap:14px">
  <div class="panel">
    <div class="hd"><strong>درآمدها</strong></div>
    <div class="bd">
      <table class="data">
        <thead><tr><th>کد</th><th>عنوان</th><th>مبلغ</th></tr></thead>
        <tbody>
        <?php foreach ($income as $r): ?>
          <tr><td class="num"><?= e($r['code']) ?></td><td><?= e($r['title']) ?></td><td class="num"><?= money((float)$r['credit']-(float)$r['debit']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="2">جمع درآمد</th><th class="num"><?= money($inc) ?></th></tr></tfoot>
      </table>
    </div>
  </div>
  <div class="panel">
    <div class="hd"><strong>هزینه‌ها</strong></div>
    <div class="bd">
      <table class="data">
        <thead><tr><th>کد</th><th>عنوان</th><th>مبلغ</th></tr></thead>
        <tbody>
        <?php foreach ($expense as $r): ?>
          <tr><td class="num"><?= e($r['code']) ?></td><td><?= e($r['title']) ?></td><td class="num"><?= money((float)$r['debit']-(float)$r['credit']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="2">جمع هزینه</th><th class="num"><?= money($exp) ?></th></tr></tfoot>
      </table>
    </div>
  </div>
</div>
<div class="panel" style="margin-top:14px">
  <div class="hd">
    <strong>سود (زیان) خالص</strong>
    <span class="badge <?= $net >= 0 ? 'operational' : 'void' ?>"><?= money($net) ?></span>
  </div>
</div>
