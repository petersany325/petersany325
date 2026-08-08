@extends('layouts.app')
@section('title', 'عملکرد '.$\2.' | '.shop_name())
@section('page_title', 'پرونده عملکرد تعمیرکار')
@section('window_title', $technician->name)

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <div class="actions" style="margin-bottom:10px;">
        <a class="btn btn-ghost" href="{{ route('reports.technicians') }}">→ بازگشت به لیست تعمیرکاران</a>
    </div>
    <h2 style="margin:0 0 8px;">{{ $technician->name }}</h2>
    <div class="p-kv report-profile-grid">
        <div><span>موبایل</span><strong dir="ltr">{{ $technician->phone ?: '—' }}</strong></div>
        <div><span>تخصص</span><strong>{{ $technician->specialty ?: '—' }}</strong></div>
        <div><span>کمیسیون</span><strong>{{ $technician->commission_percent }}٪</strong></div>
        <div><span>وضعیت</span><strong>{{ $technician->is_active ? 'فعال' : 'غیرفعال' }}</strong></div>
        <div><span>بازه گزارش</span><strong>{{ jalali_date($from) }} تا {{ jalali_date($to) }}</strong></div>
        <div><span>میانگین روز تعمیر</span><strong>{{ $avgDays !== null ? number_format((float) $avgDays, 1) : '—' }}</strong></div>
    </div>
</div>

<div class="stats stats-compact" style="margin-bottom:12px;">
    <div class="stat"><div class="label">کار دوره</div><div class="value">{{ $jobs->count() }}</div></div>
    <div class="stat"><div class="label">تحویل‌شده</div><div class="value">{{ $delivered->count() }}</div></div>
    <div class="stat"><div class="label">دست تعمیر الان</div><div class="value">{{ $inHand->count() }}</div></div>
    <div class="stat"><div class="label">اجرت</div><div class="value">{{ number_format($laborSum) }}</div></div>
    <div class="stat"><div class="label">قطعات</div><div class="value">{{ number_format($partsSum) }}</div></div>
    <div class="stat"><div class="label">کمیسیون</div><div class="value">{{ number_format($commissionSum) }}</div></div>
    <div class="stat"><div class="label">ارجاع تأیید</div><div class="value">{{ (int) ($handoffs['accepted'] ?? 0) }}</div></div>
    <div class="stat"><div class="label">ارجاع رد</div><div class="value">{{ (int) ($handoffs['rejected'] ?? 0) }}</div></div>
</div>

@if(\App\Support\ReportSettings::showCharts())
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', [
        'id' => 'chartTechStatus',
        'title' => 'وضعیت کارهای دوره',
        'labels' => $chartStatusLabels,
        'values' => $chartStatusValues,
        'type' => 'doughnut',
    ])
    @include('reports._chart', [
        'id' => 'chartTechDaily',
        'title' => 'پذیرش روزانه منتسب',
        'labels' => jalali_day_labels($daily->keys()->values()->all()),
        'values' => $daily->values()->values()->all(),
        'type' => 'bar',
    ])
</div>
@endif

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin-top:0;">کارهای دوره</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قبض</th>
                <th>مشتری</th>
                <th>شروع</th>
                <th>وضعیت</th>
                <th>قطعه</th>
                <th>اجرت</th>
                <th>پرداخت مشتری</th>
                <th>بدهی قبض</th>
            </tr>
            </thead>
            <tbody>
            @forelse($jobs as $r)
                <tr>
                    <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                    <td>{{ $r->customer?->name }}</td>
                    <td>{{ jalali_like($r->received_at ?: $r->created_at) }}</td>
                    <td><span class="badge badge-{{ $r->status }}">{{ $r->statusLabel() }}</span></td>
                    <td>{{ $r->parts->sum('quantity') }}</td>
                    <td>{{ toman((int) $r->labor_cost) }}</td>
                    <td>{{ toman((int) $r->paid_amount) }}</td>
                    <td>{{ toman($r->remainingAmount()) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">در این بازه کاری نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="split-2">
    <div class="panel">
        <h3 style="margin-top:0;">هاردهای دست تعمیر (الان)</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>قبض</th><th>مشتری</th><th>سریال</th><th>وضعیت</th></tr></thead>
                <tbody>
                @forelse($inHand as $r)
                    <tr>
                        <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                        <td>{{ $r->customer?->name }}</td>
                        <td dir="ltr">{{ $r->serial_number ?: '—' }}</td>
                        <td>{{ $r->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">دستگاهی نزد این تعمیرکار نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">قطعات مصرف‌شده در کارهای دوره</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>قطعه</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                <tbody>
                @forelse($partsUsed as $p)
                    <tr>
                        <td>{{ $p->part_name }}</td>
                        <td>{{ $p->qty }}</td>
                        <td>{{ toman((int) $p->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">قطعه‌ای ثبت نشده.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@include('reports._charts-boot')
