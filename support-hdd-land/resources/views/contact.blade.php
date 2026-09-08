@extends($layout)

@section('title', 'تماس با ما | '.vendor_name())
@section('page_title', 'تماس با ما')
@section('window_title', 'پشتیبانی سرزمین هارد')

@section('content')
<div class="contact-page">
    <div class="contact-hero">
        <div>
            <div class="contact-k">تولیدکننده نرم‌افزار</div>
            <h1 class="contact-title">{{ $vendor['name'] }}</h1>
            <p class="contact-tagline">{{ $vendor['tagline'] }}</p>
        </div>
        <a class="contact-site-btn" href="{{ $vendor['url'] }}" target="_blank" rel="noopener">ورود به سایت شرکت</a>
    </div>

    <p class="contact-lead">
        این سامانه متعلق به تعمیرگاه <strong>{{ shop_name() }}</strong> است و با نرم‌افزار تخصصی
        <a href="{{ $vendor['url'] }}" target="_blank" rel="noopener">{{ $vendor['name'] }}</a>
        ساخته شده. برای پشتیبانی لایسنس، نصب و تمدید با شرکت تماس بگیرید.
    </p>

    <div class="contact-grid">
        <a class="contact-card" href="tel:{{ $vendor['phone'] }}">
            <span class="contact-k">تلفن پشتیبانی</span>
            <strong dir="ltr">{{ $vendor['phone'] }}</strong>
        </a>
        <a class="contact-card" href="tel:{{ $vendor['mobile'] }}">
            <span class="contact-k">موبایل</span>
            <strong dir="ltr">{{ $vendor['mobile'] }}</strong>
        </a>
        <a class="contact-card" href="{{ $vendor['url'] }}" target="_blank" rel="noopener">
            <span class="contact-k">وب‌سایت</span>
            <strong dir="ltr">{{ $vendor['host'] }}</strong>
        </a>
    </div>
</div>
@endsection
