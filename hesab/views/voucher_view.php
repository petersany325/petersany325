<?php
$sumD = 0; $sumC = 0;
foreach ($lines as $l) { $sumD += (float)$l['debit']; $sumC += (float)$l['credit']; }
?>
<div class="panel">
  <div class="hd">
    <div>
      <strong>سند شماره <?= (int)$v['number'] ?></strong>
      <span class="badge <?= e($v['status']) ?>"><?= e($v['status']) ?></span>
    </div>
    <div style="display:flex;gap:8px">
      <a class="btn ghost" href="/vouchers">بازگشت</a>
      <?php if ($v['status'] === 'draft'): ?>
      <form method="post" action="/vouchers/post" style="margin:0">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
        <button class="btn" type="submit">ثبت قطعی</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <div class="form row">
    <label>تاریخ<div><?= e($v['voucher_date']) ?></div></label>
    <label style="grid-column:span 3">شرح<div><?= e($v['description'] ?: '—') ?></div></label>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>کد</th>
          <th>حساب</th>
          <th>شرح</th>
          <th>بدهکار</th>
          <th>بستانکار</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($lines as $l): ?>
        <tr>
          <td class="num"><?= e($l['code']) ?></td>
          <td><?= e($l['title']) ?></td>
          <td><?= e($l['description'] ?: '—') ?></td>
          <td class="num"><?= money($l['debit']) ?></td>
          <td class="num"><?= money($l['credit']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3">جمع</th>
          <th class="num"><?= money($sumD) ?></th>
          <th class="num"><?= money($sumC) ?></th>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
