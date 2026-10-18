<?php /** @var array $rows */ ?>
<div class="table-wrap">
  <table>
    <thead><tr><th>افزونه</th><th>شرح</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e((string) $row['name']) ?><div class="muted"><?= e((string) $row['id']) ?></div></td>
        <td><?= e((string) $row['description']) ?></td>
        <td><?= (int) $row['enabled'] === 1 ? 'فعال' : 'خاموش' ?></td>
        <td>
          <form method="post" action="<?= e(url('/admin/plugins/toggle')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
            <button type="submit"><?= (int) $row['enabled'] === 1 ? 'خاموش کردن' : 'روشن کردن' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="muted">خاموش کردن افزونه داده‌اش را پاک نمی‌کند. مسیرهای همان افزونه تا روشن شدن دوباره در دسترس نیستند.</p>
