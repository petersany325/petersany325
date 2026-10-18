<section class="section"><div class="wrap">
  <h1>تیکت‌ها</h1>
  <form method="post" action="<?= e(url('/account/tickets')) ?>" class="stack">
    <?= csrf_field() ?>
    <label>موضوع <input name="subject" required></label>
    <label>شرح <textarea name="body" required></textarea></label>
    <button class="btn" type="submit">ثبت تیکت</button>
  </form>
  <div class="sheets">
    <?php foreach ($rows as $row): ?>
      <a class="sheet" href="<?= e(url('/account/tickets/' . $row['id'])) ?>">
        <span class="code">#<?= e(fa_digits((string) $row['id'])) ?></span>
        <div><h3><?= e((string) $row['subject']) ?></h3><p><?= e(jalali_date((string) $row['updated_at'])) ?></p></div>
        <span><?= e((string) $row['status']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div></section>
