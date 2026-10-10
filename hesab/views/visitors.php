<?php
/** @var array $rows */
/** @var array|null $edit */
/** @var array $customers */
/** @var array $assigned */
$edit = $edit ?? null;
$customers = $customers ?? [];
$assigned = $assigned ?? [];
?>
<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>زیرسیستم ویزیتور</strong>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn ghost" href="<?= e(url('/visitors/cartable')) ?>">کارتابل</a>
      <a class="btn ghost" href="<?= e(url('/visitors/visits')) ?>">بازدیدها</a>
      <a class="btn ghost" href="<?= e(url('/visitors/commissions')) ?>">پورسانت</a>
      <a class="btn ghost" href="<?= e(url('/visitors/reports')) ?>">گزارشات</a>
    </div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 1.2fr;gap:12px">
  <form class="panel form" method="post" action="<?= e(url('/visitors/save')) ?>" style="padding:12px">
    <?= csrf_field() ?>
    <input type="hidden" name="op" value="save">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="hd" style="margin:-12px -12px 12px;padding:10px 12px"><strong><?= $edit ? 'ویرایش ویزیتور' : 'تعریف ویزیتور جدید' ?></strong></div>
    <label>کد<input name="code" required value="<?= e($edit['code'] ?? '') ?>" placeholder="V-001"></label>
    <label>نام ویزیتور<input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
    <label>موبایل (SMS)<input name="phone" dir="ltr" placeholder="0912xxxxxxx" value="<?= e($edit['phone'] ?? '') ?>"></label>
    <label>منطقه / مسیر<input name="region" value="<?= e($edit['region'] ?? '') ?>"></label>
    <label>درصد پورسانت پیش‌فرض<input name="commission_percent" class="num" value="<?= e((string)($edit['commission_percent'] ?? '5')) ?>"></label>
    <label>یادداشت<textarea name="notes" rows="2"><?= e($edit['notes'] ?? '') ?></textarea></label>
    <label><input type="checkbox" name="is_active" <?= !isset($edit['is_active']) || (int)($edit['is_active']??1)===1?'checked':'' ?>> فعال</label>
    <div style="display:flex;gap:8px">
      <button class="btn" type="submit"><?= $edit ? 'ذخیره تغییرات' : 'ثبت ویزیتور' ?></button>
      <?php if ($edit): ?><a class="btn ghost" href="<?= e(url('/visitors')) ?>">جدید</a><?php endif; ?>
    </div>
  </form>

  <div class="panel">
    <div class="hd"><strong>فهرست ویزیتورها</strong></div>
    <div class="bd">
      <table class="data">
        <thead>
          <tr><th>کد</th><th>نام</th><th>موبایل</th><th>منطقه</th><th>درصد</th><th>مشتری</th><th>بازدید</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="8">ویزیتوری ثبت نشده</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['code']) ?></td>
            <td><?= e($r['name']) ?><?= !(int)$r['is_active'] ? ' <span style="color:var(--muted)">(غیرفعال)</span>' : '' ?></td>
            <td class="num" dir="ltr"><?= e($r['phone'] ?: '—') ?></td>
            <td><?= e($r['region'] ?: '—') ?></td>
            <td class="num"><?= e((string)$r['commission_percent']) ?>٪</td>
            <td class="num"><?= (int)$r['customers_count'] ?></td>
            <td class="num"><?= (int)$r['visits_done'] ?></td>
            <td><a href="<?= e(url('/visitors?edit=' . (int)$r['id'])) ?>">ویرایش</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($edit): ?>
<form class="panel form" method="post" action="<?= e(url('/visitors/save')) ?>" style="margin-top:12px;padding:12px">
  <?= csrf_field() ?>
  <input type="hidden" name="op" value="assign">
  <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
  <div class="hd" style="margin:-12px -12px 12px;padding:10px 12px"><strong>تخصیص مشتریان به <?= e($edit['name']) ?></strong></div>
  <div class="perm-grid">
    <?php if (!$customers): ?>
      <p style="color:var(--muted)">مشتری در طرف‌حساب‌ها یافت نشد. از منوی <a href="<?= e(url('/parties')) ?>">اطلاعات پایه ← طرف‌حساب‌ها</a> مشتری اضافه کنید.</p>
    <?php endif; ?>
    <?php foreach ($customers as $c): ?>
      <label>
        <input type="checkbox" name="party_ids[]" value="<?= (int)$c['id'] ?>"
          <?= in_array((int)$c['id'], $assigned, true) ? 'checked' : '' ?>>
        <?= e($c['name']) ?> <span style="color:var(--muted);font-size:11px">(<?= e($c['code']) ?>)</span>
      </label>
    <?php endforeach; ?>
  </div>
  <button class="btn" type="submit" style="margin-top:10px">ذخیره تخصیص مشتری</button>
</form>
<?php endif; ?>
