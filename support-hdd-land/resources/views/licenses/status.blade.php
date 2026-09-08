@extends('layouts.app')
@section('title', 'اطلاعات فعال‌سازی لایسنس | '.shop_name())
@section('page_title', 'اطلاعات فعال‌سازی لایسنس')
@section('window_title', 'لایسنس')

@section('content')
@php
    $lic = $license;
@endphp
<div class="panel">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;">فعال‌سازی لایسنس</h2>
            <p class="muted" style="margin:6px 0 0;">پلن خریداری‌شده، مبلغ، مدت و تاریخ اعتبار این نصب</p>
        </div>
        <a class="btn btn-primary" href="{{ route('licenses.renewal') }}">تمدید مجدد</a>
    </div>

    <div class="accept-row accept-row-4" style="margin-bottom:16px;">
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">پلن / مدت</div>
            <div style="font-size:20px;font-weight:800;">{{ $lic['plan_text'] ?? 'نامشخص' }}</div>
            @if(!empty($lic['plan_months']))
                <div class="muted" style="margin-top:4px;">{{ $lic['plan_months'] }} ماه</div>
            @endif
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">مبلغ خرید</div>
            <div style="font-size:20px;font-weight:800;">{{ $lic['price_label'] ?? '—' }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">شروع اعتبار</div>
            <div style="font-size:18px;font-weight:800;">{{ $lic['activated_jalali'] ?? '—' }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted" style="font-size:12px;">پایان اعتبار</div>
            <div style="font-size:18px;font-weight:800;">
                @if(!empty($lic['lifetime']))
                    مادام‌العمر
                @else
                    {{ $lic['expires_jalali'] ?? '—' }}
                @endif
            </div>
            @if(isset($lic['remaining_days']) && $lic['remaining_days'] !== null && empty($lic['lifetime']))
                <div class="muted" style="margin-top:4px;color:{{ !empty($lic['expired']) ? '#b42318' : '#334' }};">
                    @if(!empty($lic['expired']))
                        منقضی‌شده ({{ abs((int) $lic['remaining_days']) }} روز پیش)
                    @else
                        {{ (int) $lic['remaining_days'] }} روز مانده
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="panel" style="background:#f7fafc;">
        <h3 style="margin-top:0;">جزئیات سریال</h3>
        <div style="font-size:14px;line-height:2;">
            <div>سریال: <strong dir="ltr">{{ $lic['masked_key'] ?? '—' }}</strong></div>
            <div>دامنه قفل‌شده: <strong dir="ltr">{{ !empty($lic['domain']) ? $lic['domain'] : '—' }}</strong></div>
            @if(!empty($lic['checked_at']))
                <div class="muted">آخرین بررسی: {{ jalali_date($lic['checked_at']) }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
