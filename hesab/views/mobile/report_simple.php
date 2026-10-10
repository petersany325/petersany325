<div class="m-card">
  <div class="m-card-hd"><strong><?= e($title ?? 'گزارش') ?></strong></div>
  <ul class="m-list">
    <?php if (empty($rows)): ?><li><div class="m-empty">موردی نیست</div></li><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <li>
        <div class="m-row">
          <div class="m-row-main">
            <div class="t1"><?= e(($r['code'] ?? '') . ' ' . ($r['title'] ?? $r['description'] ?? '—')) ?></div>
            <div class="t2">
              <?php if (isset($r['debit'])): ?>بدهکار <?= money($r['debit']) ?> · بستانکار <?= money($r['credit'] ?? 0) ?><?php endif; ?>
              <?php if (isset($r['balance'])): ?>مانده <?= money($r['balance']) ?><?php endif; ?>
            </div>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<a class="m-btn ghost block" href="<?= e(url('/m/reports')) ?>">بازگشت</a>
