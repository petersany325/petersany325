@extends('layouts.app')
@section('title', 'عملکرد تعمیرکاران | '.shop_name())
@section('page_title', 'گزارش عملکرد تعمیرکاران')
@section('window_title', 'تفکیک اجرت، قطعه، خروج‌نخورده و مانده کارگاه')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">عملکرد تعمیرکاران</h2>
    <p class="muted">تفکیک کارکرد ماهانه: خرجی کالا، مبلغ تعمیر تحویل‌شده، خروج‌نخورده، مانده کارگاه، غیرقابل‌تعمیر (خروج‌شده / نشده).</p>
    <form class="search-row" method="GET" action="{{ route('reports.technicians') }}">
        <input type="search" name="q" value="{{ $q }}" placeholder="نام / تخصص / موبایل" style="min-width:240px;">
        <button class="btn btn-primary" type="submit">جستجو</button>
        @if($q !== '')
            <a class="btn btn-ghost" href="{{ route('reports.technicians') }}">پاک</a>
        @endif
    </form>
</div>

@if(!empty($totals))
<div class="stats stats-compact" style="margin-bottom:12px;">
    <div class="stat"><div class="label">اجرت تحویل</div><div class="value">{{ number_format($totals['labor'] ?? 0) }}</div></div>
    <div class="stat"><div class="label">قطعات تحویل</div><div class="value">{{ number_format($totals['parts'] ?? 0) }}</div></div>
    <div class="stat"><div class="label">جمع قبض تحویل</div><div class="value">{{ number_format($totals['billed'] ?? 0) }}</div></div>
    <div class="stat"><div class="label">وصول روی قبض</div><div class="value">{{ number_format($totals['paid'] ?? 0) }}</div></div>
    <div class="stat"><div class="label">خروج‌نخورده</div><div class="value">{{ number_format($totals['pending_exit_count'] ?? 0) }}<div class="muted" style="font-size:11px;">{{ toman((int) ($totals['pending_exit'] ?? 0)) }}</div></div></div>
    <div class="stat"><div class="label">مانده کارگاه</div><div class="value">{{ number_format($totals['workshop_count'] ?? 0) }}<div class="muted" style="font-size:11px;">{{ toman((int) ($totals['workshop'] ?? 0)) }}</div></div></div>
    <div class="stat"><div class="label">غیرقابل‌تعمیر باز</div><div class="value">{{ number_format($totals['unrepairable_open'] ?? 0) }}</div></div>
    <div class="stat"><div class="label">غیرقابل‌تعمیر خروج</div><div class="value">{{ number_format($totals['unrepairable_exited'] ?? 0) }}</div></div>
</div>
@endif

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
                <th>کل کار</th>
                <th>تحویل</th>
                <th>اجرت</th>
                <th>خرجی کالا</th>
                <th>جمع قبض / وصول</th>
                <th>خروج‌نخورده</th>
                <th>مانده کارگاه</th>
                <th>غیرقابل‌تعمیر</th>
                <th>دست تعمیر</th>
                <th>کمیسیون</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row->name }}</strong>
                        <div class="muted" dir="ltr">{{ $row->phone }}</div>
                        <div class="muted" style="font-size:11px;">{{ $row->specialty ?: '—' }}</div>
                    </td>
                    <td>{{ $row->jobs_count }}</td>
                    <td>{{ $row->delivered_count }}</td>
                    <td>{{ toman((int) ($row->labor_sum ?? 0)) }}</td>
                    <td>{{ toman((int) ($row->parts_sum ?? 0)) }}</td>
                    <td>
                        {{ toman((int) ($row->billed_sum ?? 0)) }}
                        <div class="muted" style="font-size:11px;">وصول {{ toman((int) ($row->paid_sum ?? 0)) }}</div>
                    </td>
                    <td>
                        <strong>{{ number_format((int) ($row->pending_exit_count ?? 0)) }}</strong>
                        <div class="muted" style="font-size:10px;">{{ toman((int) ($row->pending_exit_sum ?? 0)) }}</div>
                    </td>
                    <td>
                        <strong>{{ number_format((int) ($row->workshop_count ?? 0)) }}</strong>
                        <div class="muted" style="font-size:10px;">{{ toman((int) ($row->workshop_sum ?? 0)) }}</div>
                    </td>
                    <td>
                        باز {{ number_format((int) ($row->unrepairable_open ?? 0)) }}
                        <div class="muted" style="font-size:10px;">خروج {{ number_format((int) ($row->unrepairable_exited ?? 0)) }}</div>
                    </td>
                    <td>{{ $row->in_hand_count }}</td>
                    <td>
                        {{ $row->commission_percent }}٪
                        <div class="muted" style="font-size:10px;">{{ toman($row->commission_sum ?? 0) }}</div>
                    </td>
                    <td><a class="btn btn-primary" href="{{ route('reports.technicians.show', $row) }}">پرونده</a></td>
                </tr>
            @empty
                <tr><td colspan="12">تعمیرکاری یافت نشد.</td></tr>
            @endforelse
            </tbody>
            @if($rows->isNotEmpty() && !empty($totals))
                <tfoot>
                <tr>
                    <th>جمع</th>
                    <th></th>
                    <th></th>
                    <th>{{ toman((int) ($totals['labor'] ?? 0)) }}</th>
                    <th>{{ toman((int) ($totals['parts'] ?? 0)) }}</th>
                    <th>{{ toman((int) ($totals['billed'] ?? 0)) }}</th>
                    <th>{{ toman((int) ($totals['pending_exit'] ?? 0)) }}</th>
                    <th>{{ toman((int) ($totals['workshop'] ?? 0)) }}</th>
                    <th>{{ number_format($totals['unrepairable_open'] ?? 0) }} / {{ number_format($totals['unrepairable_exited'] ?? 0) }}</th>
                    <th></th>
                    <th>{{ toman((int) ($totals['commission'] ?? 0)) }}</th>
                    <th></th>
                </tr>
                </tfoot>
            @endif
        </table>
    </div>
    <p class="muted" style="margin:8px 0 0;font-size:11px;">
        خروج‌نخورده = وضعیت «آماده تحویل». مانده کارگاه = پذیرش / در حال تعمیر / منتظر قطعه.
        غیرقابل‌تعمیر باز = هنوز خروج نشده؛ خروج = قبض‌هایی که مسیر غیرقابل‌تعمیر داشته و در بازه تحویل شده‌اند.
    </p>
</div>
@endsection

@include('reports._charts-boot')
