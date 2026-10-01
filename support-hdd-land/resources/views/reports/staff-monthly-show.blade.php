@extends('layouts.app')
@section('title', 'عملکرد ماهانه '.$user->name.' | '.shop_name())
@section('page_title', 'عملکرد ماهانه — '.$user->name)
@section('window_title', 'جزئیات گزارش ماهانه کارمند')

@section('content')
@include('reports._settings')

@php
    $prev = $row['prev'] ?? [];
    $fmtDelta = function (?float $pct) {
        if ($pct === null) return 'جدید';
        if ($pct > 0) return '+'.$pct.'٪';
        if ($pct < 0) return $pct.'٪';
        return '۰٪';
    };
@endphp

<div class="panel" style="margin-bottom:12px;">
    <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:flex-start;">
        <div>
            <h2 style="margin:0 0 6px;">{{ $user->name }}</h2>
            <p class="muted" style="margin:0;">{{ $row['role_label'] ?? $user->roleLabel() }} · {{ $monthLabel }}</p>
            <p class="muted" style="margin:6px 0 0;font-size:12px;" dir="ltr">{{ $user->phone }}</p>
        </div>
        <div class="actions" style="margin:0;display:flex;gap:8px;flex-wrap:wrap;">
            <a class="btn btn-ghost" href="{{ route('reports.staff-monthly') }}">بازگشت به لیست</a>
            <form method="POST" action="{{ route('reports.staff-monthly.notify', $user) }}" data-confirm="اعلان در کارتابل ثبت شود؟">
                @csrf
                <button class="btn btn-secondary" type="submit">ارسال اعلان</button>
            </form>
            <form method="POST" action="{{ route('reports.staff-monthly.notify', $user) }}" data-confirm="اعلان + پیامک ارسال شود؟">
                @csrf
                <input type="hidden" name="send_sms" value="1">
                <button class="btn btn-primary" type="submit">اعلان + پیامک</button>
            </form>
        </div>
    </div>

    <div class="alert" style="margin:14px 0 0;background:#f0f7ff;border-color:#b6d4fe;color:#0b3d6e;">
        <strong>یک‌خط ماه:</strong> {{ $row['line'] }}
    </div>
</div>

<div class="panel">
    <h3 style="margin-top:0;">مقایسه با دوره قبل ({{ $prevMonthLabel }})</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>شاخص</th>
                <th>این دوره</th>
                <th>دوره قبل</th>
                <th>تغییر</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>تعمیر تحویل‌شده</td>
                <td>{{ number_format($row['delivered']) }}</td>
                <td>{{ number_format($prev['delivered'] ?? 0) }}</td>
                <td>{{ $fmtDelta($row['delta']['delivered'] ?? null) }}</td>
            </tr>
            <tr>
                <td>درآمد قبض‌های تحویل</td>
                <td>{{ toman($row['revenue']) }}</td>
                <td>{{ toman($prev['revenue'] ?? 0) }}</td>
                <td>{{ $fmtDelta($row['delta']['revenue'] ?? null) }}</td>
            </tr>
            <tr>
                <td>اجرت</td>
                <td>{{ toman($row['labor']) }}</td>
                <td>{{ toman($prev['labor'] ?? 0) }}</td>
                <td>—</td>
            </tr>
            <tr>
                <td>قطعات</td>
                <td>{{ toman($row['parts']) }}</td>
                <td>{{ toman($prev['parts'] ?? 0) }}</td>
                <td>—</td>
            </tr>
            <tr>
                <td>وصول صندوق (توسط این نفر)</td>
                <td>{{ toman($row['collected']) }}</td>
                <td>{{ toman($prev['collected'] ?? 0) }}</td>
                <td>{{ $fmtDelta($row['delta']['collected'] ?? null) }}</td>
            </tr>
            <tr>
                <td>سود تقریبی</td>
                <td>{{ toman($row['approx_profit']) }}</td>
                <td>{{ toman($prev['approx_profit'] ?? 0) }}</td>
                <td>{{ $fmtDelta($row['delta']['approx_profit'] ?? null) }}</td>
            </tr>
            <tr>
                <td>برگشتی / گارانتی</td>
                <td>{{ number_format($row['warranty_returns']) }}</td>
                <td>{{ number_format($prev['warranty_returns'] ?? 0) }}</td>
                <td>{{ $fmtDelta($row['delta']['warranty_returns'] ?? null) }}</td>
            </tr>
            <tr>
                <td>پذیرش ثبت‌شده</td>
                <td>{{ number_format($row['intake']) }}</td>
                <td>{{ number_format($prev['intake'] ?? 0) }}</td>
                <td>—</td>
            </tr>
            @if(($row['commission_percent'] ?? 0) > 0)
            <tr>
                <td>کمیسیون تخمینی ({{ $row['commission_percent'] }}٪ از اجرت)</td>
                <td>{{ toman($row['commission']) }}</td>
                <td>{{ toman($prev['commission'] ?? 0) }}</td>
                <td>—</td>
            </tr>
            @endif
            </tbody>
        </table>
    </div>
    <p class="muted" style="margin:10px 0 0;font-size:12px;">{{ $shopLine }}</p>
</div>
@endsection
