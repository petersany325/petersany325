@extends('layouts.app')

@section('title', 'میز کار | '.shop_name())
@section('page_title', 'میز کار')
@section('window_title', 'شورت‌کارت‌های کارتابل')

@section('content')
@php
    $shortcuts = \App\Support\NavMenu::shortcuts(auth()->user());
    $menuGroups = \App\Support\NavMenu::forUser(auth()->user());
    $reception = collect($menuGroups)->firstWhere('key', 'reception');
@endphp

@php $licenseStatus = \App\Support\LicenseStatus::current(); @endphp
<div class="shortcut-desk">
    <div class="shortcut-top">
        <div class="stats stats-compact">
            <div class="stat"><div class="label">کارهای باز</div><div class="value">{{ $openCount }}</div></div>
            <div class="stat"><div class="label">آماده تحویل</div><div class="value">{{ $readyCount }}</div></div>
            <div class="stat"><div class="label">دریافتی امروز</div><div class="value">{{ number_format($todayIncome) }}</div></div>
            <div class="stat"><div class="label">کم‌موجود</div><div class="value">{{ $lowStockCount }}</div></div>
        </div>
        @if(!empty($licenseStatus['enabled']))
            <div class="panel" style="margin:10px 0 0;padding:12px 14px;">
                <div style="font-weight:800;margin-bottom:4px;">لایسنس نصب</div>
                <div style="font-size:13px;line-height:1.8;">
                    <div>پلن خریداری‌شده: <strong>{{ $licenseStatus['plan_text'] }}</strong>
                        @if(!empty($licenseStatus['plan_months']))
                            ({{ $licenseStatus['plan_months'] }} ماه)
                        @endif
                    </div>
                    @if(!empty($licenseStatus['activated_jalali']))
                        <div>شروع اعتبار: {{ $licenseStatus['activated_jalali'] }}</div>
                    @endif
                    @if(!empty($licenseStatus['expires_jalali']))
                        <div>پایان اعتبار: {{ $licenseStatus['expires_jalali'] }}</div>
                    @elseif(!empty($licenseStatus['lifetime']))
                        <div>پایان اعتبار: مادام‌العمر</div>
                    @endif
                    @if(!empty($licenseStatus['price_toman']))
                        <div>مبلغ پلن: {{ number_format((int) $licenseStatus['price_toman']) }} تومان</div>
                    @endif
                </div>
            </div>
        @endif
        <p class="lead shortcut-hint">شورت‌کارت‌ها و منوی بالا با زیرمنوی هر بخش — جمع‌وجور مثل میز کار ویندوز.</p>
    </div>

    @if($reception && count($reception['children']))
        <div class="shortcut-section">
            <div class="shortcut-section-title">پذیرش سریع</div>
            <div class="shortcut-grid shortcut-grid-sm">
                @foreach($reception['children'] as $child)
                    <a href="{{ route($child['route']) }}" class="shortcut-card tone-blue">
                        <span class="shortcut-icon">{{ $child['mark'] }}</span>
                        <span class="shortcut-text">
                            <strong>{{ $child['label'] }}</strong>
                            <small>{{ $child['hint'] ?: 'پذیرش' }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="shortcut-section">
        <div class="shortcut-section-title">همه بخش‌ها</div>
        <div class="shortcut-grid">
            @foreach($shortcuts as $card)
                <a href="{{ route($card['route']) }}" class="shortcut-card tone-{{ $card['tone'] }}">
                    <span class="shortcut-icon">{{ $card['mark'] }}</span>
                    <span class="shortcut-text">
                        <strong>{{ $card['label'] }}</strong>
                        <small>{{ $card['hint'] }}</small>
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="panel compact-panel" style="margin-top:4px;">
        <div class="compact-head" style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
            <h3 style="margin:0;font-size:13px;">آخرین قبض‌ها</h3>
            @if(auth()->user()->canAccess('receptions'))
                <div class="actions" style="margin:0;">
                    <a class="btn btn-primary" href="{{ route('receptions.create') }}">پذیرش جدید</a>
                    <a class="btn btn-ghost" href="{{ route('receptions.index') }}">همه</a>
                </div>
            @endif
        </div>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>شماره</th>
                    <th>مشتری</th>
                    <th>کالا</th>
                    <th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentReceptions as $item)
                    <tr>
                        <td><a href="{{ route('receptions.show', $item) }}">{{ $item->ticket_no }}</a></td>
                        <td>{{ $item->customer?->name }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td><span class="badge badge-{{ $item->status }}">{{ $item->statusLabel() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4">هنوز قبضی ثبت نشده.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
