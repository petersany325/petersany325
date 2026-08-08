@extends('layouts.portal')
@section('title', 'میز کار مشتری | '.shop_name())

@section('content')
<header class="p-top">
    <div class="p-brand-mini">
        <img src="{{ shop_logo_url('header') }}" alt="{{ shop_name() }}" width="96" height="22">
        <div>
            <div class="p-hello">سلام {{ $customer->name }}</div>
            <div class="p-sub" dir="ltr">{{ $customer->phone }}</div>
        </div>
    </div>
    <form method="POST" action="{{ route('portal.logout') }}">
        @csrf
        <button class="p-icon-btn" type="submit" title="خروج">⎋</button>
    </form>
</header>

<section class="p-hero">
    <div class="p-hero-text">
        <h1>کارتابل تعمیرات</h1>
        <p>{{ $stats['open'] }} دستگاه باز · {{ $stats['ready'] }} آماده تحویل</p>
    </div>
    <div class="p-hero-stat">
        <strong>{{ $stats['total'] }}</strong>
        <span>کل قبض</span>
    </div>
</section>

@if(!empty($debtSummary['has_debt']))
<section class="p-debt-banner" role="alert">
    <div class="p-debt-banner-top">
        <strong>بدهکاری فعال</strong>
        <span>{{ number_format($debtSummary['total']) }} تومان</span>
    </div>
    <p>
        @if(($debtSummary['credit_total'] ?? 0) > 0)
            بخشی از بدهی بابت تحویل نسیه است ({{ number_format($debtSummary['credit_total']) }} تومان).
        @else
            مانده پرداخت‌نشده روی قبض‌های شما ثبت است.
        @endif
        تا تسویه کامل، این هشدار در کارتابل می‌ماند.
    </p>
    <a class="p-btn primary" href="{{ route('portal.pay') }}">پرداخت بدهی / درگاه بانکی</a>
</section>
@if(($debtTickets ?? collect())->count())
<section class="p-section">
    <div class="p-section-head">
        <h2>قبض‌های بدهکار</h2>
        <a href="{{ route('portal.pay') }}">تسویه</a>
    </div>
    <div class="p-ticket-list">
        @foreach($debtTickets as $t)
            @include('portal._ticket-card', ['ticket' => $t, 'highlightPay' => true, 'highlightDebt' => true])
        @endforeach
    </div>
</section>
@endif
@endif

<div class="p-chips">
    <a class="p-chip tone-amber" href="{{ route('portal.tickets', ['status' => 'repairing']) }}">تعمیر {{ $stats['repairing'] }}</a>
    <a class="p-chip tone-rose" href="{{ route('portal.tickets', ['status' => 'waiting_part']) }}">قطعه {{ $stats['waiting_part'] }}</a>
    <a class="p-chip tone-green" href="{{ route('portal.tickets', ['status' => 'ready']) }}">آماده {{ $stats['ready'] }}</a>
    <a class="p-chip tone-slate" href="{{ route('portal.tickets', ['status' => 'delivered']) }}">تحویل {{ $stats['delivered'] }}</a>
</div>

<section class="p-section">
    <h2>منوی سریع</h2>
    <div class="p-menu-grid">
        @foreach($menus as $m)
            <a class="p-menu-card tone-{{ $m['tone'] }}" href="{{ route($m['route'], $m['params']) }}">
                <span class="p-menu-ico">{{ $m['icon'] }}</span>
                <strong>{{ $m['label'] }}</strong>
                <small>{{ $m['hint'] }}</small>
            </a>
        @endforeach
    </div>
</section>

@if($ready->count())
<section class="p-section">
    <div class="p-section-head">
        <h2>آماده خروج — پرداخت</h2>
        <a href="{{ route('portal.tickets', ['status' => 'ready']) }}">همه</a>
    </div>
    <div class="p-ticket-list">
        @foreach($ready as $t)
            @include('portal._ticket-card', ['ticket' => $t, 'highlightPay' => true])
        @endforeach
    </div>
</section>
@endif

<section class="p-section">
    <div class="p-section-head">
        <h2>آخرین قبض‌ها</h2>
        <a href="{{ route('portal.tickets') }}">لیست</a>
    </div>
    <div class="p-ticket-list">
        @forelse($recent as $t)
            @include('portal._ticket-card', ['ticket' => $t])
        @empty
            <div class="p-empty">هنوز قبضی برای شما ثبت نشده.</div>
        @endforelse
    </div>
</section>
@endsection
