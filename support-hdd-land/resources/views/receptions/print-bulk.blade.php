<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>چاپ کلی {{ $receptions->count() }} قبض</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1">
    @php
        $fontSize = max(9, min(12, (int) ($invoice['font_size'] ?? 11)));
        $pageSize = $invoice['page_size'] ?? 'A4';
        $marginMm = max(6, min(12, (int) ($invoice['margin_mm'] ?? 8)));
        $pageCss = match ($pageSize) {
            'A5' => 'A5',
            'Letter' => 'letter',
            default => 'A4',
        };
        $showLogo = ($invoice['show_logo'] ?? true);
        $totalAmount = (int) $receptions->sum(fn ($r) => (int) $r->total_amount);
        $paidAmount = (int) $receptions->sum(fn ($r) => (int) $r->paid_amount);
        $remainAmount = (int) $receptions->sum(fn ($r) => (int) $r->remainingAmount());
        $bestankar = $paidAmount;
        $bedehkar = $remainAmount;
    @endphp
    <style>
        @page { size: {{ $pageCss }}; margin: {{ $marginMm }}mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Vazirmatn, Tahoma, sans-serif;
            direction: rtl;
            color: #1f2933;
            margin: 0;
            padding: 10px;
            font-size: {{ $fontSize }}px;
            background: #fff;
        }
        .wrap { max-width: 210mm; margin: 0 auto; }
        .head {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
            border: 1px solid #2b3340;
            background: linear-gradient(180deg, #f7f8fa, #eef1f5);
            padding: 8px 10px;
            margin-bottom: 8px;
        }
        .brand { display: flex; gap: 8px; align-items: center; }
        .brand img { height: 42px; width: auto; display: block; }
        .brand h1 { margin: 0; font-size: {{ $fontSize + 4 }}px; color: #2b3340; }
        .brand .sub { color: #5f6b7a; font-size: {{ $fontSize - 1 }}px; margin-top: 1px; }
        .meta { text-align: left; font-size: {{ $fontSize }}px; line-height: 1.5; }
        .meta strong { display: block; font-size: {{ $fontSize + 1 }}px; }
        .muted { color: #5f6b7a; }
        .actions { margin: 0 0 8px; display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .btn { border: 1px solid #8e98a8; background: #eef1f5; padding: 4px 10px; border-radius: 2px; cursor: pointer; font: inherit; }
        table.list {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.list th, table.list td {
            border: 1px solid #9aa5b5;
            padding: 3px 5px;
            text-align: right;
            vertical-align: top;
            font-size: {{ $fontSize - 1 }}px;
            word-wrap: break-word;
        }
        table.list th {
            background: #2b3340;
            color: #fff;
            font-weight: 700;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        table.list tr:nth-child(even) td { background: #f7f8fa; }
        .col-row { width: 4%; }
        .col-ticket { width: 12%; }
        .col-receipt { width: 10%; }
        .col-customer { width: 16%; }
        .col-serial { width: 12%; }
        .col-device { width: 16%; }
        .col-status { width: 10%; }
        .col-money { width: 10%; }
        .num { direction: ltr; unicode-bidi: plaintext; text-align: left; white-space: nowrap; }
        .summary {
            margin-top: 10px;
            border: 2px solid #2b3340;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
        }
        .summary .box {
            padding: 8px 10px;
            border-left: 1px solid #9aa5b5;
        }
        .summary .box:last-child { border-left: 0; }
        .summary .box.bedehkar { background: #fff4f0; }
        .summary .box.bestankar { background: #eefaf1; }
        .summary .label {
            display: block;
            font-size: {{ $fontSize - 1 }}px;
            color: #5f6b7a;
            margin-bottom: 2px;
        }
        .summary .value {
            font-size: {{ $fontSize + 3 }}px;
            font-weight: 800;
            color: #1f2933;
        }
        .summary .box.bedehkar .value { color: #b42318; }
        .summary .box.bestankar .value { color: #067647; }
        .note {
            margin-top: 6px;
            font-size: {{ $fontSize - 1 }}px;
            color: #5f6b7a;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .head, table.list th, .summary .box {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table.list { page-break-inside: auto; }
            table.list tr { page-break-inside: avoid; page-break-after: auto; }
            .summary { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="actions no-print">
        <button class="btn" type="button" onclick="window.print()">چاپ فهرست ({{ $receptions->count() }} قبض)</button>
        <button class="btn" type="button" onclick="window.close()">بستن</button>
        <span class="muted">چاپ فشرده ستونی — هر قبض یک سطر؛ در پایان بدهکار و بستانکار</span>
    </div>

    <div class="head">
        <div class="brand">
            @if($showLogo)
                <img src="{{ asset('images/logo-invoice.png') }}?v=hd1" alt="{{ $invoice['shop_name'] }}">
            @endif
            <div>
                <h1>{{ $invoice['shop_name'] }}</h1>
                <div class="sub">چاپ کلی قبض</div>
                @if($invoice['phones'])
                    <div class="sub">{{ $invoice['phones'] }}</div>
                @endif
            </div>
        </div>
        <div class="meta">
            <strong>{{ $receptions->count() }} قبض انتخاب‌شده</strong>
            <span class="muted">{{ jalali_like(now()) }}</span>
        </div>
    </div>

    <table class="list">
        <thead>
        <tr>
            <th class="col-row">#</th>
            <th class="col-ticket">تیکت</th>
            <th class="col-receipt">قبض</th>
            <th class="col-customer">مشتری</th>
            <th class="col-serial">سریال</th>
            <th class="col-device">کالا / مدل</th>
            <th class="col-status">وضعیت</th>
            <th class="col-money">جمع</th>
            <th class="col-money">بستانکار<br><span style="font-weight:500;font-size:90%;">(پرداخت)</span></th>
            <th class="col-money">بدهکار<br><span style="font-weight:500;font-size:90%;">(مانده)</span></th>
        </tr>
        </thead>
        <tbody>
        @foreach($receptions as $i => $reception)
            @php
                $paid = (int) $reception->paid_amount;
                $total = (int) $reception->total_amount;
                $remain = (int) $reception->remainingAmount();
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $reception->ticket_no }}</td>
                <td>{{ $reception->receipt_no ?: '—' }}</td>
                <td>
                    {{ $reception->customer?->name ?: '—' }}
                    <div class="muted num">{{ $reception->customer?->phone }}</div>
                </td>
                <td class="num">{{ $reception->serial_number ?: '—' }}</td>
                <td>{{ trim(($reception->product_name ?: '').' '.($reception->brand ?: '').' '.($reception->model ?: '')) ?: '—' }}</td>
                <td>{{ $reception->statusLabel() }}</td>
                <td class="num">{{ number_format($total) }}</td>
                <td class="num">{{ number_format($paid) }}</td>
                <td class="num">{{ number_format($remain) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="box">
            <span class="label">تعداد قبض</span>
            <div class="value">{{ number_format($receptions->count()) }}</div>
        </div>
        <div class="box">
            <span class="label">جمع کل</span>
            <div class="value num">{{ number_format($totalAmount) }}</div>
        </div>
        <div class="box bestankar">
            <span class="label">بستانکار (پرداخت‌شده)</span>
            <div class="value num">{{ number_format($bestankar) }} تومان</div>
        </div>
        <div class="box bedehkar">
            <span class="label">بدهکار (مانده)</span>
            <div class="value num">{{ number_format($bedehkar) }} تومان</div>
        </div>
    </div>
    <div class="note">
        بستانکار = مجموع مبالغ پرداخت‌شده روی قبض‌های انتخاب‌شده · بدهکار = مجموع مانده قابل وصول
    </div>
</div>

@if(!empty($invoice['auto_print']))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
