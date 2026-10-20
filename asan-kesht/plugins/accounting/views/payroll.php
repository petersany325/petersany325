<?php /** @var list<array<string, mixed>> $rows */ ?>
<?php require __DIR__ . '/nav.php'; ?>
<p class="muted">هزینه شرکت برابر حقوق پایه + مزایا + بیمه سهم کارفرما است و در سود و زیان می‌آید. کسور، بیمه سهم کارگر و مالیاتِ کسرشده از حقوق است و هزینه را کم نمی‌کند. خالص پرداختی همان مبلغی است که به شخص می‌رسد.</p>
<h2>ردیف حقوق</h2>
<form method="post" action="<?= e(url('/admin/accounting/payroll')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>نام <input name="person" required></label>
  <label>سمت <input name="job_title"></label>
  <label>دوره <input name="period" required placeholder="2026-01" value="<?= e(date('Y-m')) ?>"></label>
  <label>حقوق پایه ریال <input name="base_pay" required inputmode="numeric"></label>
  <label>مزایا <input name="benefit" inputmode="numeric"></label>
  <label>کسور قانونی <input name="deduction" inputmode="numeric"></label>
  <label>بیمه سهم کارفرما <input name="employer_share" inputmode="numeric"></label>
  <label>تاریخ پرداخت <input name="paid_on" type="date"></label>
  <label>یادداشت <input name="note"></label>
  <button type="submit">ثبت حقوق</button>
</form>
<div class="table-wrap">
  <table>
    <thead><tr><th>دوره</th><th>نام</th><th>سمت</th><th>هزینه شرکت</th><th>خالص پرداختی</th><th>پرداخت</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php
        $cost = (int) $row['base_pay'] + (int) $row['benefit'] + (int) ($row['employer_share'] ?? 0);
        $net = (int) $row['base_pay'] + (int) $row['benefit'] - (int) $row['deduction'];
      ?>
      <tr>
        <td><?= e(fa_digits((string) $row['period'])) ?></td>
        <td><?= e((string) $row['person']) ?></td>
        <td><?= e((string) $row['job_title']) ?></td>
        <td><?= e(money($cost)) ?></td>
        <td><?= e(money($net)) ?></td>
        <td><?= e((string) ($row['paid_on'] ?? '') !== '' ? (string) $row['paid_on'] : 'ثبت نشده') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
