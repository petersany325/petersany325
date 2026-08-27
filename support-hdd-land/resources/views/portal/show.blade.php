@extends('layouts.portal')
@section('title', $reception->ticket_no.' | سرزمین هارد')

@section('content')
<header class="p-top compact"><meta charset="utf-8">
    <a class="p-back" href="{{ url()->previous(route('portal.tickets')) }}">→</a>
    <div>
        <div class="p-hello">{{ $reception->ticket_no }}</div>
        <div class="p-sub">{{ $reception->statusLabel() }}</div>
    </div>
</header>

<section class="p-detail-hero tone-{{ $reception->status === 'ready' ? 'green' : ($reception->status === 'repairing' ? 'amber' : 'teal') }}">
    <h1>{{ $reception->product_name ?: 'دستگاه تعمیراتی' }}</h1>
    <p>
        @if($reception->brand){{ $reception->brand }} @endif
        @if($reception->model){{ $reception->model }} @endif
    </p>
    <div class="p-detail-badges">
        <span>{{ $reception->statusLabel() }}</span>
        @if($reception->serial_number)
            <span dir="ltr">{{ $reception->serial_number }}</span>
        @endif
    </div>
</section>

<div class="p-kv">
    <div><span>شماره رسید</span><strong>{{ $reception->receipt_no ?: '—' }}</strong></div>
    <div><span>پذیرش</span><strong>{{ jalali_like($reception->received_at) }}</strong></div>
    <div><span>عیب اظهار شده</span><strong>{{ $reception->reported_fault ?: ($reception->faultType?->name ?: '—') }}</strong></div>
    <div><span>تعمیرکار</span><strong>{{ $reception->technician?->name ?: '—' }}</strong></div>
    <div><span>محل دستگاه</span><strong>{{ $reception->custodyLabel() }}</strong></div>
</div>

<section class="p-section">
    <h2>ردیابی ارجاع</h2>
    @forelse($reception->handoffs as $h)
        <div class="p-part-row">
            <div>
                <strong>{{ $h->directionLabel() }}</strong>
                <small>
                    @if($h->direction === 'to_bench')
                        ارجاع به تعمیرگاه / تعمیرکار {{ $h->toTechnician?->name ?: '' }}
                    @else
                        بازگشت به پذیرش برای تحویل
                    @endif
                    — {{ jalali_like($h->responded_at ?: $h->created_at) }}
                </small>
            </div>
            <span>{{ $h->statusLabel() }}</span>
        </div>
    @empty
        <div class="p-empty soft">هنوز ارجاعی ثبت نشده؛ دستگاه نزد پذیرش است.</div>
    @endforelse
    <a class="p-btn ghost" style="margin-top:10px;width:100%;" href="{{ route('portal.messages', ['reception_id' => $reception->id]) }}">پیام به تعمیرگاه درباره این قبض</a>
</section>

<section class="p-section">
    <h2>تاریخچه وضعیت</h2>
    @forelse($reception->statusLogs as $log)
        <div class="p-timeline-item">
            <div class="p-timeline-dot"></div>
            <div>
                <strong>{{ $log->displayTitle() }}</strong>
                <small>
                    {{ jalali_like($log->created_at) }}
                    @if($log->fromStatusLabel()) · از {{ $log->fromStatusLabel() }} @endif
                    → {{ $log->toStatusLabel() }}
                </small>
                @if($log->note)
                    <small style="display:block;margin-top:2px;">{{ $log->note }}</small>
                @endif
            </div>
        </div>
    @empty
        <div class="p-empty soft">هنوز تغییر وضعیتی ثبت نشده.</div>
    @endforelse
    @if($reception->delivery_cancel_count)
        <div class="p-empty soft" style="margin-top:8px;">لغو تحویل قبلی: {{ $reception->delivery_cancel_count }} بار — دستگاه روی همین سریال برگشته است.</div>
    @endif
</section>

<section class="p-section">
    <h2>قطعات ({{ $reception->parts->count() }} ردیف · {{ $reception->parts->sum('quantity') }} عدد)</h2>
    @if($reception->parts->count())
        <div class="p-parts">
            @foreach($reception->parts as $part)
                <div class="p-part-row">
                    <div>
                        <strong>{{ $part->part_name }}</strong>
                        <small>× {{ $part->quantity }}</small>
                    </div>
                    <span>{{ number_format((int) $part->total_price) }}</span>
                </div>
            @endforeach
        </div>
    @else
        <div class="p-empty soft">هنوز قطعه‌ای ثبت نشده.</div>
    @endif
</section>

