<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', ($settings['site_name'] ?? 'مؤسسه حقوقی آریان'))</title>
  <meta name="description" content="@yield('description', 'وکالت و مشاوره حقوقی تخصصی')" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
</head>
<body>
  @php
    $brand = $settings['site_name'] ?? \App\Models\Setting::get('site_name', 'مؤسسه حقوقی آریان');
  @endphp
  <header class="site-nav is-scrolled" id="nav">
    <div class="container">
      <a href="{{ route('home') }}" class="brand">
        <span class="brand-mark">آ</span>
        {{ $brand }}
      </a>
      <button class="nav-toggle" id="navToggle" aria-label="منو" type="button">
        <span></span><span></span><span></span>
      </button>
      <nav class="nav-links" id="navLinks">
        <a href="{{ route('home') }}#services">خدمات</a>
        <a href="{{ route('team') }}">تیم</a>
        <a href="{{ route('blog.index') }}">مقالات</a>
        <a href="{{ route('faq') }}">سوالات</a>
        <a href="{{ route('home') }}#contact" class="nav-cta">مشاوره</a>
      </nav>
    </div>
  </header>

  <main style="padding-top:5rem">
    @yield('content')
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-top">
        <div>
          <div class="footer-brand">{{ $brand }}</div>
          <p>وکالت و مشاوره حقوقی تخصصی</p>
        </div>
        <nav class="footer-links">
          <a href="{{ route('home') }}#services">خدمات</a>
          <a href="{{ route('team') }}">تیم</a>
          <a href="{{ route('blog.index') }}">مقالات</a>
          <a href="{{ route('pages.show', 'privacy') }}">حریم خصوصی</a>
        </nav>
      </div>
      <div class="footer-bottom">
        <span>{{ $brand }}</span>
        <span><a href="{{ url('/admin') }}">ورود مدیران</a></span>
      </div>
    </div>
  </footer>
  <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
