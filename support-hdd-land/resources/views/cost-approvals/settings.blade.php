@extends('layouts.app')
@section('title', 'تنظیم خدمات تأیید هزینه | سرزمین هارد')
@section('page_title', 'خدمات مشمول تأیید هزینه')
@section('window_title', 'جراحی / بازیابی و سایر خدمات')

@section('content')
<div class="panel" style="max-width:720px;">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2 style="margin:0;">کدام خدمات نیاز به تأیید مشتری دارند؟</h2>
            <p class="lead" style="margin:4px 0 0;">فقط برای این خدمات لینک تأیید هزینه پیشنهاد/ارسال می‌شود.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('cost-approvals.index') }}">بازگشت به کارتابل</a>
    </div>

    <form method="POST" action="{{ route('cost-approvals.settings.save') }}" style="margin-top:14px;">
        @csrf
        <div class="accept-row accept-row-2">
            @foreach($options as $name)
                <label class="check" style="display:flex;gap:8px;align-items:center;font-size:12.5px;border:1px solid #d7dde6;border-radius:8px;padding:8px 10px;background:#f7f8fa;">
                    <input type="checkbox" name="services[]" value="{{ $name }}" @checked(in_array($name, $enabled, true))>
                    <span>{{ $name }}</span>
                </label>
            @endforeach
        </div>

        <div style="margin-top:12px;">
            <label>خدمت سفارشی (اگر در لیست نبود)</label>
            <input type="text" name="custom_service" placeholder="مثلاً جراحی کنترلر" value="{{ old('custom_service') }}">
        </div>

        <div style="margin-top:12px;">
            <label>متن شرایط روی صفحه تأیید مشتری</label>
            <textarea name="terms" rows="5">{{ old('terms', $terms) }}</textarea>
        </div>

        <div class="actions" style="margin-top:12px;">
            <button class="btn btn-primary" type="submit">ذخیره تنظیمات</button>
        </div>
    </form>
</div>
@endsection
