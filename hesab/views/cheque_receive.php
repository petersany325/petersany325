<form class="panel form" method="post" action="<?= e(url('/cheques/receive')) ?>" style="max-width:720px;padding:14px">
  <?= csrf_field() ?>
  <div class="hd" style="margin:-14px -14px 12px;padding:10px 14px">
    <strong>ثبت دریافت چک از مشتری</strong>
    <a class="btn ghost" href="<?= e(url('/cheques')) ?>">بازگشت</a>
  </div>
  <div class="grid" style="grid-template-columns:1fr 1fr;gap:10px">
    <label>شماره چک<input name="check_no" required></label>
    <label>شناسه صیادی (۱۶ رقم)<input name="sayad_id" dir="ltr" maxlength="16"></label>
    <label>مبلغ (ریال)<input name="amount" required class="num" inputmode="numeric"></label>
    <label>سررسید<input type="date" name="due_date"></label>
    <label>تاریخ دریافت<input type="date" name="receive_date" value="<?= e(date('Y-m-d')) ?>" required></label>
    <label>تاریخ صدور چک<input type="date" name="issue_date"></label>
    <label>بانک عهده<input name="bank_name"></label>
    <label>شعبه<input name="branch_name"></label>
    <label>شماره حساب عهده<input name="account_no" dir="ltr"></label>
    <label>وضعیت صیادی
      <select name="sayad_status">
        <option value="unknown">نامشخص</option>
        <option value="registered">ثبت‌شده</option>
        <option value="confirmed">تأیید شده</option>
      </select>
    </label>
    <label style="grid-column:1/-1">مشتری / صادرکننده (تفصیلی)
      <select name="party_tafsili_id">
        <option value="0">—</option>
        <?php foreach ($persons as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['code'] . ' — ' . $p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>نام صادرکننده<input name="issuer_name"></label>
    <label>ذی‌نفع / در وجه<input name="payee"></label>
    <label style="grid-column:1/-1">شرح<textarea name="description" rows="2"></textarea></label>
  </div>
  <p style="color:var(--muted);font-size:12px">ثبت حسابداری: بدهکار اسناد نزد صندوق / بستانکار حساب‌های دریافتنی — هنوز وارد بانک نمی‌شود.</p>
  <button class="btn" type="submit">ثبت دریافت + صدور سند</button>
</form>
