<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>موتور چک (استاندارد حسابداری ایران)</strong>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" href="<?= e(url('/cheques')) ?>">داشبورد چک‌ها</a>
      <a class="btn ghost" href="<?= e(url('/cheques/receive')) ?>">دریافت چک</a>
      <a class="btn ghost" href="<?= e(url('/cheques/pay')) ?>">صدور چک پرداختی</a>
    </div>
  </div>
  <div class="bd" style="padding:10px 12px;color:var(--muted);font-size:12.5px;line-height:1.7">
    دریافت، واگذاری بانک، وصول/برگشت، خرج چک، صدور پرداختی و برداشت بانکی با سند خودکار دفترکل — چک وصول‌نشده وارد موجودی بانک نمی‌شود.
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
  <div class="panel">
    <div class="hd"><strong>حساب بانکی</strong></div>
    <form method="post" action="<?= e(url('/treasury/bank')) ?>" class="form" style="padding:12px">
      <?= csrf_field() ?>
      <label>عنوان<input name="title" required></label>
      <label>بانک<input name="bank_name"></label>
      <label>شماره حساب<input name="account_no"></label>
      <button class="btn" type="submit">ثبت</button>
    </form>
    <div class="bd">
      <table class="data">
        <thead><tr><th>عنوان</th><th>بانک</th><th>شماره</th></tr></thead>
        <tbody>
        <?php foreach ($banks as $b): ?>
          <tr><td><?= e($b['title']) ?></td><td><?= e($b['bank_name']) ?></td><td class="num"><?= e($b['account_no']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="hd"><strong>سریال دسته چک</strong></div>
    <form method="post" action="<?= e(url('/treasury/checkbook')) ?>" class="form" style="padding:12px">
      <?= csrf_field() ?>
      <label>حساب بانکی
        <select name="bank_account_id" required>
          <?php foreach ($banks as $b): ?>
            <option value="<?= (int)$b['id'] ?>"><?= e($b['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>سریال<input name="series" required></label>
      <label>از شماره<input name="from_no" type="number" required></label>
      <label>تا شماره<input name="to_no" type="number" required></label>
      <button class="btn" type="submit">تعریف دسته چک</button>
    </form>
    <div class="bd">
      <table class="data">
        <thead><tr><th>بانک</th><th>سریال</th><th>از</th><th>تا</th><th>بعدی</th></tr></thead>
        <tbody>
        <?php foreach ($books as $c): ?>
          <tr>
            <td><?= e($c['bank_title']) ?></td>
            <td><?= e($c['series']) ?></td>
            <td class="num"><?= (int)$c['from_no'] ?></td>
            <td class="num"><?= (int)$c['to_no'] ?></td>
            <td class="num"><?= (int)$c['next_no'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
  <div class="panel">
    <div class="hd"><strong>رسید دریافت / پرداخت (سند اتوماتیک)</strong></div>
    <form method="post" action="<?= e(url('/treasury/doc')) ?>" class="form" style="padding:12px">
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
      <label>مبلغ<input name="amount" required inputmode="numeric"></label>
      <label>طرف حساب (تفصیلی شخص)
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
      <button class="btn" type="submit">ثبت و صدور سند</button>
    </form>
  </div>

  <div class="panel">
    <div class="hd"><strong>الگوی اسناد بانکی</strong></div>
    <form method="post" action="<?= e(url('/treasury/pattern')) ?>" class="form" style="padding:12px">
      <?= csrf_field() ?>
      <label>عنوان<input name="title" required></label>
      <label>شرح الگو<input name="description"></label>
      <label>راهنمای ردیف‌ها<textarea name="lines_hint" rows="3"></textarea></label>
      <button class="btn" type="submit">ذخیره الگو</button>
    </form>
    <div class="bd">
      <ul class="list">
        <?php foreach ($patterns as $p): ?>
          <li><?= e($p['title']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<div class="panel">
  <div class="hd"><strong>اسناد خزانه</strong></div>
  <div class="bd">
    <table class="data">
      <thead><tr><th>نوع</th><th>شماره</th><th>تاریخ</th><th>مبلغ</th><th>روش</th><th>شرح</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($docs as $d): ?>
        <tr>
          <td><?= $d['doc_type'] === 'receive' ? 'دریافت' : 'پرداخت' ?></td>
          <td><?= (int)$d['number'] ?></td>
          <td class="num"><?= e($d['doc_date']) ?></td>
          <td class="num"><?= money($d['amount']) ?></td>
          <td><?= e($d['method']) ?></td>
          <td><?= e($d['description'] ?: '—') ?></td>
          <td><a class="btn ghost" target="_blank" href="<?= e(url('/treasury/print')) ?>?id=<?= (int)$d['id'] ?>">چاپ رسید</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
