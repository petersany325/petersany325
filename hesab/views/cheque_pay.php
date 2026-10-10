<form class="panel form" method="post" action="<?= e(url('/cheques/pay')) ?>" style="max-width:720px;padding:14px">
  <?= csrf_field() ?>
  <div class="hd" style="margin:-14px -14px 12px;padding:10px 14px">
    <strong>صدور و تحویل چک پرداختی</strong>
    <a class="btn ghost" href="<?= e(url('/cheques')) ?>">بازگشت</a>
  </div>
  <div class="grid" style="grid-template-columns:1fr 1fr;gap:10px">
    <label>حساب بانکی شرکت
      <select name="bank_account_id" required>
        <option value="">— انتخاب —</option>
        <?php foreach ($banks as $b): ?>
          <option value="<?= (int)$b['id'] ?>"><?= e($b['title'] . ' / ' . ($b['bank_name'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>دسته چک
      <select name="checkbook_id">
        <option value="0">—</option>
        <?php foreach ($books as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= e($c['bank_title'] . ' — ' . $c['series'] . ' (بعدی ' . $c['next_no'] . ')') ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>شماره برگه<input name="check_no" required></label>
    <label>شناسه صیادی<input name="sayad_id" dir="ltr" maxlength="16"></label>
    <label>مبلغ<input name="amount" required class="num"></label>
    <label>سررسید<input type="date" name="due_date"></label>
    <label>تاریخ صدور/تحویل<input type="date" name="issue_date" value="<?= e(date('Y-m-d')) ?>" required></label>
    <label>وضعیت صیادی
      <select name="sayad_status">
        <option value="unknown">نامشخص</option>
        <option value="registered">ثبت‌شده</option>
        <option value="confirmed">تأیید شده</option>
      </select>
    </label>
    <label style="grid-column:1/-1">ذی‌نفع (تأمین‌کننده)
      <select name="party_tafsili_id">
        <option value="0">—</option>
        <?php foreach ($persons as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['code'] . ' — ' . $p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>در وجه<input name="payee"></label>
    <label>ذی‌نفع متنی<input name="beneficiary"></label>
    <label style="grid-column:1/-1">شرح<textarea name="description" rows="2"></textarea></label>
  </div>
  <p style="color:var(--muted);font-size:12px">ثبت: بدهکار پرداختنی / بستانکار چک‌های صادره — موجودی بانک تا زمان برداشت کم نمی‌شود.</p>
  <button class="btn" type="submit">صدور و تحویل + سند</button>
</form>
