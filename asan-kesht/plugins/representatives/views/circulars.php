<?php /** @var array $rows */ /** @var array $dealers */ /** @var array $done */ ?>
<form method="post" action="<?= e(url('/admin/dealers/circulars')) ?>" class="stack">
  <?= csrf_field() ?>
  <div class="grid">
    <label>عنوان <input name="title" required></label>
    <label>نوع <select name="kind"><option value="circular">بخشنامه</option><option value="law">قانون با نسخه پذیرش</option></select></label>
    <label>اولویت <select name="priority"><option value="normal">عادی</option><option value="high">بالا</option><option value="urgent">فوری</option></select></label>
    <label>مخاطب <select name="audience"><option value="all">همه نمایندگان فعال</option><option value="province">یک استان</option><option value="selected">افراد مشخص</option></select></label>
    <label>استان <input name="province"></label>
    <label>مهلت <input type="date" name="due_date"></label>
  </div>
  <label>متن <textarea name="body" required></textarea></label>
  <fieldset>
    <legend>اگر مخاطب افراد مشخص است</legend>
    <?php foreach ($dealers as $dealer): ?>
      <label class="check"><input type="checkbox" name="dealer_ids[]" value="<?= e((string) $dealer['id']) ?>"> <?= e((string) $dealer['name']) ?> — <?= e((string) $dealer['city']) ?></label>
    <?php endforeach; ?>
  </fieldset>
  <button type="submit">ارسال</button>
</form>
<div class="table-wrap"><table>
  <thead><tr><th>عنوان</th><th>نوع</th><th>اولویت</th><th>پذیرش یا اقدام</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <tr>
      <td><?= e((string) $row['title']) ?></td>
      <td><?= e($row['kind'] === 'law' ? 'قانون' : 'بخشنامه') ?></td>
      <td><?= e((string) $row['priority']) ?></td>
      <td><?= e(fa_digits((string) $row['accepted'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<h2>گزارش انجام، در انتظار تأیید شرکت</h2>
<?php foreach ($done as $item): ?>
  <form method="post" action="<?= e(url('/admin/dealers/circulars/' . $item['circular_id'])) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="dealer_id" value="<?= e((string) $item['dealer_id']) ?>">
    <p><?= e((string) $item['title']) ?> — <?= e((string) $item['name']) ?> — <?= e((string) $item['report']) ?></p>
    <button type="submit">تأیید شرکت</button>
  </form>
<?php endforeach; ?>
