@extends('layouts.app')
@section('title', 'مشتریان | سرزمین هارد')
@section('page_title', 'مدیریت مشتریان')
@section('content')
<div class="panel">
    @include('partials.flash')
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2>مشتریان</h2>
            <p class="lead">ویرایش، حذف، و جلوگیری از موبایل/نام تکراری</p>
        </div>
        <a class="btn btn-primary" href="{{ route('customers.create') }}">مشتری جدید</a>
    </div>

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="ticket-search-bar" method="GET" style="margin:8px 0;">
        <div class="field">
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="نام، تلفن، کد ملی" data-barcode data-ascii-en autocomplete="off">
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn btn-secondary" type="submit">جستجو</button>
            @if($q !== '')
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
                    <th>قبض</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td dir="ltr">{{ $customer->phone }}</td>
                    <td>{{ $customer->national_code ?: '—' }}</td>
                    <td>{{ $customer->job ?: '—' }}</td>
                    <td>{{ $customer->referralSource?->name ?: '—' }}</td>
                    <td>{{ $customer->receptions_count ?? $customer->receptions()->count() }}</td>
                    <td>
                        <div class="actions" style="margin:0;">
                            <a class="btn btn-ghost" href="{{ route('customers.show', $customer) }}">نمایش</a>
                            <a class="btn btn-secondary" href="{{ route('customers.edit', $customer) }}">ویرایش</a>
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" style="display:inline;" data-confirm="مشتری «{{ $customer->name }}» حذف شود؟">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">مشتری‌ای یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links('partials.pagination') }}
</div>
@endsection
