<div class="panel">
  <div class="hd"><strong>انتخاب حساب</strong></div>
  <form class="form row" method="get" action="/ledger">
    <label style="grid-column: span 3">حساب معین
      <select name="moein_id" onchange="this.form.submit()">
        <option value="">— انتخاب حساب —</option>
        <?php foreach ($moeins as $m): ?>
          <option value="<?= (int)$m['id'] ?>" <?= $moeinId === (int)$m['id'] ? 'selected' : '' ?>>
            <?= e($m['code'] . ' — ' . $m['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>
</div>

<?php if ($account): ?>
<?php
$bal = 0;
?>
<div class="panel" style="margin-top:12px">
  <div class="hd">
    <strong>دفتر <?= e($account['code'] . ' — ' . $account['title']) ?></strong>
  </div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>سند</th>
          <th>تاریخ</th>
          <th>شرح</th>
          <th>بدهکار</th>
          <th>بستانکار</th>
          <th>مانده</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="6">گردشی ثبت نشده</td></tr><?php endif; ?>
      <?php foreach ($rows as $r):
        $bal += (float)$r['debit'] - (float)$r['credit'];
      ?>
        <tr>
          <td><?= (int)$r['number'] ?></td>
          <td class="num"><?= e($r['voucher_date']) ?></td>
          <td><?= e($r['description'] ?: '—') ?></td>
          <td class="num"><?= money($r['debit']) ?></td>
          <td class="num"><?= money($r['credit']) ?></td>
          <td class="num"><?= money($bal) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
