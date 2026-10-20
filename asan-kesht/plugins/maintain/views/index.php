<?php /** @var array $status */ /** @var list<array<string, mixed>> $backups */ /** @var array|null $report */ ?>
<p class="muted">کش، تعمیر و پشتیبان همین‌جا هستند. تعمیر پایگاه فقط جدول‌های این سایت با پیشوند ak_ را بررسی می‌کند. پشتیبان شامل کد سایت، عکس‌های آپلودشده و همان جدول‌هاست و رمز پایگاه داخل فایل می‌ماند.</p>
<div class="cards">
  <div class="card"><span>PHP</span><strong><?= e((string) $status['php']) ?></strong></div>
  <div class="card"><span>جدول‌های سایت</span><strong><?= e(fa_digits((string) $status['tables'])) ?></strong></div>
  <div class="card"><span>حجم پایگاه</span><strong><?= e(maintain_size((int) $status['bytes'])) ?></strong></div>
  <div class="card"><span>فایل موقت</span><strong><?= e(fa_digits((string) $status['cache_files'])) ?></strong></div>
  <div class="card"><span>پشتیبان ذخیره‌شده</span><strong><?= e(fa_digits((string) $status['backups'])) ?></strong></div>
  <div class="card"><span>کش PHP</span><strong><?= !empty($status['opcache']) ? 'روشن' : 'خاموش' ?></strong></div>
  <div class="card"><span>فشرده‌سازی</span><strong><?= !empty($status['zip']) ? 'آماده' : 'نیست' ?></strong></div>
  <div class="card"><span>پوشه ذخیره</span><strong><?= !empty($status['storage']) ? 'قابل نوشتن' : 'بسته' ?></strong></div>
</div>
<?php if (is_array($report) && is_array($report['lines'] ?? null)): ?>
  <h2><?= e((string) ($report['title'] ?? 'نتیجه')) ?></h2>
  <ul class="maintain-log">
    <?php foreach ($report['lines'] as $line): ?>
      <?php if (!is_array($line)) { continue; } ?>
      <li class="<?= empty($line['ok']) ? 'is-bad' : '' ?>"><?= e((string) ($line['text'] ?? '')) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<div class="maintain-menu">
  <form method="post" action="<?= e(url('/admin/maintain/cache')) ?>">
    <?= csrf_field() ?>
    <h2>پاک کردن کش</h2>
    <p class="muted">فایل‌های موقت سایت پاک می‌شود. اگر میزبان اجازه بدهد، کش PHP هم خالی می‌شود. نشست ورود و اطلاعات حسابداری دست نمی‌خورد.</p>
    <button type="submit">پاک کردن کش</button>
  </form>
  <form method="post" action="<?= e(url('/admin/maintain/repair-site')) ?>">
    <?= csrf_field() ?>
    <h2>تعمیر سایت</h2>
    <p class="muted">پوشه‌های ذخیره ساخته می‌شوند، دسترسی مستقیم به آن‌ها بسته می‌شود، و فایل‌های اصلی، افزونه‌ها و PHP بررسی می‌شوند.</p>
    <button type="submit">تعمیر سایت</button>
  </form>
  <form method="post" action="<?= e(url('/admin/maintain/repair-db')) ?>">
    <?= csrf_field() ?>
    <h2>تعمیر پایگاه</h2>
    <p class="muted">جدول سالم فقط بررسی می‌شود. جدول خراب بازسازی می‌شود. جدول‌های دیگر همین سرور دست نمی‌خورند.</p>
    <button type="submit">تعمیر پایگاه</button>
  </form>
  <form method="post" action="<?= e(url('/admin/maintain/backup')) ?>">
    <?= csrf_field() ?>
    <h2>پشتیبان سایت</h2>
    <p class="muted">یک فایل فشرده از سایت و پایگاه ساخته می‌شود. تا هشت پشتیبان می‌ماند. پشتیبان قبلی داخل پشتیبان تازه کپی نمی‌شود.</p>
    <button type="submit">ساخت پشتیبان</button>
  </form>
</div>
<h2>پشتیبان‌های ذخیره‌شده</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>فایل</th><th>حجم</th><th>زمان</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($backups as $backup): ?>
      <tr>
        <td><?= e((string) $backup['name']) ?></td>
        <td><?= e(maintain_size((int) $backup['bytes'])) ?></td>
        <td><?= e(jalali_date(date('Y-m-d H:i:s', (int) $backup['mtime']))) ?></td>
        <td>
          <a class="btn" href="<?= e(url('/admin/maintain/backup/' . $backup['name'])) ?>">دانلود</a>
          <form class="maintain-delete" method="post" action="<?= e(url('/admin/maintain/backup/delete')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="name" value="<?= e((string) $backup['name']) ?>">
            <label class="check"><input type="checkbox" name="confirm" value="1"> حذف شود</label>
            <button type="submit">حذف</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($backups === []): ?>
      <tr><td colspan="4">هنوز پشتیبانی ساخته نشده است.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
