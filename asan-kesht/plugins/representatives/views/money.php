<?php /** @var array $payments */ /** @var array $checks */ /** @var array $disputes */ /** @var array $dealers */ ?>
<h2>سند اصلاحی</h2>
<p class="muted">ردیف‌های قبلی عوض نمی‌شوند. اختلاف با یک سند تازه و شرح ثبت می‌شود.</p>
<form method="post" action="<?= e(url('/admin/dealers/adjust')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>نماینده <select name="dealer_id"><?php foreach ($dealers as $dealer): ?><option value="<?= e((string) $dealer['id']) ?>"><?= e((string) $dealer['name']) ?></option><?php endforeach; ?></select></label>
  <label>جهت <select name="direction"><option value="debit">بدهکار نماینده</option><option value="credit">بستانکار نماینده</option></select></label>
  <label>مبلغ ریال <input name="amount" required></label>
  <label>شرح <input name="note" required></label>
  <button type="submit">ثبت سند</button>
</form>
<h2>پرداخت‌های در انتظار</h2>
<div class="table-wrap"><table>
  <thead><tr><th>نماینده</th><th>مبلغ</th><th>روش</th><th>وضعیت</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($payments as $payment): ?>
    <tr>
      <td><?= e((string) $payment['name']) ?></td>
      <td><?= e(money((int) $payment['amount'])) ?></td>
      <td><?= e($payment['method'] === 'bank' ? 'بانکی' : 'نقد') ?></td>
      <td><?= e((string) $payment['status']) ?></td>
      <td>
        <?php if ($payment['status'] === 'pending'): ?>
          <form method="post" action="<?= e(url('/admin/dealers/payments/' . $payment['id'])) ?>">
            <?= csrf_field() ?>
            <button name="decision" value="confirm">تأیید</button>
            <button name="decision" value="reject">رد</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<h2>چک‌ها</h2>
<div class="table-wrap"><table>
  <thead><tr><th>نماینده</th><th>سریال</th><th>سررسید</th><th>مبلغ</th><th>وضعیت</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($checks as $check): ?>
    <tr>
      <td><?= e((string) $check['name']) ?></td>
      <td><?= e((string) $check['serial']) ?> — <?= e((string) $check['bank']) ?></td>
      <td><?= e(jalali_date((string) $check['due_date'])) ?></td>
      <td><?= e(money((int) $check['amount'])) ?></td>
      <td><?= e((string) $check['status']) ?></td>
      <td>
        <?php if ($check['status'] !== 'bounced'): ?>
          <form method="post" action="<?= e(url('/admin/dealers/checks/' . $check['id'])) ?>">
            <?= csrf_field() ?>
            <?php if ($check['status'] === 'pending'): ?><button name="decision" value="clear">وصول</button><?php endif; ?>
            <button name="decision" value="bounce">برگشت</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<h2>مغایرت‌ها</h2>
<?php foreach ($disputes as $dispute): ?>
  <article>
    <p><?= e((string) $dispute['name']) ?> — ردیف <?= e((string) $dispute['ledger_id']) ?> — <?= e((string) $dispute['status']) ?></p>
    <p><?= e((string) $dispute['body']) ?></p>
    <?php if ($dispute['reply'] !== ''): ?><p><?= e((string) $dispute['reply']) ?></p><?php endif; ?>
    <?php if ($dispute['status'] === 'open'): ?>
      <form method="post" action="<?= e(url('/admin/dealers/disputes/' . $dispute['id'])) ?>">
        <?= csrf_field() ?>
        <label>پاسخ <textarea name="reply" required></textarea></label>
        <button type="submit">ثبت پاسخ</button>
      </form>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
