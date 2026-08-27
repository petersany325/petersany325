@extends('layouts.app')
@section('title', 'تمدید لایسنس | '.shop_name())
@section('page_title', 'تمدید لایسنس')
@section('window_title', 'تمدید لایسنس')

@section('content')
<div class="panel">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;">تمدید لایسنس</h2>
            <p class="muted" style="margin:6px 0 0;" dir="ltr"><code>{{ $license->license_key }}</code> — {{ $license->customer_name ?: 'بدون نام' }}</p>
        </div>
        <a class="btn" href="{{ route('licenses.index') }}">← بازگشت</a>
    </div>

    <div class="accept-row accept-row-3" style="margin-bottom:16px;">
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted">شروع</div>
            <div style="font-weight:800;">{{ $license->startsAt() ? jalali_date($license->startsAt()) : '—' }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted">پایان فعلی</div>
            <div style="font-weight:800;">{{ $license->expires_at ? jalali_date($license->expires_at) : 'مادام‌العمر / نامشخص' }}</div>
        </div>
        <div class="panel" style="padding:12px;margin:0;">
            <div class="muted">پلن فعلی</div>
            <div style="font-weight:800;">{{ $license->planSummary() }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('licenses.extend', $license) }}" class="panel" style="background:#f7fafc;">
        @csrf
        <input type="hidden" name="add_plan" value="1">
        <h3 style="margin-top:0;">تمدید با پلن</h3>
        <p class="muted">مدت پلن به تاریخ پایان فعلی (اگر آینده باشد) اضافه می‌شود؛ وگرنه از امروز.</p>
        <div class="accept-row accept-row-2" style="align-items:end;">
            <div>
                <label>پلن تمدید</label>
                <select name="plan_code" required>
                    @foreach($plans as $plan)
                        <option value="{{ $plan['code'] }}" @selected(($license->plan_code ?: 'm6') === $plan['code'])>
                            {{ $plan['label'] }}
                            — {{ $plan['price'] > 0 ? number_format($plan['price']).' تومان' : 'بدون قیمت' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button class="btn btn-primary" type="submit">ثبت تمدید</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('licenses.extend', $license) }}" class="panel" style="margin-top:14px;">
        @csrf
        <h3 style="margin-top:0;">یا تاریخ پایان دستی (شمسی)</h3>
        <div class="accept-row accept-row-2" style="align-items:end;">
            <div>
                <label>تاریخ پایان جدید</label>
                <input type="text" name="expires_at" value="{{ $license->expires_at ? jalali_date($license->expires_at) : '' }}" placeholder="1405/06/01" dir="ltr" style="text-align:left;" required>
            </div>
            <div>
                <button class="btn" type="submit">ذخیره تاریخ پایان</button>
            </div>
        </div>
    </form>
</div>
@endsection
