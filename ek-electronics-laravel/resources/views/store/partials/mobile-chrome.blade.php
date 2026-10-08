@php
  $mobile = $mobile ?? \App\Support\MobileWeb::resolved();
  $menus = $menus ?? ['header' => collect()];
  $phone = $settings['phone'] ?? '';
  $email = $settings['email'] ?? '';
@endphp
<div class="mobile-drawer" id="mobile-drawer" hidden>
  <div class="mobile-drawer-panel" role="dialog" aria-modal="true" aria-label="{{ $mobile['menu_label'] }}">
    <div class="mobile-drawer-head">
      <strong>{{ $mobile['menu_label'] }}</strong>
      <button type="button" class="wa-close" data-drawer-close aria-label="Close menu">×</button>
    </div>
    <form class="search mobile-drawer-search" action="{{ route('shop') }}" method="get">
      <input name="q" type="search" placeholder="Search drives, memory, boards…" aria-label="Search products">
      <button type="submit">Search</button>
    </form>
    <nav class="drawer-nav" aria-label="Mobile">
      @foreach ($menus['header'] as $item)
        @php
          $href = str_starts_with($item->url, 'http') ? $item->url : url($item->url);
          $children = method_exists($item, 'relationLoaded') && $item->relationLoaded('children')
            ? $item->children
            : collect($item->children ?? []);
        @endphp
        <a href="{{ $href }}" target="{{ $item->target ?? '_self' }}">{{ $item->label }}</a>
        @foreach ($children as $child)
          @php $childHref = str_starts_with($child->url, 'http') ? $child->url : url($child->url); @endphp
          <a class="drawer-child" href="{{ $childHref }}" target="{{ $child->target ?? '_self' }}">
            {{ $child->label }}
            @if(!empty($child->hint))<small>{{ $child->hint }}</small>@endif
          </a>
        @endforeach
      @endforeach
    </nav>
    <div class="drawer-account">
      @auth
        @if(auth()->user()->isStaff())
          <a class="btn btn-outline" href="{{ route('staff.dashboard') }}">Staff desk</a>
        @else
          <a class="btn btn-outline" href="{{ route('account.dashboard') }}">My account</a>
        @endif
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost" type="submit">Logout</button></form>
      @else
        @if($settings['customer_login_enabled'] ?? true)
          <a class="btn btn-outline" href="{{ route('login') }}">Login</a>
          @if($settings['customer_register_enabled'] ?? true)
            <a class="btn btn-ghost" href="{{ route('register') }}">Register</a>
          @endif
        @endif
      @endauth
      @if($settings['staff_login_enabled'] ?? true)
        <a class="btn btn-primary" href="{{ route('login', ['portal' => 'staff']) }}">Staff login</a>
      @endif
      @if($phone)
        <a class="btn btn-ghost" href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a>
      @endif
      @if($email)
        <a class="btn btn-ghost" href="mailto:{{ $email }}">{{ $email }}</a>
      @endif
    </div>
  </div>
</div>

@if(!empty($mobile['bottom_nav']))
  <nav class="mobile-bar" style="--bar-cols: {{ count($mobile['bars']) + 1 }}" aria-label="Mobile shortcuts">
    @foreach ($mobile['bars'] as $bar)
      <a href="{{ $bar['url'] }}" class="{{ !empty($bar['active']) ? 'active' : '' }}">
        <span>{{ $bar['label'] }}</span>
        @if($bar['key'] === 'cart')<b class="mobile-cart-count">{{ $cartCount ?? 0 }}</b>@endif
      </a>
    @endforeach
    <button type="button" data-drawer-open>
      <span>{{ $mobile['menu_label'] }}</span>
    </button>
  </nav>
@endif

<script>
  (() => {
    const root = document.documentElement;
    const drawer = document.getElementById('mobile-drawer');
    if (!drawer) return;
    const open = () => {
      drawer.hidden = false;
      root.classList.add('drawer-open');
    };
    const close = () => {
      drawer.hidden = true;
      root.classList.remove('drawer-open');
    };
    document.querySelectorAll('[data-drawer-open]').forEach((button) => {
      button.addEventListener('click', open);
    });
    drawer.querySelectorAll('[data-drawer-close]').forEach((button) => {
      button.addEventListener('click', close);
    });
    drawer.addEventListener('click', (event) => {
      if (event.target === drawer) close();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !drawer.hidden) close();
    });
    window.addEventListener('resize', () => {
      if (!root.classList.contains('is-phone')) close();
    });
  })();
</script>
