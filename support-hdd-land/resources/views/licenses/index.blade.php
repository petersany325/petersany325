@extends('layouts.app')
@section('title', 'لایسنس‌های محصول | '.shop_name())
@section('page_title', 'صدور لایسنس نصب')
@section('window_title', 'سریال نصب مشتریان')

@section('content')
<div class="panel">
    <h2 style="margin-top:0;">صدور سریال نصب</h2>
    <p class="muted">سریال را به مشتری بدهید؛ موقع نصب در ویزارد وارد می‌کند و به دامنه قفل می‌شود.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('licenses.issue') }}" class="accept-row accept-row-3" style="align-items:end;margin-bottom:16px;">
        @csrf
        <div>
            <label>نام مشتری</label>
            <input type="text" name="customer_name" placeholder="اختیاری">
        </div>
        <div>
            <label>موبایل</label>
            <input type="text" name="customer_phone" dir="ltr" style="text-align:left;" placeholder="اختیاری">
        </div>
        <div>
            <label>انقضا (اختیاری)</label>
            <input type="date" name="expires_at">
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn btn-primary" type="submit">صدور سریال جدید</button>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
            <tr>
                <th>سریال</th>
                <th>مشتری</th>
                <th>دامنه</th>
                <th>وضعیت</th>
                <th>فعال‌سازی</th>
                <th>آخرین چک</th>
            </tr>
            </thead>
            <tbody>
            @forelse($licenses as $row)
                <tr>
                    <td dir="ltr" style="font-weight:700;">{{ $row->license_key }}</td>
                    <td>{{ $row->customer_name ?: '—' }}<div class="muted" dir="ltr">{{ $row->customer_phone }}</div></td>
                    <td dir="ltr">{{ $row->domain ?: '—' }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ $row->activated_at ? jalali_like($row->activated_at) : '—' }}</td>
                    <td>{{ $row->last_check_at ? jalali_like($row->last_check_at) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">سریالی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $licenses->links('partials.pagination') }}
</div>
@endsection
