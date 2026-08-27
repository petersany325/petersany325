@extends('layouts.app')
@section('title', 'پرونده '.$customer->name.' | سرزمین هارد')
@section('page_title', 'پرونده مشتری')
@section('window_title', $customer->name)

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <div class="actions" style="justify-content:space-between;margin-bottom:10px;">
        <a class="btn btn-ghost" href="{{ route('reports.customers') }}">→ بازگشت به گزارش مشتریان</a>
        @if(auth()->user()->canAccess('customers'))
            <a class="btn btn-ghost" href="{{ route('customers.show', $customer) }}">ویرایش مشتری</a>
        @endif
    </div>
    <h2 style="margin:0 0 8px;">{{ $customer->name }}</h2>
    <div class="p-kv report-profile-grid">
        <div><span>موبایل</span><strong dir="ltr">{{ $customer->phone ?: '—' }}</strong></div>
        <div><span>کد ملی</span><strong dir="ltr">{{ $customer->national_code ?: '—' }}</strong></div>
        <div><span>شغل</span><strong>{{ $customer->job ?: '—' }}</strong></div>
        <div><span>نحوه آشنایی</span><strong>{{ $customer->referralSource?->name ?: '—' }}</strong></div>
        <div><span>آدرس</span><strong>{{ $customer->address ?: '—' }}</strong></div>
        <div><span>یادداشت</span><strong>{{ $customer->notes ?: '—' }}</strong></div>
        <div><span>اولین مراجعه</span><strong>{{ $lifetime['first_visit'] ? jalali_like($lifetime['first_visit']) : '—' }}</strong></div>
        <div><span>آخرین مراجعه</span><strong>{{ $lifetime['last_visit'] ? jalali_like($lifetime['last_visit']) : '—' }}</strong></div>
    </div>
</div>

<div class="stats stats-compact" style="margin-bottom:12px;">
    <div class="stat"><div class="label">کل قبض‌ها</div><div class="value">{{ number_format($lifetime['tickets']) }}</div></div>
    <div class="stat"><div class="label">باز / جاری</div><div class="value">{{ number_format($lifetime['open']) }}</div></div>
    <div class="stat"><div class="label">تحویل‌شده</div><div class="value">{{ number_format($lifetime['delivered']) }}</div></div>
    <div class="stat"><div class="label">قطعات مصرفی</div><div class="value">{{ number_format($lifetime['parts_qty']) }}</div></div>
    <div class="stat"><div class="label">جمع فاکتور</div><div class="value">{{ number_format($lifetime['billed']) }}</div></div>
    <div class="stat"><div class="label">پرداخت‌شده</div><div class="value">{{ number_format($lifetime['paid']) }}</div></div>
    <div class="stat"><div class="label">بدهی</div><div class="value">{{ number_format($lifetime['debt']) }}</div></div>
    <div class="stat"><div class="label">قبض در بازه</div><div class="value">{{ number_format($period['tickets']) }}</div></div>
</div>

@if(\App\Support\ReportSettings::showCharts())
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', [
        'id' => 'chartCustStatus',
        'title' => 'توزیع وضعیت قبض‌ها',
        'labels' => $chartStatusLabels,
        'values' => $chartStatusValues,
        'type' => 'doughnut',
    ])
    @include('reports._chart', [
        'id' => 'chartCustPay',
        'title' => 'پرداخت‌ها در طول زمان',
        'labels' => $payDaily->keys()->values()->all(),
        'values' => $payDaily->values()->values()->all(),
        'type' => 'line',
    ])
</div>
@endif

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin-top:0;">تاریخچه کارها ({{ $receptions->count() }} قبض)</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قبض</th>
                <th>شروع</th>
                <th>کالا / سریال</th>
                <th>تعمیرکار</th>
                <th>وضعیت</th>
                <th>محل</th>
                <th>قطعه</th>
                <th>جمع</th>
                <th>پرداخت</th>
                <th>بدهی</th>
            </tr>
            </thead>
            <tbody>
            @forelse($receptions as $r)
                <tr>
                    <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                    <td>{{ jalali_like($r->received_at ?: $r->created_at) }}</td>
                    <td>
                        {{ $r->product_name ?: '—' }}
                        <div class="muted" dir="ltr">{{ $r->serial_number }}</div>
                    </td>
                    <td>{{ $r->technician?->name ?: '—' }}</td>
                    <td><span class="badge badge-{{ $r->status }}">{{ $r->statusLabel() }}</span></td>
                    <td>{{ $r->custodyLabel() }}</td>
                    <td>{{ $r->parts->sum('quantity') }} / {{ toman((int) $r->parts->sum('total_price')) }}</td>
                    <td>{{ toman((int) $r->total_amount) }}</td>
                    <td>{{ toman((int) $r->paid_amount) }}</td>
                    <td>{{ toman($r->remainingAmount()) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">قبضی ثبت نشده.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">پرداخت‌ها</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>زمان</th><th>قبض</th><th>نوع</th><th>روش</th><th>مبلغ</th></tr></thead>
                <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td>{{ jalali_like($p->paid_at) }}</td>
                        <td>{{ $p->reception?->ticket_no ?: '—' }}</td>
                        <td>{{ $p->typeLabel() }}</td>
                        <td>{{ $p->methodLabel() }}</td>
                        <td>{{ toman((int) $p->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">پرداختی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">پیام‌ها و پیامک</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>زمان</th><th>نوع</th><th>متن</th></tr></thead>
                <tbody>
                @foreach($messages as $m)
                    <tr>
                        <td>{{ jalali_like($m->created_at) }}</td>
                        <td>پیام کارتابل ({{ $m->priorityLabel() }})</td>
                        <td>{{ \Illuminate\Support\Str::limit($m->body, 90) }}</td>
                    </tr>
                @endforeach
                @foreach($smsLogs as $s)
                    <tr>
                        <td>{{ jalali_like($s->created_at) }}</td>
                        <td>SMS {{ $s->ok ? 'موفق' : 'ناموفق' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($s->message, 90) }}</td>
                    </tr>
                @endforeach
                @if($messages->isEmpty() && $smsLogs->isEmpty())
                    <tr><td colspan="3">پیام/پیامکی نیست.</td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@include('reports._charts-boot')
