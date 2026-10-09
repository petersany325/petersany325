<?php
/** @var array $invoice */
/** @var array $s */
$paper = $s['print_paper'] ?? 'A4';
$margin = (int) ($s['print_margin_mm'] ?? 12);
$accent = $s['print_color_accent'] ?: '#005a9e';
$fs = (int) ($s['print_font_size'] ?? 12);
$taxP = (float) ($s['inv_tax_percent'] ?? 0);
$sub = (float) ($invoice['total'] ?? 0);
if (($s['inv_show_tax'] ?? '1') === '1' && $taxP > 0) {
    $net = round($sub / (1 + $taxP / 100));
    $tax = $sub - $net;
} else {
    $net = $sub;
    $tax = 0;
}
$header = $s['print_header_html'] ?: '';
$footer = strtr($s['print_footer_html'] ?? '', [
    '{page}' => '1',
    '{company}' => $s['inv_company_name'] ?? '',
    '{date}' => date('Y-m-d'),
]);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>چاپ فاکتور <?= e((string)($invoice['number'] ?? '')) ?></title>
  <style>
    @page { size: <?= $paper === 'A5' ? 'A5' : ($paper === 'thermal80' ? '80mm auto' : 'A4') ?> <?= e($s['print_orientation'] ?? 'portrait') ?>; margin: <?= $margin ?>mm; }
    body { font-family: Tahoma, sans-serif; font-size: <?= $fs ?>px; color: #111; margin: 0; }
    .sheet { position: relative; }
    .wm { position: fixed; inset: 30% 10%; text-align: center; opacity: .08; font-size: 48px; transform: rotate(-20deg); pointer-events: none; }
    .head { display: flex; justify-content: space-between; gap: 12px; border-bottom: 2px solid <?= e($accent) ?>; padding-bottom: 8px; margin-bottom: 12px; }
    .brand strong { color: <?= e($accent) ?>; font-size: 18px; }
    .meta { font-size: 12px; color: #444; line-height: 1.7; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #bbb; padding: 6px 8px; }
    th { background: #f3f7fb; }
    .num { text-align: left; direction: ltr; }
    .totals { margin-top: 10px; width: 280px; margin-right: auto; }
    .sign { display: flex; justify-content: space-between; margin-top: 28px; }
    .sign div { width: 40%; text-align: center; border-top: 1px solid #999; padding-top: 6px; }
    .footer { margin-top: 18px; color: #666; font-size: 11px; border-top: 1px solid #ddd; padding-top: 6px; }
    .noprint { margin: 10px; }
    @media print { .noprint { display: none; } }
    <?php if ($paper === 'thermal80'): ?>
    body { width: 72mm; font-size: 11px; }
    .head { display: block; }
    .sign { display: none; }
    <?php endif; ?>
  </style>
</head>
<body>
  <div class="noprint">
    <button onclick="window.print()">چاپ</button>
    <a href="<?= e(url('/invoices')) ?>">بازگشت</a>
  </div>
  <?php if (!empty($s['print_watermark'])): ?><div class="wm"><?= e($s['print_watermark']) ?></div><?php endif; ?>
  <div class="sheet">
    <?php if ($header): ?><div><?= $header ?></div><?php endif; ?>
    <div class="head">
      <div class="brand">
        <?php if (($s['print_show_logo'] ?? '1') === '1' && !empty($s['inv_logo_url'])): ?>
          <img src="<?= e($s['inv_logo_url']) ?>" alt="" style="max-height:56px;max-width:140px"><br>
        <?php endif; ?>
        <strong><?= e($s['inv_company_name'] ?? '') ?></strong>
        <div class="meta">
          <?= e($s['inv_company_address'] ?? '') ?><br>
          تلفن: <?= e($s['inv_company_phone'] ?? '') ?>
          <?php if (!empty($s['inv_economic_code'])): ?> · اقتصادی: <?= e($s['inv_economic_code']) ?><?php endif; ?>
        </div>
      </div>
      <div class="meta" style="text-align:left">
        <strong>فاکتور فروش</strong><br>
        شماره: <?= e(($s['inv_prefix'] ?? '') . (string)$invoice['number']) ?><br>
        تاریخ: <?= e($invoice['invoice_date'] ?? '') ?><br>
        مشتری: <?= e($invoice['party_name'] ?? '') ?>
      </div>
    </div>
    <table>
      <thead>
        <tr><th>شرح</th><th>تعداد</th><th>مبلغ (<?= e($s['inv_currency'] ?? '') ?>)</th></tr>
      </thead>
      <tbody>
        <tr>
          <td><?= e($invoice['description'] ?? 'فروش') ?></td>
          <td class="num">1</td>
          <td class="num"><?= money($net) ?></td>
        </tr>
      </tbody>
    </table>
    <table class="totals">
      <tr><td>جمع جزء</td><td class="num"><?= money($net) ?></td></tr>
      <?php if ($tax > 0): ?><tr><td>مالیات <?= e((string)$taxP) ?>٪</td><td class="num"><?= money($tax) ?></td></tr><?php endif; ?>
      <tr><th>مبلغ قابل پرداخت</th><th class="num"><?= money($sub) ?></th></tr>
    </table>
    <?php if (!empty($s['inv_default_note'])): ?>
      <p style="margin-top:12px"><?= e($s['inv_default_note']) ?></p>
    <?php endif; ?>
    <?php if (($s['print_show_signature'] ?? '1') === '1' || ($s['print_show_stamp'] ?? '1') === '1'): ?>
      <div class="sign">
        <?php if (($s['print_show_signature'] ?? '1') === '1'): ?><div>امضای فروشنده</div><?php endif; ?>
        <?php if (($s['print_show_stamp'] ?? '1') === '1'): ?><div>مهر شرکت</div><?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="footer"><?= e($footer) ?></div>
  </div>
  <script>
    <?php if (($s['print_auto_open'] ?? '1') === '1'): ?>window.addEventListener('load', () => setTimeout(() => window.print(), 250));<?php endif; ?>
  </script>
</body>
</html>
