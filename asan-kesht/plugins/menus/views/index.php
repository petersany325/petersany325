<?php
/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $tree */
/** @var array{effect: string, open: string} $options */
/** @var int $parent */

$flat = [];
$walk = static function (array $nodes, int $depth) use (&$walk, &$flat): void {
    foreach ($nodes as $node) {
        $node['depth'] = $depth;
        $children = is_array($node['children'] ?? null) ? $node['children'] : [];
        unset($node['children']);
        $flat[] = $node;
        $walk($children, $depth + 1);
    }
};
$walk($tree, 0);
?>
<p class="muted">این فهرست، منوی بالای سایت است. سه سطح دارید: آیتم اصلی، ستون، و پیوند داخل ستون. با روشن بودن «مگا» یک پنل تمام‌عرض باز می‌شود: ستون تیره معرفی، کارت هر گروه، نشانه و نشان کوتاه. «برجسته» فقط روی ستون، آن کارت را زرد می‌کند. ورود، حساب و خروج جدا می‌مانند تا مسیر ورود بسته نشود.</p>
<h2>تنظیم باز شدن</h2>
<form method="post" action="<?= e(url('/admin/menus/settings')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>افکت
    <select name="effect">
      <?php foreach (['none' => 'بدون افکت', 'fade' => 'محو شدن', 'slide' => 'لغزش'] as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $options['effect'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>باز شدن
    <select name="open">
      <?php foreach (['hover' => 'با ایستادن نشانگر', 'click' => 'با کلیک روی فلش'] as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $options['open'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button type="submit">ذخیره تنظیم منو</button>
</form>
<h2>آیتم تازه</h2>
<form method="post" action="<?= e(url('/admin/menus')) ?>" class="stack">
  <?= csrf_field() ?>
  <?php $item = $parent > 0 ? ['parent_id' => $parent, 'visible' => 1] : []; require __DIR__ . '/fields.php'; ?>
  <button type="submit">افزودن به منو</button>
</form>
<form method="post" action="<?= e(url('/admin/menus/import')) ?>">
  <?= csrf_field() ?>
  <button type="submit">افزودن صفحه‌های منتشرشده‌ای که در منو نیستند</button>
</form>
<h2>آیتم‌های فعلی</h2>
<?php foreach ($flat as $item): ?>
  <?php
    $depth = (int) $item['depth'];
    $pad = $depth * 1.2;
  ?>
  <form method="post" action="<?= e(url('/admin/menus')) ?>" class="stack" style="margin-inline-start: <?= e((string) $pad) ?>rem">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
    <h3><?= e(str_repeat('— ', $depth) . (string) $item['label']) ?></h3>
    <?php require __DIR__ . '/fields.php'; ?>
    <button type="submit">ذخیره این آیتم</button>
  </form>
  <?php if ($depth < 2): ?>
    <p style="margin-inline-start: <?= e((string) $pad) ?>rem"><a href="<?= e(url('/admin/menus?parent=' . (string) $item['id'])) ?>">افزودن زیرمنو</a></p>
  <?php endif; ?>
  <form method="post" action="<?= e(url('/admin/menus/delete')) ?>" style="margin-inline-start: <?= e((string) $pad) ?>rem">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
    <button type="submit">حذف با زیرمجموعه</button>
  </form>
<?php endforeach; ?>
