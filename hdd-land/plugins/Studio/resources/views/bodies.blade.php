@php
  $receiptUrl = $receiptUrl ?? \App\Support\SiteHost::receiptUrl();
  $shopUrl = $shopUrl ?? \App\Support\SiteHost::shopUrl('/');
  $slug = $slug ?? 'home';
@endphp

@if($slug === 'home')
@include('studio::home-atelier')
@endif

@if($slug === 'design')
<section class="st-hero">
  <div class="st-hero__glow st-hero__glow--steel" aria-hidden="true"></div>
  <div class="st-hero__glow st-hero__glow--amber" aria-hidden="true"></div>
  <div class="st-wrap">
  <p class="st-kicker">طراحی سایت</p>
  <h1>سایت کسب‌وکار، نه قالب آماده</h1>
  <p>سه مدل: فروشگاهی، شرکتی، خدماتی. همه به پنل ادمین و در صورت نیاز به حسابداری وصل می‌شوند.</p>
</div></section>
<section class="st-wrap st-sec" style="padding-bottom:2rem">
  <ul class="st-list">
    <li>سایت فروشگاهی با سبد و حساب مشتری</li>
    <li>سایت شرکتی با خدمات و تماس</li>
    <li>سایت خدماتی / نوبت‌دهی</li>
    <li>منوی سبک و قابل ویرایش از ادمین</li>
  </ul>
</section>
@endif

@if($slug === 'ai')
<section class="st-hero">
  <div class="st-hero__glow st-hero__glow--steel" aria-hidden="true"></div>
  <div class="st-hero__glow st-hero__glow--amber" aria-hidden="true"></div>
  <div class="st-wrap">
  <p class="st-kicker">پروژه هوش مصنوعی</p>
  <h1>AI روی کار واقعی حسابداری و پشتیبانی</h1>
  <p>شعار راه‌اندازی هوش مصنوعی نیست. هر قابلیت یک کار مشخص دارد.</p>
</div></section>
<section class="st-wrap st-sec" style="padding-bottom:2rem">
  <ul class="st-list">
    <li>پیشنهاد سرفصل سند حسابداری</li>
    <li>خواندن فاکتور و پیش‌نویس سند</li>
    <li>توضیح گزارش مالی به زبان ساده</li>
    <li>پاسخ اولیه تیکت مشتری</li>
  </ul>
</section>
@endif

@if($slug === 'accounting')
@include('studio::acc-ai')
@endif

@if($slug === 'projects')
<section class="st-hero">
  <div class="st-hero__glow st-hero__glow--steel" aria-hidden="true"></div>
  <div class="st-hero__glow st-hero__glow--amber" aria-hidden="true"></div>
  <div class="st-wrap">
  <p class="st-kicker">پروژه‌های سایت</p>
  <h1>نمونه از همین سیستم</h1>
  <p>پروژه‌هایی که استقرار شده‌اند؛ قالب تزئینی نیستند.</p>
</div></section>
<section class="st-wrap st-sec" style="padding-bottom:2rem">
  <div class="st-grid">
    <div class="st-card"><small>نرم‌افزار + سایت</small><b>مدیریت تعمیرگاه</b><p>پذیرش، قبض، ارجاع و تحویل.</p></div>
    <div class="st-card"><small>حسابداری</small><b>دفتر متصل به فروش</b><p>یک فروش = یک سند.</p></div>
    <div class="st-card"><small>افزونه</small><b>وب‌اپ و کارت ویزیت</b><p>نصب روی گوشی و عضویت پیامکی.</p></div>
  </div>
</section>
@endif

@if($slug === 'receipt')
<section class="st-hero">
  <div class="st-hero__glow st-hero__glow--steel" aria-hidden="true"></div>
  <div class="st-hero__glow st-hero__glow--amber" aria-hidden="true"></div>
  <div class="st-wrap">
  <p class="st-kicker">نرم‌افزار قبض تحت وب</p>
  <h1>پذیرش، پیگیری و تحویل روی قبض</h1>
  <p>سامانه زنده قبض جدا است. داده و دیتابیس آن از این سایت جابه‌جا یا پاک نمی‌شود.</p>
  <div class="st-actions">
    <a class="st-cta st-cta--on" href="{{ $receiptUrl }}" rel="noopener">ورود به سامانه قبض</a>
    <a class="st-cta st-cta--line" href="{{ url('/contact') }}">درخواست استقرار</a>
  </div>
</div></section>
<section class="st-wrap st-sec" style="padding-bottom:2rem">
  <ul class="st-list">
    <li>ثبت قبض تکی و گروهی</li>
    <li>جستجو با سریال، موبایل یا شماره قبض</li>
    <li>ارجاع به تعمیرکار و تحویل</li>
    <li>پیگیری مشتری روی سامانه جدا</li>
  </ul>
  <p class="st-note" style="margin-top:1rem">آدرس سامانه: <a href="{{ $receiptUrl }}" rel="noopener">{{ $receiptUrl }}</a> — این صفحه فقط معرفی نرم‌افزار است.</p>
</section>
@endif
