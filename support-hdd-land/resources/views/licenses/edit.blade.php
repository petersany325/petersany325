@extends('layouts.app')
@section('title', 'ویرایش لایسنس | '.shop_name())
@section('page_title', 'ویرایش لایسنس')
@section('window_title', 'ویرایش لایسنس')

@section('content')
<div class="panel">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h2 style="margin:0;">ویرایش لایسنس</h2>
            <p class="muted" style="margin:6px 0 0;" dir="ltr"><code>{{ $license->license_key }}</code></p>
        </div>
        <a class="btn" href="{{ route('licenses.index') }}">← بازگشت</a>
    </div>

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('licenses.update', $license) }}">
        @csrf
        <div class="accept-row accept-row-3" style="align-items:end;">
            <div>
                <label>نام مشتری</label>
                <input type="text" name="customer_name" value="{{ old('customer_name', $license->customer_name) }}">
            </div>
            <div>
                <label>موبایل</label>
                <input type="text" name="customer_phone" value="{{ old('customer_phone', $license->customer_phone) }}" required dir="ltr" style="text-align:left;" placeholder="09xxxxxxxxx" pattern="09[0-9]{9}" inputmode="numeric">
                <div class="muted" style="font-size:11px;margin-top:4px;">پیامک کد نصب فقط به این شماره می‌رود؛ مشتری نمی‌تواند شماره دیگری بدهد.</div>
            </div>
            <div>
                <label>ایمیل</label>
                <input type="email" name="customer_email" value="{{ old('customer_email', $license->customer_email) }}" dir="ltr" style="text-align:left;">
            </div>
            <div>
                <label>دامنه</label>
                <input type="text" name="domain" value="{{ old('domain', $license->domain) }}" dir="ltr" style="text-align:left;">
            </div>
            <div>
                <label>وضعیت</label>
                <select name="status" required>
                    @foreach(['unused'=>'استفاده‌نشده','active'=>'فعال','expired'=>'منقضی','revoked'=>'باطل'] as $val=>$label)
                        <option value="{{ $val }}" @selected(old('status', $license->status)===$val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>پلن</label>
                <select name="plan_code">
                    <option value="">— بدون تغییر پلن —</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan['code'] }}" @selected(old('plan_code', $license->plan_code)===$plan['code'])>
                            {{ $plan['label'] }} — {{ $plan['price'] > 0 ? number_format($plan['price']).' تومان' : 'بدون قیمت' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>قیمت (تومان)</label>
                <input type="number" name="price_toman" min="0" step="1000" value="{{ old('price_toman', (int) $license->price_toman) }}" dir="ltr" style="text-align:left;">
            </div>
            <div>
                <label>تاریخ شروع (شمسی)</label>
                <input type="text" name="starts_at" value="{{ old('starts_at', $license->startsAt() ? jalali_date($license->startsAt()) : '') }}" placeholder="1404/01/01" dir="ltr" style="text-align:left;">
            </div>
            <div>
                <label>تاریخ پایان (شمسی)</label>
                <input type="text" name="expires_at" value="{{ old('expires_at', $license->expires_at ? jalali_date($license->expires_at) : '') }}" placeholder="1405/01/01 یا خالی=مادام‌العمر" dir="ltr" style="text-align:left;">
            </div>
            <div style="grid-column:1/-1;">
                <label>یادداشت</label>
                <input type="text" name="notes" value="{{ old('notes', $license->notes) }}">
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
            <a class="btn" href="{{ route('licenses.renew', $license) }}">تمدید</a>
        </div>
    </form>
</div>
@endsection
