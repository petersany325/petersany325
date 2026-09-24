@extends('layouts.app')
@section('title', 'کالای خرج‌شده | '.shop_name())
@section('page_title', 'گزارش کالاهای خرج‌شده')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">کالا/قطعات خرج‌شده در تاریخ</h2>
    <p class="muted" style="margin:0;">فروش روی قبض در کنار بهای خرید کارت کالا. برای بیلان کامل‌تر:
        <a href="{{ route('reports.parts-bilans', request()->only(['from','to','period'])) }}">گزارش بیلان قطعه تعمیر</a>
    </p>
</div>

@isset($totals)
<div class="stats stats-compact" style="margin-bottom:12px;">
    <div class="stat"><div class="label">تعداد</div><div class="value">{{ number_format($totals['qty']) }}</div></div>
    <div class="stat"><div class="label">فروش</div><div class="value">{{ number_format($totals['sale']) }}</div></div>
    <div class="stat"><div class="label">بهای خرید</div><div class="value">{{ number_format($totals['purchase']) }}</div></div>
    <div class="stat"><div class="label">سود</div><div class="value">{{ number_format($totals['profit']) }}</div></div>
</div>
@endisset

@if(\App\Support\ReportSettings::showCharts())
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', ['id'=>'chartParts','title'=>'۱۰ قطعه پرمصرف','labels'=>$chartPartLabels,'values'=>$chartPartValues])
</div>
@endif

<div class="panel">
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>نام قطعه</th>
                <th>تعداد مصرف</th>
                <th>فروش</th>
                <th>بهای خرید</th>
                <th>سود</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->part_name }}</td>
                    <td>{{ $row->qty }}</td>
                    <td>{{ toman((int) ($row->sale_amount ?? $row->amount ?? 0)) }}</td>
                    <td>{{ toman((int) ($row->purchase_cost ?? 0)) }}</td>
                    <td>{{ toman((int) ($row->profit ?? 0)) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">در این بازه مصرفی ثبت نشده.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@include('reports._charts-boot')
