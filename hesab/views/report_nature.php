<div class="panel">
  <div class="hd"><strong>اسناد خلاف ماهیت حساب</strong></div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr><th>سند</th><th>تاریخ</th><th>کد</th><th>حساب</th><th>ماهیت</th><th>بدهکار</th><th>بستانکار</th></tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="7">خلاف ماهیتی یافت نشد</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['number'] ?></td>
          <td class="num"><?= e($r['voucher_date']) ?></td>
          <td class="num"><?= e($r['code']) ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['nature']) ?></td>
          <td class="num"><?= money($r['debit']) ?></td>
          <td class="num"><?= money($r['credit']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
