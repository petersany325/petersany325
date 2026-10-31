@extends('layouts.app')
@section('title', 'پیش‌سفارش قطعه | '.shop_name())
@section('page_title', 'صف ورود قطعه')
@section('window_title', 'پیش‌سفارش ارسال از شهر دیگر')

@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2 style="margin:0;">صف ورود قطعه از راه دور</h2>
            <p class="lead" style="margin:4px 0 0;">مشتری عکس و کد باربری می‌فرستد؛ بعد از رسیدن، تطبیق و ساخت قبض انجام می‌شود.</p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a class="btn btn-secondary" href="{{ route('remote-preorders.settings') }}">تنظیمات</a>
        </div>
    </div>

    @unless($enabled)
        <div class="alert error" style="margin-top:10px;">این قابلیت در تنظیمات غیرفعال است؛ مشتریان نمی‌توانند پیش‌سفارش جدید بفرستند.</div>
    @endunless

    <div class="emp-stat-row" style="margin-top:10px;">
        <div class="emp-stat"><span>باز</span><strong>{{ $stats['open'] }}</strong></div>
        <div class="emp-stat tone-sms"><span>در راه</span><strong>{{ $stats['pending_arrival'] }}</strong></div>
        <div class="emp-stat tone-amber"><span>رسیده</span><strong>{{ $stats['arrived'] }}</strong></div>
        <div class="emp-stat tone-ok"><span>قبض امروز</span><strong>{{ $stats['matched'] }}</strong></div>
    </div>

    <form class="ticket-search-bar" method="GET" style="margin:14px 0 8px;">
        <div class="field">
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="کد PRE / باربری / مشتری / سریال">
        </div>
        <div class="field">
            <label>وضعیت</label>
            <select name="status">
                <option value="open" @selected($status === 'open')>باز (در راه + رسیده)</option>
                <option value="" @selected($status === '')>همه</option>
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
                <th>کد</th>
                <th>مشتری</th>
                <th>قطعه</th>
                <th>باربری</th>
                <th>وضعیت</th>
                <th>زمان</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($preorders as $row)
                <tr>
                    <td dir="ltr"><strong>{{ $row->code }}</strong></td>
                    <td>
                        {{ $row->customer?->name ?: '—' }}
                        <div class="muted" dir="ltr">{{ $row->customer?->phone }}</div>
                    </td>
                    <td>
                        {{ $row->part_title }}
                        @if($row->origin_city)<div class="muted">{{ $row->origin_city }}</div>@endif
                    </td>
                    <td dir="ltr">{{ $row->tracking_code ?: '—' }}</td>
                    <td>{{ $statusLabels[$row->status] ?? $row->status }}</td>
                    <td>{{ jalali_like($row->created_at) }}</td>
                    <td><a class="btn btn-ghost btn-sm" href="{{ route('remote-preorders.show', $row) }}">بررسی</a></td>
                </tr>
            @empty
                <tr><td colspan="7">موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $preorders->links('partials.pagination') }}
</div>
@endsection
