<div class="panel" style="margin-bottom:12px">
  <form class="form row" method="get" action="<?= e(url('/ledger')) ?>" style="padding:12px">
    <label style="grid-column:span 3">حساب معین
      <select name="moein_id">
        <option value="0">— انتخاب —</option>
        <?php foreach ($moeins as $m): ?>
          <option value="<?= (int)$m['id'] ?>" <?= (int)$moeinId === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['code'] . ' — ' . $m['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div style="align-self:end;display:flex;gap:8px">
      <button class="btn" type="submit">نمایش</button>
      <?php if ($moeinId): ?>
        <a class="btn ghost" href="<?= e(url('/ledger')) ?>?moein_id=<?= (int)$moeinId ?>&excel=1">Excel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php if ($account): ?>
<div class="panel">
  <div class="hd"><strong>دفتر معین: <?= e($account['code'] . ' — ' . $account['title']) ?></strong></div>
  <div class="bd">
    <table class="data">
      <thead>
        <tr>
          <th>سند</th><th>تاریخ</th><th>وضعیت</th><th>شرح</th>
          <th>تفصیلی۱</th><th>تفصیلی۲</th><th>تفصیلی۳</th>
          <th>بدهکار</th><th>بستانکار</th><th>مانده</th>
        </tr>
      </thead>
      <tbody>
      <?php $bal = 0; if (!$rows): ?><tr><td colspan="10">گردشی نیست</td></tr><?php endif; ?>
      <?php foreach ($rows as $r):
        $bal += (float)$r['debit'] - (float)$r['credit'];
      ?>
        <tr>
          <td><?= (int)$r['number'] ?></td>
          <td class="num"><?= e($r['voucher_date']) ?></td>
          <td><span class="badge <?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
          <td><?= e($r['description'] ?: '—') ?></td>
          <td><?= e($r['t1'] ?? '—') ?></td>
          <td><?= e($r['t2'] ?? '—') ?></td>
          <td><?= e($r['t3'] ?? '—') ?></td>
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
