<?php /** @var array $invoices */ /** @var array $parties */ /** @var array $expenses */ ?>
<h2>فاکتور تازه</h2>
<form method="post" action="<?= e(url('/admin/accounting/invoices')) ?>" class="stack">
  <?= csrf_field() ?>
  <label>طرف‌حساب موجود
    <select name="party_id">
      <option value="0">— تازه —</option>
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
      <label>شرح ردیف <input name="item_title[]"></label>
      <label>تعداد <input name="item_qty[]" inputmode="numeric"></label>
      <label>فی (ریال) <input name="item_price[]" inputmode="numeric"></label>
    </div>
  <?php endfor; ?>
  <button type="submit">ثبت فاکتور</button>
</form>
<h2>هزینه</h2>
<form method="post" action="<?= e(url('/admin/accounting/expenses')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>عنوان <input name="title" required></label>
  <label>دسته <input name="category" value="عمومی"></label>
  <label>مبلغ ریال <input name="amount" required></label>
  <label>تاریخ <input name="spent_at" type="date" required value="<?= e(date('Y-m-d')) ?>"></label>
  <button type="submit">ثبت هزینه</button>
</form>
<h2>فاکتورها</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>شماره</th><th>طرف</th><th>جمع</th><th>وضعیت</th><th>دریافت</th></tr></thead>
    <tbody>
    <?php foreach ($invoices as $invoice): ?>
      <tr>
        <td><?= e((string) $invoice['number']) ?><div class="muted"><?= e(jalali_date((string) $invoice['created_at'])) ?></div></td>
        <td><?= e((string) $invoice['party_name']) ?></td>
        <td><?= e(money((int) $invoice['total'])) ?></td>
        <td><?= e((string) $invoice['status']) ?></td>
        <td>
          <form method="post" action="<?= e(url('/admin/accounting/payments')) ?>" class="grid">
            <?= csrf_field() ?>
            <input type="hidden" name="invoice_id" value="<?= e((string) $invoice['id']) ?>">
            <input name="amount" placeholder="ریال" required>
            <select name="method">
              <option value="transfer">حواله</option>
              <option value="card">کارت</option>
              <option value="cash">نقد</option>
            </select>
            <input name="paid_at" type="date" value="<?= e(date('Y-m-d')) ?>" required>
            <button type="submit">ثبت</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<h2>هزینه‌های اخیر</h2>
<ul>
  <?php foreach ($expenses as $expense): ?>
    <li><?= e(jalali_date((string) $expense['spent_at'])) ?> — <?= e((string) $expense['title']) ?> — <?= e(money((int) $expense['amount'])) ?></li>
  <?php endforeach; ?>
</ul>
