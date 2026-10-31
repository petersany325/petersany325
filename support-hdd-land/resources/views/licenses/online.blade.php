@extends('layouts.app')
@section('title', 'گزارش آنلاین لایسنس | '.shop_name())
@section('page_title', 'گزارش آنلاین بودن لایسنس')
@section('window_title', 'آنلاین / آفلاین')

@section('content')
<div class="panel">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;">گزارش آنلاین بودن لایسنس</h2>
            <p class="muted" style="margin:6px 0 0;">نصب‌هایی که در ۷ روز اخیر با سرور لایسنس چک کرده‌اند «آنلاین» حساب می‌شوند.</p>
        </div>
        <a class="btn" href="{{ route('licenses.index') }}">← مرکز لایسنس</a>
    </div>

    <div class="accept-row accept-row-3" style="margin-bottom:16px;">
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted">فعال</div>
            <div style="font-size:22px;font-weight:800;">{{ $stats['active'] }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted">آنلاین</div>
            <div style="font-size:22px;font-weight:800;color:#0f6b3a;">{{ $stats['online'] }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted">آفلاین / بی‌پاسخ</div>
            <div style="font-size:22px;font-weight:800;color:#9a3412;">{{ $stats['offline'] }}</div>
        </div>
    </div>

    <h3>آنلاین</h3>
    <div class="table-wrap" style="margin-bottom:20px;">
        <table class="data">
            <thead>
            <tr>
                <th>سریال</th>
                <th>مشتری</th>
                <th>دامنه</th>
                <th>آخرین چک</th>
                <th>نسخه</th>
                <th>تعداد چک</th>
            </tr>
            </thead>
            <tbody>
            @forelse($onlineRows as $row)
                <tr>
                    <td dir="ltr"><code>{{ $row->license_key }}</code></td>
                    <td>{{ $row->customer_name ?: '—' }}</td>
                    <td dir="ltr">{{ $row->domain }}</td>
                    <td>{{ jalali_like($row->last_check_at) }}</td>
                    <td dir="ltr">{{ $row->last_check_version ?: '—' }}</td>
                    <td>{{ (int) $row->check_count }}</td>
                </tr>
            @empty
                <tr><td colspan="6">هنوز نصب آنلاینی گزارش نشده.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <h3>فعال ولی آفلاین / بی‌پاسخ</h3>
    <div class="table-wrap">
        <table class="data">
            <thead>
            <tr>
                <th>سریال</th>
                <th>مشتری</th>
                <th>دامنه</th>
                <th>فعال‌سازی</th>
                <th>آخرین چک</th>
            </tr>
            </thead>
            <tbody>
            @forelse($offlineRows as $row)
                <tr>
                    <td dir="ltr"><code>{{ $row->license_key }}</code></td>
                    <td>{{ $row->customer_name ?: '—' }}</td>
                    <td dir="ltr">{{ $row->domain ?: '—' }}</td>
                    <td>{{ $row->activated_at ? jalali_like($row->activated_at) : '—' }}</td>
                    <td>{{ $row->last_check_at ? jalali_like($row->last_check_at) : 'هرگز' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
