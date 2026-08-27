<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', \App\Models\Setting::getValue('shop_name', 'HDD Land'))</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/hdd-corporate.css') }}?v=3">
  @stack('head')
</head>
<body>
@php
  $shopName = \App\Models\Setting::getValue('shop_name', config('app.name', 'HDD Land'));
  $cartCount = class_exists(\Plugins\CartCheckout\src\Cart::class) ? \Plugins\CartCheckout\src\Cart::count() : 0;
  if (! class_exists(\App\Support\CorporateHomeConfig::class) && is_file(app_path('Support/CorporateHomeConfig.php'))) {
      require_once app_path('Support/CorporateHomeConfig.php');
  }
  $ch = class_exists(\App\Support\CorporateHomeConfig::class) ? \App\Support\CorporateHomeConfig::get() : [];
  $chHref = fn ($u) => class_exists(\App\Support\CorporateHomeConfig::class)
      ? \App\Support\CorporateHomeConfig::href((string) $u)
      : url($u);
  $chLinks = fn ($raw) => class_exists(\App\Support\CorporateHomeConfig::class)
      ? \App\Support\CorporateHomeConfig::links((string) $raw)
      : [];
@endphp

<div class="mega-backdrop" id="megaBackdrop" hidden></div>

<div class="shell">
  <header class="header" id="consoleHeader">
    <div class="header-top">
      <a class="brand" href="{{ url('/') }}">
        <span class="brand-mark">HL</span>
        {{ $shopName }}
      </a>

      <button class="mobile-toggle" id="mobileToggle" type="button" aria-label="منو">
        <span></span><span></span><span></span>
      </button>

      <nav class="nav" id="consoleNav" aria-label="منوی اصلی">
        <div class="nav-item">
          <a class="nav-trigger" href="{{ url('/') }}">خانه</a>
        </div>

        {{-- خدمات مگامنو --}}
        <div class="nav-item mega-host" data-menu="mega">
          <button class="nav-trigger" type="button" aria-expanded="false">خدمات <i class="chev"></i></button>
          <div class="mega">
            <div class="mega-shell">
              <aside class="mega-rail">
                <h4>CORPORATE</h4>
                <strong>خدمات تخصصی</strong>
                <p>بازیابی، تعمیر و پذیرش دستگاه — مسیر شرکتی سایت.</p>
                <a class="rail-cta" href="{{ url('/contact') }}">درخواست بازیابی</a>
              </aside>
              <div class="platter-stack">
                <article class="platter">
                  <div class="p-code">۰۱</div>
                  <h5>بازیابی داده</h5>
                  <ul>
                    <li><a href="{{ url('/services') }}">هارد دیسک <span>HDD</span></a></li>
                    <li><a href="{{ url('/services') }}">SSD / NVMe <span>SSD</span></a></li>
                    <li><a href="{{ url('/services') }}">فلش و مموری <span>USB</span></a></li>
                    <li><a href="{{ url('/services') }}">موبایل <span>Phone</span></a></li>
                  </ul>
                </article>
                <article class="platter">
                  <div class="p-code">۰۲</div>
                  <h5>تعمیر و سرور</h5>
                  <ul>
                    <li><a href="{{ url('/services') }}">تعمیر استوریج <span>Repair</span></a></li>
                    <li><a href="{{ url('/services') }}">سرور / RAID <span>RAID</span></a></li>
                    <li><a href="{{ url('/services') }}">DVR / دوربین <span>DVR</span></a></li>
                    <li><a href="{{ url('/contact') }}">بررسی فوری <span>24h</span></a></li>
                  </ul>
                </article>
                <article class="platter">
                  <div class="p-code">۰۳</div>
                  <h5>اعتماد و تعریف</h5>
                  <ul>
                    <li><a href="{{ url('/services/about-recovery') }}">تعریف تعمیرات و بازیابی <span>Guide</span></a></li>
                    <li><a href="{{ url('/services') }}">روند کار <span>Steps</span></a></li>
                    <li><a href="{{ url('/about') }}">معرفی شرکت <span>About</span></a></li>
                    <li><a href="{{ url('/contact') }}">فرم درخواست <span>Form</span></a></li>
                  </ul>
                </article>
              </div>
              <aside class="mega-aside">
                <h4>مسیر شرکتی</h4>
                <p>اگر داده از دست رفته، از اینجا شروع کنید — نه از سبد خرید.</p>
                <a class="btn btn-red" style="justify-content:center;padding:.65rem 1rem;font-size:.85rem" href="{{ url('/services') }}">ورود به خدمات</a>
              </aside>
            </div>
          </div>
        </div>

        <div class="nav-item">
          <a class="nav-trigger" href="{{ url('/about') }}">درباره ما</a>
        </div>

        {{-- آموزش آبشاری --}}
        <div class="nav-item" data-menu="cascade">
          <button class="nav-trigger" type="button" aria-expanded="false">آموزش <i class="chev"></i></button>
          <div class="cascade">
            <a href="{{ url('/training') }}">همه دوره‌ها <span class="hint">اصلی</span></a>
            <a href="{{ url('/training') }}">آموزش تعمیرات هارد</a>
            <div class="has-sub">
              <button class="cascade-row" type="button">بازیابی اطلاعات <span class="hint">آبشاری</span></button>
              <div class="cascade-l2">
                <a href="{{ url('/training') }}">مقدماتی</a>
                <a href="{{ url('/training') }}">حرفه‌ای</a>
                <a href="{{ url('/training') }}">کارگاه عملی</a>
              </div>
            </div>
            <a href="{{ url('/products') }}">پکیج آموزش + نرم‌افزار</a>
          </div>
        </div>

        <div class="nav-item">
          <a class="nav-trigger" href="{{ url('/blog') }}">بلاگ آموزشی</a>
        </div>

        {{-- فروشگاه مگامنو --}}
        <div class="nav-item mega-host" data-menu="mega">
          <button class="nav-trigger" type="button" aria-expanded="false">فروشگاه <i class="chev"></i></button>
          <div class="mega">
            <div class="mega-shell">
              <aside class="mega-rail shop">
                <h4>STORE</h4>
                <strong>فروشگاه نرم‌افزار</strong>
                <p>خرید ابزار بازیابی و تعمیر — جدا از فرم خدمات.</p>
                <a class="rail-cta" href="{{ url('/products') }}">ورود به فروشگاه</a>
              </aside>
              <div class="platter-stack">
                <article class="platter">
                  <div class="p-code">دسته</div>
                  <h5>نرم‌افزارها</h5>
                  <ul>
                    <li><a href="{{ url('/products') }}">همه محصولات <span>All</span></a></li>
                    <li><a href="{{ url('/products') }}">بازیابی اطلاعات <span>Data</span></a></li>
                    <li><a href="{{ url('/products') }}">تعمیرات هارد <span>Repair</span></a></li>
                    <li><a href="{{ url('/products') }}">ابزار تشخیص <span>Diag</span></a></li>
                  </ul>
                </article>
                <article class="platter">
                  <div class="p-code">پکیج</div>
                  <h5>ترکیبی</h5>
                  <ul>
                    <li><a href="{{ url('/products') }}">آموزش + نرم‌افزار <span>Bundle</span></a></li>
                    <li><a href="{{ url('/products') }}">لایسنس حرفه‌ای <span>Pro</span></a></li>
                    <li><a href="{{ url('/products') }}">تمدید لایسنس <span>Renew</span></a></li>
                    <li><a href="{{ url('/orders/track') }}">پیگیری سفارش <span>Track</span></a></li>
                  </ul>
                </article>
                <article class="platter">
                  <div class="p-code">خرید</div>
                  <h5>سفارش</h5>
                  <ul>
                    <li><a href="{{ url('/cart') }}">سبد خرید <span>Cart</span></a></li>
                    <li><a href="{{ url('/checkout') }}">تسویه‌حساب <span>Pay</span></a></li>
                    <li><a href="{{ url('/contact') }}">پشتیبانی خرید <span>Help</span></a></li>
                    <li><a href="{{ url('/about') }}">درباره فروشگاه <span>Info</span></a></li>
                  </ul>
                </article>
              </div>
              <aside class="mega-aside shop-aside">
                <h4>ورود سریع</h4>
                <p>مسیر فروشگاهی جدا از خدمات حضوری و سیستم قبض است.</p>
                <div class="product-mini">
                  <a href="{{ url('/products') }}">کاتالوگ محصولات <span class="price">مشاهده</span></a>
                  <a href="{{ url('/cart') }}">سبد خرید ({{ $cartCount }}) <span class="price">Cart</span></a>
                  <a href="{{ url('/orders/track') }}">پیگیری سفارش <span class="price">Track</span></a>
                </div>
              </aside>
            </div>
          </div>
        </div>

        {{-- گارانتی آبشاری --}}
        <div class="nav-item" data-menu="cascade">
          <button class="nav-trigger" type="button" aria-expanded="false">گارانتی <i class="chev"></i></button>
          <div class="cascade">
            <a href="{{ url('/warranty') }}">قبول گارانتی شرکت‌ها <span class="hint">اصلی</span></a>
            <div class="has-sub">
              <button class="cascade-row" type="button">برندها <span class="hint">آبشاری</span></button>
              <div class="cascade-l2">
                <a href="{{ url('/warranty') }}">Western Digital</a>
                <a href="{{ url('/warranty') }}">Seagate</a>
                <a href="{{ url('/warranty') }}">Toshiba</a>
                <a href="{{ url('/warranty') }}">Samsung</a>
              </div>
            </div>
            <a href="{{ url('/contact') }}">ثبت پذیرش گارانتی</a>
          </div>
        </div>

        <div class="nav-item">
          <a class="nav-trigger" href="{{ url('/contact') }}">تماس</a>
        </div>
      </nav>

      <div class="header-actions">
        <a class="cart-pill" href="{{ url('/cart') }}" aria-label="سبد خرید">{{ $cartCount > 0 ? $cartCount : 'Bag' }}</a>
        <a class="cta-red" href="{{ $chHref($ch['header_cta_url'] ?? '/contact') }}">{{ $ch['header_cta_label'] ?? 'درخواست بازیابی' }}</a>
      </div>
    </div>
  </header>

  @yield('content')

  <footer class="site-footer">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="footer-logo">
          <span class="brand-mark">HL</span>
          <div>
            <strong>{{ $shopName }}</strong>
            <span>{{ $ch['footer_tagline'] ?? 'مرکز بازیابی اطلاعات · تعمیر استوریج · فروش نرم‌افزار' }}</span>
          </div>
        </div>
        <p>{{ $ch['footer_about'] ?? '' }}</p>
        <div class="footer-ctas">
          <a class="btn btn-red" href="{{ $chHref($ch['footer_cta_red_url'] ?? '/contact') }}">{{ $ch['footer_cta_red_label'] ?? 'درخواست بازیابی' }}</a>
          <a class="btn btn-blue" href="{{ $chHref($ch['footer_cta_blue_url'] ?? '/products') }}">{{ $ch['footer_cta_blue_label'] ?? 'ورود به فروشگاه' }}</a>
        </div>
      </div>

      <div class="footer-cols">
        <div class="footer-col">
          <h4>{{ $ch['footer_col1_title'] ?? 'خدمات شرکتی' }}</h4>
          @foreach($chLinks($ch['footer_col1_links'] ?? '') as $lnk)
            <a href="{{ $chHref($lnk['url']) }}">{{ $lnk['label'] }}</a>
          @endforeach
        </div>
        <div class="footer-col">
          <h4>{{ $ch['footer_col2_title'] ?? 'فروشگاه' }}</h4>
          @foreach($chLinks($ch['footer_col2_links'] ?? '') as $lnk)
            <a href="{{ $chHref($lnk['url']) }}">{{ $lnk['label'] }}</a>
          @endforeach
        </div>
        <div class="footer-col">
          <h4>{{ $ch['footer_col3_title'] ?? 'آموزش و بلاگ' }}</h4>
          @foreach($chLinks($ch['footer_col3_links'] ?? '') as $lnk)
            <a href="{{ $chHref($lnk['url']) }}">{{ $lnk['label'] }}</a>
          @endforeach
        </div>
        <div class="footer-col footer-contact">
          <h4>{{ $ch['footer_contact_title'] ?? 'تماس با ما' }}</h4>
          <a href="tel:{{ \App\Models\Setting::getValue('shop_phone', '02100000000') }}">{{ \App\Models\Setting::getValue('shop_phone', '۰۲۱-۰۰۰۰۰۰۰۰') }}</a>
          <a href="{{ url('/contact') }}">فرم تماس</a>
          <a href="{{ url('/contact') }}">پذیرش حضوری</a>
          <div class="footer-hours">
            <b>{{ $ch['footer_hours_title'] ?? 'ساعات پاسخگویی' }}</b>
            <span>{{ $ch['footer_hours_text'] ?? 'شنبه تا پنجشنبه · ۹ تا ۱۸' }}</span>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© {{ $shopName }} {{ $ch['footer_copyright'] ?? '— همه حقوق محفوظ است' }}</span>
      <div class="footer-bottom-links">
        @foreach($chLinks($ch['footer_bottom_links'] ?? '') as $lnk)
          <a href="{{ $chHref($lnk['url']) }}">{{ $lnk['label'] }}</a>
        @endforeach
      </div>
    </div>
  </footer>
</div>

<script src="{{ asset('js/hdd-corporate-nav.js') }}?v=3"></script>
@stack('scripts')
</body>
</html>
