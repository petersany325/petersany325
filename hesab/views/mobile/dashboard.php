<div class="m-hero">
  <h1>سلام، <?= e($user['name'] ?? 'کاربر') ?></h1>
  <p>سال مالی: <?= e($fy['title'] ?? 'نامشخص') ?></p>
  <div class="m-hero-actions">
    <a class="m-btn soft" href="<?= e(url('/m/vouchers/create')) ?>">ثبت سند</a>
    <a class="m-btn soft" href="<?= e(url('/m/reports')) ?>">گزارش‌ها</a>
  </div>
</div>

<div class="m-stats">
  <div class="m-stat"><div class="lbl">معین</div><div class="val"><?= (int)($counts['moein'] ?? 0) ?></div></div>
  <div class="m-stat"><div class="lbl">اسناد</div><div class="val"><?= (int)($counts['vouchers'] ?? 0) ?></div></div>
  <div class="m-stat"><div class="lbl">قطعی</div><div class="val"><?= (int)($counts['locked'] ?? 0) ?></div></div>
  <div class="m-stat"><div class="lbl">چک</div><div class="val"><?= (int)($counts['checks'] ?? 0) ?></div></div>
</div>

<div class="m-card">
  <div class="m-card-hd">
    <strong>آخرین اسناد</strong>
    <a href="<?= e(url('/m/vouchers')) ?>">همه</a>
  </div>
  <ul class="m-list">
    <?php if (empty($recent)): ?>
      <li><div class="m-empty">سندی ثبت نشده است</div></li>
    <?php endif; ?>
    <?php foreach ($recent as $r): ?>
      <li>
        <a href="<?= e(url('/m/vouchers/view')) ?>?id=<?= (int)$r['id'] ?>">
          <div class="m-row-main">
            <div class="t1">سند <?= (int)$r['number'] ?> — <?= e($r['description'] ?: 'بدون شرح') ?></div>
            <div class="t2"><?= e($r['voucher_date']) ?> · <?= e($r['type_title'] ?? 'عمومی') ?></div>
          </div>
          <span class="m-chip <?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
