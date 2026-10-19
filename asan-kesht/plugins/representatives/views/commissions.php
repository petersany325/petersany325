<?php /** @var array $rows */ ?>
<div class="table-wrap"><table>
  <thead><tr><th>نماینده</th><th>سفارش</th><th>مبلغ</th><th>مبنا</th><th>قطعی شدن</th><th>وضعیت</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <tr>
      <td><?= e((string) $row['name']) ?></td>
      <td><?= e((string) $row['number']) ?></td>
      <td><?= e(money((int) $row['amount'])) ?></td>
      <td><?= e(dealer_basis_label((string) $row['basis'])) ?></td>
      <td><?= e(dealer_settle_label((string) $row['settle_on'])) ?></td>
      <td><?= e(dealer_commission_status_label((string) $row['status'])) ?><?php if (str_contains((string) ($row['note'] ?? ''), 'بهای تمام‌شده ثبت نشده')): ?><div class="muted">بهای تمام‌شده ثبت نشده؛ پایه برابر مبلغ فروش</div><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<p class="muted">پورسانت هر ردیف سفارش جدا ثبت می‌شود. اگر قطعی شدن «هنگام وصول وجه» باشد، تا وقتی پرداخت‌های تأییدشده به جمع سفارش برسد در جریان می‌ماند و هزینه سود و زیان نیست. لغو یا مرجوعی، پورسانت قطعی را با سند برمی‌گرداند. مدل خرید از شرکت و فروش دوباره پورسانت نمی‌سازد.</p>
