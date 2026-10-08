<form class="panel" method="post" action="/vouchers/create" id="voucher-form">
  <?= csrf_field() ?>
  <div class="hd">
    <strong>ثبت سند حسابداری</strong>
    <button class="btn" type="submit">ذخیره پیش‌نویس</button>
  </div>
  <div class="form row">
    <label>تاریخ
      <input type="date" name="voucher_date" value="<?= e(date('Y-m-d')) ?>" required>
    </label>
    <label style="grid-column: span 3">شرح سند
      <input name="description" placeholder="شرح کلی سند">
    </label>
  </div>
  <div class="bd">
    <table class="data" id="lines">
      <thead>
        <tr>
          <th style="width:34%">حساب معین</th>
          <th>شرح ردیف</th>
          <th style="width:16%">بدهکار</th>
          <th style="width:16%">بستانکار</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php for ($i = 0; $i < 4; $i++): ?>
        <tr>
          <td>
            <select name="moein_id[]">
              <option value="">— انتخاب —</option>
              <?php foreach ($moeins as $m): ?>
                <option value="<?= (int)$m['id'] ?>"><?= e($m['code'] . ' — ' . $m['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input name="line_desc[]"></td>
          <td><input class="num debit" name="debit[]" inputmode="numeric" value="0"></td>
          <td><input class="num credit" name="credit[]" inputmode="numeric" value="0"></td>
          <td><button type="button" class="btn ghost rm">حذف</button></td>
        </tr>
        <?php endfor; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2">
            <button type="button" class="btn ghost" id="add-line">ردیف جدید</button>
          </td>
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
      <select name="moein_id[]">
        <option value="">— انتخاب —</option>
        <?php foreach ($moeins as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= e($m['code'] . ' — ' . $m['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td><input name="line_desc[]"></td>
    <td><input class="num debit" name="debit[]" inputmode="numeric" value="0"></td>
    <td><input class="num credit" name="credit[]" inputmode="numeric" value="0"></td>
    <td><button type="button" class="btn ghost rm">حذف</button></td>
  </tr>
</template>
