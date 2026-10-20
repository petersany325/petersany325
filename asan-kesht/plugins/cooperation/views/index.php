<?php
/** @var array<string, string> $levels */
/** @var array<string, string> $margins */
/** @var string $floor */
/** @var list<array<string, mixed>> $dealers */
/** @var list<array<string, mixed>> $machines */
/** @var int $dealer_id */
/** @var int $machine_id */
/** @var array<string, mixed>|null $quote */
/** @var array<string, mixed>|null $picked */
?>
<p class="muted">قیمت همکاری مبلغ خرید نماینده از شرکت است. اختلاف قیمت خرده‌فروشی و قیمت همکاری سود بالقوه نماینده است، پورسانت قطعی نیست و به حساب خرید یا دفتر نماینده نمی‌نشیند. تأیید سفارش فروش قطعی فقط همان مبلغ خرید را بدهکار می‌کند. قیمتی که مرورگر می‌فرستد ملاک سفارش نیست.</p>
<form method="post" action="<?= e(url('/admin/dealers/cooperation')) ?>" class="grid">
  <?= csrf_field() ?>
  <?php foreach ($levels as $label => $key): ?>
    <label><?= e($label) ?>، درصد حاشیه از خرده
      <input name="<?= e($key) ?>" inputmode="numeric" value="<?= e((string) ($margins[$key] ?? '0')) ?>">
    </label>
  <?php endforeach; ?>
  <label>حداقل سود شرکت برای هر عدد، ریال
    <input name="min_company_profit" inputmode="numeric" value="<?= e($floor) ?>">
  </label>
  <button type="submit">ذخیره سطح‌ها</button>
</form>
<p class="muted">حاشیه سطح فقط وقتی به کار می‌رود که قیمت ثابت نماینده، قیمت سطح یا قیمت پایه ثبت نشده باشد. قیمت ثابت مدیر حتی اگر از کف سود شرکت کمتر باشد همان می‌ماند. کف فقط قیمت فرمولی را بالا می‌آورد. سطح خالی مثل عادی حساب می‌شود.</p>
<h2>پیش‌نمایش قیمت</h2>
<p class="muted">این محاسبه سفارش نمی‌سازد و حساب را عوض نمی‌کند.</p>
<form method="get" action="<?= e(url('/admin/dealers/cooperation')) ?>" class="grid">
  <label>نماینده
    <select name="dealer">
      <option value="0">انتخاب کنید</option>
      <?php foreach ($dealers as $dealer): ?>
        <option value="<?= e((string) $dealer['id']) ?>" <?= $dealer_id === (int) $dealer['id'] ? 'selected' : '' ?>><?= e((string) $dealer['name']) ?> — <?= e((string) $dealer['level']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>مدل
    <select name="machine">
      <option value="0">انتخاب کنید</option>
      <?php foreach ($machines as $machine): ?>
        <option value="<?= e((string) $machine['id']) ?>" <?= $machine_id === (int) $machine['id'] ? 'selected' : '' ?>><?= e((string) $machine['code']) ?> — <?= e((string) $machine['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button type="submit">محاسبه</button>
</form>
<?php if ($dealer_id > 0 && $machine_id > 0): ?>
  <?php if (!$picked || !$quote): ?>
    <p>برای این نماینده و مدل قیمت همکاری ساخته نشد. قیمت خرده یا یک قیمت نمایندگی لازم است.</p>
  <?php else: ?>
    <div class="cards">
      <div class="card"><span>قیمت همکاری</span><strong><?= e(money((int) $quote['price'])) ?></strong></div>
      <div class="card"><span>خرده‌فروشی</span><strong><?= (int) $quote['retail'] > 0 ? e(money((int) $quote['retail'])) : 'ثبت نشده' ?></strong></div>
      <div class="card"><span>بهای تمام‌شده</span><strong><?= e(money((int) $quote['cost'])) ?></strong></div>
      <div class="card"><span>حاشیه از خرده</span><strong><?= e((string) $quote['margin_label'] !== '' ? (string) $quote['margin_label'] : 'ثبت نشده') ?></strong></div>
      <div class="card"><span>نسبت به قیمت همکاری</span><strong><?= e((string) $quote['markup_label'] !== '' ? (string) $quote['markup_label'] : 'ثبت نشده') ?></strong></div>
      <div class="card"><span>مبنا</span><strong><?= e(cooperation_source_label((string) $quote['source'])) ?></strong></div>
    </div>
    <p class="muted">مدل معامله این نماینده: <?= e(dealer_sale_models()[(string) ($picked['sale_model'] ?? '')] ?? '') ?>. در فروش برای شرکت، اختلاف قیمت به سود بالقوه سفارش اضافه نمی‌شود.</p>
  <?php endif; ?>
<?php endif; ?>
