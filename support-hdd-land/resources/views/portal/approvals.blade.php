@extends('layouts.portal')
@section('title', 'گزارش تأیید هزینه‌ها | سرزمین هارد')

@section('content')
<header class="p-top compact">
    <a class="p-back" href="{{ route('portal.home') }}">→</a>
    <div>
        <div class="p-hello">گزارش تأیید هزینه</div>
        <div class="p-sub">لینک‌ها، مشاهده و تأییدهای شما</div>
    </div>
</header>

<section class="p-section">
    <div class="p-chips" style="margin-bottom:10px;">
        <a class="p-chip {{ $status === '' ? 'tone-teal' : 'tone-slate' }}" href="{{ route('portal.approvals') }}">همه</a>
        <a class="p-chip {{ $status === 'pending' ? 'tone-amber' : 'tone-slate' }}" href="{{ route('portal.approvals', ['status' => 'pending']) }}">در انتظار</a>
        <a class="p-chip {{ $status === 'approved' ? 'tone-green' : 'tone-slate' }}" href="{{ route('portal.approvals', ['status' => 'approved']) }}">تأییدشده</a>
        <a class="p-chip {{ $status === 'rejected' ? 'tone-rose' : 'tone-slate' }}" href="{{ route('portal.approvals', ['status' => 'rejected']) }}">ردشده</a>
    </div>

    <div class="p-ticket-list">
        @forelse($approvals as $ap)
            <a class="p-ticket" href="{{ route('portal.approvals.show', $ap) }}">
                <div class="p-ticket-top">
                    <strong>{{ $ap->reception?->ticket_no ?: 'قبض' }}</strong>
                    <span>{{ $ap->statusLabel() }}</span>
                </div>
                <div class="p-ticket-title">{{ number_format((int) $ap->amount) }} تومان</div>
                <div class="p-ticket-meta">
                    <span>{{ $ap->reception?->service_type ?: ($ap->reception?->repair_type ?: 'خدمت') }}</span>
                    <span>V{{ $ap->version }}</span>
                </div>
                <div class="p-ticket-meta" style="margin-top:4px;">
                    <span>ارسال {{ $ap->sent_at?->format('Y/m/d H:i') ?: '—' }}</span>
                    @if($ap->decided_at)
                        <span>تصمیم {{ $ap->decided_at->format('Y/m/d H:i') }}</span>
                    @elseif($ap->viewed_at)
                        <span>مشاهده {{ $ap->viewed_at->format('Y/m/d H:i') }}</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="p-empty">گزارش تأییدی برای نمایش نیست.</div>
        @endforelse
    </div>

    {{ $approvals->links('partials.pagination') }}
</section>
@endsection
