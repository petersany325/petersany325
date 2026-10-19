<?php /** @var App $app */ /** @var array $rows */ /** @var string $line */ /** @var array $lines */ ?>
<section class="page-hero">
  <div class="wrap">
    <p class="kicker">کاتالوگ</p>
    <h1>ماشین‌آلات صنعتی</h1>
    <p class="lead">بازه توان و عرض کار، راهنمای انتخاب است. عدد نهایی روی پیش‌فاکتور همان مدل نوشته می‌شود.</p>
    <div class="filters">
      <a class="chip" href="<?= e(url('/machines')) ?>" <?= $line === 'all' ? 'aria-pressed="true"' : '' ?>>همه</a>
      <?php foreach ($lines as $key => $label): ?>
        <a class="chip" href="<?= e(url('/machines?line=' . $key)) ?>" <?= $line === $key ? 'aria-pressed="true"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section">
  <div class="wrap catalog">
    <?php if ($rows === []): ?><p class="empty is-on">در این خط مدلی نیست.</p><?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <article class="machine">
        <div class="glyph" aria-hidden="true"><span class="code"><?= e((string) $row['code']) ?></span></div>
        <div>
          <h2><?= e((string) $row['name']) ?></h2>
          <p class="meta"><?= e($lines[$row['line']] ?? '') ?> — <?= e((string) $row['summary']) ?></p>
          <dl class="specs">
            <div><dt>توان تراکتور</dt><dd><?= e((string) $row['hp']) ?></dd></div>
            <div><dt>عرض کار</dt><dd><?= e((string) $row['width']) ?></dd></div>
          </dl>
          <?php
            $bits = $app->filter('shop.machine.bits', [
                'machine' => $row,
                'price_html' => '',
                'stock_html' => '',
                'actions_html' => '',
            ]);
            if (!is_array($bits)) {
                $bits = [];
            }
          ?>
          <?= (string) ($bits['price_html'] ?? '') ?>
          <?= (string) ($bits['stock_html'] ?? '') ?>
        </div>
        <div class="machine-actions">
          <a class="btn" href="<?= e(url('/contact?model=' . rawurlencode((string) $row['code']))) ?>">درخواست این مدل</a>
          <?= (string) ($bits['actions_html'] ?? '') ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
