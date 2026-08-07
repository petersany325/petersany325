@extends('layouts.app')
@section('title', 'گزارش صندوق و دریافت‌ها | سرزمین هارد')
@section('page_title', 'گزارش صندوق / دریافت‌ها')
@section('window_title', 'نقد، کارت، زرین‌پال و بدهکاران')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">صندوق و دریافت‌ها</h2>
    <p class="muted" style="margin-top:0;">گزارش روزانه دریافت‌ها به تفکیک روش پرداخت — مکمل حسابداری.</p>
    <div class="stats stats-compact">
        <div class="stat"><div class="label">دریافت دوره</div><div class="value">{{ toman((int) $totalIn) }}</div></div>
        <div class="stat"><div class="label">عودت</div><div class="value">{{ toman((int) $totalRefund) }}</div></div>
        <div class="stat"><div class="label">خالص</div><div class="value">{{ toman((int) $totalIn - (int) $totalRefund) }}</div></div>
    </div>
</div>

@if(\App\Support\ReportSettings::showCharts())
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', ['id'=>'chartPayMethod','title'=>'به تفکیک روش','labels'=>$chartMethodLabels,'values'=>$chartMethodValues,'type'=>'doughnut'])
    @include('reports._chart', ['id'=>'chartPayDaily','title'=>'خالص روزانه','labels'=>$chartDailyLabels,'values'=>$chartDailyValues,'type'=>'line'])
</div>
@endif

<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">به تفکیک روش</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>روش</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                <tbody>
                @forelse($byMethod as $row)
                    <tr>
                        <td>{{ \App\Models\Payment::METHODS[$row->method] ?? $row->method }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ toman((int) $row->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">پرداختی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">بیشترین مانده بدهکار</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>قبض</th><th>مشتری</th><th>مانده</th></tr></thead>
                <tbody>
                @forelse($receivables as $r)
                    <tr>
                        <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                        <td>
                            {{ $r->customer?->name }}
                            @if($r->customer && auth()->user()->canAccess('reports.customers'))
                                <a class="btn btn-ghost" href="{{ route('reports.customers.show', $r->customer_id) }}">پرونده</a>
                            @endif
                        </td>
                        <td>{{ toman($r->remainingAmount()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">مانده‌ای نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="panel">
    <h3 style="margin-top:0;">آخرین دریافت‌ها</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead><tr><th>زمان</th><th>مشتری</th><th>روش</th><th>مبلغ</th></tr></thead>
            <tbody>
            @forelse($recent as $p)
                <tr>
                    <td>{{ jalali_like($p->paid_at) }}</td>
                    <td>{{ $p->customer?->name }}</td>
                    <td>{{ $p->methodLabel() }}</td>
                    <td>{{ toman((int) $p->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">—</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@include('reports._charts-boot')
