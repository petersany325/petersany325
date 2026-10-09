<div class="grid stats">
  <div class="stat"><div class="label">حساب معین</div><div class="value"><?= (int)($counts['moein'] ?? 0) ?></div></div>
  <div class="stat"><div class="label">تفصیلی شناور</div><div class="value"><?= (int)($counts['tafsili'] ?? 0) ?></div></div>
  <div class="stat"><div class="label">اسناد</div><div class="value"><?= (int)($counts['vouchers'] ?? 0) ?></div></div>
  <div class="stat"><div class="label">قطعی / چک</div><div class="value"><?= (int)($counts['locked'] ?? 0) ?> / <?= (int)($counts['checks'] ?? 0) ?></div></div>
</div>

<div class="panel" style="margin-top:14px">
  <div class="hd">
    <strong>آخرین اسناد</strong>
    <div>
      <a class="btn ghost" href="<?= e(url('/reports')) ?>">گزارش‌ها</a>
      <a class="btn" href="<?= e(url('/vouchers/create')) ?>">سند جدید</a>
    </div>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>شماره</th>
          <th>تاریخ</th>
          <th>نوع</th>
          <th>شرح</th>
          <th>وضعیت</th>
          <th>کاربر</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$recent): ?>
        <tr><td colspan="6">سندی ثبت نشده است.</td></tr>
      <?php endif; ?>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><a href="<?= e(url('/vouchers/view')) ?>?id=<?= (int)$r['id'] ?>"><?= (int)$r['number'] ?></a></td>
          <td class="num"><?= e($r['voucher_date']) ?></td>
          <td><?= e($r['type_title'] ?? '—') ?></td>
          <td><?= e($r['description'] ?: '—') ?></td>
          <td><span class="badge <?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
          <td><?= e($r['user_name'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<p style="color:var(--muted);margin-top:12px">
  سال مالی فعال: <?= e($fy['title'] ?? 'نامشخص') ?>
  (<?= e(($fy['start_date'] ?? '') . ' تا ' . ($fy['end_date'] ?? '')) ?>)
</p>
