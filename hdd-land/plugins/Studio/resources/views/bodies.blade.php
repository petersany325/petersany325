@php
  $receiptUrl = $receiptUrl ?? \App\Support\SiteHost::receiptUrl();
  $shopUrl = $shopUrl ?? \App\Support\SiteHost::shopUrl('/');
  $slug = $slug ?? 'home';
  $studioHome = class_exists(\App\Support\StudioHomeConfig::class) ? \App\Support\StudioHomeConfig::get() : [];
  $agency = ! empty($studioHome['enabled']);
@endphp

@if($slug === 'home')
  @if($agency)
    @include('studio::home-agency', ['studioHome' => $studioHome])
  @else
    @include('studio::home-atelier')
  @endif
@endif

@if($slug === 'design')
@include('studio::design')
@endif

@if($slug === 'design-service')
@include('studio::design-service')
@endif

@if($slug === 'about')
@include('studio::about')
@endif

@if($slug === 'ai')
<section class="{{ $agency ? 'ag-page' : 'st-hero' }}">
  @unless($agency)
    <div class="st-hero__glow st-hero__glow--steel" aria-hidden="true"></div>
    <div class="st-hero__glow st-hero__glow--amber" aria-hidden="true"></div>
  @endunless
  <div class="{{ $agency ? 'ag-wrap' : 'st-wrap' }}">
    <p class="{{ $agency ? 'ag-kicker' : 'st-kicker' }}">هوش مصنوعی شرکت فراز نت آمل</p>
    <h1>هوش مصنوعی برای سایت و نرم‌افزار</h1>
    <p class="{{ $agency ? 'ag-lead ag-lead--wide' : '' }}">ما شرکت طراحی سایت و ساخت نرم‌افزار هستیم. هوش مصنوعی لایه سوم همان کار است: داخل سایت، داخل سامانه و داخل حسابداری — آفلاین، روی سیستم شما، بدون ارسال داده به اینترنت.</p>
    @if($agency)
      <div class="ag-actions">
        <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست مشاوره رایگان<i>←</i></a>
        <a class="ag-btn ag-btn--line" href="{{ url('/design') }}">طراحی سایت</a>
        <a class="ag-btn ag-btn--line" href="{{ url('/computer-accounting') }}">ساخت نرم‌افزار</a>
      </div>
    @endif
  </div>
</section>
<section class="ag-sec">
  <div class="ag-wrap">
    <p class="ag-kicker">سه کار مشخص</p>
    <h2>AI شعار نیست؛ روی محصول شما می‌نشیند</h2>
    <div class="ag-cards ag-cards--dense">
      <article class="ag-card"><div><b>روی سایت</b><p>جستجو، پاسخ به مشتری و راهنمای صفحات داخل همان وب‌سایت — نه چت عمومی اینترنتی.</p></div></article>
      <article class="ag-card"><div><b>داخل نرم‌افزار</b><p>گفتگو با سامانه، توضیح گزارش و پیشنهاد کار. ثبت قطعی فقط با تأیید شماست.</p></div></article>
      <article class="ag-card"><div><b>روی حسابداری</b><p>خواندن فاکتور، پیش‌نویس سند و ۹ کنترل حسابرس هوشمند؛ عدد از دفتر می‌آید نه از حدس مدل.</p></div></article>
    </div>
  </div>
</section>
@endif

@if($slug === 'accounting' || $slug === 'accounting-web')
@include('studio::accounting-web')
@endif

@if($slug === 'computer-accounting')
@include('studio::computer-accounting')
@endif

@if($slug === 'accounting-type')
@include('studio::accounting-type')
@endif

