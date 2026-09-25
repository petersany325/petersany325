@extends('layouts.app')
@section('title', 'تراز آزمایشی | '.shop_name())
@section('page_title', 'تراز آزمایشی')
@section('window_title', 'تراز آزمایشی')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'تراز آزمایشی',
    'accSub' => ($mode ?? 'balance') === 'activity'
        ? 'تراز گردش دوره — جمع بدهکار/بستانکار ردیف‌ها'
        : 'تراز مانده تا تاریخ — هر حساب در ستون ماهیت خودش',
])

<div class="acc-desk">
    <form method="GET" action="{{ route('accounting.trial') }}" class="acc-period" style="margin-bottom:10px;flex-wrap:wrap;">
        <select name="mode">
            <option value="balance" @selected(($mode ?? 'balance') === 'balance')>مانده تا تاریخ (استاندارد)</option>
            <option value="activity" @selected(($mode ?? '') === 'activity')>گردش دوره</option>
        </select>
        @if(($mode ?? '') === 'activity')
            @include('partials.jalali-date', ['name' => 'from', 'value' => $from])
            <span class="acc-period-sep">تا</span>
        @else
            <span class="acc-period-sep">تا تاریخ</span>
        @endif
        @include('partials.jalali-date', ['name' => 'to', 'value' => $to])
        <button class="btn btn-sm btn-primary" type="submit">اعمال</button>
    </form>
    <section class="acc-panel">
        <div class="table-wrap">
            <table class="compact-table acc-table">
                <thead>
                <tr><th>کد</th><th>حساب</th><th>بدهکار</th><th>بستانکار</th></tr>
                </thead>
                <tbody>
                @php
                    $trialRows = is_array($trial) && isset($trial['rows']) ? $trial['rows'] : $trial;
                    $sumD = is_array($trial) ? (int) ($trial['debit'] ?? 0) : 0;
                    $sumC = is_array($trial) ? (int) ($trial['credit'] ?? 0) : 0;
                @endphp
                @forelse($trialRows as $row)
                    <tr>
                        <td dir="ltr">{{ $row['account']->code ?? ($row['code'] ?? '') }}</td>
                        <td>{{ $row['account']->name ?? ($row['name'] ?? '') }}</td>
                        <td class="acc-num">{{ number_format((int) ($row['debit'] ?? 0)) }}</td>
                        <td class="acc-num">{{ number_format((int) ($row['credit'] ?? 0)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">داده‌ای نیست.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="2">جمع</th>
                    <th class="acc-num">{{ number_format($sumD) }}</th>
                    <th class="acc-num">{{ number_format($sumC) }}</th>
                </tr>
                </tfoot>
            </table>
        </div>
        @if($sumD !== $sumC)
            <p class="alert alert-error" style="margin-top:10px;">تراز نامتوازن است (بدهکار ≠ بستانکار). اسناد را با «بازسازی» یا بررسی سند دستی کنترل کنید.</p>
        @else
            <p class="muted" style="margin-top:10px;">تراز متوازن است.</p>
        @endif
    </section>
</div>
@endsection
