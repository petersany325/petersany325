<div class="m-hero">
  <h1>خزانه‌داری موبایل</h1>
  <p>ثبت سریع دریافت و پرداخت با صدور سند اتوماتیک</p>
</div>

<div class="m-card">
  <div class="m-card-hd"><strong>ثبت دریافت / پرداخت</strong></div>
  <form class="m-form" method="post" action="<?= e(url('/m/treasury/doc')) ?>">
    <?= csrf_field() ?>
    <label>نوع
      <select name="doc_type">
        <option value="receive">دریافت</option>
        <option value="pay">پرداخت</option>
      </select>
    </label>
    <label>تاریخ<input type="date" name="doc_date" value="<?= e(date('Y-m-d')) ?>"></label>
    <label>روش
      <select name="method">
        <option value="cash">نقد</option>
        <option value="bank">بانک</option>
        <option value="check">چک</option>
      </select>
    </label>
    <label>مبلغ<input name="amount" required inputmode="numeric" placeholder="مثلاً 1500000"></label>
    <label>طرف حساب
      <select name="party_tafsili_id">
        <option value="0">—</option>
        <?php foreach ($persons as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['code'] . ' — ' . $p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>حساب بانکی
      <select name="bank_account_id">
        <option value="0">—</option>
        <?php foreach ($banks as $b): ?>
          <option value="<?= (int)$b['id'] ?>"><?= e($b['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>شرح<input name="description"></label>
    <button class="m-btn block" type="submit">ثبت و صدور سند</button>
  </form>
</div>

<div class="m-card">
  <div class="m-card-hd"><strong>آخرین اسناد خزانه</strong></div>
  <ul class="m-list">
    <?php if (empty($docs)): ?><li><div class="m-empty">موردی نیست</div></li><?php endif; ?>
    <?php foreach ($docs as $d): ?>
      <li>
        <div class="m-row">
          <div class="m-row-main">
            <div class="t1"><?= $d['doc_type'] === 'receive' ? 'دریافت' : 'پرداخت' ?> #<?= (int)$d['number'] ?></div>
            <div class="t2"><?= e($d['doc_date']) ?> · <?= e($d['description'] ?: '—') ?></div>
          </div>
          <strong><?= money($d['amount']) ?></strong>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
