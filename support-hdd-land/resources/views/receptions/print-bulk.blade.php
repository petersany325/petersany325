<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>چاپ کلی {{ $receptions->count() }} قبض</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1">
    @php
        $fontSize = (int) ($invoice['font_size'] ?? 11);
        $pageSize = $invoice['page_size'] ?? 'A4';
        $marginMm = (int) ($invoice['margin_mm'] ?? 10);
        $pageCss = match($pageSize) {
            'A5' => 'A5',
            'Letter' => 'letter',
            default => 'A4',
        };
    @endphp
    <style>
        @page { size: {{ $pageCss }}; margin: {{ $marginMm }}mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Vazirmatn, Tahoma, sans-serif;
            direction: rtl;
            color: #1f2933;
            margin: 0;
            padding: 12px;
            font-size: {{ $fontSize }}px;
            background: #fff;
        }
        .sheet { max-width: 210mm; margin: 0 auto; }
        .sheet-break { page-break-after: always; break-after: page; }
        .sheet-break:last-child { page-break-after: auto; break-after: auto; }
        .letterhead {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
            border: 1px solid #2b3340;
            background: linear-gradient(180deg, #f7f8fa, #eef1f5);
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        .brand-block { display: flex; gap: 10px; align-items: center; }
        .brand-block img { height: 54px; width: auto; display: block; }
        .brand-block h1 { margin: 0; font-size: {{ max(14, $fontSize + 5) }}px; color: #2b3340; }
        .brand-block .sub { color: #5f6b7a; font-size: {{ max(9, $fontSize - 1) }}px; margin-top: 2px; }
        .ticket-meta { text-align: left; font-size: {{ $fontSize }}px; }
        .ticket-meta strong { display: block; font-size: {{ $fontSize + 2 }}px; }
        .muted { color: #5f6b7a; }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 10px;
            border: 1px solid #9aa5b5;
            padding: 8px;
            margin-bottom: 8px;
        }
        .grid .full { grid-column: 1 / -1; }
        .field label { display: block; font-size: {{ max(8, $fontSize - 2) }}px; color: #5f6b7a; }
        .field div { font-weight: 700; font-size: {{ $fontSize }}px; border-bottom: 1px dotted #c5ccd6; padding-bottom: 2px; min-height: 1.3em; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th, td { border: 1px solid #9aa5b5; padding: 4px 6px; text-align: right; font-size: {{ max(9, $fontSize - 1) }}px; }
        th { background: #2b3340; color: #fff; }
        .terms {
            margin-top: 10px; padding-top: 8px; border-top: 1px dashed #9aa5b5;
            font-size: {{ max(8, $fontSize - 2) }}px; line-height: 1.7; white-space: pre-wrap;
        }
        .pay-print { margin-top: 8px; padding: 6px; border: 1px solid #b7c0cd; font-size: {{ max(9, $fontSize - 1) }}px; }
        .pay-print ul { margin: 4px 0 0; padding-right: 16px; }
        .actions { margin: 10px 0; display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .btn { border: 1px solid #8e98a8; background: #eef1f5; padding: 4px 10px; border-radius: 2px; cursor: pointer; font: inherit; }
        .summary { font-size: {{ max(10, $fontSize) }}px; color: #5f6b7a; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .letterhead { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button class="btn" type="button" onclick="window.print()">چاپ همه ({{ $receptions->count() }} قبض)</button>
        <button class="btn" type="button" onclick="window.close()">بستن</button>
        <span class="summary">هر قبض در صفحه جدا چاپ می‌شود.</span>
    </div>

    @foreach($receptions as $reception)
        <div class="sheet-break">
            @include('receptions._print-sheet', ['reception' => $reception, 'invoice' => $invoice, 'payLinks' => $payLinks])
        </div>
    @endforeach

@if(!empty($invoice['auto_print']))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
