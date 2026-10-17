@php
  $receiptUrl = $receiptUrl ?? \App\Support\SiteHost::receiptUrl();
  $shopUrl = $shopUrl ?? \App\Support\SiteHost::shopUrl('/');
  $slug = $slug ?? 'home';
@endphp

@if($slug === 'home')
<section class="st-hero">
  <div class="st-wrap">
    <p class="st-kicker">یک تخصص، پنج خدمت</p>
    <h1>طراحی سایت، حسابداری تحت وب و هوش مصنوعی روی یک سیستم</h1>
    <p>فروش قطعه از این دامنه جدا شده است. قبض مشتریان روی سامانه جدا می‌ماند و داده‌اش اینجا نیست.</p>
    <div class="st-actions">
      <a class="st-cta st-cta--on" href="{{ url('/accounting') }}">نرم‌افزار حسابداری</a>
      <a class="st-cta st-cta--line" href="{{ url('/design') }}">طراحی سایت</a>
    </div>
  </div>
</section>
<section class="st-wrap st-sec">
  <div class="st-grid">
    <a class="st-card" href="{{ url('/design') }}"><small>منو</small><b>طراحی سایت</b><p>فروشگاهی، شرکتی و خدماتی؛ متصل به پنل و حسابداری.</p></a>
    <a class="st-card" href="{{ url('/ai') }}"><small>منو</small><b>پروژه AI</b><p>هوش مصنوعی روی حسابداری، فاکتور و پشتیبانی.</p></a>
    <a class="st-card" href="{{ url('/accounting') }}"><small>منو</small><b>حسابداری تحت وب</b><p>فاکتور، انبار، اقساط، چک و گزارش در مرورگر.</p></a>
    <a class="st-card" href="{{ url('/projects') }}"><small>منو</small><b>پروژه‌های سایت</b><p>نمونه استقرار سایت و افزونه برای کسب‌وکار.</p></a>
    <a class="st-card" href="{{ url('/receipt-software') }}"><small>منو</small><b>نرم‌افزار قبض تحت وب</b><p>پذیرش و پیگیری قبض؛ سامانه زنده جدا است.</p></a>
  </div>
</section>
<section class="st-wrap st-sec" style="padding-bottom:2rem">
  <div class="st-note">
    فروشگاه قطعات: <a href="{{ $shopUrl }}">{{ $shopUrl }}</a><br>
    سامانه قبض (دیتابیس جدا، دست‌نخورده): <a href="{{ $receiptUrl }}" rel="noopener">{{ $receiptUrl }}</a>
  </div>
</section>
@endif

@if($slug === 'design')
<section class="st-hero"><div class="st-wrap">
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
<section class="st-hero"><div class="st-wrap">
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
<section class="st-hero"><div class="st-wrap">
  <p class="st-kicker">نرم‌افزار حسابداری تحت وب</p>
  <h1>یک دفتر برای فروش، انبار و هزینه</h1>
  <p>در مرورگر کار می‌کند. فروش سایت و فاکتور روی همان سند می‌نشیند.</p>
</div></section>
<section class="st-wrap st-sec" style="padding-bottom:2rem">
  <ul class="st-list">
    <li>کدینگ حساب و سند روزانه</li>
    <li>فاکتور، پیش‌فاکتور و فاکتور خرید</li>
    <li>انبار، سریال، اقساط و دسته چک</li>
    <li>تراز آزمایشی، سود و زیان، ترازنامه</li>
  </ul>
</section>
@endif

@if($slug === 'projects')
<section class="st-hero"><div class="st-wrap">
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
<section class="st-hero"><div class="st-wrap">
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
