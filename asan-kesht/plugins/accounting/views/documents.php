<?php
/** @var string $kind */
/** @var array{label: string, prefix: string} $meta */
/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $parties */
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
  <label>یادداشت <input name="notes"></label>
  <?php for ($i = 0; $i < 4; $i++): ?>
    <div class="grid">
      <label>شرح <input name="item_title[]"></label>
      <label>تعداد <input name="item_qty[]" inputmode="numeric"></label>
      <label>فی ریال <input name="item_price[]" inputmode="numeric"></label>
      <?php if ($kind === 'sale'): ?>
        <label>بهای تمام‌شده واحد <input name="item_cost[]" inputmode="numeric"></label>
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
      ?>
      <tr>
        <td><?= e((string) $row['number']) ?><div class="muted"><?= e(jalali_date((string) $row['created_at'])) ?></div></td>
        <td><?= e((string) $row['party_name']) ?></td>
        <td><?= e(money((int) $row['total'])) ?></td>
        <td><?= e($kind === 'sale' ? money($cost) : '—') ?></td>
        <td><?= e($kind === 'sale' ? money($profit) : '—') ?></td>
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
