<?php /** @var list<array<string, mixed>> $rows */ ?>
<?php require __DIR__ . '/nav.php'; ?>
<h2>هزینه تازه</h2>
<form method="post" action="<?= e(url('/admin/accounting/expenses')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>عنوان <input name="title" required></label>
  <label>دسته <input name="category" value="عمومی"></label>
  <label>مبلغ ریال <input name="amount" required inputmode="numeric"></label>
  <label>تاریخ <input name="spent_at" type="date" required value="<?= e(date('Y-m-d')) ?>"></label>
  <button type="submit">ثبت هزینه</button>
</form>
<h2>هزینه‌ها</h2>
<ul>
  <?php foreach ($rows as $row): ?>
    <li><?= e(jalali_date((string) $row['spent_at'])) ?> — <?= e((string) $row['category']) ?> — <?= e((string) $row['title']) ?> — <?= e(money((int) $row['amount'])) ?></li>
  <?php endforeach; ?>
</ul>
