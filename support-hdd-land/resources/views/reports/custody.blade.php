@extends('layouts.app')
@section('title', 'گزارش ارجاع دستگاه | سرزمین هارد')
@section('page_title', 'گزارش ارجاع / محل دستگاه')
@section('window_title', 'Chain of Custody و دست تعمیر')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">ارجاع و Custody</h2>
    <p class="muted" style="margin-top:0;">کنترل شفاف قبض‌ها: چند دستگاه نزد تعمیرکار است، چند ارجاع تأیید/رد شده.</p>
    <div class="stats stats-compact">
        <div class="stat"><div class="label">کل ارجاع دوره</div><div class="value">{{ number_format($summary['total']) }}</div></div>
        <div class="stat"><div class="label">تأیید شده</div><div class="value">{{ number_format($summary['accepted']) }}</div></div>
        <div class="stat"><div class="label">رد شده</div><div class="value">{{ number_format($summary['rejected']) }}</div></div>
        <div class="stat"><div class="label">در انتظار</div><div class="value">{{ number_format($summary['pending']) }}</div></div>
        <div class="stat"><div class="label">به تعمیرکار</div><div class="value">{{ number_format($summary['to_bench']) }}</div></div>
        <div class="stat"><div class="label">بازگشت پذیرش</div><div class="value">{{ number_format($summary['to_front']) }}</div></div>
    </div>
</div>

@if(\App\Support\ReportSettings::showCharts())
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', ['id'=>'chartCustody','title'=>'محل فعلی دستگاه‌های باز','labels'=>$chartCustodyLabels,'values'=>$chartCustodyValues,'type'=>'doughnut'])
    @include('reports._chart', ['id'=>'chartHandoff','title'=>'نتیجه ارجاع دوره','labels'=>['تأیید','رد','انتظار'],'values'=>[(int)$summary['accepted'],(int)$summary['rejected'],(int)$summary['pending']],'type'=>'bar'])
</div>
@endif

<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">عملکرد ارجاع به تفکیک تعمیرکار</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>تعمیرکار</th><th>تأیید</th><th>رد</th><th>انتظار</th><th></th></tr></thead>
                <tbody>
                @forelse($byTech as $techId => $rows)
                    @php
                        $acc = (int) optional($rows->firstWhere('status','accepted'))->total;
                        $rej = (int) optional($rows->firstWhere('status','rejected'))->total;
                        $pen = (int) optional($rows->firstWhere('status','pending'))->total;
                    @endphp
                    <tr>
                        <td>{{ $techNames[$techId] ?? ('#'.$techId) }}</td>
                        <td>{{ $acc }}</td>
                        <td>{{ $rej }}</td>
                        <td>{{ $pen }}</td>
                        <td>
                            @if(auth()->user()->canAccess('reports.technicians'))
                                <a class="btn btn-ghost" href="{{ route('reports.technicians.show', $techId) }}">پرونده</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">ارجاعی در بازه نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">ارجاع‌های در انتظار تأیید</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>قبض</th><th>سریال</th><th>نوع</th><th>به</th></tr></thead>
                <tbody>
                @forelse($pendingRows as $h)
                    <tr>
                        <td><a href="{{ route('receptions.show', $h->reception_id) }}">{{ $h->reception?->ticket_no }}</a></td>
                        <td dir="ltr">{{ $h->serial_snapshot ?: '—' }}</td>
                        <td>{{ $h->directionLabel() }}</td>
                        <td>{{ $h->toTechnician?->name ?: 'پذیرش' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">موردی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="panel">
    <h3 style="margin-top:0;">هارد دیسک‌های دست تعمیر (فعلی)</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead><tr><th>قبض</th><th>مشتری</th><th>تعمیرکار</th><th>وضعیت</th><th>سریال</th></tr></thead>
            <tbody>
            @forelse($inHand as $row)
                <tr>
                    <td><a href="{{ route('receptions.show', $row) }}">{{ $row->ticket_no }}</a></td>
                    <td>{{ $row->customer?->name }}</td>
                    <td>{{ $row->custodyTechnician?->name ?: '—' }}</td>
                    <td><span class="badge badge-{{ $row->status }}">{{ $row->statusLabel() }}</span></td>
                    <td dir="ltr">{{ $row->serial_number ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">دستگاهی نزد تعمیرکار نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@include('reports._charts-boot')
