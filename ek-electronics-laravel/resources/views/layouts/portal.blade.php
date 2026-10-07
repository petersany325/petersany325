<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>@yield('title', 'EK Portal')</title>
  <link rel="icon" href="{{ asset('assets/brand/favicon.ico') }}" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/brand/favicon-32.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('assets/brand/apple-touch-icon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v=2">
  <link rel="stylesheet" href="{{ asset('assets/css/portal.css') }}?v=2">
</head>
<body class="portal-body @yield('body_class')">
  @php
    $portal = request()->routeIs('staff.*') ? 'staff' : 'account';
    $user = auth()->user();
  @endphp
  <header class="portal-top">
    <div class="portal-top-inner">
      <a class="portal-brand" href="{{ $portal === 'staff' ? route('staff.dashboard') : route('account.dashboard') }}">
        <img class="brand-mark" src="{{ asset('assets/brand/mark-hex.png') }}" width="40" height="40" alt="EK Electronics">
        <span>
          <strong>{{ $portal === 'staff' ? 'Staff desk' : 'My account' }}</strong>
          <small>{{ $user?->name }}</small>
        </span>
      </a>
      <div class="portal-top-actions">
        <a href="{{ route('home') }}">Store</a>
        @if($user?->isAdmin())
          <a href="{{ url('/admin') }}">Admin</a>
        @endif
        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
      </div>
    </div>
  </header>

  @if (session('success'))
    <div class="portal-flash ok">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="portal-flash err">{{ session('error') }}</div>
  @endif

  <main class="portal-main">
    @yield('content')
  </main>

  <nav class="portal-tabbar" aria-label="Portal navigation">
    @if($portal === 'staff')
      <a href="{{ route('staff.dashboard') }}" class="{{ request()->routeIs('staff.dashboard') ? 'active' : '' }}"><span>Home</span></a>
      <a href="{{ route('staff.orders') }}" class="{{ request()->routeIs('staff.orders') ? 'active' : '' }}"><span>Orders</span></a>
      <a href="{{ route('staff.tickets') }}" class="{{ request()->routeIs('staff.tickets*') ? 'active' : '' }}"><span>Tickets</span></a>
      <a href="{{ route('staff.accounting') }}" class="{{ request()->routeIs('staff.accounting') ? 'active' : '' }}"><span>P&amp;L</span></a>
    @else
      <a href="{{ route('account.dashboard') }}" class="{{ request()->routeIs('account.dashboard') ? 'active' : '' }}"><span>Home</span></a>
      <a href="{{ route('account.orders') }}" class="{{ request()->routeIs('account.orders') ? 'active' : '' }}"><span>Orders</span></a>
      <a href="{{ route('account.tickets') }}" class="{{ request()->routeIs('account.tickets*') ? 'active' : '' }}"><span>Tickets</span></a>
      <a href="{{ route('shop') }}"><span>Shop</span></a>
    @endif
  </nav>
</body>
</html>
