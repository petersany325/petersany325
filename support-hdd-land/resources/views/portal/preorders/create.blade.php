@extends('layouts.portal')
@section('title', 'ثبت پیش‌سفارش قطعه | '.shop_name())

@section('content')
<div class="portal-shell">
    <div class="p-card">
        <h2>ثبت ارسال قطعه</h2>
        <p class="p-lead">{{ $settings['instructions'] }}</p>

        <form method="POST" action="{{ route('portal.preorders.store') }}" enctype="multipart/form-data" class="p-form">
            @csrf
            <label>نوع قطعه / دستگاه *
                <input type="text" name="part_title" value="{{ old('part_title') }}" required maxlength="160" placeholder="مثلاً PCB هارد اکسترنال">
            </label>
            <label>برند و مدل (انگلیسی)
                <input type="text" name="brand_model" value="{{ old('brand_model') }}" maxlength="160" dir="ltr" style="text-align:left;" placeholder="BRAND MODEL" lang="en" spellcheck="false">
            </label>
            <label>سریال (اگر روی قطعه هست)
                <input type="text" name="serial_number" value="{{ old('serial_number') }}" maxlength="120" dir="ltr" style="text-align:left;" placeholder="SERIAL" lang="en" spellcheck="false">
            </label>
            <label>توضیح
                <textarea name="description" rows="4" maxlength="2000" placeholder="وضعیت قطعه، خرابی، نکات ارسال…">{{ old('description') }}</textarea>
            </label>
            <div class="p-form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <label>کد باربری
                    <input type="text" name="tracking_code" value="{{ old('tracking_code') }}" maxlength="80" dir="ltr" style="text-align:left;" placeholder="TPX-…">
                </label>
                <label>شهر مبدأ
                    <input type="text" name="origin_city" value="{{ old('origin_city') }}" maxlength="80" placeholder="مثلاً اصفهان">
                </label>
            </div>
            <label>عکس قطعه * ({{ $settings['min_photos'] }} تا {{ $settings['max_photos'] }} فایل)
                <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
            </label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="p-btn primary" type="submit">ثبت پیش‌سفارش</button>
                <a class="p-btn ghost" href="{{ route('portal.preorders.index') }}">بازگشت</a>
            </div>
        </form>
    </div>
</div>
@endsection
