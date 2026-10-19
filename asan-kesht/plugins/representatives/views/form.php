<?php /** @var array|null $dealer */ /** @var array $regions */ /** @var array $ledger */ /** @var array $orders */ /** @var array $files */ /** @var array $receipts */ /** @var int $balance */ /** @var int $overdue */ /** @var array|null $rule */ ?>
<?php $dealer = is_array($dealer) ? $dealer : []; ?>
<?php $rule = is_array($rule ?? null) ? $rule : []; ?>
<?php $groups = $dealer === [] ? [] : array_filter(explode(',', (string) ($dealer['product_groups'] ?? ''))); ?>
<?php if ($dealer): ?>
  <div class="cards">
    <div class="card"><span>کد استعلام</span><strong><?= e((string) $dealer['code']) ?></strong></div>
    <div class="card"><span><?= e($balance > 0 ? 'بدهکار نماینده' : ($balance < 0 ? 'بستانکار نماینده' : 'حساب تسویه')) ?></span><strong><?= e(money(abs($balance))) ?></strong></div>
    <div class="card"><span>سررسیدشده</span><strong><?= e(money($overdue)) ?></strong></div>
    <div class="card"><span>سقف اعتبار</span><strong><?= e(money((int) $dealer['credit_limit'])) ?></strong></div>
  </div>