@if($reception->costStages->count())
<section class="p-section">
    <h2>مراحل هزینه</h2>
    <div class="p-parts">
        @foreach($reception->costStages as $stage)
            <div class="p-part-row">
                <div>
                    <strong>{{ $stage->stage_label }}</strong>
                    <small>{{ $stage->statusLabel() }}@if($stage->note) — {{ $stage->note }}@endif</small>
                </div>
                <span>{{ number_format((int) $stage->amount) }}</span>
            </div>
        @endforeach
    </div>
</section>
@endif

<section class="p-section">
    <h2>هزینه / فاکتور</h2>
    <div class="p-cost-card">
        <div><span>اجرت</span><strong>{{ number_format((int) $reception->labor_cost) }}</strong></div>
        <div><span>قطعات</span><strong>{{ number_format((int) $reception->parts_cost) }}</strong></div>
        <div><span>مراحل هزینه</span><strong>{{ number_format((int) $reception->stages_cost) }}</strong></div>
        <div><span>تخفیف</span><strong>{{ number_format((int) $reception->discount) }}</strong></div>
        @if($reception->discount_reason)
            <div><span>دلیل تخفیف</span><strong>{{ $reception->discount_reason }}</strong></div>
        @endif
        <div class="total"><span>جمع کل</span><strong>{{ number_format((int) $reception->total_amount) }}</strong></div>
        <div><span>پرداخت‌شده</span><strong>{{ number_format((int) $reception->paid_amount) }}</strong></div>
        <div class="remain"><span>مانده</span><strong>{{ number_format($reception->remainingAmount()) }}</strong></div>
    </div>
    @php
        $latestAp = $reception->latestCostApproval;
        $apLabels = \App\Models\CostApproval::statusLabels();
    @endphp
    @if($latestAp || $reception->customer_cost_approved_at)
        <div class="p-empty soft" style="margin-top:10px;text-align:right;">
            وضعیت تأیید مشتری:
            <strong>{{ $apLabels[$reception->cost_approval_status] ?? ($reception->cost_approval_status ?: '—') }}</strong>
            @if($reception->customer_cost_approved_at)
                <div>مبلغ تأییدشده: {{ number_format((int) $reception->customer_cost_approved_amount) }} تومان</div>
                <div>زمان تأیید: {{ jalali_like($reception->customer_cost_approved_at) }}</div>
                @if($latestAp?->approval_code)
                    <div>کد: <span dir="ltr">{{ $latestAp->approval_code }}</span></div>
                @endif
            @elseif($latestAp)
                <div>ارسال لینک: {{ jalali_like($latestAp->sent_at) }}</div>
                <div>مشاهده: {{ $latestAp->viewed_at ? jalali_like($latestAp->viewed_at) : 'هنوز باز نشده' }}</div>
            @endif
        </div>
    @endif
</section>

@if(($smsLogs ?? collect())->count())
<section class="p-section">
    <h2>پیامک‌های این قبض</h2>
    <div class="p-parts">
        @foreach($smsLogs as $log)
            <div class="p-part-row">
                <div>
                    <strong>{{ $log->status_key === 'cost_approval' ? 'لینک تأیید هزینه' : ($log->rule?->title ?: ($log->status_key ?: 'پیامک')) }}</strong>
                    <small>{{ jalali_like($log->created_at) }} — {{ $log->ok ? 'موفق' : 'ناموفق' }}</small>
                </div>
                <span>{{ $log->ok ? '✓' : '!' }}</span>
            </div>
        @endforeach
    </div>
</section>
@endif

@if($reception->status === 'ready')
<section class="p-section">
    <div class="p-ready-banner">
        <strong>آماده تحویل / خروج</strong>
        <p>مانده قابل پرداخت: {{ number_format($reception->remainingAmount()) }} تومان</p>
    </div>
    @if(\App\Support\PaymentGateways::zarinpal()['configured'] && $reception->remainingAmount() >= 1000)
        <form method="POST" action="{{ route('portal.zarinpal.start', $reception) }}" style="margin-bottom:10px;">
            @csrf
            <button class="p-btn primary" type="submit" style="width:100%;">پرداخت آنلاین با زرین‌پال — {{ number_format($reception->remainingAmount()) }} تومان</button>
        </form>
    @endif
    @if(count($payLinks))
        @include('partials.payment-links', ['payLinks' => $payLinks, 'payTitle' => 'لینک بانک‌ها', 'compact' => true])
    @endif
</section>
@endif

@if($reception->payments->count())
<section class="p-section">
    <h2>پرداخت‌های ثبت‌شده</h2>
    <div class="p-parts">
        @foreach($reception->payments as $payment)
            <div class="p-part-row">
                <div>
                    <strong>{{ $payment->typeLabel() }} / {{ $payment->methodLabel() }}</strong>
                    <small>{{ jalali_like($payment->paid_at) }}</small>
                </div>
                <span>{{ number_format((int) $payment->amount) }}</span>
            </div>
        @endforeach
    </div>
</section>
@endif
@endsection
