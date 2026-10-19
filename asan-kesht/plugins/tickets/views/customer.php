<?php
/** @var array $rows */
/** @var array<string, string> $tabs */
/** @var array<string, int> $counts */
/** @var string $current */
/** @var string $base */
$tabs = $tabs ?? [];
$counts = $counts ?? [];
$current = $current ?? 'all';
$base = $base ?? '/account/tickets';
?>
<section class="section"><div class="wrap">
  <div class="ticket-toolbar">
    <h1>تیکت‌ها</h1>
    <a class="btn" href="<?= e(url('/account/tickets/new')) ?>">ایجاد تیکت</a>
  </div>
  <nav class="ticket-menu" aria-label="وضعیت تیکت">
    <?php foreach ($tabs as $key => $label): ?>
      <?php $href = $key === 'all' ? $base : $base . '?status=' . $key; ?>
      <a class="st-<?= e((string) $key) ?><?= $current === $key ? ' is-current' : '' ?>" href="<?= e(url($href)) ?>">
        <span><?= e((string) $label) ?></span>
        <b><?= e(fa_digits((string) ($counts[$key] ?? 0))) ?></b>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sheets ticket-list">
    <?php if ($rows === []): ?>
      <p class="muted">تیکتی در این وضعیت نیست.</p>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <a class="sheet" href="<?= e(url('/account/tickets/' . $row['id'])) ?>">
        <span class="code">#<?= e(fa_digits((string) $row['id'])) ?></span>
        <div><h3><?= e((string) $row['subject']) ?></h3><p><?= e(jalali_date((string) $row['updated_at'])) ?></p></div>
        <span class="ticket-pill st-<?= e((string) $row['status']) ?>"><?= e(ticket_status_label((string) $row['status'])) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div></section>
