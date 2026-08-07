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

<div class="panel" style="margin-top:12px;">
    <h2 style="margin-top:0;">تأیید هزینه مشتری (لینک یک‌بارمصرف)</h2>
    <div class="stats stats-compact">
        <div class="stat"><div class="label">ارسال‌شده</div><div class="value">{{ number_format($approvalSummary['sent'] ?? 0) }}</div></div>
        <div class="stat"><div class="label">مشاهده‌شده+</div><div class="value">{{ number_format($approvalSummary['viewed'] ?? 0) }}</div></div>
        <div class="stat"><div class="label">تأییدشده</div><div class="value">{{ number_format($approvalSummary['approved'] ?? 0) }}</div></div>
        <div class="stat"><div class="label">ردشده</div><div class="value">{{ number_format($approvalSummary['rejected'] ?? 0) }}</div></div>
    </div>
    <div class="table-wrap" style="margin-top:10px;">
        <table class="compact-table">
            <thead>
                <tr>
                    <th>قبض</th>
                    <th>مشتری</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>ارسال</th>
                    <th>مشاهده</th>
                    <th>تصمیم</th>
                    <th>کد</th>
                </tr>
            </thead>
            <tbody>
            @forelse(($approvals ?? []) as $ap)
                <tr>
                    <td>
                        @if($ap->reception_id)
                            <a href="{{ route('receptions.show', $ap->reception_id) }}">{{ $ap->reception?->ticket_no }}</a>
                        @else — @endif
                    </td>
                    <td>{{ $ap->customer?->name ?: '—' }}</td>
                    <td>{{ number_format((int) $ap->amount) }}</td>
                    <td>{{ $ap->statusLabel() }}</td>
                    <td>{{ $ap->sent_at?->format('Y/m/d H:i') ?: '—' }}</td>
                    <td>{{ $ap->viewed_at?->format('Y/m/d H:i') ?: '—' }}</td>
                    <td>{{ $ap->decided_at?->format('Y/m/d H:i') ?: '—' }}</td>
                    <td dir="ltr">{{ $ap->approval_code ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">در این بازه لینک تأییدی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@include('reports._charts-boot')
