<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>@yield('title', 'Sign in') — EK Electronics</title>
  <link rel="icon" href="{{ asset('assets/brand/favicon.ico') }}" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/brand/favicon-32.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('assets/brand/apple-touch-icon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v=2">
  <link rel="stylesheet" href="{{ asset('assets/css/portal.css') }}?v=2">
</head>
<body class="auth-body">
  <div class="auth-shell">
    <a class="auth-brand" href="{{ route('home') }}">
      <img class="brand-mark" src="{{ asset('assets/brand/mark-hex.png') }}" width="48" height="48" alt="EK Electronics">
      <span>EK Electronics</span>
    </a>
    @if (session('error'))
      <div class="portal-flash err">{{ session('error') }}</div>
    @endif
    @if (session('success'))
      <div class="portal-flash ok">{{ session('success') }}</div>
    @endif
    @yield('content')
  </div>
</body>
</html>
