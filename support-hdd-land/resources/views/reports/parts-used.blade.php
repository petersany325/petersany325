@extends('layouts.app')
@section('title', 'گزارش خروجی کالا | '.shop_name())
@section('page_title', 'گزارش خروجی کالا')
@section('window_title', 'بیلان خروج + صندوق + انبار')

@section('content')
@include('reports._settings')

@php
    $periodLabels = \App\Support\ReportSettings::periodLabels();
    $quick = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year', 'last_30'];
@endphp

<div class="panel" style="margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
        <div>
            <h2 style="margin-top:0;">گزارش خروجی کالا</h2>
            <p class="muted" style="margin:0;">
                از {{ jalali_date($from) }} تا {{ jalali_date($to) }}
                @if(!empty($period) && ($periodLabels[$period] ?? null))
                    — <strong>{{ $periodLabels[$period] }}</strong>
                @endif
            </p>
        </div>
        <div class="actions no-print" style="margin:0;flex-wrap:wrap;">
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
</div>

<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">بیلان خروج قبض (تحویل)</h3>
        <div class="stats stats-compact">
            <div class="stat"><div class="label">تعداد خروج</div><div class="value">{{ number_format($exitTotals['count'] ?? 0) }}</div></div>
            <div class="stat"><div class="label">جمع اجرت</div><div class="value">{{ toman((int) ($exitTotals['labor'] ?? 0)) }}</div></div>
            <div class="stat"><div class="label">جمع قطعات قبض</div><div class="value">{{ toman((int) ($exitTotals['parts'] ?? 0)) }}</div></div>
            <div class="stat"><div class="label">جمع مبلغ خروج</div><div class="value">{{ toman((int) ($exitTotals['total'] ?? 0)) }}</div></div>
            <div class="stat"><div class="label">پرداخت روی همان قبض‌ها</div><div class="value">{{ toman((int) ($exitTotals['paid_on_tickets'] ?? 0)) }}</div></div>
            <div class="stat"><div class="label">مانده نسیه خروج</div><div class="value">{{ toman((int) ($exitTotals['remaining'] ?? 0)) }}</div></div>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">بیلان صندوق دوره</h3>
        <p class="muted" style="margin-top:0;font-size:11px;">همه دریافت‌های ثبت‌شده در همین بازه (بیعانه + تسویه)، حتی اگر قبض هنوز خروج نخورده باشد.</p>
        <div class="stats stats-compact">
            <div class="stat"><div class="label">تعداد سند دریافت</div><div class="value">{{ number_format($cashTotals['count'] ?? 0) }}</div></div>
            <div class="stat"><div class="label">نقد</div><div class="value">{{ toman((int) ($cashTotals['cash'] ?? 0)) }}</div></div>
            <div class="stat"><div class="label">کارتخوان</div><div class="value">{{ toman((int) ($cashTotals['card'] ?? 0)) }}</div></div>
            <div class="stat"><div class="label">کارت‌به‌کارت</div><div class="value">{{ toman((int) ($cashTotals['transfer'] ?? 0)) }}</div></div>
            <div class="stat"><div class="label">درگاه</div><div class="value">{{ toman((int) ($cashTotals['zarinpal'] ?? 0)) }}</div></div>
            <div class="stat"><div class="label">خالص دریافتی</div><div class="value">{{ toman((int) ($cashTotals['net'] ?? 0)) }}</div></div>
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin-top:0;">خلاصه بیلان بازه</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <tbody>
            <tr><td>جمع مبلغ خروج قبض‌ها</td><td><strong>{{ toman((int) ($exitTotals['total'] ?? 0)) }}</strong></td></tr>
            <tr><td>خالص دریافتی صندوق در بازه</td><td><strong>{{ toman((int) ($cashTotals['net'] ?? 0)) }}</strong></td></tr>
            <tr><td>مانده نسیه روی قبض‌های خروج‌شده</td><td>{{ toman((int) ($exitTotals['remaining'] ?? 0)) }}</td></tr>
            <tr><td>خروج قطعه انبار (بهای تمام‌شده)</td><td>{{ toman((int) ($stockTotals['amount'] ?? 0)) }}</td></tr>
            </tbody>
        </table>
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
                <th>پرداخت قبض</th>
                <th>مانده</th>
            </tr>
            </thead>
            <tbody>
            @forelse($exits as $r)
                @php $remain = max(0, (int) $r->total_amount - (int) $r->paid_amount); @endphp
                <tr>
                    <td dir="ltr">{{ jalali_like($r->delivered_at) }}</td>
                    <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                    <td>{{ $r->customer?->name ?: '—' }}</td>
                    <td>{{ $r->technician?->name ?: '—' }}</td>
                    <td>{{ toman((int) $r->labor_cost) }}</td>
                    <td>{{ toman((int) $r->parts_cost) }}</td>
                    <td><strong>{{ toman((int) $r->total_amount) }}</strong></td>
                    <td>{{ toman((int) $r->paid_amount) }}</td>
                    <td style="{{ $remain > 0 ? 'color:#b42318;font-weight:700;' : '' }}">{{ toman($remain) }}</td>
                </tr>
            @empty
                <tr><td colspan="9">در این بازه خروجی (تحویل قبض) ثبت نشده.</td></tr>
            @endforelse
            </tbody>
            @if($exits->isNotEmpty())
                <tfoot>
                <tr>
                    <th colspan="4">جمع {{ number_format($exitTotals['count']) }} قبض</th>
                    <th>{{ toman((int) $exitTotals['labor']) }}</th>
                    <th>{{ toman((int) $exitTotals['parts']) }}</th>
                    <th>{{ toman((int) $exitTotals['total']) }}</th>
                    <th>{{ toman((int) $exitTotals['paid_on_tickets']) }}</th>
                    <th>{{ toman((int) $exitTotals['remaining']) }}</th>
                </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin-top:0;">دریافت‌های صندوق در بازه</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr><th>زمان</th><th>قبض</th><th>مبلغ</th><th>روش</th><th>نوع</th><th>یادداشت</th></tr>
            </thead>
            <tbody>
            @forelse($payments as $p)
                <tr>
                    <td dir="ltr">{{ jalali_like($p->paid_at) }}</td>
                    <td>
                        @if($p->reception)
                            <a href="{{ route('receptions.show', $p->reception) }}">{{ $p->reception->ticket_no }}</a>
                        @else —
                        @endif
                    </td>
                    <td>{{ toman((int) $p->amount) }}</td>
                    <td>{{ $p->methodLabel() }}</td>
                    <td>{{ $p->typeLabel() }}</td>
                    <td>{{ $p->note ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">دریافتی در این بازه نیست.</td></tr>
            @endforelse
            </tbody>
            @if($payments->isNotEmpty())
                <tfoot>
                <tr>
                    <th colspan="2">خالص</th>
                    <th>{{ toman((int) $cashTotals['net']) }}</th>
                    <th colspan="3"></th>
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
