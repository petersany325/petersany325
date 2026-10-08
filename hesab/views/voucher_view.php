<?php
$sumD = 0; $sumC = 0;
foreach ($lines as $l) { $sumD += (float)$l['debit']; $sumC += (float)$l['credit']; }
$st = $v['status'];
?>
<div class="panel">
  <div class="hd">
    <div>
      <strong>سند شماره <?= (int)$v['number'] ?></strong>
      <span class="badge <?= e($st) ?>"><?= e(status_label($st)) ?></span>
      <?php if (!empty($v['type_title'])): ?><span class="badge"><?= e($v['type_title']) ?></span><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn ghost" href="<?= e(url('/vouchers')) ?>">بازگشت</a>
      <a class="btn ghost" target="_blank" href="<?= e(url('/vouchers/print')) ?>?id=<?= (int)$v['id'] ?>">چاپ</a>
      <?php if ($st === 'draft' && Permission::can(current_user(), 'vouchers.create')): ?>
        <form method="post" action="<?= e(url('/vouchers/status')) ?>" style="margin:0">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
          <input type="hidden" name="status" value="operational">
          <button class="btn" type="submit">عملیاتی کردن</button>
        </form>
      <?php endif; ?>
      <?php if ($st === 'operational' && Permission::can(current_user(), 'vouchers.review')): ?>
        <form method="post" action="<?= e(url('/vouchers/status')) ?>" style="margin:0">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
          <input type="hidden" name="status" value="reviewed">
          <button class="btn" type="submit">بررسی‌شده</button>
        </form>
      <?php endif; ?>
      <?php if (in_array($st, ['reviewed', 'posted'], true) && Permission::can(current_user(), 'vouchers.lock')): ?>
        <form method="post" action="<?= e(url('/vouchers/status')) ?>" style="margin:0" onsubmit="return confirm('قطعی کردن سند؟ پس از آن قابل تغییر نیست.')">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
          <input type="hidden" name="status" value="locked">
          <button class="btn" type="submit">قطعی کردن</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <div class="form row">
    <label>تاریخ<div><?= e($v['voucher_date']) ?></div></label>
    <label style="grid-column:span 3">شرح<div><?= e($v['description'] ?: '—') ?></div></label>
  </div>
  <div class="bd" style="overflow:auto">
    <table class="data">
      <thead>
        <tr>
          <th>کد</th>
          <th>حساب</th>
          <th>تفصیلی</th>
          <th>پروژه</th>
          <th>مرکز هزینه</th>
          <th>شعبه</th>
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
          <td><?= e($l['t1title'] ?? '—') ?></td>
          <td><?= e($l['project_title'] ?? '—') ?></td>
          <td><?= e($l['cost_title'] ?? '—') ?></td>
          <td><?= e($l['branch_title'] ?? '—') ?></td>
          <td><?= e($l['description'] ?: '—') ?></td>
          <td class="num"><?= money($l['debit']) ?></td>
          <td class="num"><?= money($l['credit']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="7">جمع</th>
          <th class="num"><?= money($sumD) ?></th>
          <th class="num"><?= money($sumC) ?></th>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
