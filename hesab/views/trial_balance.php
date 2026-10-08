<?php
$cols = (int)($cols ?? 4);
$sumD = 0; $sumC = 0;
foreach ($rows as $r) { $sumD += (float)$r['debit']; $sumC += (float)$r['credit']; }
$q = http_build_query(array_filter(['from' => $from ?? null, 'to' => $to ?? null, 'cols' => $cols]));
?>
<div class="panel" style="margin-bottom:12px">
  <form class="form row" method="get" action="<?= e(url('/trial-balance')) ?>" style="padding:12px">
    <label>از تاریخ<input type="date" name="from" value="<?= e($from ?? '') ?>"></label>
    <label>تا تاریخ<input type="date" name="to" value="<?= e($to ?? '') ?>"></label>
    <label>ستون‌ها
      <select name="cols">
        <?php foreach ([2,4,6] as $c): ?>
          <option value="<?= $c ?>" <?= $cols === $c ? 'selected' : '' ?>><?= $c ?> ستونی</option>
        <?php endforeach; ?>
      </select>
    </label>
    <div style="align-self:end;display:flex;gap:8px">
      <button class="btn" type="submit">نمایش</button>
      <a class="btn ghost" href="<?= e(url('/trial-balance')) ?>?<?= e($q) ?>&excel=1">Excel</a>
    </div>
  </form>
</div>

<div class="panel">
  <div class="hd"><strong>تراز آزمایشی <?= $cols ?> ستونی</strong></div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>کد</th>
          <th>عنوان</th>
          <?php if ($cols >= 2): ?><th>بدهکار</th><th>بستانکار</th><?php endif; ?>
          <?php if ($cols >= 4): ?><th>مانده بدهکار</th><th>مانده بستانکار</th><?php endif; ?>
          <?php if ($cols >= 6): ?><th>گردش بدهکار</th><th>گردش بستانکار</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="8">مانده‌ای نیست</td></tr><?php endif; ?>
      <?php foreach ($rows as $r):
        $bal = (float)$r['debit'] - (float)$r['credit'];
      ?>
        <tr>
          <td class="num"><?= e($r['code']) ?></td>
          <td><?= e($r['title']) ?></td>
          <?php if ($cols >= 2): ?>
            <td class="num"><?= money($r['debit']) ?></td>
            <td class="num"><?= money($r['credit']) ?></td>
          <?php endif; ?>
          <?php if ($cols >= 4): ?>
            <td class="num"><?= money(max($bal, 0)) ?></td>
            <td class="num"><?= money(max(-$bal, 0)) ?></td>
          <?php endif; ?>
          <?php if ($cols >= 6): ?>
            <td class="num"><?= money($r['debit']) ?></td>
            <td class="num"><?= money($r['credit']) ?></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2">جمع</th>
          <?php if ($cols >= 2): ?>
            <th class="num"><?= money($sumD) ?></th>
            <th class="num"><?= money($sumC) ?></th>
          <?php endif; ?>
          <?php if ($cols >= 4): ?><th></th><th></th><?php endif; ?>
          <?php if ($cols >= 6): ?><th></th><th></th><?php endif; ?>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
