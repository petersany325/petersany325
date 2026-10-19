<?php
/** @var string $phone */
/** @var string $serial */
/** @var list<array<string, mixed>> $rows */
/** @var string $error */
/** @var bool $asked */
?>
<section class="section"><div class="wrap">
  <p class="kicker">خدمات پس از فروش</p>
  <h1>استعلام گارانتی</h1>
  <p class="lead">با موبایل مالک یا شماره سریال دقیق جستجو کنید. فهرست همه گارانتی‌ها اینجا نشان داده نمی‌شود و این صفحه سند مالی نیست.</p>
  <form method="get" action="<?= e(url('/warranty')) ?>" class="quote-form">
    <label class="field">موبایل <input name="phone" value="<?= e($phone) ?>" inputmode="numeric" placeholder="09120000000"></label>
    <label class="field">سریال <input name="serial" value="<?= e($serial) ?>"></label>
    <button class="btn" type="submit">بررسی</button>
  </form>
  <?php if ($error !== ''): ?>
    <p class="flash flash-error"><?= e($error) ?></p>
  <?php endif; ?>
  <?php if ($asked && $rows === []): ?>
    <p class="flash flash-error">گارانتی با این مشخصات ثبت نشده است.</p>
  <?php endif; ?>
  <?php foreach ($rows as $row): ?>
    <?php $end = warranty_ends((string) $row['started_on'], (int) $row['months']); ?>
    <article class="machine">
      <div>
        <h2><?= e((string) ($row['machine_name'] ?? 'محصول')) ?></h2>
        <p>مالک: <?= e((string) $row['holder_name']) ?></p>
        <?php if ((string) $row['serial'] !== ''): ?><p>سریال: <?= e((string) $row['serial']) ?></p><?php endif; ?>
        <p>شروع <?= e(jalali_date((string) $row['started_on'])) ?> — پایان <?= e(jalali_date($end)) ?> — <?= e(fa_digits((string) $row['months'])) ?> ماه</p>
        <p>وضعیت: <?= e(warranty_label($row)) ?></p>
      </div>
    </article>
  <?php endforeach; ?>
</div></section>
