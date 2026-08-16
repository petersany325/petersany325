@extends('layouts.app')
@section('title', 'گزارش خروج | '.shop_name())
@section('page_title', 'گزارش خروج قبض و کالا')
@section('window_title', 'خروج روز / هفته — تحویل و قطعه')

@section('content')
@include('reports._settings')

@php
    $periodLabels = \App\Support\ReportSettings::periodLabels();
    $quick = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_30'];
@endphp

<div class="panel" style="margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
        <div>
            <h2 style="margin-top:0;">گزارش خروج</h2>
            <p class="muted" style="margin:0;">
                تحویل قبض‌ها + خروج قطعه انبار —
                از {{ jalali_date($from) }} تا {{ jalali_date($to) }}
                @if(!empty($period) && ($periodLabels[$period] ?? null))
                    ({{ $periodLabels[$period] }})
                @endif
            </p>
        </div>
        <div class="actions" style="margin:0;flex-wrap:wrap;">
            @foreach($quick as $key)
                <form method="POST" action="{{ route('reports.settings') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ url()->current() }}">
                    <input type="hidden" name="period" value="{{ $key }}">
                    <input type="hidden" name="chart_type" value="{{ \App\Support\ReportSettings::chartType() }}">
                    <input type="hidden" name="show_charts" value="{{ \App\Support\ReportSettings::showCharts() ? '1' : '0' }}">
                    <button class="btn {{ ($period ?? '') === $key ? 'btn-primary' : 'btn-ghost' }} btn-sm" type="submit">{{ $periodLabels[$key] }}</button>
                </form>
            @endforeach
        </div>
    </div>

    <div class="stats stats-compact" style="margin-top:12px;">
        <div class="stat"><div class="label">تعداد خروج قبض</div><div class="value">{{ number_format($exitTotals['count'] ?? 0) }}</div></div>
        <div class="stat"><div class="label">جمع اجرت خروج</div><div class="value">{{ toman((int) ($exitTotals['labor'] ?? 0)) }}</div></div>
        <div class="stat"><div class="label">جمع قطعات روی قبض</div><div class="value">{{ toman((int) ($exitTotals['parts'] ?? 0)) }}</div></div>
        <div class="stat"><div class="label">جمع مبلغ خروج</div><div class="value">{{ toman((int) ($exitTotals['total'] ?? 0)) }}</div></div>
        <div class="stat"><div class="label">پرداخت‌شده</div><div class="value">{{ toman((int) ($exitTotals['paid'] ?? 0)) }}</div></div>
    </div>
</div>

@if(\App\Support\ReportSettings::showCharts() && (!empty($chartExitLabels) || !empty($chartPartLabels)))
<div class="report-charts-row" style="margin-bottom:12px;">
    @if(!empty($chartExitLabels))
        @include('reports._chart', ['id'=>'chartExitDaily','title'=>'مبلغ خروج قبض روزانه','labels'=>$chartExitLabels,'values'=>$chartExitValues,'type'=>'bar'])
    @endif
    @if(!empty($chartPartLabels))
        @include('reports._chart', ['id'=>'chartParts','title'=>'قطعات انبار پرمصرف','labels'=>$chartPartLabels,'values'=>$chartPartValues])
    @endif
</div>
@endif

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin-top:0;">خروج قبض‌های تحویل‌شده</h3>
    <p class="muted" style="margin-top:0;">همین جدول مبلغ خروج دستگاه/قبض را نشان می‌دهد (اجرت + قطعات ثبت‌شده روی قبض).</p>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>زمان خروج</th>
                <th>قبض</th>
                <th>مشتری</th>
                <th>تعمیرکار</th>
                <th>اجرت</th>
                <th>قطعات</th>
                <th>جمع</th>
                <th>پرداخت</th>
            </tr>
            </thead>
            <tbody>
            @forelse($exits as $r)
                <tr>
                    <td dir="ltr">{{ jalali_like($r->delivered_at) }}</td>
                    <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                    <td>{{ $r->customer?->name ?: '—' }}</td>
                    <td>{{ $r->technician?->name ?: '—' }}</td>
                    <td>{{ toman((int) $r->labor_cost) }}</td>
                    <td>{{ toman((int) $r->parts_cost) }}</td>
                    <td><strong>{{ toman((int) $r->total_amount) }}</strong></td>
                    <td>{{ toman((int) $r->paid_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">در این بازه خروجی (تحویل قبض) ثبت نشده.</td></tr>
            @endforelse
            </tbody>
            @if($exits->isNotEmpty())
                <tfoot>
                <tr>
                    <th colspan="4">جمع {{ number_format($exitTotals['count']) }} قبض</th>
                    <th>{{ toman((int) $exitTotals['labor']) }}</th>
                    <th>{{ toman((int) $exitTotals['parts']) }}</th>
                    <th>{{ toman((int) $exitTotals['total']) }}</th>
                    <th>{{ toman((int) $exitTotals['paid']) }}</th>
                </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">خروج قطعه از انبار</h3>
        <div class="stats stats-compact" style="margin-bottom:10px;">
            <div class="stat"><div class="label">سند</div><div class="value">{{ number_format($stockTotals['docs'] ?? 0) }}</div></div>
            <div class="stat"><div class="label">تعداد</div><div class="value">{{ number_format($stockTotals['qty'] ?? 0) }}</div></div>
            <div class="stat"><div class="label">مبلغ</div><div class="value">{{ toman((int) ($stockTotals['amount'] ?? 0)) }}</div></div>
        </div>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>نام قطعه</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->part_name }}</td>
                        <td>{{ number_format((int) $row->qty) }}</td>
                        <td>{{ toman((int) $row->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">در این بازه خروج انبار ثبت نشده.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">جزئیات سند انبار</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr><th>زمان</th><th>قطعه</th><th>تعداد</th><th>مبلغ</th><th>قبض</th></tr>
                </thead>
                <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td dir="ltr">{{ jalali_like($m->created_at) }}</td>
                        <td>{{ $m->part?->name ?: ('#'.$m->part_id) }}</td>
                        <td>{{ number_format(abs((int) $m->quantity)) }}</td>
                        <td>{{ toman(abs((int) $m->total_cost)) }}</td>
                        <td>
                            @if($m->reception)
                                <a href="{{ route('receptions.show', $m->reception) }}">{{ $m->reception->ticket_no }}</a>
                            @else —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">سندی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@include('reports._charts-boot')
