@extends('layouts.app')
@section('title', 'تنظیمات پیش‌سفارش قطعه | '.shop_name())
@section('page_title', 'تنظیمات پیش‌سفارش')
@section('window_title', 'ارسال قطعه از راه دور')

@section('content')
<div class="panel" style="max-width:720px;">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
        <div>
            <h2 style="margin:0;">تنظیمات ارسال قطعه از شهر دیگر</h2>
            <p class="lead" style="margin:4px 0 0;">فعال‌سازی در پورتال مشتری و قوانین عکس.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('remote-preorders.index') }}">صف ورود قطعه</a>
    </div>

    <form method="POST" action="{{ route('remote-preorders.settings.save') }}" style="margin-top:14px;display:grid;gap:10px;">
        @csrf
        @include('partials.toggle', [
            'name' => 'enabled',
            'label' => 'فعال در کارتابل مشتری',
            'checked' => old('enabled', $settings['enabled']),
        ])
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <label>حداقل تعداد عکس
                <input type="number" name="min_photos" min="1" max="8" value="{{ old('min_photos', $settings['min_photos']) }}" required>
            </label>
            <label>حداکثر تعداد عکس
                <input type="number" name="max_photos" min="1" max="8" value="{{ old('max_photos', $settings['max_photos']) }}" required>
            </label>
        </div>
        <label>متن راهنمای مشتری
            <textarea name="instructions" rows="4" maxlength="1000">{{ old('instructions', $settings['instructions']) }}</textarea>
        </label>
        <button class="btn btn-primary" type="submit">ذخیره تنظیمات</button>
    </form>
</div>
@endsection
