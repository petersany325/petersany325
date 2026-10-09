<div class="panel">
  <div class="hd">
    <strong>اسناد دریافتنی / پرداختنی (چک)</strong>
    <a class="btn" href="<?= e(url('/cheques')) ?>">موتور مدیریت چک</a>
    <a class="btn ghost" href="?excel=1">Excel</a>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr><th>شماره</th><th>جهت</th><th>مبلغ</th><th>سررسید</th><th>وضعیت فیزیکی</th><th>تسویه</th><th>در وجه</th></tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="7">چکی ثبت نشده</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="num"><?= e($r['check_no']) ?></td>
          <td><?= ($r['direction'] ?? '') === 'receivable' ? 'دریافتنی' : 'پرداختنی' ?></td>
          <td class="num"><?= money($r['amount']) ?></td>
          <td class="num"><?= e($r['due_date'] ?? '—') ?></td>
          <td><?= e(class_exists('ChequeEngine') ? ChequeEngine::physicalLabel((string)($r['status'] ?? '')) : ($r['status'] ?? '')) ?></td>
          <td><?= e($r['settlement_status'] ?? '—') ?></td>
          <td><?= e($r['payee'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
