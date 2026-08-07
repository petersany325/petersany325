<!DOCTYPE html>
<html lang="fa" dir="rtl" class="webapp">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1" />
  @php
    $brand = $siteSettings['site_name'] ?? 'مؤسسه حقوقی آریان';
    $theme = $siteSettings['pwa_theme_color'] ?? '#0a1628';
    $pwaOn = ($siteSettings['pwa_enabled'] ?? '1') === '1';
    $bannerH = max(25, min(70, (int) ($siteSettings['app_banner_height'] ?? 42)));
    $bannerPos = $siteSettings['app_banner_position'] ?? 'center 35%';
    $bannerShowLead = ($siteSettings['app_banner_show_lead'] ?? '1') === '1';
    $bannerMin = match (true) {
        $bannerH <= 36 => '180px',
        $bannerH >= 50 => '240px',
        default => '200px',
    };
    $bannerMax = match (true) {
        $bannerH <= 36 => '250px',
        $bannerH >= 50 => '340px',
        default => '300px',
    };
  @endphp
  <meta name="theme-color" content="{{ $theme }}" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <meta name="apple-mobile-web-app-title" content="{{ $siteSettings['pwa_short_name'] ?? 'آریان' }}" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="application-name" content="{{ $siteSettings['pwa_short_name'] ?? 'آریان' }}" />
  <title>{{ $brand }} | وب‌اپ</title>
  <meta name="description" content="{{ $siteSettings['pwa_description'] ?? ($siteSettings['hero_lead'] ?? '') }}" />
  @if ($pwaOn)
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}" />
  @endif
  <link rel="apple-touch-icon" href="{{ asset('assets/icons/apple-touch-icon.png') }}" />
  <link rel="icon" href="{{ asset('assets/icons/icon-192.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v=11" />
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v=5" />
  <style>
    :root {
      --app-banner-h: {{ $bannerH }}vh;
      --app-banner-min: {{ $bannerMin }};
      --app-banner-max: {{ $bannerMax }};
      --app-banner-pos: {{ $bannerPos }};
    }
    html.webapp, body.is-webapp, .app-shell { overflow-x: clip; max-width: 100%; }
  </style>
</head>
<body class="is-webapp">
  <div class="app-shell">
    <header class="app-topbar">
      <div class="app-brand">
        <span class="brand-mark">آ</span>
        <div>
          <strong>{{ $brand }}</strong>
          <small>{{ $siteSettings['site_tagline'] ?? 'وکالت · مشاوره · دفاع' }}</small>
        </div>
      </div>
      <button type="button" class="app-install-btn" id="appInstallBtn" hidden>نصب اپ</button>
    </header>

    <main class="app-main" id="top">
      @yield('content')
    </main>

    <nav class="app-tabbar" aria-label="منوی پایین">
      <a href="#top" class="app-tab is-active" data-tab="home"><span>خانه</span></a>
      <a href="#services" class="app-tab" data-tab="services"><span>خدمات</span></a>
      <a href="#appointment" class="app-tab app-tab-cta" data-tab="appointment"><span>{{ $siteSettings['cta_text'] ?? 'نوبت' }}</span></a>
      <a href="#team" class="app-tab" data-tab="team"><span>وکلا</span></a>
      <a href="#contact" class="app-tab" data-tab="contact"><span>تماس</span></a>
    </nav>
  </div>

  <div class="app-toast" id="appToast" hidden>برای دسترسی سریع، وب‌اپ را به صفحه اصلی اضافه کنید.</div>

  <script src="{{ asset('assets/js/main.js') }}?v=11"></script>
  <script src="{{ asset('assets/js/app.js') }}?v=5"></script>
  @if ($pwaOn)
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('{{ url('/sw.js') }}', { scope: '/' }).catch(function () {});
    }
  </script>
  @endif
</body>
</html>
