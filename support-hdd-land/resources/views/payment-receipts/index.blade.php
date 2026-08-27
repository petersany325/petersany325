@extends('layouts.app')
@section('title', 'تأیید فیش کارت‌به‌کارت | '.shop_name())
@section('page_title', 'تأیید فیش بانکی')
@section('window_title', 'بررسی فیش‌های واریزی پرتال مشتری')

@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2 style="margin:0;">تأیید فیش کارت‌به‌کارت</h2>
            <p class="lead" style="margin:4px 0 0;">فقط مدیر و حسابدار می‌توانند فیش را تأیید یا رد کنند. رد = حذف خودکار تصویر.</p>
        </div>
        @if(auth()->user()->canAccess('settings'))
            <a class="btn btn-secondary" href="{{ route('settings.index', ['tab' => 'payments']) }}#payments">تنظیمات کارت شرکت</a>
        @endif
    </div>

    <div class="emp-stat-row" style="margin-top:10px;">
        <div class="emp-stat tone-sms"><span>در انتظار</span><strong>{{ $stats['pending'] }}</strong></div>
        <div class="emp-stat tone-ok"><span>تأییدشده</span><strong>{{ $stats['approved'] }}</strong></div>
        <div class="emp-stat"><span>ردشده</span><strong>{{ $stats['rejected'] }}</strong></div>
        <div class="emp-stat"><span>کل</span><strong>{{ $stats['total'] }}</strong></div>
    </div>

    <form class="ticket-search-bar" method="GET" style="margin:14px 0 8px;">
        <div class="field">
            <label>شماره قبض / جستجو</label>
            @include('partials.receipt-search-input', [
                'name' => 'q',
                'value' => $q,
                'placeholder' => '1000',
                'hint' => 'T-20N ثابت؛ ادامه کد یا نام مشتری',
                'allowFree' => true,
            ])
        </div>
        <div class="field">
            <label>وضعیت</label>
            <select name="status">
                <option value="">همه</option>
                @foreach($statusLabels as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field actions" style="align-self:end;">
            <button class="btn btn-primary" type="submit">فیلتر</button>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data compact-table">
            <thead>
                <tr>
                    <th>زمان</th>
                    <th>قبض</th>
                    <th>مشتری</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
            @forelse($receipts as $receipt)
                <tr>
                    <td>{{ jalali_like($receipt->created_at) }}</td>
                    <td>
                        @if($receipt->reception)
                            <a href="{{ route('receptions.show', $receipt->reception) }}">{{ $receipt->reception->ticket_no }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        {{ $receipt->customer?->name }}
                        <div class="muted" dir="ltr">{{ $receipt->customer?->phone }}</div>
                    </td>
                    <td>{{ number_format((int) $receipt->amount) }}</td>
                    <td>{{ $statusLabels[$receipt->status] ?? $receipt->status }}</td>
                    <td><a class="btn btn-ghost" href="{{ route('payment-receipts.show', $receipt) }}">بررسی</a></td>
                </tr>
            @empty
                <tr><td colspan="6">فیشی یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:10px;">{{ $receipts->links() }}</div>
</div>
@endsection
