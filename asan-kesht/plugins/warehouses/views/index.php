<?php
/** @var list<array<string, mixed>> $houses */
/** @var list<array<string, mixed>> $stock */
/** @var list<array<string, mixed>> $moves */
/** @var list<array<string, mixed>> $machines */
/** @var list<array<string, mixed>> $dealers */
/** @var list<array<string, mixed>> $counts */
/** @var list<array<string, mixed>> $parties */
/** @var string $today */
$dealers = $dealers ?? [];
$counts = $counts ?? [];
$parties = $parties ?? [];
$nav = dirname(__DIR__, 2) . '/accounting/views/nav.php';
if (is_file($nav)) {
    require $nav;
}
$activeHouses = array_values(array_filter($houses, static function (array $house): bool {
    return (int) $house['active'] === 1;
}));
$consignTargets = array_values(array_filter($activeHouses, static function (array $house): bool {
    $kind = (string) ($house['kind'] ?? '');
    return $kind === 'consignment' || $kind === 'transit';
}));
$consignHouses = array_values(array_filter($activeHouses, static function (array $house): bool {
    return (string) ($house['kind'] ?? '') === 'consignment';
}));
$dealerNames = [];
foreach ($dealers as $dealer) {
    $dealerNames[(int) $dealer['id']] = (string) $dealer['name'];
}
?>
<p class="muted">اگر روی فاکتور خرید یا فروش ماشین انتخاب شود، خرید یک ورود و فروش یک خروج می‌سازد و بهای فروش از میانگین همان انبار می‌آید. ردیف بدون ماشین فقط مالی است. پیش‌فاکتور موجودی را جابه‌جا نمی‌کند. ارسال به انبار امانی یا در راه فروش نیست و برگشت کالای فروش‌نرفته برگشت از فروش نیست. فروش امانی از انبار امانی به‌نام مشتری نهایی خارج می‌شود. خطای شمارش فقط تعداد و ارزش را اصلاح می‌کند. کسری با علت سرقت، آسیب یا اسقاط هزینه می‌شود و اضافی موجودی درآمد نیست. موجودی فروشگاه جداست و با این تعداد جمع نمی‌شود.</p>
<h2>انبار تازه</h2>
<form method="post" action="<?= e(url('/admin/accounting/warehouses')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>نام <input name="name" required></label>
  <label>کد <input name="code" required placeholder="WH2"></label>
  <label>محل <input name="place"></label>
  <label>نوع
    <select name="kind">
      <?php foreach (warehouse_kinds() as $key => $label): ?>
        <option value="<?= e($key) ?>"><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>نماینده، برای انبار امانی
    <select name="dealer_id">
      <option value="0">بدون نماینده</option>
      <?php foreach ($dealers as $dealer): ?>
        <option value="<?= e((string) $dealer['id']) ?>"><?= e((string) $dealer['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="check"><input type="checkbox" name="active" checked> فعال</label>
  <label class="check"><input type="checkbox" name="is_default"> پیش‌فرض</label>
  <button type="submit">ثبت انبار</button>
</form>
<h2>فهرست انبارها</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>ویرایش و حذف</th><th>نام</th><th>کد</th><th>نوع</th><th>محل</th><th>وضعیت</th><th>تعداد</th><th>ارزش</th></tr></thead>
    <tbody>
    <?php foreach ($houses as $house): ?>
      <tr>
        <td>
          <div class="user-actions">
            <a class="btn" href="<?= e(url('/admin/accounting/warehouses/' . $house['id'])) ?>">ویرایش</a>
            <form method="post" action="<?= e(url('/admin/accounting/warehouses/' . $house['id'] . '/delete')) ?>">
              <?= csrf_field() ?>
              <label class="check"><input type="checkbox" name="confirm" value="1"> حذف شود</label>
              <button type="submit">حذف</button>
            </form>
          </div>
        </td>
        <td><?= e((string) $house['name']) ?><?php if ((int) $house['is_default'] === 1): ?><div class="muted">پیش‌فرض</div><?php endif; ?><?php if ((int) ($house['dealer_id'] ?? 0) > 0): ?><div class="muted"><?= e($dealerNames[(int) $house['dealer_id']] ?? '') ?></div><?php endif; ?></td>
        <td><?= e((string) $house['code']) ?></td>
        <td><?= e(warehouse_kind_label((string) ($house['kind'] ?? 'central'))) ?></td>
        <td><?= e((string) $house['place']) ?></td>
        <td><?= (int) $house['active'] === 1 ? 'فعال' : 'غیرفعال' ?></td>
        <td><?= e(fa_digits((string) $house['qty'])) ?></td>
        <td><?= e(money((int) $house['stock_value'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($houses === []): ?>
      <tr><td colspan="8">انباری ثبت نشده است.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<h2>موجودی</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>انبار</th><th>ماشین</th><th>تعداد</th><th>میانگین</th><th>ارزش</th></tr></thead>
    <tbody>
    <?php foreach ($stock as $row): ?>
      <?php $average = (int) $row['qty'] > 0 ? intdiv((int) $row['value'], (int) $row['qty']) : 0; ?>
      <tr>
        <td><?= e((string) $row['warehouse_name']) ?></td>
        <td><?= e((string) ($row['machine_code'] ?? '')) ?> — <?= e((string) ($row['machine_name'] ?? 'ماشین حذف‌شده')) ?></td>
        <td><?= e(fa_digits((string) $row['qty'])) ?></td>
        <td><?= e(money($average)) ?></td>
        <td><?= e(money((int) $row['value'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($stock === []): ?>
      <tr><td colspan="5">موجودی انباری ثبت نشده است.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<h2>گردش</h2>
<?php if ($machines === [] || $activeHouses === []): ?>
  <p class="muted">برای گردش، هم ماشین کاتالوگ و هم انبار فعال لازم است.</p>
<?php else: ?>
  <form method="post" action="<?= e(url('/admin/accounting/warehouses/move')) ?>" class="grid">
    <?= csrf_field() ?>
    <label>انبار
      <select name="warehouse_id">
        <?php foreach ($activeHouses as $house): ?>
          <option value="<?= e((string) $house['id']) ?>"><?= e((string) $house['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>ماشین
      <select name="machine_id">
        <?php foreach ($machines as $machine): ?>
          <option value="<?= e((string) $machine['id']) ?>"><?= e((string) $machine['code']) ?> — <?= e((string) $machine['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>نوع
      <select name="move">
        <option value="receipt">ورود</option>
        <option value="issue">خروج</option>
        <option value="transfer">انتقال به انبار دیگر</option>
        <option value="adjust_in">اصلاح افزایش</option>
        <option value="adjust_out">اصلاح کاهش</option>
      </select>
    </label>
    <label>انبار مقصد، فقط برای انتقال
      <select name="peer_id">
        <option value="0">—</option>
        <?php foreach ($activeHouses as $house): ?>
          <option value="<?= e((string) $house['id']) ?>"><?= e((string) $house['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>تعداد <input name="qty" required inputmode="numeric"></label>
    <label>بهای واحد ریال، فقط ورود و اصلاح افزایش <input name="unit_cost" inputmode="numeric" value="0"></label>
    <label>تاریخ <input name="moved_on" type="date" required value="<?= e($today) ?>"></label>
    <label>شرح <input name="note"></label>
    <button type="submit">ثبت گردش</button>
  </form>
  <h2>ارسال امانی</h2>
  <p class="muted">این انتقال فروش نیست. مالکیت پیش شرکت می‌ماند و برگشت کالای فروش‌نرفته برگشت از فروش نیست.</p>
  <?php if ($consignTargets === []): ?>
    <p class="muted">برای ارسال، اول یک انبار فعال با نوع امانی یا در راه بسازید.</p>
  <?php else: ?>
    <form method="post" action="<?= e(url('/admin/accounting/warehouses/consign')) ?>" class="grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="send">
      <label>از انبار
        <select name="from_id">
          <?php foreach ($activeHouses as $house): ?>
            <option value="<?= e((string) $house['id']) ?>"><?= e((string) $house['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>به انبار امانی یا در راه
        <select name="to_id">
          <?php foreach ($consignTargets as $house): ?>
            <option value="<?= e((string) $house['id']) ?>"><?= e((string) $house['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>ماشین
        <select name="machine_id">
          <?php foreach ($machines as $machine): ?>
            <option value="<?= e((string) $machine['id']) ?>"><?= e((string) $machine['code']) ?> — <?= e((string) $machine['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>تعداد <input name="qty" required inputmode="numeric"></label>
      <label>تاریخ <input name="moved_on" type="date" required value="<?= e($today) ?>"></label>
      <label>شرح <input name="note" value="ارسال امانی"></label>
      <button type="submit">ثبت ارسال</button>
    </form>
  <?php endif; ?>
  <h2>فروش امانی</h2>
  <p class="muted">خروج از انبار امانی و فاکتور فروش به‌نام مشتری نهایی است، نه نماینده. اگر انبار به نماینده پورسانتی وصل باشد یک پورسانت می‌سازد و در حساب بدهکار نماینده نمی‌نشیند.</p>
  <?php if ($consignHouses === []): ?>
    <p class="muted">فروش امانی به انبار فعال با نوع امانی نیاز دارد.</p>
  <?php else: ?>
    <form method="post" action="<?= e(url('/admin/accounting/warehouses/consign')) ?>" class="grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="sale">
      <label>انبار امانی
        <select name="warehouse_id">
          <?php foreach ($consignHouses as $house): ?>
            <option value="<?= e((string) $house['id']) ?>"><?= e((string) $house['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>ماشین
        <select name="machine_id">
          <?php foreach ($machines as $machine): ?>
            <option value="<?= e((string) $machine['id']) ?>"><?= e((string) $machine['code']) ?> — <?= e((string) $machine['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>مشتری نهایی موجود
        <select name="party_id">
          <option value="0">تازه</option>
          <?php foreach ($parties as $party): ?>
            <option value="<?= e((string) $party['id']) ?>"><?= e((string) $party['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>نام مشتری نهایی <input name="party_name"></label>
      <label>تعداد <input name="qty" required inputmode="numeric"></label>
      <label>فی ریال <input name="unit_price" required inputmode="numeric"></label>
      <label>تاریخ <input name="moved_on" type="date" required value="<?= e($today) ?>"></label>
      <button type="submit">ثبت فروش امانی</button>
    </form>
  <?php endif; ?>
  <h2>شمارش موجودی</h2>
  <p class="muted">اختلاف برابر شمارش منهای دفتر است. خطای شمارش هزینه نیست. سرقت، آسیب و اسقاط اگر کسری باشند یک هزینه می‌سازند. اضافی درآمد نیست.</p>
  <form method="post" action="<?= e(url('/admin/accounting/warehouses/count')) ?>" class="grid">
    <?= csrf_field() ?>
    <label>انبار
      <select name="warehouse_id">
        <?php foreach ($activeHouses as $house): ?>
          <option value="<?= e((string) $house['id']) ?>"><?= e((string) $house['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>ماشین
      <select name="machine_id">
        <?php foreach ($machines as $machine): ?>
          <option value="<?= e((string) $machine['id']) ?>"><?= e((string) $machine['code']) ?> — <?= e((string) $machine['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>تعداد شمارش‌شده <input name="counted_qty" required inputmode="numeric" value="0"></label>
    <label>علت
      <select name="cause">
        <?php foreach (warehouse_causes() as $key => $label): ?>
          <option value="<?= e($key) ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>تاریخ <input name="counted_on" type="date" required value="<?= e($today) ?>"></label>
    <label>شرح <input name="note"></label>
    <button type="submit">ثبت شمارش</button>
  </form>
<?php endif; ?>
<div class="table-wrap">
  <table>
    <thead><tr><th>تاریخ</th><th>انبار</th><th>ماشین</th><th>گردش</th><th>تعداد</th><th>بهای واحد</th><th>مبلغ</th><th>طرف</th></tr></thead>
    <tbody>
    <?php foreach ($moves as $move): ?>
      <tr>
        <td><?= e(jalali_date((string) $move['moved_on'])) ?></td>
        <td><?= e((string) $move['warehouse_name']) ?></td>
        <td><?= e((string) ($move['machine_code'] ?? '')) ?></td>
        <td><?= e(warehouse_move_label((string) $move['kind'], (string) $move['direction'])) ?></td>
        <td><?= e(fa_digits((string) $move['qty'])) ?></td>
        <td><?= e(money((int) $move['unit_cost'])) ?></td>
        <td><?= e(money((int) $move['total'])) ?></td>
        <td><?= e((string) ($move['peer_name'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($moves === []): ?>
      <tr><td colspan="8">گردشی ثبت نشده است.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<h2>شمارش‌های اخیر</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>برگشت</th><th>تاریخ</th><th>انبار</th><th>ماشین</th><th>دفتر</th><th>شمارش</th><th>اختلاف</th><th>علت</th><th>وضعیت</th></tr></thead>
    <tbody>
    <?php foreach ($counts as $count): ?>
      <tr>
        <td>
          <?php if ((int) $count['voided'] !== 1): ?>
            <form method="post" action="<?= e(url('/admin/accounting/warehouses/count-void')) ?>" class="user-actions">
              <?= csrf_field() ?>
              <input type="hidden" name="count_id" value="<?= e((string) $count['id']) ?>">
              <label class="check"><input type="checkbox" name="confirm" value="1"> برگشت شود</label>
              <button type="submit">برگشت</button>
            </form>
          <?php endif; ?>
        </td>
        <td><?= e(jalali_date((string) $count['counted_on'])) ?></td>
        <td><?= e((string) $count['warehouse_name']) ?></td>
        <td><?= e((string) ($count['machine_code'] ?? '')) ?></td>
        <td><?= e(fa_digits((string) $count['book_qty'])) ?></td>
        <td><?= e(fa_digits((string) $count['counted_qty'])) ?></td>
        <td><?= e(fa_digits((string) $count['difference'])) ?></td>
        <td><?= e(warehouse_causes()[(string) $count['cause']] ?? (string) $count['cause']) ?></td>
        <td><?= (int) $count['voided'] === 1 ? 'برگشت‌خورده' : 'ثبت‌شده' ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($counts === []): ?>
      <tr><td colspan="9">شمارشی ثبت نشده است.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
