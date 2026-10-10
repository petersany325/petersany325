<div class="m-card">
  <div class="m-card-hd"><strong>تراز آزمایشی</strong></div>
  <ul class="m-list">
    <?php if (empty($rows)): ?><li><div class="m-empty">مانده‌ای نیست</div></li><?php endif; ?>
    <?php foreach ($rows as $r):
      $bal = (float)$r['debit'] - (float)$r['credit'];
    ?>
      <li>
        <div class="m-row">
          <div class="m-row-main">
            <div class="t1"><?= e($r['code'] . ' — ' . $r['title']) ?></div>
            <div class="t2">بدهکار <?= money($r['debit']) ?> · بستانکار <?= money($r['credit']) ?></div>
          </div>
          <strong><?= money($bal) ?></strong>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