@if($slug === 'projects')
<section class="{{ $agency ? 'ag-page' : 'st-hero' }}">
  @unless($agency)
    <div class="st-hero__glow st-hero__glow--steel" aria-hidden="true"></div>
    <div class="st-hero__glow st-hero__glow--amber" aria-hidden="true"></div>
  @endunless
  <div class="{{ $agency ? 'ag-wrap' : 'st-wrap' }}">
    <p class="{{ $agency ? 'ag-kicker' : 'st-kicker' }}">پروژه‌های سایت</p>
    <h1>نمونه از همین سیستم</h1>
    <p class="{{ $agency ? 'ag-lead' : '' }}">پروژه‌هایی که استقرار شده‌اند؛ قالب تزئینی نیستند.</p>
    @if($agency)
      <div class="ag-actions">
        <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">شروع پروژه مشابه<i>←</i></a>
      </div>
      <div class="ag-cards ag-cards--dense" style="margin-top:1rem">
        <div class="ag-card"><i class="ag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 11 12 4l8 7v9H4z"/><path d="M9 20v-6h6v6"/></svg></i><div><b>مدیریت تعمیرگاه</b><p>پذیرش، قبض، ارجاع و تحویل.</p></div></div>
        <div class="ag-card"><i class="ag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 18V6M4 18h16"/><path d="m8 14 3-4 3 3 5-7"/></svg></i><div><b>دفتر متصل به فروش</b><p>یک فروش = یک سند.</p></div></div>
        <div class="ag-card"><i class="ag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6" y="4" width="12" height="16" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg></i><div><b>وب‌اپ و کارت ویزیت</b><p>نصب روی گوشی و عضویت پیامکی.</p></div></div>
      </div>
    @else
      <div class="st-grid">
        <div class="st-card"><small>نرم‌افزار + سایت</small><b>مدیریت تعمیرگاه</b><p>پذیرش، قبض، ارجاع و تحویل.</p></div>
        <div class="st-card"><small>حسابداری</small><b>دفتر متصل به فروش</b><p>یک فروش = یک سند.</p></div>
        <div class="st-card"><small>افزونه</small><b>وب‌اپ و کارت ویزیت</b><p>نصب روی گوشی و عضویت پیامکی.</p></div>
      </div>
    @endif
  </div>
</section>
@endif

@if($slug === 'receipt')
<section class="{{ $agency ? 'ag-page' : 'st-hero' }}">
  @unless($agency)
    <div class="st-hero__glow st-hero__glow--steel" aria-hidden="true"></div>
    <div class="st-hero__glow st-hero__glow--amber" aria-hidden="true"></div>
  @endunless
  <div class="{{ $agency ? 'ag-wrap' : 'st-wrap' }}">
    <p class="{{ $agency ? 'ag-kicker' : 'st-kicker' }}">نرم‌افزار قبض تحت وب</p>
    <h1>پذیرش، پیگیری و تحویل روی قبض</h1>
    <p class="{{ $agency ? 'ag-lead' : '' }}">سامانه زنده قبض جدا است. داده و دیتابیس آن از این سایت جابه‌جا یا پاک نمی‌شود.</p>
    <div class="{{ $agency ? 'ag-actions' : 'st-actions' }}">
      <a class="{{ $agency ? 'ag-btn ag-btn--on' : 'st-cta st-cta--on' }}" href="{{ $receiptUrl }}" rel="noopener">ورود به سامانه قبض@if($agency)<i>←</i>@endif</a>
      <a class="{{ $agency ? 'ag-btn ag-btn--line' : 'st-cta st-cta--line' }}" href="{{ url('/contact') }}">درخواست استقرار@if($agency)<i>←</i>@endif</a>
    </div>
    <ul class="{{ $agency ? 'ag-list' : 'st-list' }}">
      <li>ثبت قبض تکی و گروهی</li>
      <li>جستجو با سریال، موبایل یا شماره قبض</li>
      <li>ارجاع به تعمیرکار و تحویل</li>
      <li>پیگیری مشتری روی سامانه جدا</li>
    </ul>
    <p class="{{ $agency ? 'ag-note' : 'st-note' }}" @unless($agency) style="margin-top:1rem" @endunless>آدرس سامانه: <a href="{{ $receiptUrl }}" rel="noopener">{{ $receiptUrl }}</a> — این صفحه فقط معرفی نرم‌افزار است.</p>
  </div>
