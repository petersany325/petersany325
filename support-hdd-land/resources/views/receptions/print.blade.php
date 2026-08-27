<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>چاپ قبض {{ $reception->ticket_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1">
    @php
        $fontSize = (int) ($invoice['font_size'] ?? 11);
        $pageSize = $invoice['page_size'] ?? 'A4';
        $marginMm = (int) ($invoice['margin_mm'] ?? 10);
        $showLogo = ($invoice['show_logo'] ?? true);
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
        .actions { margin: 10px 0; display: flex; gap: 6px; }
        .btn { border: 1px solid #8e98a8; background: #eef1f5; padding: 4px 10px; border-radius: 2px; cursor: pointer; font: inherit; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .letterhead { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="letterhead">
        <div class="brand-block">
            @if($showLogo)
                <img src="{{ shop_logo_url('invoice') }}" alt="{{ shop_name() }}">
            @endif
            <div>
                <h1>{{ $invoice['shop_name'] }}</h1>
                <div class="sub">{{ shop_tagline() }} — {{ shop_name() }}</div>
                @if($invoice['footer'])
                    <div class="sub">{{ $invoice['footer'] }}</div>
                @endif
                @if($invoice['phones'])
                    <div class="sub">{{ $invoice['phones'] }}</div>
                @endif
                @if($invoice['address'])
                    <div class="sub">{{ $invoice['address'] }}</div>
                @endif
            </div>
        </div>
        <div class="ticket-meta">
            <strong>{{ $reception->ticket_no }}</strong>
            <span class="muted">قبض {{ $reception->receipt_no ?: '—' }}</span><br>
            <span class="muted">{{ jalali_like($reception->received_at) }}</span>
        </div>
    </div>

    <div class="actions no-print">
        <button class="btn" type="button" onclick="window.print()">چاپ</button>
        <button class="btn" type="button" onclick="window.close()">بستن</button>
    </div>

    <div class="grid">
        <div class="field"><label>نوع پذیرش</label><div>{{ $reception->admission_type ?: '—' }}</div></div>
        <div class="field"><label>مشتری</label><div>{{ $reception->customer->name }} — {{ $reception->customer->phone }}</div></div>
        <div class="field"><label>کالا / مدل</label><div>{{ $reception->product_name }} {{ $reception->brand }} {{ $reception->model }}</div></div>
        @if($invoice['show_serial'])
            <div class="field"><label>سریال</label><div dir="ltr">{{ $reception->serial_number ?: '—' }}</div></div>
        @endif
        <div class="field"><label>نوع خدمات</label><div>{{ $reception->service_type ?: '—' }} / {{ $reception->repair_type ?: '—' }}</div></div>
        <div class="field"><label>ظرفیت هارد</label><div>{{ $reception->capacityLabel() }}</div></div>
        @if($invoice['show_fault'])
            <div class="field full"><label>عیب اظهار مشتری</label><div>{{ $reception->reported_fault ?: ($reception->faultType?->name ?: '—') }}</div></div>
        @endif
        @if($invoice['show_accessories'])
            <div class="field full"><label>لوازم همراه</label><div>{{ $reception->accessories ?: '—' }}</div></div>
        @endif
        @if($invoice['show_appearance'])
            <div class="field full"><label>وضعیت ظاهری</label><div>{{ $reception->appearance_notes ?: '—' }}</div></div>
        @endif
        @if($invoice['show_warranty'])
            <div class="field"><label>گارانتی</label><div>{{ $reception->warranty_type ?: '—' }}</div></div>
        @endif
        @if($invoice['show_technician'])
            <div class="field"><label>تعمیرکار</label><div>{{ $reception->technician?->name ?: '—' }}</div></div>
        @endif
        @if($invoice['show_deposit'])
            <div class="field"><label>بیعانه</label><div>{{ number_format($reception->deposit) }} تومان</div></div>
        @endif
        @if($invoice['show_estimated_cost'])
            <div class="field"><label>هزینه تخمینی</label><div>{{ number_format($reception->estimated_cost) }} تومان</div></div>
        @endif
        <div class="field"><label>وضعیت</label><div>{{ $reception->statusLabel() }}</div></div>
    </div>

    @if($invoice['show_parts'] && $reception->parts->count())
        <table>
            <thead><tr><th>قطعه</th><th>تعداد</th><th>مبلغ</th></tr></thead>
            <tbody>
            @foreach($reception->parts as $part)
                <tr>
                    <td>{{ $part->part_name }}</td>
                    <td>{{ $part->quantity }}</td>
                    <td>{{ number_format($part->total_price) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($reception->costStages->count())
        <table>
            <thead><tr><th>مرحله هزینه</th><th>وضعیت</th><th>مبلغ</th></tr></thead>
            <tbody>
            @foreach($reception->costStages as $stage)
                <tr>
                    <td>{{ $stage->stage_label }}</td>
                    <td>{{ $stage->statusLabel() }}</td>
                    <td>{{ number_format($stage->amount) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($invoice['show_payments'])
        <div class="grid">
            <div class="field"><label>اجرت</label><div>{{ number_format($reception->labor_cost) }} تومان</div></div>
            <div class="field"><label>قطعات</label><div>{{ number_format($reception->parts_cost) }} تومان</div></div>
            <div class="field"><label>مراحل هزینه</label><div>{{ number_format($reception->stages_cost ?? 0) }} تومان</div></div>
            <div class="field"><label>تخفیف</label><div>{{ number_format($reception->discount) }} تومان@if($reception->discount_reason) — {{ $reception->discount_reason }}@endif</div></div>
            <div class="field"><label>جمع کل</label><div>{{ number_format($reception->total_amount) }} تومان</div></div>
            <div class="field"><label>پرداخت‌شده</label><div>{{ number_format($reception->paid_amount) }} تومان</div></div>
            <div class="field"><label>مانده</label><div>{{ number_format($reception->remainingAmount()) }} تومان</div></div>
        </div>
    @endif

    @if(!empty($payLinks))
        <div class="pay-print">
            <strong>پرداخت آنلاین</strong>
            <ul>
                @foreach($payLinks as $link)
                    <li>{{ $link['label'] }}: <span dir="ltr">{{ $link['url'] }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(trim((string) $invoice['terms']) !== '')
        <div class="terms">
            <strong>شرایط و مقررات</strong>
            <div>{{ $invoice['terms'] }}</div>
        </div>
    @endif
</div>

@if(!empty($invoice['auto_print']))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
