<?php
/** @var array<string, int> $figures */
/** @var list<array<string, mixed>> $open */
/** @var list<array<string, mixed>> $checks */
require __DIR__ . '/nav.php';
$lines = [
    ['فروش خالص', 'فاکتور فروش صادرشده، به‌جز باطل. پیش‌فاکتور داخل این عدد نیست.', $figures['sales'], ''],
    ['بهای تمام‌شده کالای فروش‌رفته', 'تعداد ضربدر بهای تمام‌شده واحد روی همان فاکتور فروش.', $figures['cogs'], ''],
    ['سود ناخالص', 'فروش خالص منهای بهای تمام‌شده.', $figures['gross'], accounting_rate($figures['gross'], $figures['sales'])],
    ['پورسانت قطعی نماینده', 'فقط مدل فروش برای شرکت و پورسانت. وضعیت قطعی هزینه است. در جریان و برگشت‌خورده هزینه نیست. درصد، پورسانت قطعی تقسیم بر فروش تأییدشده همان مدل است.', $figures['dealer_commission'], accounting_rate($figures['dealer_commission'], $figures['dealer_commission_sales'])],
    ['سهم ویزیتور', 'سهم ثبت‌شده روی هر فاکتور یا سفارش. درصد این ردیف، جمع سهم نسبت به فروش خالص شرکت است، نه درصد تعریف هر ویزیتور.', $figures['visitor_commission'], accounting_rate($figures['visitor_commission'], $figures['sales'])],
    ['حقوق و مزایا', 'حقوق پایه به‌علاوه مزایا به‌علاوه بیمه سهم کارفرما. کسور قانونی از این هزینه کم نشده.', $figures['payroll'], ''],
    ['سایر هزینه‌ها', 'هزینه‌های عملیاتی ثبت‌شده، غیر از حقوق و سهم فروش.', $figures['expenses'], ''],
    ['سود (زیان) عملیاتی', 'سود ناخالص منهای پورسانت، سهم ویزیتور، حقوق و سایر هزینه‌ها.', $figures['operating'], accounting_rate($figures['operating'], $figures['sales'])],
];
?>
<h2>صورت سود و زیان</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>عنوان</th><th>تعریف</th><th>مبلغ</th><th>درصد</th></tr></thead>
    <tbody>
    <?php foreach ($lines as $line): ?>
      <tr>
        <td><?= e($line[0]) ?></td>
        <td class="muted"><?= e($line[1]) ?></td>
        <td><?= e(money($line[2])) ?></td>
        <td><?= e($line[3]) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php if ($figures['missing_cost'] > 0): ?>
  <p class="flash flash-error"><?= e(fa_digits((string) $figures['missing_cost'])) ?> ردیف فروش بهای تمام‌شده ندارد. تا وقتی صفر است، سود ناخالص بیشتر از واقع دیده می‌شود.</p>
<?php endif; ?>
<?php if ($figures['dealer_gross_gap'] > 0): ?>
  <p class="flash flash-error"><?= e(fa_digits((string) $figures['dealer_gross_gap'])) ?> ردیف پورسانت با مبنای سود ناخالص، بهای تمام‌شده نداشته و پایه آن برابر مبلغ فروش شده است.</p>
<?php endif; ?>
<h2>اقلام خارج از سود و زیان</h2>
<div class="cards">
  <div class="card"><span>پیش‌فاکتور باز</span><strong><?= e(money($figures['proforma'])) ?></strong></div>
  <div class="card"><span>خرید دوره</span><strong><?= e(money($figures['purchases'])) ?></strong></div>
  <div class="card"><span>خالص پرداختی حقوق</span><strong><?= e(money($figures['payroll_net'])) ?></strong></div>
  <div class="card"><span>پورسانت در جریان</span><strong><?= e(money($figures['dealer_pending'])) ?></strong></div>
  <div class="card"><span>فروش کانال نمایندگی</span><strong><?= e(money($figures['dealer_sales'])) ?></strong></div>
  <div class="card"><span>فروش مدل پورسانتی</span><strong><?= e(money($figures['dealer_commission_sales'])) ?></strong></div>
  <div class="card"><span>فروش فروشگاه داخل فاکتور</span><strong><?= e(money($figures['shop_sales'])) ?></strong></div>
  <div class="card"><span>تعداد فاکتور فروشگاه</span><strong><?= e(fa_digits((string) $figures['shop_count'])) ?></strong></div>
  <div class="card"><span>سفارش آنلاین پرداخت‌شده</span><strong><?= e(money($figures['online_paid'])) ?></strong></div>
</div>
<p class="muted">خرید، ورود موجودی است و دوباره از سود کم نمی‌شود. فروش نمایندگی فقط وقتی در فروش شرکت می‌آید که فاکتور فروش صادر شده باشد. درصد روبه‌روی پورسانت، پورسانت قطعی تقسیم بر فروش تأییدشده نمایندگانی است که مدلشان فروش برای شرکت و پورسانت است. فروش کانال نمایندگی همه مدل‌ها را با هم نشان می‌دهد و در این درصد نیست. مالیات و ارسال فروشگاه با بهای برابر مبلغ ثبت می‌شوند و سودی نمی‌سازند. دریافت وجه، فروش را عوض نمی‌کند؛ فروش با صدور فاکتور شناسایی می‌شود و پرداخت آنلاین با تأیید درگاه.</p>
<p><button type="button" onclick="window.print()">چاپ گزارش</button></p>
<h2>مانده فاکتورهای فروش</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>شماره</th><th>طرف</th><th>جمع</th><th>دریافت</th><th>مانده</th></tr></thead>
    <tbody>
    <?php foreach ($open as $row): ?>
      <tr>
        <td><?= e((string) $row['number']) ?></td>
        <td><?= e((string) $row['name']) ?></td>
        <td><?= e(money((int) $row['total'])) ?></td>
        <td><?= e(money((int) $row['paid'])) ?></td>
        <td><?= e(money((int) $row['total'] - (int) $row['paid'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<h2>چک‌های در جریان</h2>
<ul>
  <?php foreach ($checks as $check): ?>
    <li><?= e((string) $check['due_on']) ?> — <?= e((string) $check['number']) ?> — <?= e((string) $check['party_name']) ?> — <?= e(money((int) $check['amount'])) ?></li>
  <?php endforeach; ?>
</ul>
