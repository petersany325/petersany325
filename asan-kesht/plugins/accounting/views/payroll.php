<?php /** @var list<array<string, mixed>> $rows */ ?>
<?php require __DIR__ . '/nav.php'; ?>
<h2>ردیف حقوق</h2>
<form method="post" action="<?= e(url('/admin/accounting/payroll')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>نام <input name="person" required></label>
  <label>سمت <input name="job_title"></label>
  <label>دوره <input name="period" required placeholder="2026-01" value="<?= e(date('Y-m')) ?>"></label>
  <label>حقوق پایه ریال <input name="base_pay" required inputmode="numeric"></label>
  <label>مزایا <input name="benefit" inputmode="numeric"></label>
  <label>کسور <input name="deduction" inputmode="numeric"></label>
  <label>تاریخ پرداخت <input name="paid_on" type="date"></label>
  <label>یادداشت <input name="note"></label>
  <button type="submit">ثبت حقوق</button>
</form>
<div class="table-wrap">
  <table>
    <thead><tr><th>دوره</th><th>نام</th><th>سمت</th><th>خالص</th><th>پرداخت</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php $net = (int) $row['base_pay'] + (int) $row['benefit'] - (int) $row['deduction']; ?>
      <tr>
        <td><?= e(fa_digits((string) $row['period'])) ?></td>
        <td><?= e((string) $row['person']) ?></td>
        <td><?= e((string) $row['job_title']) ?></td>
        <td><?= e(money($net)) ?></td>
        <td><?= e((string) ($row['paid_on'] ?? '') !== '' ? (string) $row['paid_on'] : 'ثبت نشده') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
