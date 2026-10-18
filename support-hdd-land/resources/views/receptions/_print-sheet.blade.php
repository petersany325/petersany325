{{-- Single receipt sheet body (used by print + print-bulk). Expects $reception, $invoice, $payLinks. --}}
@php
    $showLogo = ($invoice['show_logo'] ?? true);
@endphp
<div class="sheet">
    <div class="letterhead">
        <div class="brand-block">
            @if($showLogo)
                <img src="{{ asset('images/logo-invoice.png') }}?v=hd1" alt="HDD LAND">
            @endif
            <div>
                <h1>{{ $invoice['shop_name'] }}</h1>
                <div class="sub">سیستم مدیریت تعمیرات — سرزمین هارد</div>
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

    <div class="grid">
        <div class="field"><label>نوع پذیرش</label><div>{{ $reception->admission_type ?: '—' }}</div></div>
        <div class="field"><label>مشتری</label><div>{{ $reception->customer->name }} — {{ $reception->customer->phone }}</div></div>
        <div class="field"><label>کالا / مدل</label><div>{{ $reception->product_name }} {{ $reception->brand }} {{ $reception->model }}</div></div>
        @if($invoice['show_serial'])
            <div class="field"><label>سریال</label><div dir="ltr">{{ $reception->serial_number ?: '—' }}</div></div>
        @endif
        <div class="field"><label>نوع خدمات</label><div>{{ $reception->service_type ?: '—' }} / {{ $reception->repair_type ?: '—' }}</div></div>
        <div class="field"><label>ظرفیت هارد</label><div>{{ $reception->hdd_capacity ?: '—' }}</div></div>
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
