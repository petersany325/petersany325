<div class="m-card">
  <div class="m-card-hd"><strong>تفصیلی شناور</strong></div>
  <div style="display:flex;gap:6px;padding:10px 12px;overflow:auto">
    <?php foreach ($types as $t): ?>
      <a class="m-chip <?= (int)$typeId === (int)$t['id'] ? 'operational' : '' ?>" href="<?= e(url('/m/tafsili')) ?>?type_id=<?= (int)$t['id'] ?>"><?= e($t['title']) ?></a>
    <?php endforeach; ?>
  </div>
  <ul class="m-list">
    <?php if (empty($items)): ?><li><div class="m-empty">موردی نیست</div></li><?php endif; ?>
    <?php foreach ($items as $i): ?>
      <li>
        <div class="m-row">
          <div class="m-row-main">
            <div class="t1"><?= e($i['code'] . ' — ' . $i['title']) ?></div>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
