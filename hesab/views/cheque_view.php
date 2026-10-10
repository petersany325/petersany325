<?php
/** @var array $cheque */
/** @var array $events */
/** @var array|null $party */
$ch = $cheque;
$recv = $ch['direction'] === 'receivable';
$phys = (string) $ch['physical_status'];
?>
<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>چک <?= e($ch['check_no']) ?> — <?= $recv ? 'دریافتی' : 'پرداختی' ?></strong>
    <a class="btn ghost" href="<?= e(url('/cheques?dir=' . $ch['direction'])) ?>">فهرست</a>
  </div>
  <div class="bd" style="padding:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;font-size:13px;line-height:1.8">
    <div>مبلغ: <strong class="num"><?= money($ch['amount']) ?></strong></div>
    <div>مانده: <strong class="num"><?= money((float)$ch['amount'] - (float)$ch['amount_settled']) ?></strong></div>
    <div>سررسید: <span class="num"><?= e($ch['due_date'] ?: '—') ?></span></div>
    <div>فیزیکی: <strong><?= e(ChequeEngine::physicalLabel($phys)) ?></strong></div>
    <div>صیادی: <strong><?= e($ch['sayad_status']) ?></strong> <span dir="ltr"><?= e($ch['sayad_id'] ?: '') ?></span></div>
    <div>تسویه: <strong><?= e($ch['settlement_status']) ?></strong></div>
    <div>طرف: <?= e($party['title'] ?? ($ch['payee'] ?: '—')) ?></div>
    <div>بانک: <?= e($ch['bank_name'] ?: '—') ?></div>
    <div>سند آخر: <?php if ($ch['last_voucher_id']): ?><a href="<?= e(url('/vouchers/view?id=' . (int)$ch['last_voucher_id'])) ?>">#<?= (int)$ch['last_voucher_id'] ?></a><?php else: ?>—<?php endif; ?></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
  <div class="panel">
    <div class="hd"><strong>عملیات مجاز</strong></div>
    <div class="bd" style="padding:12px;display:grid;gap:10px">
      <?php if ($recv && $phys === 'in_hand'): ?>
        <form method="post" action="<?= e(url('/cheques/action')) ?>" class="form">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="deposit">
          <label>واگذاری به بانک
            <select name="bank_account_id">
              <option value="0">—</option>
              <?php foreach ($banks as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['title']) ?></option><?php endforeach; ?>
            </select>
          </label>
          <label>شماره فیش<input name="slip_no"></label>
          <button class="btn" type="submit">واگذاری (در جریان وصول)</button>
        </form>
        <form method="post" action="<?= e(url('/cheques/action')) ?>" class="form">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="endorse">
          <label>خرج به تأمین‌کننده
            <select name="party_tafsili_id">
              <option value="0">—</option>
              <?php foreach ($persons as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?>
            </select>
          </label>
          <label>نام گیرنده<input name="to_name"></label>
          <label><input type="checkbox" name="with_recourse"> با مسئولیت / recourse</label>
          <button class="btn ghost" type="submit">خرج / انتقال چک</button>
        </form>
        <form method="post" action="<?= e(url('/cheques/action')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="collect">
          <button class="btn" type="submit">وصول مستقیم (بدون واگذاری)</button>
        </form>
      <?php endif; ?>

      <?php if ($recv && $phys === 'deposited'): ?>
        <form method="post" action="<?= e(url('/cheques/action')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="collect">
          <button class="btn" type="submit">وصول کامل بانکی</button>
        </form>
        <form method="post" action="<?= e(url('/cheques/action')) ?>" class="form">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="partial_collect">
          <label>وصول جزئی<input name="amount" class="num" required></label>
          <button class="btn ghost" type="submit">ثبت وصول جزئی</button>
        </form>
        <form method="post" action="<?= e(url('/cheques/action')) ?>" class="form">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="return_recv">
          <label>دلیل برگشت<input name="reason"></label>
          <button class="btn ghost" type="submit">برگشت از بانک</button>
        </form>
        <form method="post" action="<?= e(url('/cheques/action')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="restore_hand">
          <button class="btn ghost" type="submit">استرداد به صندوق</button>
        </form>
      <?php endif; ?>

      <?php if ($recv && $phys === 'returned'): ?>
        <form method="post" action="<?= e(url('/cheques/action')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="legal">
          <button class="btn" type="submit">انتقال به پیگیری حقوقی</button>
        </form>
      <?php endif; ?>

      <?php if (!$recv && $phys === 'issued'): ?>
        <form method="post" action="<?= e(url('/cheques/action')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="clear_pay">
          <button class="btn" type="submit">برداشت بانکی / تسویه</button>
        </form>
        <form method="post" action="<?= e(url('/cheques/action')) ?>" class="form">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="return_pay">
          <label>دلیل برگشت<input name="reason"></label>
          <button class="btn ghost" type="submit">برگشت چک پرداختی</button>
        </form>
      <?php endif; ?>

      <form method="post" action="<?= e(url('/cheques/action')) ?>" class="form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
        <input type="hidden" name="action" value="sayad_update">
        <label>به‌روزرسانی صیاد
          <select name="sayad_status">
            <?php foreach (['unknown','registered','confirmed','transferred','rejected'] as $s): ?>
              <option value="<?= e($s) ?>" <?= $ch['sayad_status']===$s?'selected':'' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>شناسه صیادی<input name="sayad_id" value="<?= e($ch['sayad_id'] ?? '') ?>" dir="ltr"></label>
        <button class="btn ghost" type="submit">ذخیره صیاد</button>
      </form>

      <?php if ($ch['settlement_status'] !== 'settled' && $phys !== 'cancelled'): ?>
        <form method="post" action="<?= e(url('/cheques/action')) ?>" onsubmit="return confirm('ابطال چک؟')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
          <input type="hidden" name="action" value="cancel">
          <button class="btn ghost" type="submit">ابطال</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="panel">
    <div class="hd"><strong>تاریخچه رویدادها</strong></div>
    <div class="bd">
      <table class="data">
        <thead><tr><th>تاریخ</th><th>رویداد</th><th>مبلغ</th><th>سند</th><th>شرح</th><th>کاربر</th></tr></thead>
        <tbody>
        <?php if (!$events): ?><tr><td colspan="6">—</td></tr><?php endif; ?>
        <?php foreach ($events as $e): ?>
          <tr>
            <td class="num"><?= e($e['event_date']) ?></td>
            <td><?= e($e['event_type']) ?></td>
            <td class="num"><?= money($e['amount']) ?></td>
            <td><?php if ($e['voucher_id']): ?><a href="<?= e(url('/vouchers/view?id=' . (int)$e['voucher_id'])) ?>">#<?= (int)$e['voucher_id'] ?></a><?php else: ?>—<?php endif; ?></td>
            <td><?= e($e['detail'] ?: '—') ?></td>
            <td><?= e($e['user_name'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
