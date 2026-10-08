<div class="panel">
  <div class="hd">
    <strong>کدینگ ۴ سطحی (گروه / کل / معین)</strong>
    <span class="badge"><?= count($rows) ?> معین</span>
  </div>
  <div class="bd" style="max-height:70vh;overflow:auto">
    <table class="data">
      <thead>
        <tr>
          <th>گروه</th>
          <th>کل</th>
          <th>کد معین</th>
          <th>عنوان معین</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['gc'] . ' — ' . $r['gt']) ?></td>
          <td><?= e($r['kc'] . ' — ' . $r['kt']) ?></td>
          <td class="num"><?= e($r['mc']) ?></td>
          <td><?= e($r['mt']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