</section>
@endif

@if($slug === 'contact')
@php $contactCopy = $contactCopy ?? \App\Support\StudioDesignCopy::contact(); @endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-kicker">تماس با فراز نت آمل</p>
    <h1>{{ $contactCopy['title'] }}</h1>
    <p class="ag-lead ag-lead--wide">{{ $contactCopy['lead'] }}</p>
    <div class="ag-actions">
      @if(!empty($studioHome['phone']))
        <a class="ag-btn ag-btn--on" href="tel:{{ preg_replace('/\s+/', '', $studioHome['phone']) }}">تماس {{ $studioHome['phone'] }}<i>←</i></a>
      @endif
      <a class="ag-btn ag-btn--line" href="{{ $shopUrl }}">فروشگاه قطعات<i>←</i></a>
    </div>
    <div class="ag-cards ag-cards--dense" style="margin-top:1rem">
      <a class="ag-card" href="tel:{{ preg_replace('/\s+/', '', $studioHome['phone'] ?? '01144447220') }}">
        <i class="ag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h3l1.5 4-2 1.5a12 12 0 0 0 6 6L17 13l4 1.5V18a2 2 0 0 1-2 2A16 16 0 0 1 5 6a2 2 0 0 1 2-2z"/></svg></i>
        <div><b>تلفن استودیو</b><p>{{ $studioHome['phone'] ?? '01144447220' }}</p></div>
      </a>
      <a class="ag-card" href="{{ url('/design') }}">
        <i class="ag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 8 4 12l4 4M16 8l4 4-4 4M13 5l-2 14"/></svg></i>
        <div><b>طراحی سایت</b><p>وب‌سایت حرفه‌ای برای برند شما</p></div>
      </a>
      <a class="ag-card" href="{{ url('/accounting') }}">
        <i class="ag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 11 12 4l8 7v9H4z"/></svg></i>
        <div><b>حسابداری وب</b><p>فقط تعمیرکاران: سند قبض، معین و صندوق تعمیرگاه</p></div>
      </a>
      <a class="ag-card" href="{{ url('/computer-accounting') }}">
        <i class="ag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 18V6M4 18h16"/><path d="m8 14 3-4 3 3 5-7"/></svg></i>
        <div><b>حسابداری کامپیوتر</b><p>صنوف روی SQL Server 2022: شرکتی، فروشگاهی، رستوران</p></div>
      </a>
      <a class="ag-card" href="{{ url('/repair-shop') }}">
        <i class="ag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16v12H4z"/><path d="M8 7V5h8v2"/></svg></i>
        <div><b>تعمیرگاه</b><p>پذیرش، ارجاع تعمیرکار و کارتابل مشتری</p></div>
      </a>
    </div>
  </div>
</section>
@endif

@if($slug === 'sites')
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-kicker">نرم‌افزار تحت وب</p>
    <h1>حسابداری وب، حسابداری کامپیوتر و تعمیرگاه</h1>
    <p class="ag-lead">حسابداری وب فقط تعمیرکاران است. صنوف دیگر روی حسابداری کامپیوتر با SQL Server 2022 هستند.</p>
    <div class="ag-cards ag-cards--dense" style="margin-top:1rem">
      @foreach(($catalog ?? []) as $row)
        <a class="ag-card" href="{{ url($row['url']) }}">
          <i class="ag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16v12H4z"/><path d="M8 7V5h8v2"/></svg></i>
          <div><b>{{ $row['title'] }}</b><p>{{ $row['short'] }}</p><small>مشاهده منوها ←</small></div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

@if($slug === 'repair')
@include('studio::repair-hub')
@endif

@if($slug === 'repair-staff')
@include('studio::repair-tree', ['kind' => 'staff'])
@endif

@if($slug === 'repair-customer')
@include('studio::repair-tree', ['kind' => 'customer'])
@endif

@if($slug === 'product-item')
@include('studio::product-item')
@endif
