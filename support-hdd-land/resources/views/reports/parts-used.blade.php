@extends('layouts.app')
@section('title', 'کالای خرج‌شده | '.shop_name())
@section('page_title', 'گزارش کالاهای خرج‌شده')
@section('window_title', 'خروج انبار و مصرف روی قبض')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">کالا/قطعات خرج‌شده در تاریخ</h2>
    <p class="muted" style="margin:0;">خروج‌های انبار (حواله و مصرف روی قبض) در بازه {{ jalali_date($from) }} تا {{ jalali_date($to) }}.</p>
    <div class="stats stats-compact" style="margin-top:10px;">
        <div class="stat"><div class="label">اقلام</div><div class="value">{{ number_format($totals['lines'] ?? 0) }}</div></div>
        <div class="stat"><div class="label">جمع تعداد</div><div class="value">{{ number_format($totals['qty'] ?? 0) }}</div></div>
        <div class="stat"><div class="label">جمع مبلغ</div><div class="value">{{ toman((int) ($totals['amount'] ?? 0)) }}</div></div>
    </div>
</div>

@if(\App\Support\ReportSettings::showCharts() && !empty($chartPartLabels))
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', ['id'=>'chartParts','title'=>'۱۰ قطعه پرمصرف','labels'=>$chartPartLabels,'values'=>$chartPartValues])
</div>
@endif

<div class="panel">
    <div class="table-wrap">
        <table class="compact-table">
            <thead><tr><th>نام قطعه</th><th>تعداد مصرف</th><th>مبلغ</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->part_name }}</td>
                    <td>{{ number_format((int) $row->qty) }}</td>
                    <td>{{ toman((int) $row->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">در این بازه خروجی از انبار ثبت نشده. بازه را روی «این ماه» بگذارید یا از حواله خروج / ثبت قطعه روی قبض استفاده کنید.</td></tr>
            @endforelse
            </tbody>
            @if($rows->isNotEmpty())
                <tfoot>
                <tr>
                    <th>جمع</th>
                    <th>{{ number_format($totals['qty'] ?? 0) }}</th>
                    <th>{{ toman((int) ($totals['amount'] ?? 0)) }}</th>
                </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
@include('reports._charts-boot')
