<div class="grid" style="grid-template-columns:360px 1fr;gap:12px">
  <form class="panel form" method="post" action="<?= e(url('/invoices')) ?>">
    <?= csrf_field() ?>
    <div class="hd" style="margin:-14px -14px 0;border:0;border-bottom:1px solid var(--line)"><strong>فاکتور فروش سریع</strong></div>
    <label>مشتری
      <select name="party_id" required>
        <option value="">— انتخاب —</option>
        <?php foreach ($parties as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>تاریخ<input type="date" name="invoice_date" value="<?= e(date('Y-m-d')) ?>" required></label>
    <label>شرح کالا/خدمت<input name="item_title" value="فروش کالا/خدمت" required></label>
    <label>تعداد<input name="qty" value="1" required></label>
    <label>فی<input name="unit_price" required placeholder="مثلا 1000000"></label>
    <button class="btn" type="submit">صدور فاکتور + سند</button>
  </form>

  <div class="panel">
    <div class="hd"><strong>فاکتورها</strong></div>
    <div class="bd">
      <table class="data">
        <thead>
          <tr><th>شماره</th><th>تاریخ</th><th>طرف‌حساب</th><th>مبلغ</th><th>سند</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6">موردی نیست</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['number'] ?></td>
            <td class="num"><?= e($r['invoice_date']) ?></td>
            <td><?= e($r['party_name']) ?></td>
            <td class="num"><?= money($r['total']) ?></td>
            <td>
              <?php if ($r['voucher_id']): ?>
                <a href="<?= e(url('/vouchers/view')) ?>?id=<?= (int)$r['voucher_id'] ?>">#<?= (int)$r['voucher_id'] ?></a>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <?php if (Permission::can(current_user(), 'invoices.print')): ?>
                <a href="<?= e(url('/invoices/print?id=' . (int)$r['id'])) ?>" target="_blank">چاپ</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
