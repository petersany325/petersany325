@extends('layouts.app')
@section('title', 'غیرقابل تعمیر | '.shop_name())
@section('page_title', 'گزارش غیرقابل تعمیر')
@section('window_title', 'کالاهای غیرقابل تعمیر')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">گزارش غیرقابل تعمیر</h2>
    <p class="muted" style="margin:0 0 10px;">قبض‌هایی با وضعیت غیرقابل تعمیر — بازه {{ jalali_date($from) }} تا {{ jalali_date($to) }}.</p>
    @include('reports._period-quick')

    <form method="GET" class="search-row no-print" style="margin-bottom:10px;">
        <label>محدوده
            <select name="scope" onchange="this.form.submit()">
                <option value="current" @selected($scope === 'current')>همه غیرقابل‌تعمیرهای فعلی</option>
                <option value="received" @selected($scope === 'received')>پذیرش‌شده در بازه</option>
                <option value="updated" @selected($scope === 'updated')>تغییر وضعیت در بازه</option>
            </select>
        </label>
    </form>

    <div class="stats stats-compact">
        <div class="stat"><div class="label">تعداد</div><div class="value">{{ number_format($totals['count']) }}</div></div>
        <div class="stat"><div class="label">اجرت</div><div class="value">{{ toman((int) $totals['labor']) }}</div></div>
        <div class="stat"><div class="label">قطعات</div><div class="value">{{ toman((int) $totals['parts']) }}</div></div>
        <div class="stat"><div class="label">جمع</div><div class="value">{{ toman((int) $totals['total']) }}</div></div>
        <div class="stat"><div class="label">پرداخت</div><div class="value">{{ toman((int) $totals['paid']) }}</div></div>
    </div>
</div>

<div class="panel">
    @include('reports._goods-table', ['rows' => $rows, 'dateField' => 'received_at'])
</div>
@endsection
