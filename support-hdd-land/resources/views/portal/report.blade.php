@extends('layouts.portal')
@section('title', 'گزارش وضعیت | '.shop_name())

@section('content')
<header class="p-top compact"><meta charset="utf-8">
    <a class="p-back" href="{{ route('portal.home') }}">→</a>
    <div>
        <div class="p-hello">گزارش تعمیرات</div>
        <div class="p-sub">وضعیت دستگاه‌های شما</div>
    </div>
</header>

<div class="p-report-grid">
    <div class="p-report-card tone-teal"><span>کل</span><strong>{{ $stats['total'] }}</strong></div>
    <div class="p-report-card tone-amber"><span>در حال تعمیر</span><strong>{{ $stats['repairing'] }}</strong></div>
    <div class="p-report-card tone-rose"><span>منتظر قطعه</span><strong>{{ $stats['waiting_part'] }}</strong></div>
    <div class="p-report-card tone-green"><span>آماده تحویل</span><strong>{{ $stats['ready'] }}</strong></div>
    <div class="p-report-card tone-blue"><span>باز / جاری</span><strong>{{ $stats['open'] }}</strong></div>
    <div class="p-report-card tone-slate"><span>تحویل‌شده</span><strong>{{ $stats['delivered'] }}</strong></div>
</div>

<section class="p-section">
    <h2>تفکیک وضعیت</h2>
    <div class="p-status-bars">
        @foreach(\App\Models\Reception::STATUSES as $key => $label)
            @php $c = (int) ($byStatus[$key] ?? 0); $pct = $stats['total'] > 0 ? round($c * 100 / $stats['total']) : 0; @endphp
            @if($c > 0)
                <div class="p-bar-row">
                    <div class="p-bar-label"><span>{{ $label }}</span><b>{{ $c }}</b></div>
                    <div class="p-bar-track"><i style="width:{{ max(8, $pct) }}%"></i></div>
                </div>
            @endif
        @endforeach
    </div>
</section>

<section class="p-section">
    <h2>دستگاه‌های در جریان</h2>
    <div class="p-ticket-list">
        @forelse($open as $t)
            @include('portal._ticket-card', ['ticket' => $t, 'highlightPay' => $t->status === 'ready'])
        @empty
            <div class="p-empty">دستگاه بازی ندارید.</div>
        @endforelse
    </div>
</section>
@endsection
