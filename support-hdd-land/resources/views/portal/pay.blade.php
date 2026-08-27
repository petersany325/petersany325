@extends('layouts.portal')
@section('title', 'پرداخت آنلاین | سرزمین هارد')

@section('content')
<header class="p-top compact"><meta charset="utf-8">
    <a class="p-back" href="{{ route('portal.home') }}">→</a>
    <div>
        <div class="p-hello">پرداخت آنلاین</div>
        <div class="p-sub">درگاه‌ها و قبض‌های آماده</div>
    </div>
</header>

<section class="p-section">
    <h2>پرداخت آنلاین زرین‌پال</h2>
    @if(\App\Support\PaymentGateways::zarinpal()['configured'])
        <p class="p-empty soft" style="margin-bottom:10px;">روی قبض آماده، دکمه پرداخت زرین‌پال مبلغ مانده را می‌گیرد.</p>
    @else
        <div class="p-empty">Merchant ID زرین‌پال هنوز در تنظیمات وارد نشده.</div>
    @endif
</section>

<section class="p-section">
    <h2>لینک موقت بانک‌ها</h2>
    @if(count($payLinks))
        @include('partials.payment-links', ['payLinks' => $payLinks, 'payTitle' => 'بانک‌ها', 'compact' => true])
    @else
        <div class="p-empty">لینک بانکی تنظیم نشده است.</div>
    @endif
</section>

<section class="p-section">
    <h2>قبض‌های آماده تحویل</h2>
    <div class="p-ticket-list">
        @forelse($ready as $t)
            @include('portal._ticket-card', ['ticket' => $t, 'highlightPay' => true])
        @empty
            <div class="p-empty">فعلاً قبض آماده‌ای نیست.</div>
        @endforelse
    </div>
</section>
@endsection
