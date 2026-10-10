<div class="panel">
  <div class="hd"><strong>سهم‌بری حساب‌ها / پروژه (بر اساس تفصیلی ۱)</strong></div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr><th>کد</th><th>حساب</th><th>تفصیلی / پروژه</th><th>بدهکار</th><th>بستانکار</th><th>مانده</th></tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="6">موردی نیست</td></tr><?php endif; ?>
      <?php foreach ($rows as $r):
        $bal = (float)$r['debit'] - (float)$r['credit'];
      ?>
        <tr>
          <td class="num"><?= e($r['code']) ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['tafsili']) ?></td>
          <td class="num"><?= money($r['debit']) ?></td>
          <td class="num"><?= money($r['credit']) ?></td>
          <td class="num"><?= money($bal) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
