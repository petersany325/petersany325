@extends('layouts.app')
@section('title', 'لایسنس نصب | '.shop_name())
@section('page_title', 'لایسنس نصب')
@section('window_title', 'وضعیت لایسنس و تمدید')

@section('content')
@php
    $days = $license['days_remaining'] ?? null;
    $expired = is_int($days) && $days < 0;
    $soon = is_int($days) && $days >= 0 && $days <= 30;
@endphp
<div class="panel">
    <h2 style="margin-top:0;">وضعیت لایسنس این نصب</h2>
    <p class="lead">اینجا فقط وضعیت لایسنس فروشگاه شماست — ساخت سریال برای دیگران فقط روی سایت فروشنده است.</p>

    <div class="accept-row accept-row-2" style="margin-top:10px;">
        <div class="panel" style="margin:0;background:#f8fafc;">
            <div class="muted" style="font-size:12px;">پلن</div>
            <div style="font-weight:800;font-size:18px;">{{ $license['plan_text'] ?? '—' }}</div>
        </div>
        <div class="panel" style="margin:0;background:{{ $expired ? '#fff1f2' : ($soon ? '#fff7ed' : '#f0fdf4') }};">
            <div class="muted" style="font-size:12px;">روز باقی‌مانده</div>
            <div style="font-weight:800;font-size:18px;">
                @if(!empty($license['lifetime']))
                    مادام‌العمر
                @elseif(is_int($days))
                    @if($expired)
                        منقضی ({{ abs($days) }} روز گذشته)
                    @else
                        {{ $days }} روز
                    @endif
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    <div style="margin-top:12px;line-height:1.9;font-size:14px;">
        @if(!empty($license['domain']))
            <div>دامنه: <strong dir="ltr">{{ $license['domain'] }}</strong></div>
        @endif
        @if(!empty($license['activated_jalali']))
            <div>شروع اعتبار: {{ $license['activated_jalali'] }}</div>
        @endif
        @if(!empty($license['expires_jalali']))
            <div>پایان اعتبار: {{ $license['expires_jalali'] }}</div>
        @elseif(!empty($license['lifetime']))
            <div>پایان اعتبار: مادام‌العمر</div>
        @endif
        @if(!empty($license['price_toman']))
            <div>مبلغ پلن: {{ number_format((int) $license['price_toman']) }} تومان</div>
        @endif
    </div>

    <div class="actions" style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;">
        <form method="POST" action="{{ route('my-license.refresh') }}">
            @csrf
            <button class="btn btn-secondary" type="submit">بروزرسانی وضعیت از سرور</button>
        </form>
        @if(Route::has('system-tools.updates') && auth()->user()?->canAccess('system.tools'))
            <a class="btn btn-primary" href="{{ route('system-tools.updates') }}">آپدیت نرم‌افزار</a>
        @endif
    </div>
</div>

<div id="renew" class="panel" style="margin-top:14px;{{ ($focus ?? '') === 'renew' ? 'border-color:#f59e0b;' : '' }}">
    <h3 style="margin-top:0;">تمدید لایسنس</h3>
    <p style="margin:0 0 8px;line-height:1.8;">
        برای تمدید، با فروشنده هماهنگ کنید تا لایسنس این دامنه را تمدید کند.
        پس از تمدید، دکمه «بروزرسانی وضعیت از سرور» را بزنید تا روز باقی‌مانده به‌روز شود.
    </p>
    @if(!empty($officePhone))
        <div>تماس فروشنده: <strong dir="ltr">{{ $officePhone }}</strong></div>
    @endif
</div>
@endsection
