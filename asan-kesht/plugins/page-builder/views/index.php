<?php /** @var array $pages */ ?>
<p><a class="btn" href="<?= e(url('/admin/pages/new')) ?>">صفحه تازه</a></p>
<div class="table-wrap">
  <table>
    <thead><tr><th>عنوان</th><th>نامک</th><th>وضعیت</th><th>منو</th></tr></thead>
    <tbody>
    <?php foreach ($pages as $page): ?>
      <tr>
        <td><a href="<?= e(url('/admin/pages/' . $page['id'])) ?>"><?= e((string) $page['title']) ?></a></td>
        <td><?= e((string) $page['slug']) ?></td>
        <td><?= $page['status'] === 'published' ? 'منتشر' : 'پیش‌نویس' ?></td>
        <td><?= (int) $page['in_menu'] === 1 ? 'بله' : 'خیر' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
