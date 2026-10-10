<?php
$projects = $projects ?? [];
$costCenters = $costCenters ?? [];
$branches = $branches ?? [];
?>
<form class="panel" method="post" action="<?= e(url('/vouchers/create')) ?>" id="voucher-form">
  <?= csrf_field() ?>
  <div class="hd">
    <strong>ثبت سند حسابداری</strong>
    <div style="display:flex;gap:8px">
      <button class="btn ghost" type="submit" name="save_as" value="draft">پیش‌نویس</button>
      <button class="btn" type="submit" name="save_as" value="operational">عملیاتی</button>
    </div>
  </div>
  <div class="form row">
    <label>تاریخ
      <input type="date" name="voucher_date" value="<?= e(date('Y-m-d')) ?>" required>
    </label>
    <label>نوع سند
      <select name="voucher_type_id">
        <option value="">—</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= (int)$t['id'] ?>"><?= e($t['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label style="grid-column: span 2">شرح سند
      <input name="description" placeholder="توضیحات دلخواه سند">
    </label>
  </div>
  <div class="bd" style="overflow:auto">
    <table class="data" id="lines">
      <thead>
        <tr>
          <th>حساب معین</th>
          <th>تفصیلی ۱</th>
          <th>پروژه</th>
          <th>مرکز هزینه</th>
          <th>شعبه</th>
          <th>شرح</th>
          <th>بدهکار</th>
          <th>بستانکار</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php for ($i = 0; $i < 4; $i++): ?>
        <tr>
          <td>
            <select name="moein_id[]" class="moein-sel">
              <option value="">— انتخاب —</option>
              <?php foreach ($moeins as $m): ?>
                <option value="<?= (int)$m['id'] ?>"><?= e($m['code'] . ' — ' . $m['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <select name="tafsili1_id[]">
              <option value="0">—</option>
              <?php foreach ($tafsili as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= e($t['type_title'] . ': ' . $t['code'] . ' ' . $t['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <select name="project_id[]">
              <option value="0">—</option>
              <?php foreach ($projects as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= e($p['code'] . ' — ' . $p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <select name="cost_center_id[]">
              <option value="0">—</option>
              <?php foreach ($costCenters as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= e($p['code'] . ' — ' . $p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <select name="branch_id[]">
              <option value="0">—</option>
              <?php foreach ($branches as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= e($p['code'] . ' — ' . $p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input name="line_desc[]"></td>
          <td><input class="num debit" name="debit[]" inputmode="numeric" value="0"></td>
          <td><input class="num credit" name="credit[]" inputmode="numeric" value="0"></td>
          <td>
            <input type="hidden" name="tafsili2_id[]" value="0">
            <input type="hidden" name="tafsili3_id[]" value="0">
            <button type="button" class="btn ghost rm">حذف</button>
          </td>
        </tr>
        <?php endfor; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="6"><button type="button" class="btn ghost" id="add-line">ردیف جدید</button></td>
          <td class="num" id="sum-d">0</td>
          <td class="num" id="sum-c">0</td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</form>

<template id="line-tpl">
  <tr>
    <td>
      <select name="moein_id[]" class="moein-sel">
        <option value="">— انتخاب —</option>
        <?php foreach ($moeins as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= e($m['code'] . ' — ' . $m['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td>
      <select name="tafsili1_id[]">
        <option value="0">—</option>
        <?php foreach ($tafsili as $t): ?>
          <option value="<?= (int)$t['id'] ?>"><?= e($t['type_title'] . ': ' . $t['code'] . ' ' . $t['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td>
      <select name="project_id[]">
        <option value="0">—</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['code'] . ' — ' . $p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td>
      <select name="cost_center_id[]">
        <option value="0">—</option>
        <?php foreach ($costCenters as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['code'] . ' — ' . $p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td>
      <select name="branch_id[]">
        <option value="0">—</option>
        <?php foreach ($branches as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['code'] . ' — ' . $p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td><input name="line_desc[]"></td>
    <td><input class="num debit" name="debit[]" inputmode="numeric" value="0"></td>
    <td><input class="num credit" name="credit[]" inputmode="numeric" value="0"></td>
    <td>
      <input type="hidden" name="tafsili2_id[]" value="0">
      <input type="hidden" name="tafsili3_id[]" value="0">
      <button type="button" class="btn ghost rm">حذف</button>
    </td>
  </tr>
</template>
