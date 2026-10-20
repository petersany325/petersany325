<section class="section">
  <div class="wrap">
    <h1>فاکتورهای من</h1>
    <?php if ($rows === []): ?><p>فاکتوری با این شماره موبایل ثبت نشده است.</p><?php endif; ?>
    <div class="sheets">
      <?php foreach ($rows as $row): ?>
        <article class="sheet">
          <span class="code"><?= e((string) $row['number']) ?></span>
          <div><h3><?= e(money((int) $row['total'])) ?></h3><p><?= e(jalali_date((string) $row['created_at'])) ?></p></div>
          <?php $kindMeta = accounting_kind((string) ($row['kind'] ?? 'sale')); ?>
          <span><?= e($kindMeta['label'] ?? 'فاکتور') ?> — <?= e(accounting_status_label((string) $row['status'])) ?></span>
          <a href="<?= e(url('/account/invoices/' . (string) $row['id'] . '/print')) ?>">چاپ</a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
