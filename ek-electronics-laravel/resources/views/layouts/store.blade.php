<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', ($settings['store_name'] ?? 'EK Electronics'))</title>
  <meta name="description" content="@yield('meta', 'Hard drive refurbishment, data recovery, secure erasure, and computer component sales in South Africa.')">
  <link rel="icon" href="{{ asset('assets/brand/favicon.ico') }}" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/brand/favicon-32.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('assets/brand/apple-touch-icon.png') }}">
  <link rel="manifest" href="{{ asset('site.webmanifest') }}">
  <meta property="og:image" content="{{ asset('assets/brand/logo-hex.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v=6">
</head>
<body>
  @php
    $phone = $settings['phone'] ?? '+27 10 500 2140';
    $email = $settings['email'] ?? 'info@ekelectronics.co.za';
    $wa = $settings['whatsapp'] ?? '27105002140';
    $tagline = $settings['tagline'] ?? 'Innovation. Integrity. Impact.';
    $storeName = $settings['store_name'] ?? 'EK Electronics';
    $hours = $settings['hours'] ?? 'Mon–Fri 08:00–17:00 SAST';
    $waMsg = $settings['whatsapp_default_message'] ?? 'Hi EK Electronics, I need help with a drive.';
    $cartCount = collect(session('cart', []))->sum();
    $menus = $menus ?? ['header' => collect(), 'footer' => collect(), 'footer_shop' => collect(), 'footer_services' => collect(), 'footer_legal' => collect()];
  @endphp
  <div class="topbar">
    <div class="topbar-inner">
      <span>Midrand · Waterfall Business Park · {{ $hours }}</span>
      <span>
        <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a> ·
        <a href="mailto:{{ $email }}">{{ $email }}</a> ·
        <a class="whatsapp-chip" href="https://wa.me/{{ $wa }}?text={{ urlencode($waMsg) }}">Chat on WhatsApp</a>
      </span>
    </div>
  </div>

  <header class="site-header">
    <div class="nav-inner">
      <a class="brand" href="{{ route('home') }}">
        <img class="brand-mark" src="{{ asset('assets/brand/mark-primary.png') }}" width="44" height="44" alt="EK Electronics">
        <span>{{ $storeName }}<small>{{ $tagline }}</small></span>
      </a>
      <form class="search" action="{{ route('shop') }}" method="get">
        <input name="q" type="search" value="{{ request('q') }}" placeholder="Search 885+ drives, memory, boards…" aria-label="Search products">
        <button type="submit">Search</button>
      </form>
      <div class="header-actions">
        @auth
          @if(auth()->user()->isStaff())
            <a class="btn btn-outline" href="{{ route('staff.dashboard') }}">Staff desk</a>
          @else
            <a class="btn btn-outline" href="{{ route('account.dashboard') }}">My account</a>
          @endif
          <form method="post" action="{{ route('logout') }}" style="display:inline;margin:0">@csrf<button class="btn btn-ghost" type="submit">Logout</button></form>
        @else
          @if($settings['customer_login_enabled'] ?? true)
            <a class="btn btn-outline" href="{{ route('login') }}">Login</a>
            @if($settings['customer_register_enabled'] ?? true)
              <a class="btn btn-ghost" href="{{ route('register') }}">Register</a>
            @endif
          @endif
        @endauth
        <a class="icon-btn" href="{{ route('cart') }}" aria-label="Shopping cart">🛒<span class="badge">{{ $cartCount }}</span></a>
        @if($settings['staff_login_enabled'] ?? true)
          <a class="btn btn-primary" href="{{ route('login', ['portal' => 'staff']) }}">Staff login</a>
        @endif
      </div>
    </div>
    <nav class="menu">
      @foreach ($menus['header'] as $item)
        @php
          $href = str_starts_with($item->url, 'http') ? $item->url : url($item->url);
          $children = method_exists($item, 'relationLoaded') && $item->relationLoaded('children')
            ? $item->children
            : collect($item->children ?? []);
        @endphp
        @if ($children->isNotEmpty())
          <div class="menu-item has-sub">
            <a href="{{ $href }}" target="{{ $item->target ?? '_self' }}" class="{{ !empty($item->is_highlighted) ? 'is-hot' : '' }}">{{ $item->label }}</a>
            <div class="submenu">
              @foreach ($children as $child)
                @php $childHref = str_starts_with($child->url, 'http') ? $child->url : url($child->url); @endphp
                <a href="{{ $childHref }}" target="{{ $child->target ?? '_self' }}">
                  {{ $child->label }}
                  @if(!empty($child->hint))<small>{{ $child->hint }}</small>@endif
                </a>
              @endforeach
            </div>
          </div>
        @else
          <a href="{{ $href }}" target="{{ $item->target ?? '_self' }}" class="{{ !empty($item->is_highlighted) ? 'is-hot' : '' }}">{{ $item->label }}</a>
        @endif
      @endforeach
      @guest
        @if($settings['customer_login_enabled'] ?? true)
          <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">Login</a>
        @endif
      @endguest
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
      <div class="footer-brand">
        <a href="{{ route('home') }}" class="footer-logo">
          <img src="{{ asset('assets/brand/logo-hex-plate.png') }}" width="220" height="174" alt="EK Electronics — Innovation. Integrity. Impact.">
        </a>
        <p>{{ $settings['footer_about'] ?? $tagline }}</p>
        @if($settings['footer_show_socials'] ?? true)
          <div class="socials">
            @if(!empty($settings['footer_facebook']))<a href="{{ $settings['footer_facebook'] }}" aria-label="Facebook" target="_blank" rel="noopener">f</a>@endif
            @if(!empty($settings['footer_instagram']))<a href="{{ $settings['footer_instagram'] }}" aria-label="Instagram" target="_blank" rel="noopener">ig</a>@endif
            @if(!empty($settings['footer_tiktok']))<a href="{{ $settings['footer_tiktok'] }}" aria-label="TikTok" target="_blank" rel="noopener">♪</a>@endif
            @if(!empty($settings['footer_x']))<a href="{{ $settings['footer_x'] }}" aria-label="X" target="_blank" rel="noopener">𝕏</a>@endif
          </div>
        @endif
      </div>
      <div>
        <h4>{{ $settings['footer_col1_title'] ?? 'Customer services' }}</h4>
        @forelse ($menus['footer'] as $item)
          <p><a href="{{ str_starts_with($item->url, 'http') ? $item->url : url($item->url) }}" target="{{ $item->target ?? '_self' }}">{{ $item->label }}</a></p>
        @empty
          <p><a href="{{ route('page', 'shipping') }}">Shipping</a></p>
          <p><a href="{{ route('page', 'warranty') }}">Warranty policy</a></p>
          <p><a href="{{ route('page', 'terms') }}">Terms and conditions</a></p>
          <p><a href="{{ route('login') }}">Customer login</a></p>
          <p><a href="{{ route('login', ['portal' => 'staff']) }}">Staff login</a></p>
        @endforelse
        @foreach ($menus['footer_legal'] as $item)
          <p><a href="{{ str_starts_with($item->url, 'http') ? $item->url : url($item->url) }}">{{ $item->label }}</a></p>
        @endforeach
      </div>
      <div>
        <h4>{{ $settings['footer_col2_title'] ?? 'Shop' }}</h4>
        @forelse ($menus['footer_shop']->merge($menus['footer_services']) as $item)
          <p><a href="{{ str_starts_with($item->url, 'http') ? $item->url : url($item->url) }}">{{ $item->label }}</a></p>
        @empty
          <p><a href="{{ route('shop') }}">All products</a></p>
          <p><a href="{{ route('services') }}">Refurbishment</a></p>
          <p><a href="{{ route('services') }}">Data recovery</a></p>
        @endforelse
      </div>
      <div>
        <h4>{{ $settings['footer_col3_title'] ?? 'Contact' }}</h4>
        <p>{{ $phone }}<br>{{ $email }}<br>{{ $settings['address'] ?? '' }}</p>
      </div>
    </div>
    <div class="wrap legal">{{ $settings['footer_legal'] ?? ('© '.date('Y').' EK Electronics · ekelectronics.co.za') }}</div>
  </footer>

  @if(($settings['whatsapp_fab_enabled'] ?? true) && ($settings['footer_show_whatsapp'] ?? true))
    @php
      $waPos = $settings['whatsapp_chat_position'] ?? 'bottom-right';
      $waColor = $settings['whatsapp_chat_color'] ?? '#25d366';
      $waLabel = $settings['whatsapp_chat_label'] ?? 'WhatsApp us';
      $waSub = $settings['whatsapp_chat_subtitle'] ?? '';
      $waAgent = $settings['whatsapp_chat_agent_name'] ?? 'EK Support';
      $waQuick = $settings['whatsapp_quick_replies'] ?? [];
    @endphp
    <div class="wa-widget {{ $waPos === 'bottom-left' ? 'wa-left' : 'wa-right' }}" data-auto-open="{{ ($settings['whatsapp_chat_auto_open'] ?? false) ? '1' : '0' }}" style="--wa-accent: {{ $waColor }}">
      <div class="wa-panel" id="wa-panel" hidden>
        <div class="wa-panel-head">
          <strong>{{ $waLabel }}</strong>
          @if($settings['whatsapp_chat_show_agent'] ?? true)<small>{{ $waAgent }} · {{ $waSub }}</small>@endif
          <button type="button" class="wa-close" data-wa-close aria-label="Close">×</button>
        </div>
        <p class="wa-welcome">{{ $settings['whatsapp_welcome_message'] ?? $waMsg }}</p>
        @if(!empty($waQuick))
          <div class="wa-chips">
            @foreach($waQuick as $chip)
              <a href="https://wa.me/{{ $wa }}?text={{ urlencode($chip) }}">{{ $chip }}</a>
            @endforeach
          </div>
        @endif
        <a class="wa-panel-cta" href="https://wa.me/{{ $wa }}?text={{ urlencode($waMsg) }}">Continue on WhatsApp</a>
      </div>
      <button type="button" class="fab-wa" data-wa-toggle>{{ $waLabel }}</button>
    </div>
    <script>
      (() => {
        const root = document.querySelector('.wa-widget');
        if (!root) return;
        const panel = root.querySelector('#wa-panel');
        const open = () => { panel.hidden = false; };
        const close = (event) => {
          event?.preventDefault();
          event?.stopPropagation();
          panel.hidden = true;
          sessionStorage.setItem('ek_wa_closed', '1');
        };
        root.querySelector('[data-wa-toggle]')?.addEventListener('click', () => {
          if (panel.hidden) {
            sessionStorage.removeItem('ek_wa_closed');
            open();
          } else {
            close();
          }
        });
        root.querySelector('[data-wa-close]')?.addEventListener('click', close);
        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && !panel.hidden) close(event);
        });
        if (root.dataset.autoOpen === '1' && !sessionStorage.getItem('ek_wa_closed')) {
          setTimeout(open, 1200);
        }
      })();
    </script>
  @endif
</body>
</html>
