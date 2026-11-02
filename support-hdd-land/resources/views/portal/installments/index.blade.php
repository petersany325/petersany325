@extends('layouts.portal')
@section('title', 'اقساط من | '.shop_name())

@section('content')
<header class="p-top compact">
    <a class="p-back" href="{{ route('portal.home') }}">→</a>
    <div>
        <div class="p-hello">اقساط من</div>
        <div class="p-sub">نمایش و پرداخت قسط‌ها</div>
    </div>
</header>

<section class="p-section">
    <div class="p-ticket-list">
        @forelse($plans as $plan)
            @php
                $paid = (int) $plan->items->sum('paid_amount');
                $total = (int) $plan->items->sum('amount');
                $remain = max(0, $total - $paid);
                $open = $plan->items->filter(fn ($i) => $i->remainingAmount() > 0)->count();
            @endphp
            <a class="p-ticket {{ $remain > 0 ? 'is-debt' : '' }}" href="{{ route('portal.installments.show', $plan) }}">
                <div class="p-ticket-top">
                    <strong>{{ $plan->title ?: ('طرح اقساط #'.$plan->id) }}</strong>
                    <span>{{ $plan->statusLabel() }}</span>
                </div>
                <div class="p-ticket-meta">
                    <span>{{ $plan->items->count() }} قسط · {{ $open }} باز</span>
                    <span>مانده {{ number_format($remain) }} ت</span>
                </div>
                <div class="p-ticket-meta">
                    <span>پرداخت‌شده {{ number_format($paid) }} از {{ number_format($total) }}</span>
                    @if($plan->reception)
                        <span>قبض {{ $plan->reception->ticket_no }}</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="p-empty">طرح اقساطی برای شما ثبت نشده است.</div>
        @endforelse
    </div>
</section>
@endsection
