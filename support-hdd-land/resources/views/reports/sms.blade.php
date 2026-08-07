@extends('layouts.app')
@section('title', 'گزارش پیامک | سرزمین هارد')
@section('page_title', 'گزارش پیامک وضعیت')
@section('window_title', 'موفق / ناموفق و به تفکیک وضعیت')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">پیامک‌های ارسال‌شده</h2>
    <div class="stats stats-compact">
        <div class="stat"><div class="label">کل</div><div class="value">{{ number_format($summary['total']) }}</div></div>
        <div class="stat"><div class="label">موفق</div><div class="value">{{ number_format($summary['ok']) }}</div></div>
        <div class="stat"><div class="label">ناموفق</div><div class="value">{{ number_format($summary['fail']) }}</div></div>
        <div class="stat"><div class="label">به مشتری</div><div class="value">{{ number_format($summary['customer']) }}</div></div>
        <div class="stat"><div class="label">همکار</div><div class="value">{{ number_format($summary['coworker']) }}</div></div>
    </div>
</div>

@if(\App\Support\ReportSettings::showCharts())
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', ['id'=>'chartSms','title'=>'موفق در برابر ناموفق','labels'=>$chartSmsLabels,'values'=>$chartSmsValues,'type'=>'doughnut'])
    @include('reports._chart', ['id'=>'chartSmsStatus','title'=>'به تفکیک وضعیت','labels'=>$byStatus->pluck('status_key')->map(fn($v)=>$v?:'—')->values()->all(),'values'=>$byStatus->pluck('total')->map(fn($v)=>(int)$v)->values()->all()])
</div>
@endif

<div class="split-2">
    <div class="panel">
        <h3 style="margin-top:0;">به تفکیک وضعیت قبض</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>کلید وضعیت</th><th>کل</th><th>موفق</th></tr></thead>
                <tbody>
                @forelse($byStatus as $row)
                    <tr>
                        <td>{{ $row->status_key ?: '—' }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ $row->ok_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">پیامکی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">آخرین ناموفق‌ها</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>زمان</th><th>موبایل</th><th>قبض</th><th>پاسخ پنل</th></tr></thead>
                <tbody>
                @forelse($fails as $f)
                    <tr>
                        <td>{{ $f->created_at?->format('Y/m/d H:i') }}</td>
                        <td dir="ltr">{{ $f->phone }}</td>
                        <td>
                            @if($f->reception_id)
                                <a href="{{ route('receptions.show', $f->reception_id) }}">{{ $f->reception?->ticket_no }}</a>
                            @else — @endif
                        </td>
                        <td class="muted">{{ \Illuminate\Support\Str::limit($f->provider_message, 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">ناموفقی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@include('reports._charts-boot')
