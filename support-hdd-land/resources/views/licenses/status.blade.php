@extends('layouts.app')
@section('title', 'وضعیت لایسنس | '.shop_name())
@section('page_title', 'لایسنس نصب')
@section('window_title', 'سریال فعال این نصب')

@section('content')
<div class="panel">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;">وضعیت لایسنس این نصب</h2>
            <p class="muted" style="margin:6px 0 0;">سریال فعال همین دامنه — نه مرکز صدور لایسنس فروشنده</p>
        </div>
        @if(!empty($license['ok']))
            <span class="pill ok" style="font-size:13px;padding:6px 12px;">فعال / معتبر</span>
        @else
            <span class="pill bad" style="font-size:13px;padding:6px 12px;">نامعتبر</span>
        @endif
    </div>

    <div class="accept-row accept-row-2" style="margin-bottom:16px;">
        <div class="panel" style="padding:14px;margin:0;">
            <div class="muted" style="font-size:12px;margin-bottom:6px;">سریال لایسنس</div>
            <div style="font-size:20px;font-weight:800;letter-spacing:.04em;direction:ltr;text-align:left;" dir="ltr">
                {{ $license['key'] ?? '—' }}
            </div>
        </div>
        <div class="panel" style="padding:14px;margin:0;">
            <div class="muted" style="font-size:12px;margin-bottom:6px;">پلن</div>
            <div style="font-size:20px;font-weight:800;">
                {{ $license['plan_text'] ?? '—' }}
                @if(!empty($license['plan_months']))
                    <span class="muted" style="font-size:13px;font-weight:600;">({{ $license['plan_months'] }} ماه)</span>
                @endif
            </div>
        </div>
    </div>

    <table style="width:100%;border-collapse:collapse;">
        <tbody>
            <tr>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;width:180px;" class="muted">دامنه قفل‌شده</td>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;direction:ltr;text-align:left;" dir="ltr">{{ $license['domain'] ?? '—' }}</td>
            </tr>
            <tr>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;" class="muted">شروع اعتبار</td>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;">
                    {{ $license['activated_jalali'] ?? ($license['activated_at'] ?? '—') }}
                </td>
            </tr>
            <tr>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;" class="muted">پایان اعتبار</td>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;">
                    @if(!empty($license['lifetime']))
                        مادام‌العمر
                    @else
                        {{ $license['expires_jalali'] ?? ($license['expires_at'] ?? '—') }}
                    @endif
                </td>
            </tr>
            @if(!empty($license['price_toman']))
            <tr>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;" class="muted">مبلغ پلن</td>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;">{{ number_format((int) $license['price_toman']) }} تومان</td>
            </tr>
            @endif
            @if(!empty($license['message']))
            <tr>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;" class="muted">آخرین وضعیت</td>
                <td style="padding:10px 8px;border-bottom:1px solid #e5e7eb;">{{ $license['message'] }}</td>
            </tr>
            @endif
            @if(!empty($license['checked_at']))
            <tr>
                <td style="padding:10px 8px;" class="muted">آخرین بررسی</td>
                <td style="padding:10px 8px;direction:ltr;text-align:left;" dir="ltr">{{ $license['checked_at'] }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <p class="muted" style="margin:16px 0 0;font-size:13px;line-height:1.8;">
        این صفحه فقط لایسنس فعال <strong>همین نصب</strong> را نشان می‌دهد.
        برای تمدید یا تغییر دامنه با فروشنده / پشتیبانی HDD Land هماهنگ کنید.
    </p>
</div>
@endsection
