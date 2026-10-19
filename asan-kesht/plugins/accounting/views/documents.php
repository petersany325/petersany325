<?php
/** @var string $kind */
/** @var array{label: string, prefix: string} $meta */
/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $parties */
/** @var list<array<string, mixed>> $visitors */
/** @var list<array<string, mixed>> $machines */
/** @var list<array<string, mixed>> $houses */
$visitors = $visitors ?? [];
$machines = $machines ?? [];
$houses = $houses ?? [];
require __DIR__ . '/nav.php';
?>
<h2><?= e($meta['label']) ?> تازه</h2>
<form method="post" action="<?= e(url('/admin/accounting/documents')) ?>" class="stack">
  <?= csrf_field() ?>
  <input type="hidden" name="kind" value="<?= e($kind) ?>">
  <label>طرف‌حساب موجود
    <select name="party_id">
      <option value="0">تازه</option>
      <?php foreach ($parties as $party): ?>
        <option value="<?= e((string) $party['id']) ?>"><?= e((string) $party['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="grid">
    <label>نام تازه <input name="party_name"></label>
    <label>موبایل تازه <input name="party_phone"></label>
  </div>
  <?php if ($kind === 'sale' && $visitors !== []): ?>
    <label>ویزیتور
      <select name="visitor_id">
        <option value="0">بدون ویزیتور</option>
        <?php foreach ($visitors as $visitor): ?>
          <option value="<?= e((string) $visitor['id']) ?>"><?= e((string) $visitor['name']) ?> — <?= e(fa_digits((string) $visitor['percent'])) ?>٪ <?= (string) $visitor['basis'] === 'gross' ? 'از سود' : 'از فروش' ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>
  <label>یادداشت <input name="notes"></label>
  <?php if ($machines !== [] && $kind !== 'proforma'): ?>
    <p class="muted">اگر ماشین انتخاب شود، فاکتور خرید یک ورود و فاکتور فروش یک خروج در همان انبار می‌سازد. بهای فروش از میانگین آن انبار می‌آید و عدد بهای تمام‌شده نادیده گرفته می‌شود. اگر موجودی کم باشد سند ثبت نمی‌شود. بدون ماشین، سند فقط مالی است و موجودی را عوض نمی‌کند. موجودی فروشگاه جداست و با انبار جمع نمی‌شود.</p>
  <?php endif; ?>
  <?php for ($i = 0; $i < 4; $i++): ?>
    <div class="grid">
      <label>شرح <input name="item_title[]"></label>
      <label>تعداد <input name="item_qty[]" inputmode="numeric"></label>
      <label>فی ریال <input name="item_price[]" inputmode="numeric"></label>
      <?php if ($kind === 'sale'): ?>
        <label>بهای واحد، فقط بدون ماشین <input name="item_cost[]" inputmode="numeric"></label>
      <?php endif; ?>
      <?php if ($machines !== [] && $kind !== 'proforma'): ?>
        <label>ماشین انبار
          <select name="item_machine[]">
            <option value="0">فقط مالی</option>
            <?php foreach ($machines as $machine): ?>
              <option value="<?= e((string) $machine['id']) ?>"><?= e((string) $machine['code']) ?> — <?= e((string) $machine['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>انبار
          <select name="item_warehouse[]">
            <option value="0">پیش‌فرض</option>
            <?php foreach ($houses as $house): ?>
              <option value="<?= e((string) $house['id']) ?>"><?= e((string) $house['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endif; ?>
    </div>
  <?php endfor; ?>
  <button type="submit">ثبت <?= e($meta['label']) ?></button>
</form>
<h2>اسناد</h2>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>شماره</th><th>طرف</th><th>جمع</th><th>بهای تمام‌شده</th><th>سود</th><th>وضعیت</th><th>دریافت</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php
        $cost = (int) $row['cost'];
        $profit = $kind === 'sale' ? (int) $row['total'] - $cost : 0;
        $rate = $kind === 'sale' ? accounting_rate($profit, (int) $row['total']) : '';
      ?>
      <tr>
        <td>
          <?= e((string) $row['number']) ?>
          <div class="muted"><?= e(jalali_date((string) $row['created_at'])) ?></div>
          <a href="<?= e(url('/admin/accounting/print/' . (string) $row['id'])) ?>" target="_blank">چاپ</a>
          <?php if ($kind === 'sale'): ?>
            <a href="<?= e(url('/admin/accounting/print/' . (string) $row['id'] . '?internal=1')) ?>" target="_blank">چاپ داخلی</a>
          <?php endif; ?>
          <?php if ($kind !== 'proforma' && (string) $row['status'] !== 'cancelled' && (int) $row['paid'] === 0 && (string) ($row['source'] ?? '') !== 'shop'): ?>
            <form method="post" action="<?= e(url('/admin/accounting/cancel')) ?>" class="user-actions">
              <?= csrf_field() ?>
              <input type="hidden" name="invoice_id" value="<?= e((string) $row['id']) ?>">
              <input type="hidden" name="kind" value="<?= e($kind) ?>">
              <label class="check"><input type="checkbox" name="confirm" value="1"> ابطال شود</label>
              <button type="submit">ابطال</button>
            </form>
          <?php endif; ?>
        </td>
        <td><?= e((string) $row['party_name']) ?></td>
        <td><?= e(money((int) $row['total'])) ?></td>
        <td><?= e($kind === 'sale' ? money($cost) : '—') ?></td>
        <td><?= e($kind === 'sale' ? money($profit) : '—') ?><?php if ($rate !== ''): ?><div class="muted"><?= e($rate) ?></div><?php endif; ?></td>
        <td><?= e(accounting_status_label((string) $row['status'])) ?></td>
        <td>
          <?php if ($kind !== 'proforma' && $row['status'] !== 'cancelled'): ?>
            <form method="post" action="<?= e(url('/admin/accounting/payments')) ?>" class="grid">
              <?= csrf_field() ?>
              <input type="hidden" name="invoice_id" value="<?= e((string) $row['id']) ?>">
              <input type="hidden" name="return_to" value="<?= e('/admin/accounting/' . $kind) ?>">
              <input name="amount" placeholder="ریال" required>
              <select name="method">
                <option value="transfer">حواله</option>
                <option value="card">کارت</option>
                <option value="cash">نقد</option>
              </select>
              <input name="paid_at" type="date" value="<?= e(date('Y-m-d')) ?>" required>
              <button type="submit">ثبت</button>
            </form>
          <?php else: ?>
            <span class="muted"><?= e(money((int) $row['paid'])) ?></span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
