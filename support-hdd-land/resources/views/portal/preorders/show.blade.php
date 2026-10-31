@extends('layouts.portal')
@section('title', $preorder->code.' | '.shop_name())

@section('content')
<div class="portal-shell">
    <div class="p-card">
        <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:center;">
            <div>
                <h2>پیش‌سفارش قطعه</h2>
                <p class="p-lead" style="margin:0;">{{ $preorder->part_title }}</p>
            </div>
            <span class="p-chip tone-amber">{{ $preorder->statusLabel() }}</span>
        </div>

        <div class="p-code-box" dir="ltr" style="margin-top:12px;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:2px;font-family:Consolas,Menlo,monospace;font-weight:800;letter-spacing:.04em;">
            {{ $preorder->code }}
        </div>
        <p class="muted" style="margin:8px 0 0;font-size:12px;">این کد را روی بسته یا فاکتور باربری بنویسید تا موقع تحویل سریع پیدا شود.</p>
        <p class="p-office-phone" style="margin:10px 0 0;">تلفن دفتر: <a href="tel:{{ shop_office_phone() }}" dir="ltr">{{ shop_office_phone() }}</a></p>
    </div>

    @if($preorder->status === 'matched' && $preorder->reception)
        <div class="p-card" style="border-color:#86efac;background:#f0fdf4;">
            <strong>قبض تأیید شد</strong>
            <p class="muted" style="margin:4px 0 0;">شماره قبض: <a href="{{ route('portal.show', $preorder->reception) }}">{{ $preorder->reception->ticket_no }}</a></p>
        </div>
    @elseif($preorder->status === 'rejected')
        <div class="p-card" style="border-color:#f5d59a;background:#fff8eb;">
            <strong>قبض تأیید نشد · نیاز به تماس</strong>
            <p class="muted" style="margin:4px 0 0;">{{ $preorder->admin_note ?: 'لطفاً با دفتر تماس بگیرید.' }}</p>
            <p class="p-office-phone" style="margin:8px 0 0;">تلفن دفتر: <a href="tel:{{ shop_office_phone() }}" dir="ltr">{{ shop_office_phone() }}</a></p>
            <a class="p-btn ghost" style="margin-top:8px;" href="{{ route('portal.messages') }}">مشاهده پیام‌ها</a>
        </div>
    @endif

    <div class="p-card">
        <h3>جزئیات</h3>
        <div class="p-kv" style="display:grid;gap:8px;font-size:12.5px;">
            @if($preorder->brand_model)<div><span class="muted">برند/مدل</span><div dir="ltr">{{ $preorder->brand_model }}</div></div>@endif
            @if($preorder->serial_number)<div><span class="muted">سریال</span><div dir="ltr">{{ $preorder->serial_number }}</div></div>@endif
            @if($preorder->tracking_code)<div><span class="muted">باربری</span><div dir="ltr">{{ $preorder->tracking_code }}</div></div>@endif
            @if($preorder->origin_city)<div><span class="muted">مبدأ</span><div>{{ $preorder->origin_city }}</div></div>@endif
            @if($preorder->description)<div><span class="muted">توضیح</span><div>{{ $preorder->description }}</div></div>@endif
            @if($preorder->admin_note)<div><span class="muted">یادداشت پذیرش</span><div>{{ $preorder->admin_note }}</div></div>@endif
            @if($preorder->reception)
                <div>
                    <span class="muted">قبض ساخته‌شده</span>
                    <div><a href="{{ route('portal.show', $preorder->reception) }}">{{ $preorder->reception->ticket_no }}</a></div>
                </div>
            @endif
        </div>
    </div>

    <div class="p-card">
        <h3>عکس‌ها</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            @foreach($preorder->photoList() as $photo)
                <a href="{{ route('portal.preorders.photo', ['preorder' => $preorder, 'path' => $photo['path']]) }}" target="_blank" rel="noopener">
                    <img src="{{ route('portal.preorders.photo', ['preorder' => $preorder, 'path' => $photo['path']]) }}" alt="عکس قطعه" style="width:100%;aspect-ratio:1.1;object-fit:cover;border:1px solid #9aa5b5;border-radius:2px;background:#eef1f5;">
                </a>
            @endforeach
        </div>
    </div>

    <div class="p-card">
        <h3>روند</h3>
        <ol class="muted" style="margin:0;padding-right:18px;font-size:12.5px;line-height:1.8;">
            <li>ثبت توسط شما</li>
            <li @if($preorder->status === 'pending_arrival') style="font-weight:800;color:#92400e;" @endif>رسیدن به شرکت</li>
            <li @if(in_array($preorder->status, ['arrived'])) style="font-weight:800;color:#92400e;" @endif>تطبیق و ساخت قبض</li>
            <li @if($preorder->status === 'matched') style="font-weight:800;color:#166534;" @endif>شروع روند تعمیر</li>
        </ol>
        <div style="margin-top:12px;">
            <a class="p-btn ghost" href="{{ route('portal.preorders.index') }}">همه پیش‌سفارش‌ها</a>
        </div>
    </div>
</div>
@endsection
