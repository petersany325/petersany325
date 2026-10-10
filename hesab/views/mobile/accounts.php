<div class="m-card">
  <div class="m-card-hd"><strong>کدینگ (مرور موبایل)</strong><span class="m-chip"><?= count($rows) ?></span></div>
  <ul class="m-list">
    <?php foreach ($rows as $r): ?>
      <li>
        <div class="m-row">
          <div class="m-row-main">
            <div class="t1"><?= e($r['code'] . ' — ' . $r['title']) ?></div>
            <div class="t2"><?= e(($r['gc'] ?? '') . ' / ' . ($r['kc'] ?? '')) ?> · <?= e($r['nature'] ?? '') ?></div>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
