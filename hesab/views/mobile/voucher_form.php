<form class="m-card" method="post" action="<?= e(url('/m/vouchers/create')) ?>" id="m-voucher-form">
  <?= csrf_field() ?>
  <div class="m-card-hd"><strong>ثبت سند موبایل</strong></div>
  <div class="m-form">
    <label>تاریخ
      <input type="date" name="voucher_date" value="<?= e(date('Y-m-d')) ?>" required>
    </label>
    <label>نوع سند
      <select name="voucher_type_id">
        <option value="">عمومی</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= (int)$t['id'] ?>"><?= e($t['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>شرح سند
      <input name="description" placeholder="توضیح سند">
    </label>

    <div id="m-lines">
      <?php for ($i = 0; $i < 2; $i++): ?>
      <div class="m-line-card">
        <label>حساب معین
          <select name="moein_id[]" required>
            <option value="">— انتخاب —</option>
            <?php foreach ($moeins as $m): ?>
              <option value="<?= (int)$m['id'] ?>"><?= e($m['code'] . ' — ' . $m['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>تفصیلی
          <select name="tafsili1_id[]">
            <option value="0">—</option>
            <?php foreach ($tafsili as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= e($t['type_title'] . ': ' . $t['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="row2">
          <label>بدهکار<input class="m-debit" name="debit[]" inputmode="numeric" value="0"></label>
          <label>بستانکار<input class="m-credit" name="credit[]" inputmode="numeric" value="0"></label>
        </div>
        <input type="hidden" name="tafsili2_id[]" value="0">
        <input type="hidden" name="tafsili3_id[]" value="0">
        <input type="hidden" name="project_id[]" value="0">
        <input type="hidden" name="cost_center_id[]" value="0">
        <input type="hidden" name="branch_id[]" value="0">
        <input type="hidden" name="line_desc[]" value="">
      </div>
      <?php endfor; ?>
    </div>

    <button type="button" class="m-btn ghost block" id="m-add-line">+ ردیف جدید</button>
    <div style="display:flex;justify-content:space-between;color:var(--m-muted);font-size:12px">
      <span>جمع بدهکار: <b id="m-sum-d">0</b></span>
      <span>جمع بستانکار: <b id="m-sum-c">0</b></span>
    </div>
    <button class="m-btn block" type="submit" name="save_as" value="operational">ذخیره عملیاتی</button>
    <button class="m-btn ghost block" type="submit" name="save_as" value="draft">ذخیره پیش‌نویس</button>
  </div>
</form>

<template id="m-line-tpl">
  <div class="m-line-card">
    <label>حساب معین
      <select name="moein_id[]">
        <option value="">— انتخاب —</option>
        <?php foreach ($moeins as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= e($m['code'] . ' — ' . $m['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>تفصیلی
      <select name="tafsili1_id[]">
        <option value="0">—</option>
        <?php foreach ($tafsili as $t): ?>
          <option value="<?= (int)$t['id'] ?>"><?= e($t['type_title'] . ': ' . $t['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="row2">
      <label>بدهکار<input class="m-debit" name="debit[]" inputmode="numeric" value="0"></label>
      <label>بستانکار<input class="m-credit" name="credit[]" inputmode="numeric" value="0"></label>
    </div>
    <input type="hidden" name="tafsili2_id[]" value="0">
    <input type="hidden" name="tafsili3_id[]" value="0">
    <input type="hidden" name="project_id[]" value="0">
    <input type="hidden" name="cost_center_id[]" value="0">
    <input type="hidden" name="branch_id[]" value="0">
    <input type="hidden" name="line_desc[]" value="">
  </div>
</template>
