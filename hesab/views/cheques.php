<?php
/** @var array $rows */
/** @var string $dir */
/** @var string $status */
/** @var array $stats */
$recv = $stats['receivable'] ?? [];
$pay = $stats['payable'] ?? [];
?>
<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>خزانه‌داری / مدیریت چک‌ها</strong>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" href="<?= e(url('/cheques/receive')) ?>">+ دریافت چک</a>
      <a class="btn" href="<?= e(url('/cheques/pay')) ?>">+ صدور چک پرداختی</a>
      <a class="btn ghost" href="<?= e(url('/treasury')) ?>">خزانه</a>
      <a class="btn ghost" href="<?= e(url('/reports/checks')) ?>">گزارش کلاسیک</a>
    </div>
  </div>
</div>

<div class="grid stats" style="margin-bottom:12px;grid-template-columns:repeat(4,minmax(0,1fr))">
  <div class="stat"><div class="label">دریافتی باز (ریال)</div><div class="value num"><?= money($recv['open_amt'] ?? 0) ?></div></div>
  <div class="stat"><div class="label">نزد صندوق</div><div class="value num"><?= money($recv['in_hand'] ?? 0) ?></div></div>
  <div class="stat"><div class="label">در جریان وصول</div><div class="value num"><?= money($recv['deposited'] ?? 0) ?></div></div>
  <div class="stat"><div class="label">پرداختی صادره</div><div class="value num"><?= money($pay['issued_amt'] ?? 0) ?></div></div>
</div>

<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <div style="display:flex;gap:6px">
      <a class="btn <?= $dir==='receivable'?'':'ghost' ?>" href="<?= e(url('/cheques?dir=receivable')) ?>">دریافتی</a>
      <a class="btn <?= $dir==='payable'?'':'ghost' ?>" href="<?= e(url('/cheques?dir=payable')) ?>">پرداختی</a>
    </div>
    <form method="get" action="<?= e(url('/cheques')) ?>" style="display:flex;gap:6px;align-items:center">
      <input type="hidden" name="dir" value="<?= e($dir) ?>">
      <select name="status" onchange="this.form.submit()">
        <option value="">همه وضعیت‌ها</option>
        <?php
        $opts = $dir === 'receivable'
          ? ['in_hand'=>'نزد صندوق','deposited'=>'در جریان وصول','collected'=>'وصول‌شده','returned'=>'برگشتی','endorsed'=>'خرج‌شده','legal'=>'حقوقی']
          : ['issued'=>'صادر و تحویل','cleared'=>'برداشت بانکی','returned'=>'برگشتی','cancelled'=>'ابطال'];
        foreach ($opts as $k=>$lab): ?>
          <option value="<?= e($k) ?>" <?= $status===$k?'selected':'' ?>><?= e($lab) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>شماره</th><th>صیاد</th><th>طرف</th><th>بانک</th>
          <th>مبلغ</th><th>سررسید</th><th>فیزیکی</th><th>صیادی</th><th>تسویه</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="10">چکی در این فیلتر نیست</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="num"><?= e($r['check_no']) ?></td>
          <td class="num" dir="ltr"><?= e($r['sayad_id'] ?: '—') ?></td>
          <td><?= e($r['party_name'] ?: ($r['payee'] ?: ($r['beneficiary'] ?: '—'))) ?></td>
          <td><?= e($r['bank_name'] ?: '—') ?></td>
          <td class="num"><?= money($r['amount']) ?></td>
          <td class="num"><?= e($r['due_date'] ?: '—') ?></td>
          <td><?= e(ChequeEngine::physicalLabel((string)$r['physical_status'])) ?></td>
          <td><?= e($r['sayad_status']) ?></td>
          <td><?= e($r['settlement_status']) ?></td>
          <td><a href="<?= e(url('/cheques/view?id=' . (int)$r['id'])) ?>">عملیات</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<p style="color:var(--muted);font-size:12px;margin:0">
  کنترل استاندارد: چک وصول‌نشده به موجودی بانک اضافه نمی‌شود؛ هر رویداد سند جدا و غیرتکراری دارد؛ وضعیت فیزیکی / صیادی / تسویه مستقل است.
</p>
