<?php
$natureLabel = ['debit' => 'بدهکار', 'credit' => 'بستانکار', 'neutral' => 'خنثی'];
?>
<div class="panel" style="margin-bottom:14px">
  <div class="hd"><strong>افزودن معین جدید</strong></div>
  <form class="form row" method="post" action="<?= e(url('/accounts/add-moein')) ?>" style="padding:12px">
    <?= csrf_field() ?>
    <label>کد کل
      <input name="kol_code" placeholder="مثلاً 1101" required>
    </label>
    <label>کد معین
      <input name="code" required>
    </label>
    <label>عنوان
      <input name="title" required>
    </label>
    <label>ماهیت
      <select name="nature">
        <option value="neutral">خنثی</option>
        <option value="debit">بدهکار</option>
        <option value="credit">بستانکار</option>
      </select>
    </label>
    <div style="align-self:end"><button class="btn" type="submit">افزودن</button></div>
  </form>
</div>

<div class="panel">
  <div class="hd">
    <strong>کدینگ + کنترل ماهیت + ارتباط تفصیلی شناور</strong>
    <span class="badge"><?= count($rows) ?> معین</span>
  </div>
  <div class="bd" style="max-height:70vh;overflow:auto">
    <table class="data">
      <thead>
        <tr>
          <th>گروه</th>
          <th>کل</th>
          <th>معین</th>
          <th>ماهیت / کنترل</th>
          <th>تفصیلی ۱–۳</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r):
        $maps = $byMoein[$r['id']] ?? [];
        $byLvl = [];
        $rq = [];
        foreach ($maps as $mm) {
            $byLvl[(int)$mm['level']] = (int)$mm['tafsili_type_id'];
            $rq[(int)$mm['level']] = (int)$mm['is_required'];
        }
        $m1 = $byLvl[1] ?? 0;
        $m2 = $byLvl[2] ?? 0;
        $m3 = $byLvl[3] ?? 0;
      ?>
        <tr>
          <td><?= e($r['gc'] . ' — ' . $r['gt']) ?></td>
          <td><?= e($r['kc'] . ' — ' . $r['kt']) ?></td>
          <td><strong class="num"><?= e($r['code']) ?></strong><br><?= e($r['title']) ?></td>
          <td colspan="3" style="padding:0">
            <form method="post" action="<?= e(url('/accounts/moein-save')) ?>" class="inline-form">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding:8px">
                <select name="nature">
                  <?php foreach ($natureLabel as $k => $lab): ?>
                    <option value="<?= e($k) ?>" <?= ($r['nature'] ?? '') === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
                  <?php endforeach; ?>
                </select>
                <label><input type="checkbox" name="allow_debit" <?= (int)($r['allow_debit'] ?? 1) ? 'checked' : '' ?>> بدهکار</label>
                <label><input type="checkbox" name="allow_credit" <?= (int)($r['allow_credit'] ?? 1) ? 'checked' : '' ?>> بستانکار</label>
                <?php for ($lvl = 1; $lvl <= 3; $lvl++):
                  $cur = ${'m' . $lvl};
                ?>
                  <select name="tafsili_type_<?= $lvl ?>" title="تفصیلی سطح <?= $lvl ?>">
                    <option value="0">تفصیلی <?= $lvl ?> —</option>
                    <?php foreach ($types as $t): ?>
                      <option value="<?= (int)$t['id'] ?>" <?= (int)$cur === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['title']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <label><input type="checkbox" name="tafsili_req_<?= $lvl ?>" <?= !empty($rq[$lvl]) ? 'checked' : '' ?>> الزام<?= $lvl ?></label>
                <?php endfor; ?>
                <button class="btn ghost" type="submit">ذخیره</button>
              </div>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
