@extends('layouts.app')
@section('title', 'گزارش اقساط | '.shop_name())
@section('page_title', 'گزارش اقساط')
@section('window_title', 'پرداخت‌شده / نشده')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'گزارش اقساط',
    'accSub' => 'همه مشتریان یا یک مشتری — پرداخت‌شده و معوق',
])

<div class="acc-desk">
    <div class="acc-kpi-grid">
        <div class="acc-kpi tone-teal">
            <span class="acc-kpi-label">مبلغ برنامه‌ریزی</span>
            <strong class="acc-kpi-value">{{ number_format($totals['scheduled']) }}</strong>
        </div>
        <div class="acc-kpi tone-amber">
            <span class="acc-kpi-label">پرداخت‌شده</span>
            <strong class="acc-kpi-value">{{ number_format($totals['paid']) }}</strong>
        </div>
        <div class="acc-kpi tone-rose">
            <span class="acc-kpi-label">مانده</span>
            <strong class="acc-kpi-value">{{ number_format($totals['remain']) }}</strong>
        </div>
    </div>

    <section class="acc-panel">
        <form method="get" class="ticket-search-bar" style="display:flex;flex-wrap:wrap;gap:8px;align-items:end;margin-bottom:12px;">
            <label>مشتری (شناسه)
                <input type="number" name="customer_id" value="{{ $customerId ?: '' }}" min="1" dir="ltr" placeholder="خالی = همه">
            </label>
            <label>وضعیت
                <select name="filter">
                    <option value="unpaid" @selected($filter === 'unpaid')>پرداخت‌نشده</option>
                    <option value="overdue" @selected($filter === 'overdue')>معوق</option>
                    <option value="paid" @selected($filter === 'paid')>پرداخت‌شده</option>
                    <option value="all" @selected($filter === 'all')>همه</option>
                </select>
            </label>
            <label>از
                @include('partials.jalali-date', ['name' => 'from', 'value' => $from ? jalali_input($from) : ''])
            </label>
            <label>تا
                @include('partials.jalali-date', ['name' => 'to', 'value' => $to ? jalali_input($to) : ''])
            </label>
            <button class="btn btn-primary btn-sm" type="submit">اعمال</button>
            <a class="btn btn-ghost btn-sm" href="{{ route('installments.index') }}">طرح‌ها</a>
            @if($customer)
                <span class="muted">مشتری: {{ $customer->displayName() }}</span>
            @endif
        </form>

        <div class="table-wrap">
            <table class="compact-table acc-table">
                <thead>
                <tr>
                    <th>مشتری</th>
                    <th>طرح</th>
                    <th>قسط</th>
                    <th>سررسید</th>
                    <th>مبلغ</th>
                    <th>پرداختی</th>
                    <th>مانده</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->plan?->customer?->displayName() ?? '—' }}</td>
                        <td>#{{ $item->installment_plan_id }} {{ $item->plan?->title }}</td>
                        <td>{{ $item->sequence }}</td>
                        <td dir="ltr">{{ jalali_date($item->due_date) }}</td>
                        <td class="acc-num">{{ number_format($item->amount) }}</td>
                        <td class="acc-num">{{ number_format($item->paid_amount) }}</td>
                        <td class="acc-num">{{ number_format($item->remainingAmount()) }}</td>
                        <td>{{ $item->statusLabel() }}</td>
                        <td><a class="btn btn-ghost btn-sm" href="{{ route('installments.show', $item->installment_plan_id) }}">باز کردن</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9">موردی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.pagination', ['paginator' => $items])
    </section>

    <section class="acc-panel" style="margin-top:12px;">
        <header class="acc-panel-head"><h3>آخرین دریافت‌های اقساط</h3></header>
        <div class="table-wrap">
            <table class="compact-table acc-table">
                <thead><tr><th>تاریخ</th><th>مشتری</th><th>قسط</th><th>مبلغ</th><th>روش</th></tr></thead>
                <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td dir="ltr">{{ jalali_like($p->paid_at) }}</td>
                        <td>{{ $p->plan?->customer?->displayName() ?? '—' }}</td>
                        <td>#{{ $p->installment_plan_id }} / {{ $p->item?->sequence }}</td>
                        <td class="acc-num">{{ number_format($p->amount) }}</td>
                        <td>{{ $p->methodLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">پرداختی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
