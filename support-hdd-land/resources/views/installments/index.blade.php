@extends('layouts.app')
@section('title', 'اقساط | '.shop_name())
@section('page_title', 'طرح‌های اقساط')
@section('window_title', 'اقساط مشتریان')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'اقساط',
    'accSub' => 'زمان‌بندی بدهی، دریافت قسط، چک امانی و یادآوری پیامکی',
])

<div class="acc-desk">
    <div class="acc-panels" style="grid-template-columns:1fr;">
        <section class="acc-panel">
            <header class="acc-panel-head" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;">
                <h3 style="margin:0;">لیست طرح‌ها</h3>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a class="btn btn-primary btn-sm" href="{{ route('installments.create') }}">طرح جدید</a>
                    <a class="btn btn-ghost btn-sm" href="{{ route('installments.report') }}">گزارش</a>
                    <a class="btn btn-ghost btn-sm" href="{{ route('installments.settings') }}">پیامک اقساط</a>
                </div>
            </header>

            <form method="get" class="ticket-search-bar" style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:end;">
                <label>جستجو
                    <input type="text" name="q" value="{{ $q }}" placeholder="نام / موبایل / ضامن">
                </label>
                <label>وضعیت
                    <select name="status">
                        <option value="">همه</option>
                        @foreach(\App\Models\InstallmentPlan::STATUSES as $k => $lab)
                            <option value="{{ $k }}" @selected($status === $k)>{{ $lab }}</option>
                        @endforeach
                    </select>
                </label>
                @if($customerId > 0)
                    <input type="hidden" name="customer_id" value="{{ $customerId }}">
                @endif
                <button class="btn btn-primary btn-sm" type="submit">فیلتر</button>
            </form>

            <div class="table-wrap">
                <table class="compact-table acc-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>مشتری</th>
                        <th>عنوان</th>
                        <th>تعداد</th>
                        <th>مبلغ کل</th>
                        <th>پرداخت‌شده</th>
                        <th>مانده</th>
                        <th>وضعیت</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($plans as $plan)
                        @php
                            $paid = (int) $plan->items->sum('paid_amount');
                            $total = (int) $plan->items->sum('amount');
                        @endphp
                        <tr>
                            <td>{{ $plan->id }}</td>
                            <td>
                                @if($plan->customer)
                                    <a class="acc-link" href="{{ route('customers.show', $plan->customer) }}">{{ $plan->customer->displayName() }}</a>
                                    <div class="muted" dir="ltr" style="font-size:11px;">{{ $plan->customer->phone }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $plan->title ?: '—' }}</td>
                            <td>{{ $plan->items->count() }}</td>
                            <td class="acc-num">{{ number_format($total) }}</td>
                            <td class="acc-num">{{ number_format($paid) }}</td>
                            <td class="acc-num" style="color:#b42318;font-weight:700;">{{ number_format(max(0, $total - $paid)) }}</td>
                            <td>{{ $plan->statusLabel() }}</td>
                            <td><a class="btn btn-ghost btn-sm" href="{{ route('installments.show', $plan) }}">جزئیات</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9">طرح اقساطی ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.pagination', ['paginator' => $plans])
        </section>
    </div>
</div>
@endsection
