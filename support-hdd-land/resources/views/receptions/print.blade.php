<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    
    <title>چاپ قبض {{ $reception->ticket_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:Vazirmatn,Tahoma,sans-serif;direction:rtl;color:#111;margin:24px;}
        .box{border:1px solid #333;padding:16px;max-width:720px;margin:auto;}
        h1{margin:0 0 4px;font-size:20px;}
        .muted{color:#555;}
        .print-brand{display:flex;align-items:center;gap:12px;margin-bottom:4px;}
        .print-brand img{height:56px;width:auto;display:block;}
        table{width:100%;border-collapse:collapse;margin-top:12px;}
        th,td{border:1px solid #999;padding:8px;text-align:right;font-size:13px;}
        .row{display:flex;justify-content:space-between;gap:12px;margin-top:8px;align-items:flex-start;}
        .terms{margin-top:18px;padding-top:12px;border-top:1px dashed #999;font-size:12px;line-height:1.7;white-space:pre-wrap;}
        .pay-print{margin-top:14px;padding:10px;border:1px solid #bbb;border-radius:6px;font-size:12px;}
        .pay-print ul{margin:6px 0 0;padding-right:18px;}
        .pay-print li{margin:3px 0;}
        @media print{.no-print{display:none;}}
    </style>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1">
</head>
<body>
<div class="box">
    <div class="row">
        <div>
            <div class="print-brand">
                <img src="{{ asset('images/logo-invoice.png') }}?v=hd1" alt="HDD LAND">
                <div>
                    <h1>{{ $invoice['shop_name'] }}</h1>
                    <div class="muted">سیستم مدیریت تعمیرات — سرزمین هارد</div>
                </div>
            </div>
            <div class="muted">{{ $invoice['footer'] }}</div>
            @if($invoice['phones'])
                <div class="muted">{{ $invoice['phones'] }}</div>
            @endif
            @if($invoice['address'])
                <div class="muted">{{ $invoice['address'] }}</div>
            @endif
        </div>
        <div style="text-align:left;">
            <strong>{{ $reception->ticket_no }}</strong><br>
            <span class="muted">{{ optional($reception->received_at)->format('Y/m/d H:i') }}</span>
        </div>
    </div>
    <hr>
    <p><strong>شماره قبض:</strong> {{ $reception->receipt_no ?: '—' }}</p>
    <p><strong>نوع پذیرش:</strong> {{ $reception->admission_type ?: '—' }}</p>
    <p><strong>مشتری:</strong> {{ $reception->customer->name }} — {{ $reception->customer->phone }}</p>
    <p><strong>کالا / مدل:</strong> {{ $reception->product_name }} {{ $reception->brand }} {{ $reception->model }}</p>
    @if($invoice['show_serial'])
        <p><strong>سریال:</strong> {{ $reception->serial_number ?: '—' }}</p>
    @endif
    <p><strong>نوع خدمات:</strong> {{ $reception->service_type ?: '—' }} / {{ $reception->repair_type ?: '—' }}</p>
    @if($invoice['show_fault'])
        <p><strong>عیب اظهار مشتری:</strong> {{ $reception->reported_fault ?: ($reception->faultType?->name ?: '—') }}</p>
    @endif
    @if($invoice['show_accessories'])
        <p><strong>لوازم همراه:</strong> {{ $reception->accessories ?: '—' }}</p>
    @endif
    @if($invoice['show_appearance'])
        <p><strong>وضعیت ظاهری:</strong> {{ $reception->appearance_notes ?: '—' }}</p>
    @endif
    <p><strong>ظرفیت هارد:</strong> {{ $reception->hdd_capacity ?: '—' }}</p>
    @if($invoice['show_warranty'])
        <p><strong>گارانتی:</strong> {{ $reception->warranty_type ?: '—' }}</p>
    @endif
    @if($invoice['show_technician'])
        <p><strong>تعمیرکار:</strong> {{ $reception->technician?->name ?: '—' }}</p>
    @endif
    @if($invoice['show_deposit'])
        <p><strong>بیعانه:</strong> {{ number_format($reception->deposit) }} تومان</p>
    @endif
    @if($invoice['show_estimated_cost'])
        <p><strong>هزینه تخمینی:</strong> {{ number_format($reception->estimated_cost) }} تومان</p>
    @endif
    <p><strong>وضعیت:</strong> {{ $reception->statusLabel() }}</p>

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

    @if($invoice['show_payments'])
        <p style="margin-top:16px;"><strong>جمع کل:</strong> {{ number_format($reception->total_amount) }} تومان</p>
        <p><strong>پرداخت‌شده:</strong> {{ number_format($reception->paid_amount) }} تومان</p>
        <p><strong>مانده:</strong> {{ number_format($reception->remainingAmount()) }} تومان</p>
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
            <strong>شرایط تعمیرات و پذیرش</strong>
            <div>{{ $invoice['terms'] }}</div>
        </div>
    @endif
</div>
<p class="no-print" style="text-align:center;margin-top:16px;">
    <button onclick="window.print()">چاپ</button>
</p>
@if(!empty($invoice['auto_print']))
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 250); });</script>
@endif
</body>
</html>
