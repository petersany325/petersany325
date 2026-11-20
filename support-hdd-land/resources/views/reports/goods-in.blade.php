@extends('layouts.app')
@section('title', 'ورودی کالا | '.shop_name())
@section('page_title', 'گزارش ورودی کالا')
@section('window_title', 'پذیرش‌های بازه')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">گزارش ورودی کالا</h2>
    <p class="muted" style="margin:0 0 10px;">قبض‌هایی که در بازه {{ jalali_date($from) }} تا {{ jalali_date($to) }} پذیرش شده‌اند.</p>
    @include('reports._period-quick')
    <div class="stats stats-compact">
        <div class="stat"><div class="label">تعداد ورودی</div><div class="value">{{ number_format($totals['count']) }}</div></div>
        <div class="stat"><div class="label">بیعانه</div><div class="value">{{ toman((int) $totals['deposit']) }}</div></div>
        <div class="stat"><div class="label">جمع اجرت</div><div class="value">{{ toman((int) $totals['labor']) }}</div></div>
        <div class="stat"><div class="label">جمع مبلغ</div><div class="value">{{ toman((int) $totals['total']) }}</div></div>
        <div class="stat"><div class="label">پرداخت‌شده</div><div class="value">{{ toman((int) $totals['paid']) }}</div></div>
    </div>
</div>

@if($byService->isNotEmpty())
<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin-top:0;">تفکیک نوع خدمات</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead><tr><th>نوع خدمات</th><th>تعداد</th></tr></thead>
            <tbody>
            @foreach($byService as $name => $c)
                <tr><td>{{ $name ?: '—' }}</td><td>{{ number_format((int) $c) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="panel">
    @include('reports._goods-table', ['rows' => $rows, 'dateField' => 'received_at'])
</div>
@endsection
