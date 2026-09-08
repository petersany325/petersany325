@extends('layouts.app')
@section('title', 'تمدید مجدد لایسنس | '.shop_name())
@section('page_title', 'تمدید مجدد لایسنس')
@section('window_title', 'تمدید لایسنس')

@section('content')
@php
    $lic = $license;
    $buyUrl = $lic['purchase_url'] ?? 'https://hdd-land.ir';
@endphp
<div class="panel">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;">تمدید مجدد</h2>
            <p class="muted" style="margin:6px 0 0;">پس از خرید تمدید، اعتبار همین نصب از طرف سرزمین هارد به‌روز می‌شود.</p>
        </div>
        <a class="btn" href="{{ route('licenses.status') }}">← اطلاعات فعال‌سازی</a>
    </div>

    <div class="accept-row accept-row-3" style="margin-bottom:16px;">
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted">پلن فعلی</div>
            <div style="font-weight:800;">{{ $lic['plan_text'] ?? '—' }}</div>
            @if(!empty($lic['plan_months']))
                <div class="muted">{{ $lic['plan_months'] }} ماهه</div>
            @endif
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted">مبلغ پلن فعلی</div>
            <div style="font-weight:800;">{{ $lic['price_label'] ?? '—' }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted">پایان اعتبار</div>
            <div style="font-weight:800;">
                @if(!empty($lic['lifetime']))
                    مادام‌العمر
                @else
                    {{ $lic['expires_jalali'] ?? '—' }}
                @endif
            </div>
        </div>
    </div>

    <div class="panel" style="background:#f7fafc;">
        <h3 style="margin-top:0;">خرید تمدید</h3>
        <p class="muted">از فروشگاه سرزمین هارد پلن جدید بخرید یا برای تمدید همین سریال هماهنگ کنید. بعد از ثبت تمدید، مبلغ و تاریخ پایان در صفحه «اطلاعات فعال‌سازی» دیده می‌شود.</p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
            <a class="btn btn-primary" href="{{ $buyUrl }}" target="_blank" rel="noopener">خرید / تمدید از سرزمین هارد</a>
            <a class="btn" href="{{ route('licenses.status') }}">مشاهده وضعیت فعلی</a>
        </div>
    </div>
</div>
@endsection
