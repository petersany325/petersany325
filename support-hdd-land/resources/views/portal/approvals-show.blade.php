@extends('layouts.portal')
@section('title', 'جزئیات تأیید هزینه | سرزمین هارد')

@section('content')
<header class="p-top compact">
    <a class="p-back" href="{{ route('portal.approvals') }}">→</a>
    <div>
        <div class="p-hello">{{ $approval->reception?->ticket_no ?: 'تأیید هزینه' }}</div>
        <div class="p-sub">{{ $approval->statusLabel() }} · کد <span dir="ltr">{{ $approval->approval_code }}</span></div>
    </div>
</header>

<section class="p-detail-hero tone-{{ $approval->status === 'approved' ? 'green' : ($approval->status === 'rejected' ? 'rose' : 'amber') }}">
    <h1>{{ number_format((int) $approval->amount) }} تومان</h1>
    <p>{{ $approval->description ?: 'اعلام هزینه' }}</p>
    <div class="p-detail-badges">
        <span>{{ $approval->statusLabel() }}</span>
        <span>نسخه {{ $approval->version }}</span>
        @if($approval->reception?->service_type)
            <span>{{ $approval->reception->service_type }}</span>
        @endif
    </div>
</section>

<div class="p-kv">
    <div><span>ارسال لینک</span><strong>{{ $approval->sent_at?->format('Y/m/d H:i') ?: '—' }}</strong></div>
    <div><span>مشاهده لینک</span><strong>{{ $approval->viewed_at?->format('Y/m/d H:i') ?: 'هنوز باز نشده' }}</strong></div>
    <div><span>تصمیم</span><strong>{{ $approval->decided_at?->format('Y/m/d H:i') ?: '—' }}</strong></div>
    <div><span>اجرت / قطعات</span><strong>{{ number_format((int) $approval->labor_cost) }} / {{ number_format((int) $approval->parts_cost) }}</strong></div>
</div>

@if($approval->reject_reason)
<section class="p-section">
    <h2>دلیل رد</h2>
    <div class="p-empty soft">{{ $approval->reject_reason }}</div>
</section>
@endif

@if($smsLogs->count())
<section class="p-section">
    <h2>پیامک‌های مرتبط</h2>
    <div class="p-parts">
        @foreach($smsLogs as $log)
            <div class="p-part-row">
                <div>
                    <strong>{{ $log->ok ? 'ارسال موفق' : 'ارسال ناموفق' }}</strong>
                    <small>{{ $log->created_at?->format('Y/m/d H:i') }}</small>
                </div>
                <span>{{ $log->ok ? '✓' : '!' }}</span>
            </div>
        @endforeach
    </div>
</section>
@endif

@if($approval->reception_id)
<section class="p-section">
    <a class="p-btn ghost" style="width:100%;" href="{{ route('portal.show', $approval->reception_id) }}">مشاهده قبض مرتبط</a>
</section>
@endif
@endsection
