<?php
/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $tree */

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
<p class="muted">این فهرست، منوی بالای سایت است. سه سطح دارید: آیتم اصلی، ستون یا گروه، و پیوند داخل آن. اگر «مگا» روشن باشد پنل بزرگ باز می‌شود؛ اگر خاموش باشد و زیرمجموعه داشته باشد، فهرست کوچک نشان داده می‌شود. ورود، حساب و خروج جدا می‌مانند تا مسیر ورود بسته نشود.</p>
<h2>آیتم تازه</h2>
<form method="post" action="<?= e(url('/admin/menus')) ?>" class="stack">
  <?= csrf_field() ?>
  <?php $item = []; require __DIR__ . '/fields.php'; ?>
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
  <form method="post" action="<?= e(url('/admin/menus/delete')) ?>" style="margin-inline-start: <?= e((string) $pad) ?>rem">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
    <button type="submit">حذف با زیرمجموعه</button>
  </form>
<?php endforeach; ?>
