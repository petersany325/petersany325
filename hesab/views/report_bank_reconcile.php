<div class="grid" style="grid-template-columns:1fr 1fr;gap:14px">
  <div class="panel">
    <div class="hd"><strong>ثبت مغایرت بانکی</strong></div>
    <form method="post" action="<?= e(url('/reports/bank-reconcile')) ?>" class="form" style="padding:12px">
      <?= csrf_field() ?>
      <label>حساب بانکی
        <select name="bank_account_id" required>
          <?php foreach ($banks as $b): ?>
            <option value="<?= (int)$b['id'] ?>"><?= e($b['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>تاریخ صورتحساب<input type="date" name="statement_date" value="<?= e(date('Y-m-d')) ?>" required></label>
      <label>مانده صورتحساب بانک<input name="statement_balance" required></label>
      <label>مانده دفاتر<input name="book_balance" required></label>
      <label>یادداشت<input name="note"></label>
      <button class="btn" type="submit">ذخیره</button>
    </form>
  </div>
  <div class="panel">
    <div class="hd"><strong>سوابق مغایرت</strong></div>
    <div class="bd">
      <table class="data">
        <thead><tr><th>بانک</th><th>تاریخ</th><th>بانک</th><th>دفتر</th><th>اختلاف</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
          $diff = (float)$r['statement_balance'] - (float)$r['book_balance'];
        ?>
          <tr>
            <td><?= e($r['bank_title']) ?></td>
            <td class="num"><?= e($r['statement_date']) ?></td>
            <td class="num"><?= money($r['statement_balance']) ?></td>
            <td class="num"><?= money($r['book_balance']) ?></td>
            <td class="num"><?= money($diff) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
