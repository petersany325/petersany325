<?php
/** @var array<string, int> $figures */
/** @var list<array<string, mixed>> $visitors */
/** @var list<array<string, mixed>> $dealers */
/** @var list<array<string, mixed>> $events */
require __DIR__ . '/nav.php';
$checks = [
    ['فروش بدون بهای تمام‌شده', (string) $figures['missing_cost'] . ' ردیف', $figures['missing_cost'] === 0],
    ['پیش‌فاکتور داخل فروش نیست', money($figures['proforma']) . ' خارج از سود', true],
    ['پورسانت در جریان هزینه نشده', money($figures['dealer_pending']), true],
    ['پورسانت قطعی نماینده', money($figures['dealer_commission']), true],
    ['سهم ویزیتور', money($figures['visitor_commission']), true],
    ['حقوق با سهم کارفرما', money($figures['payroll']) . '؛ خالص ' . money($figures['payroll_net']), true],
    ['فروشگاه داخل فاکتور فروش', money($figures['shop_sales']) . ' در ' . fa_digits((string) $figures['shop_count']) . ' فاکتور', true],
    ['آنلاین پرداخت‌شده', money($figures['online_paid']), true],
];
?>
<p class="muted">حسابرسی همین تعریف‌ها را با سندها مقابله می‌کند. خرید دوباره از سود کم نشده، پیش‌فاکتور فروش نیست، و سهم ویزیتور و پورسانت وصول‌شده هزینه فروش‌اند.</p>
<div class="table-wrap">
  <table>
    <thead><tr><th>کنترل</th><th>نتیجه</th><th>وضعیت</th></tr></thead>
    <tbody>
      <?php foreach ($checks as $check): ?>
        <tr>
          <td><?= e($check[0]) ?></td>
          <td><?= e($check[1]) ?></td>
          <td><?= $check[2] ? 'منطبق' : 'نیاز به اصلاح' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<h2>ویزیتورها</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>نام</th><th>کد</th><th>درصد</th><th>سهم ثبت‌شده</th></tr></thead>
    <tbody>
      <?php foreach ($visitors as $row): ?>
        <tr>
          <td><?= e((string) $row['name']) ?></td>
          <td><?= e((string) $row['code']) ?></td>
          <td><?= e(fa_digits((string) $row['percent'])) ?>٪ <?= (string) $row['basis'] === 'gross' ? 'از سود' : 'از فروش' ?></td>
          <td><?= e(money((int) $row['earned'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<h2>نمایندگان و درصد سود</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>نماینده</th><th>مدل</th><th>درصد تعریف‌شده</th><th>پورسانت قطعی</th><th>در جریان</th></tr></thead>
    <tbody>
      <?php foreach ($dealers as $row): ?>
        <tr>
          <td><?= e((string) $row['name']) ?><div class="muted"><?= e((string) $row['code']) ?></div></td>
          <td><?= e((string) $row['sale_model']) ?></td>
          <td><?= $row['percent'] === null ? 'پیش‌فرض تنظیمات' : e(fa_digits((string) $row['percent'])) . '٪ ' . ((string) $row['basis'] === 'gross' ? 'از سود' : 'از فروش') ?></td>
          <td><?= e(money((int) $row['earned'])) ?></td>
          <td><?= e(money((int) $row['pending'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<h2>رویدادها</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>زمان</th><th>کاربر</th><th>کار</th><th>موضوع</th><th>شرح</th></tr></thead>
    <tbody>
      <?php foreach ($events as $event): ?>
        <tr>
          <td><?= e(jalali_date((string) $event['created_at'])) ?></td>
          <td><?= e((string) $event['user_name']) ?></td>
          <td><?= e((string) $event['action']) ?></td>
          <td><?= e((string) $event['subject']) ?></td>
          <td><?= e((string) $event['detail']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
