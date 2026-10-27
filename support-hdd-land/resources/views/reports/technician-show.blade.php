@extends('layouts.app')
@section('title', 'عملکرد '.$technician->name.' | '.shop_name())
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
        <div><span>بازه گزارش</span><strong>{{ $from }} تا {{ $to }}</strong></div>
        <div><span>میانگین روز تعمیر</span><strong>{{ $avgDays !== null ? number_format((float) $avgDays, 1) : '—' }}</strong></div>
    </div>
</div>

<div class="stats stats-compact" style="margin-bottom:12px;">
    <div class="stat"><div class="label">تحویل‌شده</div><div class="value">{{ $delivered->count() }}</div></div>
    <div class="stat"><div class="label">مبلغ تعمیر (قبض)</div><div class="value">{{ number_format($billedSum) }}</div></div>
    <div class="stat"><div class="label">اجرت</div><div class="value">{{ number_format($laborSum) }}</div></div>
    <div class="stat"><div class="label">خرجی کالا</div><div class="value">{{ number_format($partsUsedTotal) }}</div></div>
    <div class="stat"><div class="label">وصول روی قبض</div><div class="value">{{ number_format($paidSum) }}</div></div>
    <div class="stat"><div class="label">کمیسیون</div><div class="value">{{ number_format($commissionSum) }}</div></div>
    <div class="stat"><div class="label">خروج‌نخورده</div><div class="value">{{ $breakdown['pending_exit_count'] }}<div class="muted" style="font-size:11px;">{{ number_format($breakdown['pending_exit_sum']) }}</div></div></div>
    <div class="stat"><div class="label">مانده کارگاه</div><div class="value">{{ $breakdown['workshop_count'] }}<div class="muted" style="font-size:11px;">{{ number_format($breakdown['workshop_sum']) }}</div></div></div>
    <div class="stat"><div class="label">غیرقابل‌تعمیر باز</div><div class="value">{{ $breakdown['unrepairable_open'] }}</div></div>
    <div class="stat"><div class="label">غیرقابل‌تعمیر خروج</div><div class="value">{{ $breakdown['unrepairable_exited'] }}</div></div>
    <div class="stat"><div class="label">دست تعمیر الان</div><div class="value">{{ $inHand->count() }}</div></div>
    <div class="stat"><div class="label">ارجاع تأیید/رد</div><div class="value">{{ (int) ($handoffs['accepted'] ?? 0) }}/{{ (int) ($handoffs['rejected'] ?? 0) }}</div></div>
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
        'title' => 'تحویل روزانه',
        'labels' => $daily->keys()->values()->all(),
        'values' => $daily->values()->values()->all(),
        'type' => 'bar',
    ])
</div>
@endif

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin-top:0;">تعمیر شده و تحویل‌شده (بازه)</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قبض</th>
                <th>مشتری</th>
                <th>تحویل</th>
                <th>اجرت</th>
                <th>قطعه</th>
                <th>جمع قبض</th>
                <th>وصول</th>
                <th>مانده</th>
            </tr>
            </thead>
            <tbody>
            @forelse($delivered as $r)
                <tr>
                    <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                    <td>{{ $r->customer?->name }}</td>
                    <td>{{ jalali_like($r->delivered_at) }}</td>
                    <td>{{ toman((int) $r->labor_cost) }}</td>
                    <td>{{ toman((int) $r->parts_cost) }}</td>
                    <td>{{ toman((int) $r->total_amount) }}</td>
                    <td>{{ toman((int) $r->paid_amount) }}</td>
                    <td>{{ toman($r->remainingAmount()) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">در این بازه تحویلی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">خروج‌نخورده (آماده تحویل)</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>قبض</th><th>مشتری</th><th>اجرت+قطعه</th></tr></thead>
                <tbody>
                @forelse($pendingExit as $r)
                    <tr>
                        <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                        <td>{{ $r->customer?->name }}</td>
                        <td>{{ toman((int) $r->labor_cost + (int) $r->parts_cost) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">قبض آمادهٔ خروج‌نخورده نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">مانده تعمیری در کارگاه</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>قبض</th><th>وضعیت</th><th>مشتری</th></tr></thead>
                <tbody>
                @forelse($workshopOpen as $r)
                    <tr>
                        <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                        <td>{{ $r->statusLabel() }}</td>
                        <td>{{ $r->customer?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">مانده‌ای در کارگاه نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">غیرقابل‌تعمیر — خروج نشده</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>قبض</th><th>مشتری</th><th>سریال</th></tr></thead>
                <tbody>
                @forelse($unrepairableOpen as $r)
                    <tr>
                        <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                        <td>{{ $r->customer?->name }}</td>
                        <td dir="ltr">{{ $r->serial_number ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">موردی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">غیرقابل‌تعمیر — خروج‌شده (بازه)</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>قبض</th><th>مشتری</th><th>تحویل</th></tr></thead>
                <tbody>
                @forelse($unrepairableExited as $r)
                    <tr>
                        <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                        <td>{{ $r->customer?->name }}</td>
                        <td>{{ jalali_like($r->delivered_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">در این بازه خروجی از مسیر غیرقابل‌تعمیر نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
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
        <h3 style="margin-top:0;">گزارش خرجی کالا (قطعات مصرف‌شده)</h3>
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
                @if($partsUsed->isNotEmpty())
                    <tfoot>
                    <tr><th colspan="2">جمع خرجی</th><th>{{ toman($partsUsedTotal) }}</th></tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

@include('reports._charts-boot')
