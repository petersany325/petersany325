<?php
/** @var array<string, string> $sheet */
/** @var array<string, mixed> $invoice */
/** @var list<array<string, mixed>> $items */
/** @var list<array<string, mixed>> $payments */
/** @var bool $internal */
/** @var string $back */
$kind = (string) ($invoice['kind'] ?? 'sale');
$meta = accounting_kind($kind);
$label = $meta['label'] ?? 'فاکتور';
$subtotal = (int) $invoice['total'];
$taxPercent = $sheet['tax_on'] === '1' ? (int) $sheet['tax_percent'] : 0;
$tax = $taxPercent > 0 ? (int) round($subtotal * $taxPercent / 100) : 0;
$payable = $subtotal + $tax;
$paid = 0;
foreach ($payments as $payment) {
    $paid += (int) $payment['amount'];
}
?>
<div class="print-tools">
  <button type="button" onclick="window.print()">چاپ فاکتور</button>
  <a href="<?= e(url($back)) ?>">بازگشت</a>
</div>
<article class="invoice">
  <header class="invoice-head">
    <div>
      <strong><?= e($sheet['title']) ?></strong>
      <?php if ($sheet['subtitle'] !== ''): ?><p><?= e($sheet['subtitle']) ?></p><?php endif; ?>
      <?php if ($sheet['address'] !== ''): ?><p><?= e($sheet['address']) ?></p><?php endif; ?>
      <?php if ($sheet['phone'] !== ''): ?><p>تلفن: <?= e(fa_digits($sheet['phone'])) ?></p><?php endif; ?>
      <?php if ($sheet['email'] !== ''): ?><p><?= e($sheet['email']) ?></p><?php endif; ?>
    </div>
    <div class="invoice-id">
      <b><?= e($label) ?></b>
      <span><?= e((string) $invoice['number']) ?></span>
      <span><?= e(jalali_date((string) $invoice['created_at'])) ?></span>
      <?php if ($sheet['show_status'] === '1'): ?><span><?= e(accounting_status_label((string) $invoice['status'])) ?></span><?php endif; ?>
      <?php if ($kind === 'proforma' && (int) $sheet['proforma_days'] > 0): ?>
        <span>اعتبار: <?= e(fa_digits($sheet['proforma_days'])) ?> روز</span>
      <?php endif; ?>
    </div>
  </header>
  <?php if ($sheet['header'] !== ''): ?><p class="invoice-note"><?= e($sheet['header']) ?></p><?php endif; ?>
  <dl class="invoice-meta">
    <?php if ($sheet['national'] !== ''): ?><div><dt>شناسه ملی</dt><dd><?= e(fa_digits($sheet['national'])) ?></dd></div><?php endif; ?>
    <?php if ($sheet['economic'] !== ''): ?><div><dt>کد اقتصادی</dt><dd><?= e(fa_digits($sheet['economic'])) ?></dd></div><?php endif; ?>
    <?php if ($sheet['postal'] !== ''): ?><div><dt>کد پستی</dt><dd><?= e(fa_digits($sheet['postal'])) ?></dd></div><?php endif; ?>
    <div><dt>خریدار</dt><dd><?= e((string) $invoice['party_name']) ?></dd></div>
    <?php if ((string) ($invoice['party_phone'] ?? '') !== ''): ?><div><dt>تلفن خریدار</dt><dd><?= e(fa_digits((string) $invoice['party_phone'])) ?></dd></div><?php endif; ?>
  </dl>
  <table>
    <thead>
      <tr>
        <th>ردیف</th>
        <th>شرح</th>
        <th>تعداد</th>
        <th>فی</th>
        <?php if ($internal): ?><th>بهای تمام‌شده</th><?php endif; ?>
        <th>مبلغ</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $index => $item): ?>
        <tr>
          <td><?= e(fa_digits((string) ($index + 1))) ?></td>
          <td><?= e((string) $item['title']) ?></td>
          <td><?= e(fa_digits((string) $item['qty'])) ?></td>
          <td><?= e(money((int) $item['unit_price'])) ?></td>
          <?php if ($internal): ?><td><?= e(money((int) $item['unit_cost'])) ?></td><?php endif; ?>
          <td><?= e(money((int) $item['qty'] * (int) $item['unit_price'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="invoice-totals">
    <div><span>جمع اقلام</span><b><?= e(money($subtotal)) ?></b></div>
    <?php if ($tax > 0): ?>
      <div><span>مالیات <?= e(fa_digits($sheet['tax_percent'])) ?>٪</span><b><?= e(money($tax)) ?></b></div>
      <div><span>قابل پرداخت</span><b><?= e(money($payable)) ?></b></div>
    <?php endif; ?>
    <?php if ($internal): ?>
      <?php
        $cost = 0;
        foreach ($items as $item) {
            $cost += (int) $item['qty'] * (int) $item['unit_cost'];
        }
      ?>
      <div><span>بهای تمام‌شده</span><b><?= e(money($cost)) ?></b></div>
      <div><span>سود این سند</span><b><?= e(money($subtotal - $cost)) ?></b></div>
    <?php endif; ?>
  </div>
  <?php if ((string) ($invoice['notes'] ?? '') !== ''): ?><p class="invoice-note"><?= e((string) $invoice['notes']) ?></p><?php endif; ?>
  <?php if ($sheet['show_paid'] === '1' && $payments !== []): ?>
    <h2>دریافت‌ها</h2>
    <ul>
      <?php foreach ($payments as $payment): ?>
        <li><?= e(jalali_date((string) $payment['paid_at'])) ?> — <?= e(money((int) $payment['amount'])) ?></li>
      <?php endforeach; ?>
    </ul>
    <p>مانده: <?= e(money(max(0, $payable - $paid))) ?></p>
  <?php endif; ?>
  <?php if ($sheet['footer'] !== ''): ?><p class="invoice-note"><?= e($sheet['footer']) ?></p><?php endif; ?>
  <div class="invoice-signs">
    <div><?= e($sheet['sign_seller']) ?></div>
    <div><?= e($sheet['sign_buyer']) ?></div>
  </div>
</article>
