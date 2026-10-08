<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>ترازنامه</strong>
    <a class="btn ghost" href="?excel=1">Excel</a>
  </div>
</div>
<div class="grid" style="grid-template-columns:1fr 1fr;gap:14px">
  <div class="panel">
    <div class="hd"><strong>دارایی‌ها</strong></div>
    <div class="bd">
      <table class="data">
        <thead><tr><th>کد</th><th>عنوان</th><th>مانده</th></tr></thead>
        <tbody>
        <?php $sa=0; foreach ($assets as $r): $sa += (float)$r['balance']; ?>
          <tr><td class="num"><?= e($r['code']) ?></td><td><?= e($r['title']) ?></td><td class="num"><?= money($r['balance']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="2">جمع</th><th class="num"><?= money($sa) ?></th></tr></tfoot>
      </table>
    </div>
  </div>
  <div>
    <div class="panel" style="margin-bottom:14px">
      <div class="hd"><strong>بدهی‌ها</strong></div>
      <div class="bd">
        <table class="data">
          <thead><tr><th>کد</th><th>عنوان</th><th>مانده</th></tr></thead>
          <tbody>
          <?php $sl=0; foreach ($liab as $r): $sl += (float)$r['balance']; ?>
            <tr><td class="num"><?= e($r['code']) ?></td><td><?= e($r['title']) ?></td><td class="num"><?= money($r['balance']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th colspan="2">جمع</th><th class="num"><?= money($sl) ?></th></tr></tfoot>
        </table>
      </div>
    </div>
    <div class="panel">
      <div class="hd"><strong>حقوق صاحبان سهام</strong></div>
      <div class="bd">
        <table class="data">
          <thead><tr><th>کد</th><th>عنوان</th><th>مانده</th></tr></thead>
          <tbody>
          <?php $se=0; foreach ($equity as $r): $se += (float)$r['balance']; ?>
            <tr><td class="num"><?= e($r['code']) ?></td><td><?= e($r['title']) ?></td><td class="num"><?= money($r['balance']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th colspan="2">جمع</th><th class="num"><?= money($se) ?></th></tr></tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
