<div class="panel">
  <div class="hd">
    <strong>دفتر روزنامه</strong>
    <a class="btn ghost" href="?excel=1">Excel</a>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr><th>سند</th><th>تاریخ</th><th>کد</th><th>حساب</th><th>شرح</th><th>بدهکار</th><th>بستانکار</th></tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="7">موردی نیست</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['number'] ?></td>
          <td class="num"><?= e($r['voucher_date']) ?></td>
          <td class="num"><?= e($r['code']) ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['description'] ?: '—') ?></td>
          <td class="num"><?= money($r['debit']) ?></td>
          <td class="num"><?= money($r['credit']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
