<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'EK Electronics')</title>
  <meta name="description" content="@yield('meta', 'Hard drive refurbishment, data recovery, secure erasure, and computer component sales in South Africa.')">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>
  @php
    $phone = $settings['phone'] ?? '+27 10 500 2140';
    $email = $settings['email'] ?? 'info@ekelectronics.co.za';
    $wa = $settings['whatsapp'] ?? '27105002140';
    $tagline = $settings['tagline'] ?? 'Innovation. Integrity. Impact.';
    $cartCount = collect(session('cart', []))->sum();
  @endphp
  <div class="topbar">
    <div class="topbar-inner">
      <span>Midrand · Waterfall Business Park · Mon–Fri 08:00–17:00 SAST</span>
      <span>
        <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a> ·
        <a href="mailto:{{ $email }}">{{ $email }}</a> ·
        <a class="whatsapp-chip" href="https://wa.me/{{ $wa }}?text={{ urlencode('Hi EK Electronics, I need help with a drive.') }}">Chat on WhatsApp</a>
      </span>
    </div>
  </div>

  <header class="site-header">
    <div class="nav-inner">
      <a class="brand" href="{{ route('home') }}">
        <svg viewBox="0 0 48 48" aria-hidden="true"><rect width="48" height="48" rx="12" fill="#1d4ed8"/><text x="24" y="31" text-anchor="middle" fill="#fff" font-size="16" font-family="Manrope, sans-serif" font-weight="800">EK</text></svg>
        <span>EK Electronics<small>{{ $tagline }}</small></span>
      </a>
      <form class="search" action="{{ route('shop') }}" method="get">
        <input name="q" type="search" value="{{ request('q') }}" placeholder="Search 885+ drives, memory, boards…" aria-label="Search products">
        <button type="submit">Search</button>
      </form>
      <div class="header-actions">
        <a class="icon-btn" href="{{ route('contact') }}" aria-label="Contact">👤</a>
        <a class="icon-btn" href="{{ route('cart') }}" aria-label="Shopping cart">🛒<span class="badge">{{ $cartCount }}</span></a>
        <a class="btn btn-primary" href="{{ url('/admin') }}">Staff panel</a>
      </div>
    </div>
    <nav class="menu">
      <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
      <a href="{{ route('shop') }}" class="{{ request()->routeIs('shop') ? 'active' : '' }}">Shop</a>
      <a href="{{ route('services') }}" class="{{ request()->routeIs('services') ? 'active' : '' }}">Services</a>
      <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About</a>
      <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a>
      <a href="{{ route('page', 'shipping') }}">Shipping</a>
      <a href="{{ route('page', 'warranty') }}">Warranty</a>
      <a href="{{ route('page', 'terms') }}">Terms</a>
    </nav>
  </header>

  @if (session('success'))
    <div class="toast">{{ session('success') }}</div>
  @endif

  <main>
    @yield('content')
  </main>

  <footer class="site-footer">
    <div class="wrap">
      <div>
        <h4>EK Electronics</h4>
        <p>{{ $tagline }}</p>
        <div class="socials">
          <a href="#" aria-label="Facebook">f</a>
          <a href="#" aria-label="Instagram">ig</a>
          <a href="#" aria-label="TikTok">♪</a>
          <a href="#" aria-label="X">𝕏</a>
        </div>
      </div>
      <div>
        <h4>Customer services</h4>
        <p><a href="{{ route('page', 'shipping') }}">Shipping</a></p>
        <p><a href="{{ route('page', 'warranty') }}">Warranty policy</a></p>
        <p><a href="{{ route('page', 'terms') }}">Terms and conditions</a></p>
        <p><a href="{{ url('/admin') }}">Staff login</a></p>
      </div>
      <div>
        <h4>Shop</h4>
        <p><a href="{{ route('shop') }}">All products</a></p>
        <p><a href="{{ route('services') }}">Refurbishment</a></p>
        <p><a href="{{ route('services') }}">Data recovery</a></p>
      </div>
      <div>
        <h4>Contact</h4>
        <p>{{ $phone }}<br>{{ $email }}<br>{{ $settings['address'] ?? '' }}</p>
      </div>
    </div>
    <div class="wrap legal">© {{ date('Y') }} EK Electronics · ekelectronics.co.za</div>
  </footer>

  <a class="fab-wa" href="https://wa.me/{{ $wa }}?text={{ urlencode('Hi EK Electronics') }}">WhatsApp us</a>
</body>
</html>
