<section class="section"><div class="wrap">
  <p class="kicker">استعلام عمومی</p>
  <h1>بررسی اعتبار نمایندگی</h1>
  <p class="lead">با کد نمایندگی ببینید همکاری این شخص یا شرکت با آسان‌کشت فعال است یا نه. اطلاعات مالی نشان داده نمی‌شود.</p>
  <form method="get" action="<?= e(url('/estelam')) ?>" class="quote-form">
    <label class="field">کد نمایندگی <input name="code" value="<?= e($code) ?>" placeholder="AKN-100000"></label>
    <button class="btn" type="submit">بررسی</button>
  </form>
  <?php if ($code !== ''): ?>
    <?php if (!$dealer): ?>
      <p class="flash flash-error">نماینده‌ای با این کد ثبت نشده است.</p>
    <?php else: ?>
      <?php $active = $dealer['status'] === 'active' && (empty($dealer['contract_end']) || $dealer['contract_end'] >= date('Y-m-d')); ?>
      <article class="machine">
        <div>
          <h2><?= e((string) $dealer['name']) ?></h2>
          <p><?= e((string) $dealer['province']) ?> — <?= e((string) $dealer['city']) ?></p>
          <p>نوع همکاری: <?= e(dealer_cooperations()[$dealer['cooperation']] ?? '') ?></p>
          <p>وضعیت: <?= e($active ? 'نمایندگی فعال است' : 'این نمایندگی اکنون فعال نیست') ?></p>
          <?php if ((int) $dealer['exclusive_city'] === 1): ?><p>نماینده انحصاری این شهر ثبت شده است.</p><?php endif; ?>
        </div>
      </article>
    <?php endif; ?>
  <?php endif; ?>
</div></section>
