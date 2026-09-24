@extends('layouts.app')
@section('title', 'کالای خرج‌شده | '.shop_name())
@section('page_title', 'گزارش کالاهای خرج‌شده')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">کالا/قطعات خرج‌شده در تاریخ</h2>
</div>

@if(\App\Support\ReportSettings::showCharts())
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
                    <td>{{ $row->qty }}</td>
                    <td>{{ toman($row->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">در این بازه مصرفی ثبت نشده.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@include('reports._charts-boot')
