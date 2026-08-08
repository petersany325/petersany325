@extends('layouts.app')
@section('title', 'گزارش عملیات کارگاه | سرزمین هارد')
@section('page_title', 'گزارش عملیات / وضعیت قبض‌ها')
@section('window_title', 'پذیرش، تحویل، WIP و درآمد دوره')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">عملیات کارگاه</h2>
    <p class="muted" style="margin-top:0;">خلاصه پذیرش، تحویل، کارهای باز و درآمد قبض‌های تحویل‌شده در بازه انتخابی.</p>
    <div class="stats stats-compact">
        <div class="stat"><div class="label">پذیرش دوره</div><div class="value">{{ number_format($intake) }}</div></div>
        <div class="stat"><div class="label">تحویل دوره</div><div class="value">{{ number_format($delivered) }}</div></div>
        <div class="stat"><div class="label">باز فعلی</div><div class="value">{{ number_format($openNow) }}</div></div>
        <div class="stat"><div class="label">آمادهٔ بدهکار</div><div class="value">{{ number_format($readyUnpaid) }}</div></div>
        <div class="stat"><div class="label">منتظر قطعه</div><div class="value">{{ number_format($waitingPart) }}</div></div>
        <div class="stat"><div class="label">میانگین روز تعمیر</div><div class="value">{{ $avgDays !== null ? number_format((float) $avgDays, 1) : '—' }}</div></div>
    </div>
</div>

@if(\App\Support\ReportSettings::showCharts())
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', ['id'=>'chartOpsStatus','title'=>'توزیع وضعیت','labels'=>$chartStatusLabels,'values'=>$chartStatusValues,'type'=>'doughnut'])
    @include('reports._chart', ['id'=>'chartOpsDaily','title'=>'پذیرش روزانه','labels'=>jalali_day_labels($daily->pluck('day')->values()->all()),'values'=>$daily->pluck('total')->map(fn($v)=>(int)$v)->values()->all()])
</div>
@endif

<div class="split-2">
    <div class="panel">
        <h3 style="margin-top:0;">توزیع وضعیت (پذیرش‌های دوره)</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>وضعیت</th><th>تعداد</th></tr></thead>
                <tbody>
                @forelse($byStatus as $status => $total)
                    <tr>
                        <td>{{ $statusLabels[$status] ?? $status }}</td>
                        <td>{{ number_format($total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">در این بازه قبضی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">درآمد قبض‌های تحویل‌شده</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <tbody>
                <tr><td>جمع کل</td><td>{{ toman((int) ($revenue->total ?? 0)) }}</td></tr>
                <tr><td>اجرت</td><td>{{ toman((int) ($revenue->labor ?? 0)) }}</td></tr>
                <tr><td>قطعات</td><td>{{ toman((int) ($revenue->parts ?? 0)) }}</td></tr>
                <tr><td>تخفیف</td><td>{{ toman((int) ($revenue->discount ?? 0)) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@include('reports._charts-boot')
