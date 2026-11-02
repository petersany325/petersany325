@extends('layouts.app')
@section('title', 'پیامک اقساط | '.shop_name())
@section('page_title', 'تنظیمات پیامک اقساط')
@section('window_title', 'یادآوری سررسید')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'پیامک اقساط',
    'accSub' => 'قبل از سررسید / روز سررسید / بعد از سررسید + تشکر از پرداخت',
])

<div class="acc-desk">
    <section class="acc-panel">
        <form method="post" action="{{ route('installments.settings.update') }}" class="form-grid">
            @csrf
            <label style="grid-column:1/-1;">
                <input type="checkbox" name="enabled" value="1" @checked($settings['enabled'])>
                فعال بودن پیامک اقساط (علاوه بر سوئیچ کلی پیامک سیستم)
            </label>
            <label>چند روز قبل از سررسید؟
                <input type="number" name="before_days" min="0" max="60" value="{{ $settings['before_days'] }}" dir="ltr">
                <span class="muted" style="font-size:11px;">۰ = بدون یادآوری قبل</span>
            </label>
            <label>
                <input type="checkbox" name="on_due" value="1" @checked($settings['on_due'])>
                پیامک در روز سررسید
            </label>
            <label>روزهای بعد از سررسید (با ویرگول)
                <input type="text" name="after_days" value="{{ implode(',', $settings['after_days']) }}" dir="ltr" placeholder="1,3,7">
            </label>

            <label style="grid-column:1/-1;">قالب قبل از سررسید
                <textarea name="tpl_before" rows="2">{{ $settings['tpl_before'] }}</textarea>
            </label>
            <label style="grid-column:1/-1;">قالب روز سررسید
                <textarea name="tpl_due" rows="2">{{ $settings['tpl_due'] }}</textarea>
            </label>
            <label style="grid-column:1/-1;">قالب بعد از سررسید
                <textarea name="tpl_after" rows="2">{{ $settings['tpl_after'] }}</textarea>
            </label>
            <label style="grid-column:1/-1;">قالب تشکر از پرداخت
                <textarea name="tpl_thanks" rows="2">{{ $settings['tpl_thanks'] }}</textarea>
            </label>
            <p class="muted" style="grid-column:1/-1;font-size:11.5px;margin:0;">
                متغیرها: {shop} {customer} {seq} {amount} {remain} {paid} {due} {days}
            </p>

            <label style="grid-column:1/-1;">
                <input type="checkbox" name="regenerate_cron_token" value="1">
                تولید مجدد توکن کرون
            </label>

            <div class="actions" style="grid-column:1/-1;">
                <button type="submit" class="btn btn-primary">ذخیره</button>
            </div>
        </form>

        <div style="margin-top:16px;padding-top:12px;border-top:1px solid #e5e7eb;">
            <p class="muted" style="font-size:12px;">کرون HTTP (روزانه پیشنهاد می‌شود):</p>
            <code dir="ltr" style="display:block;word-break:break-all;font-size:12px;">
                {{ url('/cron/installments') }}?token={{ $settings['cron_token'] }}
            </code>
            <form method="post" action="{{ route('installments.reminders.run') }}" style="margin-top:10px;">
                @csrf
                <button class="btn btn-ghost btn-sm" type="submit">اجرای دستی یادآوری‌ها الان</button>
            </form>
        </div>
    </section>
</div>
@endsection
