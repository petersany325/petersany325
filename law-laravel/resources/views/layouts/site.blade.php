<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#0a1628" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <title>@yield('title', $siteSettings['site_name'] ?? 'مؤسسه حقوقی آریان')</title>
  <meta name="description" content="@yield('description', $siteSettings['hero_lead'] ?? 'وکالت و مشاوره حقوقی تخصصی')" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v=8" />
</head>
<body>
  @php
    $brand = $siteSettings['site_name'] ?? 'مؤسسه حقوقی آریان';
    $headerMenus = $headerMenus ?? collect();
    $footerMenus = $footerMenus ?? collect();
  @endphp

  <header class="site-nav {{ request()->routeIs('home') ? '' : 'is-scrolled' }}" id="nav">
    <div class="container nav-bar">
      <a href="{{ url('/') }}" class="brand">
        <span class="brand-mark">آ</span>
        <span class="brand-text">
          <strong>{{ $brand }}</strong>
          @if (!empty($siteSettings['site_tagline']))
            <small>{{ $siteSettings['site_tagline'] }}</small>
          @endif
        </span>
      </a>

      @if (($siteSettings['show_phone_in_header'] ?? '1') === '1' && !empty($siteSettings['phone']))
        <a class="nav-phone" href="tel:{{ preg_replace('/\s+/', '', $siteSettings['phone']) }}">{{ $siteSettings['phone'] }}</a>
      @endif

      <nav class="nav-links" id="navLinks" aria-label="منوی اصلی">
        @foreach ($headerMenus as $item)
          <a
            href="{{ $item->resolvedUrl() }}"
            class="nav-link nav-{{ $item->style }} {{ $item->style === 'cta' ? 'nav-cta' : '' }}"
            @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
          >{{ $item->label }}</a>
        @endforeach
      </nav>

      <button class="nav-toggle" id="navToggle" aria-label="باز کردن منو" aria-expanded="false" aria-controls="navLinks" type="button">
        <span></span><span></span><span></span>
      </button>
    </div>
    <div class="nav-backdrop" id="navBackdrop" hidden></div>
  </header>

  <main class="site-main @yield('mainClass')" id="top">
    @yield('content')
  </main>

  <footer class="site-footer fancy-footer">
    <div class="container footer-grid">
      <div class="footer-brand-block">
        <div class="footer-brand">{{ $brand }}</div>
        <p>{{ $siteSettings['footer_about'] ?? ($siteSettings['hero_lead'] ?? '') }}</p>
        <div class="footer-contact">
          @if (!empty($siteSettings['phone']))<div>تلفن: {{ $siteSettings['phone'] }}</div>@endif
          @if (!empty($siteSettings['email']))<div>ایمیل: {{ $siteSettings['email'] }}</div>@endif
          @if (!empty($siteSettings['address']))<div>{{ $siteSettings['address'] }}</div>@endif
        </div>
        <div class="footer-social">
          @if (!empty($siteSettings['social_instagram']))<a href="{{ $siteSettings['social_instagram'] }}" target="_blank" rel="noopener">اینستاگرام</a>@endif
          @if (!empty($siteSettings['social_linkedin']))<a href="{{ $siteSettings['social_linkedin'] }}" target="_blank" rel="noopener">لینکدین</a>@endif
          @if (!empty($siteSettings['social_whatsapp']))<a href="{{ $siteSettings['social_whatsapp'] }}" target="_blank" rel="noopener">واتساپ</a>@endif
        </div>
      </div>

      <div>
        <h4 class="footer-title">دسترسی سریع</h4>
        <nav class="footer-links-col">
          @foreach ($footerMenus as $item)
            <a
              href="{{ $item->resolvedUrl() }}"
              class="footer-link footer-{{ $item->style }}"
              @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
            >{{ $item->label }}</a>
          @endforeach
        </nav>
      </div>

      <div>
        <h4 class="footer-title">نوبت مشاوره</h4>
        <p>برای رزرو جلسه، از منوی «درخواست نوبت» استفاده کنید یا همین حالا تماس بگیرید.</p>
        <a class="btn btn-primary" href="{{ url('/#appointment') }}">{{ $siteSettings['cta_text'] ?? 'درخواست نوبت' }}</a>
      </div>
    </div>

    <div class="container footer-bottom-bar">
      <span>{{ $siteSettings['footer_copyright'] ?? '' }}</span>
      <span class="footer-disclaimer">{{ $siteSettings['footer_disclaimer'] ?? '' }}</span>
      <a href="{{ url('/admin') }}">ورود مدیران</a>
    </div>
  </footer>

  <script src="{{ asset('assets/js/main.js') }}?v=8"></script>
</body>
</html>
