@extends('layouts.app')
@section('title', 'عملکرد تعمیرکاران | '.shop_name())
@section('page_title', 'گزارش عملکرد تعمیرکاران')
@section('window_title', 'جستجو و پرونده تعمیرکار')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">عملکرد تعمیرکاران</h2>
    <p class="muted">نام تعمیرکار را جستجو کنید یا روی پرونده بزنید تا کارهای دوره، قطعات، کمیسیون و دستگاه‌های دست تعمیر را ببینید.</p>
    <form class="search-row no-print" method="GET" action="{{ route('reports.technicians') }}">
        <input type="search" name="q" value="{{ $q }}" placeholder="نام / تخصص / موبایل" style="min-width:240px;">
        <button class="btn btn-primary" type="submit">جستجو</button>
        @if($q !== '')
            <a class="btn btn-ghost" href="{{ route('reports.technicians') }}">پاک</a>
        @endif
    </form>
</div>

@if(\App\Support\ReportSettings::showCharts())
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', [
        'id' => 'chartTechJobs',
        'title' => 'تعداد کار در بازه',
        'labels' => $chartLabels,
        'values' => $chartJobs,
    ])
    @include('reports._chart', [
        'id' => 'chartTechLabor',
        'title' => 'جمع اجرت تحویل‌شده',
        'labels' => $chartLabels,
        'values' => $chartLabor,
    ])
</div>
@endif

<div class="panel">
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>تعمیرکار</th>
                <th>تخصص</th>
                <th>کل کار</th>
                <th>تحویل‌شده</th>
                <th>دست تعمیر</th>
                <th>جمع اجرت</th>
                <th>جمع قطعات</th>
                <th>جمع خروج‌نخورده</th>
                <th>کمیسیون%</th>
                <th>مبلغ کمیسیون</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td><strong>{{ $row->name }}</strong><div class="muted" dir="ltr">{{ $row->phone }}</div></td>
                    <td>{{ $row->specialty ?: '—' }}</td>
                    <td>{{ $row->jobs_count }}</td>
                    <td>{{ $row->delivered_count }}</td>
                    <td>{{ $row->in_hand_count }}</td>
                    <td>{{ toman((int) ($row->labor_sum ?? 0)) }}</td>
                    <td>{{ toman((int) ($row->parts_sum ?? 0)) }}</td>
                    <td>
                        <strong>{{ toman((int) ($row->pending_exit_sum ?? 0)) }}</strong>
                        <div class="muted" style="font-size:10px;">آماده تحویل</div>
                    </td>
                    <td>{{ $row->commission_percent }}٪</td>
                    <td>{{ toman($row->commission_sum ?? 0) }}</td>
                    <td><a class="btn btn-primary" href="{{ route('reports.technicians.show', $row) }}">پرونده عملکرد</a></td>
                </tr>
            @empty
                <tr><td colspan="11">تعمیرکاری یافت نشد.</td></tr>
            @endforelse
            </tbody>
            @if($rows->isNotEmpty())
                <tfoot>
                <tr>
                    <th colspan="5">جمع کل</th>
                    <th>{{ toman((int) ($totals['labor'] ?? 0)) }}</th>
                    <th>{{ toman((int) ($totals['parts'] ?? 0)) }}</th>
                    <th>{{ toman((int) ($totals['pending_exit'] ?? 0)) }}</th>
                    <th></th>
                    <th>{{ toman((int) ($totals['commission'] ?? 0)) }}</th>
                    <th></th>
                </tr>
                </tfoot>
            @endif
        </table>
    </div>
    <p class="muted" style="margin:8px 0 0;font-size:11px;">
        «جمع خروج‌نخورده» = اجرت + قطعات قبض‌هایی که وضعیت‌شان الان «آماده تحویل» است و هنوز خروج نخورده‌اند.
    </p>
</div>
@endsection

@include('reports._charts-boot')
