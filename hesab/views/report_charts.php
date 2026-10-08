<div class="panel">
  <div class="hd"><strong>بررسی نموداری تعداد اسناد در دوره‌ها</strong></div>
  <div class="bd">
    <?php if (!$months): ?>
      <p>داده‌ای برای نمودار نیست.</p>
    <?php else:
      $max = max(array_map(fn($m) => (int)$m['cnt'], $months)) ?: 1;
    ?>
      <div class="bars">
        <?php foreach (array_reverse($months) as $m):
          $h = max(8, (int)round(((int)$m['cnt'] / $max) * 160));
        ?>
          <div class="bar-col">
            <div class="bar" style="height:<?= $h ?>px" title="<?= (int)$m['cnt'] ?>"></div>
            <div class="bar-label"><?= e($m['ym']) ?></div>
            <div class="bar-val"><?= (int)$m['cnt'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
