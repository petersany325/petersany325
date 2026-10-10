<div class="grid" style="grid-template-columns:280px 1fr;gap:14px">
  <div class="panel">
    <div class="hd"><strong>انواع تفصیلی</strong></div>
    <div class="bd">
      <ul class="list">
        <?php foreach ($types as $t): ?>
          <li>
            <a class="<?= (int)$typeId === (int)$t['id'] ? 'active' : '' ?>" href="<?= e(url('/tafsili')) ?>?type_id=<?= (int)$t['id'] ?>">
              <?= e($t['code'] . ' — ' . $t['title']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <form method="post" action="<?= e(url('/tafsili/type')) ?>" class="form" style="margin-top:12px">
        <?= csrf_field() ?>
        <label>کد<input name="code" required></label>
        <label>عنوان<input name="title" required></label>
        <button class="btn" type="submit">نوع جدید</button>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="hd">
      <strong>اقلام تفصیلی شناور</strong>
      <span class="badge"><?= count($items) ?> مورد</span>
    </div>
    <div class="bd">
      <form method="post" action="<?= e(url('/tafsili/item')) ?>" class="form row" style="margin-bottom:14px">
        <?= csrf_field() ?>
        <input type="hidden" name="type_id" value="<?= (int)$typeId ?>">
        <label>کد<input name="code" required></label>
        <label>عنوان<input name="title" required></label>
        <div style="align-self:end"><button class="btn" type="submit">افزودن</button></div>
      </form>
      <table class="data">
        <thead><tr><th>کد</th><th>عنوان</th><th>فعال</th></tr></thead>
        <tbody>
        <?php if (!$items): ?><tr><td colspan="3">موردی نیست</td></tr><?php endif; ?>
        <?php foreach ($items as $i): ?>
          <tr>
            <td class="num"><?= e($i['code']) ?></td>
            <td><?= e($i['title']) ?></td>
            <td><?= (int)$i['is_active'] ? 'بله' : 'خیر' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
