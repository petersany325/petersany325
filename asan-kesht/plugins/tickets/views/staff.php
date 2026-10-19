<?php
/** @var array $rows */
/** @var array<string, string> $tabs */
/** @var array<string, int> $counts */
/** @var string $current */
/** @var string $base */
$tabs = $tabs ?? [];
$counts = $counts ?? [];
$current = $current ?? 'active';
$base = $base ?? '/admin/tickets';
?>
<nav class="ticket-menu" aria-label="وضعیت تیکت">
  <?php foreach ($tabs as $key => $label): ?>
    <?php $href = $key === 'active' ? $base : $base . '?status=' . $key; ?>
    <a class="st-<?= e((string) $key) ?><?= $current === $key ? ' is-current' : '' ?>" href="<?= e(url($href)) ?>">
      <span><?= e((string) $label) ?></span>
      <b><?= e(fa_digits((string) ($counts[$key] ?? 0))) ?></b>
    </a>
  <?php endforeach; ?>
</nav>
<div class="table-wrap">
  <table>
    <thead><tr><th>شماره</th><th>مشتری</th><th>موضوع</th><th>وضعیت</th><th>به‌روز</th></tr></thead>
    <tbody>
    <?php if ($rows === []): ?>
      <tr><td colspan="5" class="muted">تیکتی در این وضعیت نیست.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><a href="<?= e(url('/admin/tickets/' . $row['id'])) ?>">#<?= e(fa_digits((string) $row['id'])) ?></a></td>
        <td><?= e((string) $row['name']) ?></td>
        <td><?= e((string) $row['subject']) ?></td>
        <td><span class="ticket-pill st-<?= e((string) $row['status']) ?>"><?= e(ticket_status_label((string) $row['status'])) ?></span></td>
        <td><?= e(jalali_date((string) $row['updated_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