<?php endif; ?>
<form method="post" action="<?= e(url('/admin/dealers')) ?>" enctype="multipart/form-data" class="stack">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e((string) ($dealer['id'] ?? 0)) ?>">
  <div class="grid">
    <label>نام <input name="name" required value="<?= e((string) ($dealer['name'] ?? '')) ?>"></label>
    <label>نوع
      <select name="kind">
        <option value="person" <?= ($dealer['kind'] ?? '') === 'person' ? 'selected' : '' ?>>شخص</option>
        <option value="company" <?= ($dealer['kind'] ?? '') === 'company' ? 'selected' : '' ?>>شرکت</option>
      </select>
    </label>
    <label>شناسه یا کد ملی <input name="national_id" value="<?= e((string) ($dealer['national_id'] ?? '')) ?>"></label>
    <label>موبایل <input name="phone" value="<?= e((string) ($dealer['phone'] ?? '')) ?>"></label>
    <label>استان <input name="province" value="<?= e((string) ($dealer['province'] ?? '')) ?>"></label>
    <label>شهر <input name="city" required value="<?= e((string) ($dealer['city'] ?? '')) ?>"></label>
    <label>نوع همکاری
      <select name="cooperation">
        <?php foreach (dealer_cooperations() as $key => $label): ?><option value="<?= e($key) ?>" <?= ($dealer['cooperation'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>مدل معامله
      <select name="sale_model">
        <?php foreach (dealer_sale_models() as $key => $label): ?><option value="<?= e($key) ?>" <?= ($dealer['sale_model'] ?? 'resale') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>محدوده <input name="territory" value="<?= e((string) ($dealer['territory'] ?? '')) ?>"></label>
    <label>سطح <input name="level" value="<?= e((string) ($dealer['level'] ?? '')) ?>" placeholder="پایه، نقره‌ای، طلایی"></label>
    <label>درصد پورسانت این نماینده <input name="commission_percent" inputmode="numeric" value="<?= e((string) ($rule['percent'] ?? '')) ?>" placeholder="خالی = پیش‌فرض"></label>
    <label>مبنای درصد
      <select name="commission_basis">
        <option value="sale" <?= ($rule['basis'] ?? 'sale') === 'sale' ? 'selected' : '' ?>>از مبلغ فروش</option>
        <option value="gross" <?= ($rule['basis'] ?? '') === 'gross' ? 'selected' : '' ?>>از سود ناخالص</option>
      </select>
    </label>
    <label>قطعی شدن پورسانت
      <select name="commission_settle">
        <option value="collection" <?= ($rule['settle_on'] ?? 'collection') === 'collection' ? 'selected' : '' ?>>هنگام وصول وجه</option>
        <option value="sale" <?= ($rule['settle_on'] ?? '') === 'sale' ? 'selected' : '' ?>>هنگام تأیید فروش</option>
      </select>
    </label>
    <label>سقف اعتبار ریال <input name="credit_limit" value="<?= e((string) ($dealer['credit_limit'] ?? 0)) ?>"></label>
    <label>مهلت تسویه روز <input name="credit_days" value="<?= e((string) ($dealer['credit_days'] ?? 30)) ?>"></label>
    <label>شروع قرارداد <input type="date" name="contract_start" value="<?= e((string) ($dealer['contract_start'] ?? '')) ?>"></label>
    <label>پایان قرارداد <input type="date" name="contract_end" value="<?= e((string) ($dealer['contract_end'] ?? '')) ?>"></label>
    <label>منطقه
      <select name="region_id">
        <option value="0">—</option>
        <?php foreach ($regions as $region): ?><option value="<?= e((string) $region['id']) ?>" <?= (int) ($dealer['region_id'] ?? 0) === (int) $region['id'] ? 'selected' : '' ?>><?= e((string) $region['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>وضعیت
      <select name="status">
        <?php foreach (dealer_statuses() as $key => $label): ?><option value="<?= e($key) ?>" <?= ($dealer['status'] ?? 'pending') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>نام کاربری ورود <input name="username"></label>
    <label>رمز ورود <input type="password" name="password" autocomplete="new-password"></label>
  </div>
  <p class="muted">درصد خالی یعنی درصد، مبنا و زمان قطعی‌شدن از تنظیمات شرکت. صفر یعنی همین نماینده پورسانت ندارد. سود ناخالص برابر مبلغ فروش منهای بهای تمام‌شده است؛ اگر این تفاوت صفر باشد برای آن ردیف پورسانتی ساخته نمی‌شود. وصول وجه تا وقتی پرداخت‌های تأییدشده به جمع سفارش برسد، پورسانت را در جریان نگه می‌دارد. مدل خرید و فروش دوباره از این درصد استفاده نمی‌کند.</p>
  <label>شرایط تسویه <input name="settlement" value="<?= e((string) ($dealer['settlement'] ?? '')) ?>"></label>
  <fieldset>
    <legend>گروه کالای مجاز</legend>
    <?php foreach (dealer_lines() as $key => $label): ?>
      <label class="check"><input type="checkbox" name="lines[]" value="<?= e($key) ?>" <?= in_array($key, $groups, true) ? 'checked' : '' ?>> <?= e($label) ?></label>
    <?php endforeach; ?>
  </fieldset>
  <label class="check"><input type="checkbox" name="exclusive_city" <?= (int) ($dealer['exclusive_city'] ?? 0) === 1 ? 'checked' : '' ?>> نماینده انحصاری این شهر</label>
  <label class="check"><input type="checkbox" name="exclusive_exception" <?= (int) ($dealer['exclusive_exception'] ?? 0) === 1 ? 'checked' : '' ?>> استثنای شرکت بر انحصار</label>
  <label>عنوان مدرک <input name="document_title" placeholder="قرارداد، جواز، کارت ملی"></label>
  <label>فایل مدرک <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp"></label>
  <button type="submit">ذخیره پرونده</button>
</form>
<?php if ($files !== []): ?>
  <h2>مدارک</h2>
  <ul><?php foreach ($files as $file): ?><li><a href="<?= e(url('/admin/dealers/files/' . $file['id'])) ?>"><?= e((string) $file['title']) ?></a></li><?php endforeach; ?></ul>
<?php endif; ?>
<?php if ($dealer): ?>
  <h2>سفارش‌ها</h2>
  <ul><?php foreach ($orders as $order): ?><li><a href="<?= e(url('/admin/dealers/orders/' . $order['id'])) ?>"><?= e((string) $order['number']) ?></a> — <?= e(money((int) $order['total'])) ?> — <?= e(dealer_order_statuses()[$order['status']] ?? '') ?></li><?php endforeach; ?></ul>
  <h2>گردش حساب</h2>
  <ul><?php foreach ($ledger as $row): ?><li><?= e(jalali_date((string) $row['created_at'])) ?> — <?= e($row['direction'] === 'debit' ? 'بدهکار' : 'بستانکار') ?> <?= e(money((int) $row['amount'])) ?> — <?= e((string) $row['note']) ?></li><?php endforeach; ?></ul>
  <h2>پذیرش بخشنامه و قانون</h2>
  <ul><?php foreach ($receipts as $receipt): ?><li><?= e((string) $receipt['title']) ?> — <?= e((string) $receipt['kind']) ?> — <?= e((string) $receipt['status']) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
