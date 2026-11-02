@extends('layouts.portal')
@section('title', 'ارسال قطعه | '.shop_name())

@section('content')
<div class="portal-shell">
    <div class="p-card">
        <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2>ارسال قطعه از شهر دیگر</h2>
                <p class="p-lead" style="margin:0;">پیش‌سفارش ثبت می‌کنید؛ بعد از رسیدن بار، پذیرش قبض انجام می‌شود.</p>
            </div>
            @if($enabled)
                <a class="p-btn primary" href="{{ route('portal.preorders.create') }}">پیش‌سفارش جدید</a>
            @endif
        </div>
        @unless($enabled)
            <div class="p-alert err" style="margin-top:10px;">این قابلیت فعلاً غیرفعال است.</div>
        @endunless
    </div>

    <div class="p-card">
        <h3>پیش‌سفارش‌های من</h3>
        @forelse($preorders as $row)
            <a href="{{ route('portal.preorders.show', $row) }}" class="p-ticket-card" style="display:block;text-decoration:none;color:inherit;border-top:1px solid var(--line);padding:10px 0;">
                <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                    <strong dir="ltr">{{ $row->code }}</strong>
                    <span class="p-chip tone-amber">{{ $row->statusLabel() }}</span>
                </div>
                <div style="margin-top:4px;font-size:12.5px;">{{ $row->part_title }}</div>
                <div class="muted" style="font-size:11px;margin-top:2px;">
                    {{ jalali_like($row->created_at) }}
                    @if($row->tracking_code) · باربری <span dir="ltr">{{ $row->tracking_code }}</span>@endif
                    @if($row->origin_city) · {{ $row->origin_city }}@endif
                </div>
            </a>
        @empty
            <p class="muted">هنوز پیش‌سفارشی ندارید.</p>
        @endforelse
        {{ $preorders->links() }}
    </div>
</div>
@endsection
