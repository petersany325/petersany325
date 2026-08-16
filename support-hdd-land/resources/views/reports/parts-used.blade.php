@extends('layouts.app')
@section('title', 'کالای خرج‌شده | '.shop_name())
@section('page_title', 'گزارش کالاهای خرج‌شده')
@section('window_title', 'خروج انبار روز / هفته / ماه')

@section('content')
@include('reports._settings')

@php
    $periodLabels = \App\Support\ReportSettings::periodLabels();
    $quick = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_30'];
@endphp

<div class="panel" style="margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
        <div>
            <h2 style="margin-top:0;">کالا/قطعات خرج‌شده</h2>
            <p class="muted" style="margin:0;">
                خروج انبار (مصرف روی قبض + حواله خروج) —
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
        <div class="stat"><div class="label">تعداد سند خروج</div><div class="value">{{ number_format($totals['docs'] ?? 0) }}</div></div>
        <div class="stat"><div class="label">اقلام</div><div class="value">{{ number_format($totals['lines'] ?? 0) }}</div></div>
        <div class="stat"><div class="label">جمع تعداد</div><div class="value">{{ number_format($totals['qty'] ?? 0) }}</div></div>
        <div class="stat"><div class="label">جمع مبلغ</div><div class="value">{{ toman((int) ($totals['amount'] ?? 0)) }}</div></div>
    </div>
</div>

@if(\App\Support\ReportSettings::showCharts() && (!empty($chartPartLabels) || !empty($chartDailyLabels)))
<div class="report-charts-row" style="margin-bottom:12px;">
    @if(!empty($chartPartLabels))
        @include('reports._chart', ['id'=>'chartParts','title'=>'۱۰ قطعه پرمصرف','labels'=>$chartPartLabels,'values'=>$chartPartValues])
    @endif
    @if(!empty($chartDailyLabels))
        @include('reports._chart', ['id'=>'chartPartsDaily','title'=>'خروج روزانه','labels'=>$chartDailyLabels,'values'=>$chartDailyValues,'type'=>'line'])
    @endif
</div>
@endif

<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">جمع به تفکیک قطعه</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>نام قطعه</th><th>تعداد</th><th>مبلغ</th><th>سند</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->part_name }}</td>
                        <td>{{ number_format((int) $row->qty) }}</td>
                        <td>{{ toman((int) $row->amount) }}</td>
                        <td>{{ number_format((int) ($row->docs ?? 0)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">در این بازه خروجی ثبت نشده.</td></tr>
                @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                    <tfoot>
                    <tr>
                        <th>جمع</th>
                        <th>{{ number_format($totals['qty'] ?? 0) }}</th>
                        <th>{{ toman((int) ($totals['amount'] ?? 0)) }}</th>
                        <th>{{ number_format($totals['docs'] ?? 0) }}</th>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">جزئیات خروج در بازه</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>زمان</th>
                    <th>قطعه</th>
                    <th>تعداد</th>
                    <th>مبلغ</th>
                    <th>نوع</th>
                    <th>قبض</th>
                </tr>
                </thead>
                <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td dir="ltr">{{ jalali_like($m->created_at) }}</td>
                        <td>{{ $m->part?->name ?: ('#'.$m->part_id) }}</td>
                        <td>{{ number_format(abs((int) $m->quantity)) }}</td>
                        <td>{{ toman(abs((int) $m->total_cost)) }}</td>
                        <td>{{ $m->docTypeLabel() }}</td>
                        <td>
                            @if($m->reception)
                                <a href="{{ route('receptions.show', $m->reception) }}">{{ $m->reception->ticket_no }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    @forelse($ticketParts as $tp)
                        <tr>
                            <td dir="ltr">{{ jalali_like($tp->used_at ?: $tp->created_at) }}</td>
                            <td>{{ $tp->part_name }}</td>
                            <td>{{ number_format((int) $tp->quantity) }}</td>
                            <td>{{ toman((int) $tp->total_price) }}</td>
                            <td>ثبت دستی روی قبض</td>
                            <td>
                                @if($tp->reception)
                                    <a href="{{ route('receptions.show', $tp->reception) }}">{{ $tp->reception->ticket_no }}</a>
                                @else —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">خروجی در این بازه نیست.</td></tr>
                    @endforelse
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($recentFallback->isNotEmpty())
<div class="panel">
    <h3 style="margin-top:0;">آخرین خروج‌های ثبت‌شده (خارج از این بازه)</h3>
    <p class="muted" style="margin-top:0;">برای روز/هفته فعلی خروجی نبود؛ ۱۰ خروج اخیر برای کنترل:</p>
    <div class="table-wrap">
        <table class="compact-table">
            <thead><tr><th>زمان</th><th>قطعه</th><th>تعداد</th><th>مبلغ</th><th>قبض</th></tr></thead>
            <tbody>
            @foreach($recentFallback as $m)
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
            @endforeach
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('reports.settings') }}" style="margin-top:10px;">
        @csrf
        <input type="hidden" name="redirect" value="{{ url()->current() }}">
        <input type="hidden" name="period" value="last_week">
        <input type="hidden" name="chart_type" value="{{ \App\Support\ReportSettings::chartType() }}">
        <input type="hidden" name="show_charts" value="{{ \App\Support\ReportSettings::showCharts() ? '1' : '0' }}">
        <button class="btn btn-secondary" type="submit">نمایش هفته قبل</button>
    </form>
</div>
@endif
@endsection
@include('reports._charts-boot')
