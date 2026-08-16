@extends('layouts.app')

@section('title', 'لیست قبض‌ها | '.shop_name())
@section('page_title', 'قبض‌های ورودی و خروجی')
@section('window_title', 'لیست قبض‌ها')

@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:center;">
        <div>
            <h2>قبض‌ها</h2>
            <p class="lead">لیست سریع پذیرش‌ها — برای گزارش کامل از «جستجوی قبض» استفاده کنید.</p>
        </div>
        <div class="actions" style="margin:0;">
            <a class="btn btn-secondary" href="{{ route('receptions.search') }}">جستجوی قبض / گزارش کامل</a>
            <a class="btn btn-primary" href="{{ route('receptions.create') }}">پذیرش جدید</a>
        </div>
    </div>

    <form class="search-row" method="GET" style="align-items:end;">
        <div style="flex:1 1 260px;min-width:200px;">
            <label>جستجو (قبض / نام / موبایل)</label>
            @include('partials.receipt-search-input', [
                'name' => 'q',
                'value' => $q,
                'placeholder' => '1000 یا نام یا 09…',
                'hint' => 'ادامه شماره قبض، یا چند حرف از نام / موبایل — پیشنهاد مشتریان از بانک زیر کادر می‌آید.',
                'allowFree' => true,
                'inputmode' => 'text',
                'customerSuggest' => true,
            ])
        </div>
        <select name="status">
            <option value="">همه وضعیت‌ها</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">فیلتر لیست</button>
        @if($q !== '')
            <a class="btn btn-primary" href="{{ route('receptions.search', array_filter(['q' => $q, 'status' => $status])) }}">نمایش گزارش کامل نتایج</a>
        @endif
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>شماره قبض</th>
                <th>کد سیستم</th>
                <th>مشتری</th>
                <th>کالا / مدل</th>
                <th>سریال</th>
                <th>تعمیرکار</th>
                <th>بیعانه</th>
                <th>مانده</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($receptions as $item)
                <tr>
                    <td>
                        <strong dir="ltr">{{ $item->receipt_no ?: '—' }}</strong>
                    </td>
                    <td><span class="muted" dir="ltr">{{ $item->ticket_no }}</span></td>
                    <td>{{ $item->customer?->name }}<div class="muted" dir="ltr">{{ $item->customer?->phone }}</div></td>
                    <td>{{ $item->product_name }} {{ $item->model }}</td>
                    <td>{{ $item->serial_number ?: '—' }}</td>
                    <td>{{ $item->technician?->name ?? '—' }}</td>
                    <td>{{ toman($item->deposit) }}</td>
                    <td style="{{ $item->remainingAmount() > 0 ? 'color:#b42318;font-weight:700;' : '' }}">{{ toman($item->remainingAmount()) }}</td>
                    <td>
                        <span class="badge badge-{{ $item->status }}">{{ $item->statusLabel() }}</span>
                        @if(in_array($item->financeStatus(), ['credit_open', 'credit_partial'], true))
                            <div class="pill" style="margin-top:3px;background:#fde8e8;color:#9f1239;">{{ $item->financeStatusLabel() }}</div>
                        @elseif($item->financeStatus() === 'credit_settled')
                            <div class="pill" style="margin-top:3px;background:#e8f8ef;color:#0f6b3a;">نسیه تسویه</div>
                        @endif
                    </td>
                    <td>
                        <a class="btn btn-secondary" href="{{ route('receptions.show', $item) }}">جزئیات</a>
                        <a class="btn btn-ghost" href="{{ route('receptions.search', ['q' => $item->receipt_no ?: $item->ticket_no]) }}">گزارش</a>
                        @if($item->canCollectDebt())
                            <a class="btn btn-ghost" href="{{ route('receptions.show', $item) }}#rx-collect">ثبت دریافت</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10">موردی یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $receptions->links('partials.pagination') }}
</div>
@endsection
