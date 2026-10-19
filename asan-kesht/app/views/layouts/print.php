<?php
/** @var string $content */
/** @var string $title */
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'فاکتور') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { margin: 0; background: #e8edf3; color: #172033; font-family: Vazirmatn, Tahoma, sans-serif; font-size: 13px; line-height: 1.7; }
    .print-tools { width: min(190mm, calc(100% - 1.5rem)); margin: 0.8rem auto; display: flex; gap: 0.6rem; align-items: center; }
    .print-tools button, .print-tools a { background: #e2b000; color: #172033; border: 0; text-decoration: none; font: inherit; font-weight: 800; padding: 0.4rem 0.8rem; border-radius: 999px; cursor: pointer; }
    .invoice { width: min(190mm, calc(100% - 1.5rem)); margin: 0 auto 1.5rem; background: #fff; padding: 14mm 12mm; box-shadow: 0 12px 30px rgba(14, 28, 51, 0.12); }
    .invoice-head, .invoice-signs, .invoice-meta { display: flex; justify-content: space-between; gap: 1rem; }
    .invoice-head { border-bottom: 3px solid #0e1c33; padding-bottom: 0.7rem; }
    .invoice-head strong { font-size: 1.35rem; }
    .invoice-head p, .invoice-note { margin: 0.15rem 0; }
    .invoice-id { text-align: left; display: grid; }
    .invoice-id b { font-size: 1.15rem; }
    .invoice-meta { flex-wrap: wrap; margin: 0.8rem 0; }
    .invoice-meta div { min-width: 9rem; }
    .invoice-meta dt { color: #5c6b80; font-size: 11px; }
    .invoice-meta dd { margin: 0; font-weight: 700; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #d5dde6; padding: 0.35rem 0.45rem; text-align: right; }
    th { background: #0e1c33; color: #fff; font-weight: 700; }
    .invoice-totals { margin-top: 0.8rem; display: grid; justify-items: start; gap: 0.25rem; }
    .invoice-totals div { display: flex; gap: 1rem; }
    .invoice-signs { margin-top: 1.4rem; }
    .invoice-signs div { flex: 1; min-height: 4.5rem; border: 1px dashed #8aa; padding: 0.4rem; }
    h2 { font-size: 1rem; margin: 1rem 0 0.3rem; }
    @page { size: A4; margin: 10mm; }
    @media print {
      body { background: #fff; }
      .print-tools { display: none; }
      .invoice { width: auto; margin: 0; box-shadow: none; padding: 0; }
    }
  </style>
</head>
<body>
  <?= $content ?>
</body>
</html>
