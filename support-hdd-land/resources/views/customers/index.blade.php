@extends('layouts.app')
@section('title', 'مشتریان | '.shop_name())
@section('page_title', 'مدیریت مشتریان')
@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2>{{ ($filter ?? '') === 'blacklist' ? 'لیست سیاه مشتریان' : 'مشتریان' }}</h2>
            <p class="lead">ویرایش، حذف، لیست سیاه، و جلوگیری از موبایل/نام تکراری</p>
        </div>
        <div class="actions" style="margin:0;">
            <a class="btn btn-ghost" href="{{ route('customers.index', ['filter' => 'blacklist']) }}">لیست سیاه</a>
            <a class="btn btn-ghost" href="{{ route('device-blacklists.index') }}">لیست سیاه دستگاه</a>
            <a class="btn btn-primary" href="{{ route('customers.create') }}">مشتری جدید</a>
        </div>
    </div>

    <form class="ticket-search-bar" method="GET" style="margin:8px 0;">
        @if(!empty($filter))
            <input type="hidden" name="filter" value="{{ $filter }}">
        @endif
        <div class="field">
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="نام، مستعار، تلفن، کد ملی" data-barcode data-ascii-en autocomplete="off">
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn btn-secondary" type="submit">جستجو</button>
            @if($q !== '' || ($filter ?? '') !== '')
                <a class="btn btn-ghost" href="{{ route('customers.index') }}">پاک</a>
            @endif
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>تلفن</th>
                    <th>کد ملی</th>
                    <th>شغل</th>
                    <th>نحوه آشنایی</th>
                    <th>وضعیت</th>
                    <th>قبض</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>
                        {{ $customer->displayName() }}
                        @if($customer->gender)
                            <span class="muted" style="font-size:11px;">({{ $customer->genderLabel() }})</span>
                        @endif
                    </td>
                    <td dir="ltr">{{ $customer->phone }}</td>
                    <td>{{ $customer->national_code ?: '—' }}</td>
                    <td>{{ $customer->job ?: '—' }}</td>
                    <td>{{ $customer->referralSource?->name ?: '—' }}</td>
                    <td>
                        @if($customer->is_blacklisted)
                            <span class="pill pill-off">لیست سیاه</span>
                        @else
                            <span class="pill pill-ok">عادی</span>
                        @endif
                    </td>
                    <td>{{ $customer->receptions_count ?? $customer->receptions()->count() }}</td>
                    <td>
                        <div class="actions" style="margin:0;">
                            <a class="btn btn-ghost" href="{{ route('customers.show', $customer) }}">نمایش</a>
                            <a class="btn btn-secondary" href="{{ route('customers.edit', $customer) }}">ویرایش</a>
                            @php
                                $rc = (int) ($customer->receptions_count ?? 0);
                                $confirm = $rc > 0
                                    ? "مشتری «{$customer->name}» {$rc} قبض دارد. از فهرست مشتریان حذف می‌شود ولی قبض‌ها می‌مانند. ادامه؟"
                                    : "مشتری «{$customer->name}» حذف شود؟";
                            @endphp
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" style="display:inline;" data-confirm="{{ $confirm }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">مشتری‌ای یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links('partials.pagination') }}
</div>
@endsection
