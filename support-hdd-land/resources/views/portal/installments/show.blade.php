@extends('layouts.portal')
@section('title', ($plan->title ?: 'اقساط #'.$plan->id).' | '.shop_name())

@section('content')
<header class="p-top compact">
    <a class="p-back" href="{{ route('portal.installments.index') }}">→</a>
    <div>
        <div class="p-hello">{{ $plan->title ?: ('طرح اقساط #'.$plan->id) }}</div>
        <div class="p-sub">{{ $plan->statusLabel() }}
            @if($plan->reception) · قبض {{ $plan->reception->ticket_no }} @endif
        </div>
    </div>
</header>

@php
    $paid = (int) $plan->items->sum('paid_amount');
    $total = (int) $plan->items->sum('amount');
    $remain = max(0, $total - $paid);
@endphp

<section class="p-debt-banner" role="status">
    <div class="p-debt-banner-top">
        <strong>مانده اقساط</strong>
        <span>{{ number_format($remain) }} تومان</span>
    </div>
    <p>پرداخت‌شده {{ number_format($paid) }} از {{ number_format($total) }} تومان</p>
</section>

@if($plan->notes)
<section class="p-section">
    <h2>یادداشت طرح</h2>
    <p class="p-empty soft" style="text-align:right;">{{ $plan->notes }}</p>
</section>
@endif

@if(\App\Support\BankTransferSettings::isEnabled())
<section class="p-section">
    <h2>واریز کارت‌به‌کارت برای قسط</h2>
    <div class="p-ready-banner">
        <strong>{{ $bankTransfer['bank_name'] ?: 'کارت شرکت' }}</strong>
        <p style="margin:6px 0 0;font-size:1.15rem;letter-spacing:.04em;" dir="ltr">{{ \App\Support\BankTransferSettings::formattedCard($bankTransfer['card_number']) }}</p>
        @if($bankTransfer['card_holder'])
            <p style="margin:4px 0 0;">به نام: {{ $bankTransfer['card_holder'] }}</p>
        @endif
        @if($bankTransfer['instructions'])
            <p style="margin:8px 0 0;font-size:.9rem;">{{ $bankTransfer['instructions'] }}</p>
        @endif
    </div>
    <p class="p-empty soft" style="margin-top:8px;">بعد از واریز، از فرم هر قسط مبلغ را ثبت کنید (کارت‌به‌کارت یا کارت).</p>
</section>
@endif

@if(!empty($zarinpalReady))
<section class="p-section">
    <h2>پرداخت آنلاین زرین‌پال</h2>
    <form method="POST" action="{{ route('portal.zarinpal.start', $plan->reception) }}">
        @csrf
        <button class="p-btn primary" type="submit" style="width:100%;">پرداخت مانده قبض مرتبط</button>
    </form>
    <p class="p-empty soft" style="margin-top:8px;">این پرداخت روی قبض می‌نشیند؛ قسط را هم جداگانه از لیست زیر ثبت کنید.</p>
</section>
@endif

<section class="p-section">
    <h2>جدول اقساط</h2>
    @foreach($plan->items as $item)
        @php $left = $item->remainingAmount(); @endphp
        <div class="p-ready-banner" style="margin-bottom:10px;">
            <div class="p-debt-banner-top" style="margin:0;">
                <strong>قسط {{ $item->sequence }}</strong>
                <span>{{ $item->statusLabel() }}</span>
            </div>
            <p style="margin:6px 0 0;">سررسید {{ jalali_date($item->due_date) }}</p>
            <p style="margin:4px 0 0;">مبلغ {{ number_format($item->amount) }} · پرداختی {{ number_format($item->paid_amount) }} · مانده <strong>{{ number_format($left) }}</strong></p>
            @if($item->notes)
                <p style="margin:4px 0 0;font-size:.9rem;">{{ $item->notes }}</p>
            @endif

            @if($plan->status === 'active' && $left > 0)
                <form method="POST" action="{{ route('portal.installments.pay', $item) }}" style="margin-top:10px;">
                    @csrf
                    <label style="display:block;font-size:11px;margin-bottom:4px;">مبلغ پرداخت (ناقص هم مجاز)</label>
                    <input type="number" name="amount" min="1" max="{{ $left }}" value="{{ $left }}" required dir="ltr" style="width:100%;margin-bottom:6px;padding:8px;border:1px solid #9aa5b5;border-radius:2px;">
                    <label style="display:block;font-size:11px;margin-bottom:4px;">روش</label>
                    <select name="method" required style="width:100%;margin-bottom:6px;padding:8px;border:1px solid #9aa5b5;border-radius:2px;">
                        <option value="transfer">کارت‌به‌کارت / واریز</option>
                        <option value="card">کارت‌خوان / درگاه</option>
                    </select>
                    <input type="text" name="note" placeholder="شماره پیگیری / توضیح (اختیاری)" style="width:100%;margin-bottom:8px;padding:8px;border:1px solid #9aa5b5;border-radius:2px;">
                    <button class="p-btn primary" type="submit" style="width:100%;">ثبت پرداخت این قسط</button>
                </form>
            @endif
        </div>
    @endforeach
</section>

@if($plan->reception)
<section class="p-section">
    <a class="p-btn ghost" style="width:100%;" href="{{ route('portal.show', $plan->reception) }}">مشاهده قبض مرتبط</a>
</section>
@endif
@endsection
