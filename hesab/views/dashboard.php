<div class="grid stats">
  <div class="stat"><div class="label">حساب معین</div><div class="value"><?= (int)$counts['moein'] ?></div></div>
  <div class="stat"><div class="label">اسناد</div><div class="value"><?= (int)$counts['vouchers'] ?></div></div>
  <div class="stat"><div class="label">اسناد قطعی</div><div class="value"><?= (int)$counts['posted'] ?></div></div>
  <div class="stat"><div class="label">طرف‌حساب / فاکتور</div><div class="value"><?= (int)$counts['parties'] ?> / <?= (int)$counts['invoices'] ?></div></div>
</div>

<div class="panel" style="margin-top:14px">
  <div class="hd">
    <strong>آخرین اسناد</strong>
    <div>
      <a class="btn ghost" href="/vouchers">همه اسناد</a>
      <a class="btn" href="/vouchers/create">سند جدید</a>
    </div>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>شماره</th>
          <th>تاریخ</th>
          <th>شرح</th>
          <th>وضعیت</th>
          <th>کاربر</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$recent): ?>
        <tr><td colspan="5">سندی ثبت نشده است.</td></tr>
      <?php endif; ?>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><a href="/vouchers/view?id=<?= (int)$r['id'] ?>"><?= (int)$r['number'] ?></a></td>
          <td class="num"><?= e($r['voucher_date']) ?></td>
          <td><?= e($r['description'] ?: '—') ?></td>
          <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
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
