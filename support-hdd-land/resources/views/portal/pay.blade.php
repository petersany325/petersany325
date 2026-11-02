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
        <p class="p-empty soft" style="margin-bottom:10px;">روی قبض آماده، دکمه پرداخت زرین‌پال مبلغ مانده را می‌گیرد و پس از موفقیت در گزارش مالی ثبت می‌شود.</p>
    @else
        <div class="p-empty">Merchant ID زرین‌پال هنوز در تنظیمات وارد نشده.</div>
    @endif
</section>

@if(\App\Support\BankTransferSettings::isEnabled())
<section class="p-section">
    <h2>واریز کارت‌به‌کارت</h2>
    <div class="p-ready-banner">
        <strong>{{ $bankTransfer['bank_name'] ?: 'کارت شرکت' }}</strong>
        <p style="margin:6px 0 0;font-size:1.15rem;letter-spacing:.04em;" dir="ltr">{{ \App\Support\BankTransferSettings::formattedCard($bankTransfer['card_number']) }}</p>
        @if($bankTransfer['card_holder'])
            <p style="margin:4px 0 0;">به نام: {{ $bankTransfer['card_holder'] }}</p>
        @endif
        @if($bankTransfer['iban'])
            <p style="margin:4px 0 0;" dir="ltr">شبا: {{ $bankTransfer['iban'] }}</p>
        @endif
        @if($bankTransfer['instructions'])
            <p style="margin:8px 0 0;font-size:.9rem;">{{ $bankTransfer['instructions'] }}</p>
        @endif
    </div>
    <p class="p-empty soft" style="margin-top:10px;">از صفحه هر قبض، تصویر فیش را ارسال کنید. تا تأیید حسابدار/مدیر، واریز قطعی نیست.</p>
</section>
@endif

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

@if(($payable ?? collect())->count())
<section class="p-section">
    <h2>قبض‌های دارای مانده</h2>
    <div class="p-ticket-list">
        @foreach($payable as $t)
            @include('portal._ticket-card', ['ticket' => $t, 'highlightPay' => true])
        @endforeach
    </div>
</section>
@endif
@endsection
