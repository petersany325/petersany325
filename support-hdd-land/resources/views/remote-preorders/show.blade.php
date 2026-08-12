@extends('layouts.app')
@section('title', $preorder->code.' | '.shop_name())
@section('page_title', 'بررسی پیش‌سفارش')
@section('window_title', $preorder->code)

@section('content')
<div class="panel" style="margin-bottom:10px;">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
        <div>
            <h2 style="margin:0;">بررسی مشخصات و تأیید قبض</h2>
            <p class="lead" style="margin:4px 0 0;">
                {{ $preorder->customer?->name }}
                · <span dir="ltr">{{ $preorder->code }}</span>
                @if($preorder->tracking_code) · باربری <span dir="ltr">{{ $preorder->tracking_code }}</span>@endif
            </p>
            <p class="muted" style="margin:6px 0 0;font-size:12px;">اول مشخصات را با قطعه واقعی ویرایش کنید، بعد قبض را تأیید یا رد کنید.</p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
            <span class="daybook-status warn">{{ $statusLabels[$preorder->status] ?? $preorder->status }}</span>
            <a class="btn btn-ghost" href="{{ route('remote-preorders.index') }}">بازگشت</a>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:minmax(0,1.05fr) minmax(300px,.95fr);gap:10px;align-items:start;">
    <section class="panel">
        <h3 style="margin-top:0;">عکس و توضیح مشتری</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;">
            @forelse($preorder->photoList() as $photo)
                <a href="{{ route('remote-preorders.photo', ['preorder' => $preorder, 'path' => $photo['path']]) }}" target="_blank" rel="noopener">
                    <img src="{{ route('remote-preorders.photo', ['preorder' => $preorder, 'path' => $photo['path']]) }}"
                         alt="عکس"
                         style="width:100%;aspect-ratio:1.15;object-fit:cover;border:1px solid #c5ccd6;border-radius:3px;background:#f1f5f9;">
                </a>
            @empty
                <p class="muted">عکسی نیست.</p>
            @endforelse
        </div>
        <div style="margin-top:12px;display:grid;gap:8px;font-size:12.5px;">
            <div><span class="muted">ثبت اولیه مشتری:</span> {{ $preorder->part_title }}</div>
            @if($preorder->description)<div><span class="muted">توضیح مشتری:</span> {{ $preorder->description }}</div>@endif
            @if($preorder->origin_city)<div><span class="muted">مبدأ:</span> {{ $preorder->origin_city }}</div>@endif
            <div><span class="muted">تلفن مشتری:</span> <span dir="ltr">{{ $preorder->customer?->phone }}</span></div>
            <div><span class="muted">تلفن دفتر در پیامک:</span> <span dir="ltr">{{ $officePhone }}</span></div>
        </div>
    </section>

    <section class="panel">
        <h3 style="margin-top:0;">اقدام منشی / مدیر</h3>

        @if($preorder->reception)
            <p>قبض ساخته شده:
                <a href="{{ route('receptions.show', $preorder->reception) }}">{{ $preorder->reception->ticket_no }}</a>
            </p>
        @endif

        @if($preorder->status === 'pending_arrival')
            <form method="POST" action="{{ route('remote-preorders.arrived', $preorder) }}" style="margin-bottom:12px;">
                @csrf
                <button class="btn btn-secondary" type="submit">علامت «بار رسیده»</button>
            </form>
        @endif

        @if($preorder->canConvert())
            <form method="POST" action="{{ route('remote-preorders.convert', $preorder) }}" style="display:grid;gap:8px;">
                @csrf
                <label>نوع قطعه / عنوان
                    <input type="text" name="part_title" value="{{ old('part_title', $preorder->part_title) }}" required>
                </label>
                <label>سریال (انگلیسی)
                    <input type="text" name="serial_number" value="{{ old('serial_number', $preorder->serial_number) }}"
                           class="field-latin" dir="ltr" style="text-align:left;" lang="en" spellcheck="false" placeholder="SERIAL">
                </label>
                <label>برند و مدل (انگلیسی)
                    <input type="text" name="brand_model" value="{{ old('brand_model', $preorder->brand_model) }}"
                           class="field-latin" dir="ltr" style="text-align:left;" lang="en" spellcheck="false" placeholder="BRAND MODEL">
                </label>
                <label>نوع خرابی / شرح
                    <textarea name="reported_fault" rows="3">{{ old('reported_fault', $preorder->description) }}</textarea>
                </label>
                <label>یادداشت منشی (در رد، برای مشتری هم می‌رود)
                    <textarea name="admin_note" rows="2">{{ old('admin_note', $preorder->admin_note) }}</textarea>
                </label>
                <label style="display:flex;gap:8px;align-items:center;font-weight:700;">
                    <input type="checkbox" name="notify_customer" value="1" checked>
                    ارسال پیام کارتابل + اس‌ام‌اس
                </label>

                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px;">
                    <button class="btn btn-ghost" type="submit" formaction="{{ route('remote-preorders.specs', $preorder) }}">فقط ذخیره ویرایش</button>
                    <button class="btn btn-primary" type="submit" name="match_result" value="ok">تأیید و صدور قبض</button>
                    <button class="btn btn-secondary" type="submit" name="match_result" value="mismatch">تأیید نشد · نیاز به تماس</button>
                </div>
                <p class="muted" style="margin:0;font-size:11px;">بعد از تأیید/رد، پیام کارتابل و اس‌ام‌اس ارسال می‌شود. تلفن دفتر: {{ $officePhone }}</p>
            </form>
        @else
            <p class="muted">این پیش‌سفارش دیگر قابل تبدیل نیست.</p>
            @if($preorder->admin_note)
                <p><span class="muted">یادداشت:</span> {{ $preorder->admin_note }}</p>
            @endif
            @if($preorder->reviewer)
                <p class="muted" style="font-size:11px;">بررسی: {{ $preorder->reviewer->name }} · {{ jalali_like($preorder->reviewed_at) }}</p>
            @endif
        @endif
    </section>
</div>
@endsection
