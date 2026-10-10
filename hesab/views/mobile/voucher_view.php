<?php
$sumD = 0; $sumC = 0;
foreach ($lines as $l) { $sumD += (float)$l['debit']; $sumC += (float)$l['credit']; }
?>
<div class="m-hero">
  <h1>سند شماره <?= (int)$v['number'] ?></h1>
  <p><?= e($v['voucher_date']) ?> · <?= e($v['type_title'] ?? 'عمومی') ?></p>
  <div style="margin-top:10px">
    <span class="m-chip <?= e($v['status']) ?>" style="background:#fff"><?= e(status_label($v['status'])) ?></span>
  </div>
</div>

<div class="m-card">
  <div class="m-card-hd"><strong>شرح</strong></div>
  <div style="padding:12px 14px"><?= e($v['description'] ?: '—') ?></div>
</div>

<div class="m-card">
  <div class="m-card-hd">
    <strong>ردیف‌ها</strong>
    <span><?= money($sumD) ?> / <?= money($sumC) ?></span>
  </div>
  <ul class="m-list">
    <?php foreach ($lines as $l): ?>
      <li>
        <div class="m-row">
          <div class="m-row-main">
            <div class="t1"><?= e($l['code'] . ' — ' . $l['title']) ?></div>
            <div class="t2"><?= e($l['t1title'] ?? 'بدون تفصیلی') ?></div>
          </div>
          <div style="text-align:left;font-size:12px">
            <?php if ((float)$l['debit'] > 0): ?><div>بدهکار<br><b><?= money($l['debit']) ?></b></div><?php endif; ?>
            <?php if ((float)$l['credit'] > 0): ?><div>بستانکار<br><b><?= money($l['credit']) ?></b></div><?php endif; ?>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<div style="display:grid;gap:8px">
  <a class="m-btn ghost block" href="<?= e(url('/m/vouchers')) ?>">بازگشت به فهرست</a>
  <?php if ($v['status'] === 'draft'): ?>
  <form method="post" action="<?= e(url('/m/vouchers/status')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
    <input type="hidden" name="status" value="operational">
    <button class="m-btn block" type="submit">عملیاتی کردن</button>
  </form>
  <?php endif; ?>
</div>
