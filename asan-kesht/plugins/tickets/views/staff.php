<div class="table-wrap">
  <table>
    <thead><tr><th>شماره</th><th>مشتری</th><th>موضوع</th><th>وضعیت</th><th>به‌روز</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><a href="<?= e(url('/admin/tickets/' . $row['id'])) ?>">#<?= e(fa_digits((string) $row['id'])) ?></a></td>
        <td><?= e((string) $row['name']) ?></td>
        <td><?= e((string) $row['subject']) ?></td>
        <td><?= e((string) $row['status']) ?></td>
        <td><?= e(jalali_date((string) $row['updated_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
