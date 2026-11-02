@extends('layouts.app')
@section('title', 'تماس با ما | '.shop_name())
@section('page_title', 'تماس با ما')
@section('window_title', 'پشتیبانی سرزمین هارد')

@section('content')
<div class="panel">
    <h2 style="margin-top:0;">تماس با ما</h2>
    <p class="lead" style="margin-bottom:14px;">{{ $contact['title'] }}</p>

    <div class="accept-row accept-row-2">
        <div class="panel" style="margin:0;background:#f8fafc;">
            <div class="muted" style="font-size:12px;">شماره تلفن</div>
            <div style="font-weight:800;font-size:18px;margin-top:4px;" dir="ltr">
                <a href="tel:{{ $contact['phone'] }}" style="text-decoration:none;color:inherit;">{{ $contact['phone'] }}</a>
            </div>
        </div>
        <div class="panel" style="margin:0;background:#f0fdf4;">
            <div class="muted" style="font-size:12px;">واتساپ</div>
            <div style="font-weight:800;font-size:18px;margin-top:4px;" dir="ltr">
                <a href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener" style="text-decoration:none;color:inherit;">{{ $contact['whatsapp'] }}</a>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-top:12px;background:#eff6ff;">
        <div class="muted" style="font-size:12px;">سایت</div>
        <div style="font-weight:800;font-size:16px;margin-top:4px;" dir="ltr">
            <a href="{{ $contact['website_url'] }}" target="_blank" rel="noopener">{{ $contact['website'] }}</a>
        </div>
    </div>

    <div class="actions" style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;">
        <a class="btn btn-primary" href="tel:{{ $contact['phone'] }}">تماس تلفنی</a>
        <a class="btn btn-secondary" href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener">چت واتساپ</a>
        <a class="btn" href="{{ $contact['website_url'] }}" target="_blank" rel="noopener">باز کردن سایت</a>
    </div>
</div>
@endsection
