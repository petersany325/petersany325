@extends('layouts.app')
@section('title', 'گزارش ماهانه کارکنان | '.shop_name())
@section('page_title', 'گزارش ماهانه کارکنان')
@section('window_title', 'سود، برگشتی و مقایسه ماه‌به‌ماه')

@section('content')
@include('reports._settings')

@php
    $fmtDelta = function (?float $pct) {
        if ($pct === null) return 'جدید';
        if ($pct > 0) return '+'.$pct.'٪';
        if ($pct < 0) return $pct.'٪';
        return '۰٪';
    };
@endphp

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">گزارش تخصصی ماهانه کارکنان</h2>
    <p class="muted" style="margin:0 0 10px;">
        فقط برای مدیر — اعداد از قبض‌های تحویل‌شده، صندوق و پذیرش خوانده می‌شوند و چیزی در سیستم تغییر نمی‌دهد.
        بازه فعلی: <strong>{{ $monthLabel }}</strong> · مقایسه با: <strong>{{ $prevMonthLabel }}</strong>
    </p>

    <div class="alert" style="background:#f0f7ff;border-color:#b6d4fe;color:#0b3d6e;margin:0 0 12px;">
        <strong>خلاصه مجموعه:</strong> {{ $shopLine }}
    </div>

    <div class="table-wrap" style="margin-bottom:12px;">
        <table class="compact-table">
            <thead>
            <tr>
                <th></th>
                <th>تحویل</th>
                <th>درآمد</th>
                <th>وصول</th>
                <th>سود تقریبی</th>
                <th>برگشتی/گارانتی</th>
                <th>لغو</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><strong>این دوره</strong></td>
                <td>{{ number_format($shop['delivered']) }}</td>
                <td>{{ toman($shop['revenue']) }}</td>
                <td>{{ toman($shop['collected']) }}</td>
                <td>{{ toman($shop['approx_profit']) }}</td>
                <td>{{ number_format($shop['warranty_returns']) }}</td>
                <td>{{ number_format($shop['cancelled']) }}</td>
            </tr>
            <tr>
                <td><strong>دوره قبل</strong></td>
                <td>{{ number_format($prevShop['delivered']) }}</td>
                <td>{{ toman($prevShop['revenue']) }}</td>
                <td>{{ toman($prevShop['collected']) }}</td>
                <td>{{ toman($prevShop['approx_profit']) }}</td>
                <td>{{ number_format($prevShop['warranty_returns']) }}</td>
                <td>{{ number_format($prevShop['cancelled']) }}</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="actions" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
        <form method="GET" action="{{ route('reports.staff-monthly') }}" style="display:flex;gap:8px;align-items:center;margin:0;">
            <label style="display:flex;gap:6px;align-items:center;font-size:13px;">
                <input type="checkbox" name="active_only" value="1" @checked($activeOnly) onchange="this.form.submit()">
                فقط افراد دارای فعالیت
            </label>
        </form>
        <form method="POST" action="{{ route('reports.staff-monthly.notify-all') }}" data-confirm="اعلان ماهانه برای همه کارکنان فعال این دوره در کارتابل ثبت شود؟">
            @csrf
            <button class="btn btn-secondary" type="submit">ارسال اعلان به همه (کارتابل)</button>
        </form>
        <form method="POST" action="{{ route('reports.staff-monthly.notify-all') }}" data-confirm="اعلان + پیامک برای همه کارکنان فعال ارسال شود؟ هزینه پیامک دارد.">
            @csrf
            <input type="hidden" name="send_sms" value="1">
            <button class="btn btn-ghost" type="submit">ارسال اعلان + پیامک به همه</button>
        </form>
    </div>
</div>

@if(\App\Support\ReportSettings::showCharts() && count($chartLabels))
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', [
        'id' => 'chartStaffDelivered',
        'title' => 'تعداد تحویل',
        'labels' => $chartLabels,
        'values' => $chartDelivered,
    ])
    @include('reports._chart', [
        'id' => 'chartStaffRevenue',
        'title' => 'درآمد تحویل‌شده',
        'labels' => $chartLabels,
        'values' => $chartRevenue,
    ])
</div>
@endif

<div class="panel">
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>کارمند</th>
                <th>نقش</th>
                <th>تحویل</th>
                <th>درآمد</th>
                <th>وصول</th>
                <th>سود تقریبی</th>
                <th>برگشتی</th>
                <th>پذیرش ثبت‌شده</th>
                <th>نسبت به قبل</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($staff as $row)
                <tr>
                    <td>
                        <strong>{{ $row['name'] }}</strong>
                        @if($row['technician_name'] && $row['technician_name'] !== $row['name'])
                            <div class="muted" style="font-size:11px;">تعمیرکار: {{ $row['technician_name'] }}</div>
                        @endif
                        <div class="muted" style="font-size:11px;margin-top:4px;">{{ $row['line'] }}</div>
                    </td>
                    <td>{{ $row['role_label'] }}</td>
                    <td>{{ number_format($row['delivered']) }}</td>
                    <td>{{ toman($row['revenue']) }}</td>
                    <td>{{ toman($row['collected']) }}</td>
                    <td>{{ toman($row['approx_profit']) }}</td>
                    <td>{{ number_format($row['warranty_returns']) }}</td>
                    <td>{{ number_format($row['intake']) }}</td>
                    <td style="font-size:12px;">
                        درآمد {{ $fmtDelta($row['delta']['revenue'] ?? null) }}
                        <div class="muted">سود {{ $fmtDelta($row['delta']['approx_profit'] ?? null) }}</div>
                    </td>
                    <td style="white-space:nowrap;">
                        <a class="btn btn-primary" style="padding:4px 8px;font-size:11px;" href="{{ route('reports.staff-monthly.show', $row['user_id']) }}">جزئیات</a>
                        <form method="POST" action="{{ route('reports.staff-monthly.notify', $row['user_id']) }}" style="display:inline;" data-confirm="اعلان ماهانه برای {{ $row['name'] }} در کارتابل ثبت شود؟">
                            @csrf
                            <button class="btn btn-secondary" type="submit" style="padding:4px 8px;font-size:11px;">اعلان</button>
                        </form>
                        <form method="POST" action="{{ route('reports.staff-monthly.notify', $row['user_id']) }}" style="display:inline;" data-confirm="اعلان + پیامک برای {{ $row['name'] }} ارسال شود؟">
                            @csrf
                            <input type="hidden" name="send_sms" value="1">
                            <button class="btn btn-ghost" type="submit" style="padding:4px 8px;font-size:11px;">SMS</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10">در این بازه فعالیتی برای کارکنان یافت نشد. بازه گزارش را از تنظیمات بالا عوض کنید.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="muted" style="margin:10px 0 0;font-size:12px;">
        سود تقریبی = درآمد قبض‌های تحویل‌شده − قطعات. کمیسیون کارمند در جزئیات نمایش داده می‌شود و از حساب مجموعه کسر نشده است.
    </p>
</div>
@endsection

@include('reports._charts-boot')
