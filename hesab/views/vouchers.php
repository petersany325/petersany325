<div class="panel">
  <div class="hd">
    <strong>فهرست اسناد</strong>
    <a class="btn" href="<?= e(url('/vouchers/create')) ?>">سند جدید</a>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>شماره</th>
          <th>تاریخ</th>
          <th>شرح</th>
          <th>وضعیت</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="5">موردی نیست</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['number'] ?></td>
          <td class="num"><?= e($r['voucher_date']) ?></td>
          <td><?= e($r['description'] ?: '—') ?></td>
          <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
          <td><a class="btn ghost" href="<?= e(url('/vouchers/view')) ?>?id=<?= (int)$r['id'] ?>">مشاهده</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
