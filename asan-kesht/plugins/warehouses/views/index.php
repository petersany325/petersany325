<?php
/** @var list<array<string, mixed>> $houses */
/** @var list<array<string, mixed>> $stock */
/** @var list<array<string, mixed>> $moves */
/** @var list<array<string, mixed>> $machines */
/** @var string $today */
$nav = dirname(__DIR__, 2) . '/accounting/views/nav.php';
if (is_file($nav)) {
    require $nav;
}
$activeHouses = array_values(array_filter($houses, static function (array $house): bool {
    return (int) $house['active'] === 1;
}));
?>
<p class="muted">چند انبار با میانگین متحرک نگه داشته می‌شود. ورود، بهای واحد را به ارزش همان انبار اضافه می‌کند. خروج و اصلاح کاهش و انتقال، به بهای میانگین همان انبار است و بیشتر از موجودی نمی‌شود. انتقال، ارزش را از یک انبار به انبار دیگر می‌برد و جمع شرکت عوض نمی‌شود. این گردش فروش، بهای تمام‌شده فروش یا هزینه نیست. کسری انبار خودش در سود نمی‌آید؛ اگر باید هزینه شود در صفحه هزینه‌ها ثبت می‌شود. اصلاح افزایش هم درآمد نیست. موجودی فروشگاه جداست و با این صفحه عوض نمی‌شود.</p>
<h2>انبار تازه</h2>
<form method="post" action="<?= e(url('/admin/accounting/warehouses')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>نام <input name="name" required></label>
  <label>کد <input name="code" required placeholder="WH2"></label>
  <label>محل <input name="place"></label>
  <label class="check"><input type="checkbox" name="active" checked> فعال</label>
  <label class="check"><input type="checkbox" name="is_default"> پیش‌فرض</label>
  <button type="submit">ثبت انبار</button>
</form>
<h2>فهرست انبارها</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>ویرایش و حذف</th><th>نام</th><th>کد</th><th>محل</th><th>وضعیت</th><th>تعداد</th><th>ارزش</th></tr></thead>
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
        <td><?= e((string) $house['name']) ?><?php if ((int) $house['is_default'] === 1): ?><div class="muted">پیش‌فرض</div><?php endif; ?></td>
        <td><?= e((string) $house['code']) ?></td>
        <td><?= e((string) $house['place']) ?></td>
        <td><?= (int) $house['active'] === 1 ? 'فعال' : 'غیرفعال' ?></td>
        <td><?= e(fa_digits((string) $house['qty'])) ?></td>
        <td><?= e(money((int) $house['stock_value'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($houses === []): ?>
      <tr><td colspan="7">انباری ثبت نشده است.</td></tr>
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
