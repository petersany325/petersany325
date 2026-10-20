<?php /** @var App $app */ /** @var array $rows */ /** @var string $line */ /** @var array $lines */ /** @var array $line_images */ ?>
<section class="page-hero">
  <div class="wrap">
    <p class="kicker">فروشگاه</p>
    <h1>ماشین‌آلات صنعتی</h1>
    <p class="lead">هر محصول و هر دسته‌بندی در یک کادر هم‌اندازه دیده می‌شود. اگر عکسی نباشد، طرح پیش‌فرض همان کادر را پر می‌کند.</p>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <div class="section-head"><h2>دسته‌بندی</h2></div>
    <div class="shop-grid">
      <?php foreach ($lines as $key => $label): ?>
        <a class="shop-card<?= $line === $key ? ' is-current' : '' ?>" href="<?= e(url('/machines?line=' . $key)) ?>">
          <?= catalog_frame((string) ($line_images[$key] ?? ''), (string) $key, (string) $label) ?>
          <div class="shop-body">
            <h2><?= e((string) $label) ?></h2>
            <p class="meta">مدل‌های این خط</p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="filters">
      <a class="chip" href="<?= e(url('/machines')) ?>" <?= $line === 'all' ? 'aria-pressed="true"' : '' ?>>همه</a>
      <?php foreach ($lines as $key => $label): ?>
        <a class="chip" href="<?= e(url('/machines?line=' . $key)) ?>" <?= $line === $key ? 'aria-pressed="true"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <div class="section-head"><h2><?= e($line === 'all' ? 'همه مدل‌ها' : (string) ($lines[$line] ?? 'مدل‌ها')) ?></h2></div>
    <?php if ($rows === []): ?><p class="empty is-on">در این خط مدلی نیست.</p><?php endif; ?>
    <div class="shop-grid">
      <?php foreach ($rows as $row): ?>
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
          $rowLine = (string) ($row['line'] ?? '');
        ?>
        <article class="shop-card">
          <?= catalog_frame((string) ($row['image'] ?? ''), $rowLine, (string) $row['name']) ?>
          <div class="shop-body">
            <p class="code"><?= e((string) $row['code']) ?></p>
            <h2><?= e((string) $row['name']) ?></h2>
            <p class="meta"><?= e((string) ($lines[$rowLine] ?? '')) ?> — <?= e((string) $row['summary']) ?></p>
            <dl class="specs">
              <div><dt>توان تراکتور</dt><dd><?= e((string) $row['hp']) ?></dd></div>
              <div><dt>عرض کار</dt><dd><?= e((string) $row['width']) ?></dd></div>
            </dl>
            <?= (string) ($bits['price_html'] ?? '') ?>
            <?= (string) ($bits['stock_html'] ?? '') ?>
            <div class="machine-actions">
              <a class="btn" href="<?= e(url('/contact?model=' . rawurlencode((string) $row['code']))) ?>">درخواست این مدل</a>
              <?= (string) ($bits['actions_html'] ?? '') ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
