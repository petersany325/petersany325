<div class="grid" style="grid-template-columns:1fr 1fr;gap:14px">
  <div class="panel">
    <div class="hd"><strong>دوره‌های مالی</strong></div>
    <div class="bd">
      <table class="data">
        <thead><tr><th>عنوان</th><th>از</th><th>تا</th><th>فعال</th><th>بسته</th></tr></thead>
        <tbody>
        <?php foreach ($years as $y): ?>
          <tr>
            <td><?= e($y['title']) ?></td>
            <td class="num"><?= e($y['start_date']) ?></td>
            <td class="num"><?= e($y['end_date']) ?></td>
            <td><?= (int)$y['is_active'] ? '✓' : '' ?></td>
            <td><?= (int)($y['is_closed'] ?? 0) ? 'بله' : 'خیر' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div>
    <div class="panel" style="margin-bottom:14px">
      <div class="hd"><strong>ایجاد دوره مالی جدید</strong></div>
      <form method="post" action="<?= e(url('/fiscal/create')) ?>" class="form" style="padding:12px">
        <?= csrf_field() ?>
        <label>عنوان<input name="title" required placeholder="سال مالی ۱۴۰۵"></label>
        <label>از تاریخ<input type="date" name="start_date" required></label>
        <label>تا تاریخ<input type="date" name="end_date" required></label>
        <button class="btn" type="submit">ایجاد و فعال‌سازی</button>
      </form>
    </div>
    <div class="panel">
      <div class="hd"><strong>اسناد افتتاحیه / اختتامیه</strong></div>
      <div class="bd" style="display:flex;gap:8px">
        <form method="post" action="<?= e(url('/fiscal/open-close')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="kind" value="opening">
          <button class="btn" type="submit">صدور افتتاحیه</button>
        </form>
        <form method="post" action="<?= e(url('/fiscal/open-close')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="kind" value="closing">
          <button class="btn ghost" type="submit">صدور اختتامیه</button>
        </form>
      </div>
    </div>
  </div>
</div>
