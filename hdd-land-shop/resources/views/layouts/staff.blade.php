<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل کارمندان')</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v=45">
    <link rel="stylesheet" href="{{ asset('css/admin-nav.css') }}?v=9">
    <style>
      /* تراکم و کادر مثل پنل ادمین */
      :root { --admin-ui-scale: .94; }
      body.adm-compact .admin-main { padding: .85rem 1rem 1.4rem; }
      .admin-main { font-size: calc(.92rem * var(--admin-ui-scale, 1)); }
      .admin-main > h1:first-child,
      .admin-main h1 { margin: 0 0 .35rem; font-size: 1.15rem; font-weight: 800; }
      .admin-main .muted { font-size: .8rem; }
      .admin-main .panel {
        border-radius: 6px !important;
        padding: .7rem .8rem !important;
        border: 1px solid #d5dbe3 !important;
        box-shadow: none !important;
        background: #fff;
      }
      .admin-main .panel h3 { margin: 0 0 .55rem; font-size: .95rem; }
      .admin-main .staff-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
        gap: .5rem;
        margin: .65rem 0;
      }
      .admin-main .staff-stat {
        background: #fff;
        border: 1px solid #d7dee8;
        border-radius: 5px;
        padding: .55rem .65rem;
      }
      .admin-main .staff-stat strong {
        display: block;
        font-size: 1.05rem;
        margin-top: .15rem;
        font-weight: 800;
        line-height: 1.25;
      }
      .admin-main .staff-stat .muted,
      .admin-main .staff-stat [style*="font-size:.75rem"] { font-size: .72rem !important; }
      .admin-main .table th,
      .admin-main .table td { padding: .4rem .5rem; font-size: .8rem; }
      .admin-main .btn { border-radius: 6px; font-size: .8rem; }
      .admin-main .btn-sm { padding: .28rem .55rem; font-size: .72rem; border-radius: 5px; }
      .admin-main .alert { border-radius: 6px; padding: .55rem .7rem; font-size: .82rem; }
      .adm-group[data-group="staff-ops"].is-open > .adm-sub,
      .adm-group[data-group="staff-cartable"].is-open > .adm-sub,
      .adm-group[data-group="staff-links"].is-open > .adm-sub {
        display: block;
        grid-template-columns: none;
        background: #f4f6f8;
        border-radius: 0 0 7px 7px;
        padding: .2rem;
        margin: 0;
      }
      .adm-group[data-group="staff-ops"] .adm-sub a,
      .adm-group[data-group="staff-cartable"] .adm-sub a,
      .adm-group[data-group="staff-links"] .adm-sub a {
        min-height: auto;
        flex-direction: row;
        justify-content: flex-start;
        text-align: right;
        gap: .45rem;
        padding: .48rem .65rem;
        border: 0;
        border-bottom: 1px solid #e2e6ea;
        background: transparent !important;
      }
    </style>
</head>
<body class="adm-compact">
@php
  $path = trim(request()->path(), '/');
  $perms = $perms ?? [];
  $items = class_exists(\App\Support\PortalNav::class)
    ? \App\Support\PortalNav::staffItems($perms, $path)
    : [];
  $cross = class_exists(\App\Support\PortalNav::class)
    ? \App\Support\PortalNav::crossLinks('staff')
    : [['label' => 'مشاهده سایت', 'url' => '/']];

  $visible = array_values(array_filter($items, fn ($it) => ! empty($it['show'])));
  $cartableKeys = ['داشبورد', 'گزارش کار و سود'];
  $opsKeys = ['سفارش‌ها', 'محصولات', 'سریال‌ها / گارانتی', 'فروش قطعه', 'پشتیبانی', 'حسابداری'];
  $cartable = array_values(array_filter($visible, fn ($it) => in_array($it['label'], $cartableKeys, true)));
  $ops = array_values(array_filter($visible, fn ($it) => in_array($it['label'], $opsKeys, true)));
  $icons = [
    'داشبورد' => '⌂',
    'گزارش کار و سود' => '◉',
    'سفارش‌ها' => '▣',
    'محصولات' => '▤',
    'سریال‌ها / گارانتی' => '⌁',
    'فروش قطعه' => '◎',
    'پشتیبانی' => '✉',
    'حسابداری' => '₫',
  ];
@endphp
<div class="admin-topbar">
  <button type="button" class="adm-menu-btn" id="admMenuBtn" aria-label="منو">☰ منو</button>
  <div class="adm-top-user">
    <strong>{{ auth()->user()->name ?? 'کارمند' }}</strong>
    <form action="{{ url('/logout') }}" method="post">@csrf
      <button class="adm-logout-btn" type="submit">خروج</button>
    </form>
  </div>
</div>
<div class="admin-shell" id="adminShell">
  <div class="adm-backdrop" id="admBackdrop" hidden></div>
  <aside class="admin-side" id="adminSide">
    <div class="adm-brand">
      <span class="brand-mark">ST</span>
      <div>
        <strong>پنل کارمندان</strong>
        <span>{{ auth()->user()->name ?? 'کارمند' }} · HDD Land</span>
      </div>
    </div>

    <nav class="adm-nav" id="admNav">
      <div class="adm-nav-search">
        <span>⌕</span>
        <input type="search" id="admNavSearch" placeholder="جستجو در منو…" autocomplete="off">
      </div>

      <a class="adm-dash {{ $path === 'staff' ? 'active' : '' }}" href="{{ url('/staff') }}">
        <span>🏠</span> داشبورد کارتابل
      </a>

      @php
        $groups = [
          'staff-cartable' => ['title' => 'کارتابل', 'icon' => '▦', 'items' => $cartable],
          'staff-ops' => ['title' => 'عملیات و فروش', 'icon' => '⚡', 'items' => $ops],
          'staff-links' => [
            'title' => 'لینک‌های سریع',
            'icon' => '↗',
            'items' => array_map(fn ($cl) => [
              'label' => $cl['label'],
              'href' => url($cl['url']),
              'active' => false,
              'ext' => ($cl['url'] ?? '') === '/',
              'show' => true,
            ], $cross),
          ],
        ];
      @endphp

      @foreach($groups as $gid => $g)
        @continue(empty($g['items']))
        @php
          $hasActive = collect($g['items'])->contains(fn ($it) => ! empty($it['active']));
          $open = $hasActive || $gid === 'staff-cartable' || $gid === 'staff-ops';
        @endphp
        <div class="adm-group {{ $open ? 'is-open' : '' }} {{ $hasActive ? 'is-active' : '' }}" data-group="{{ $gid }}">
          <button type="button" class="adm-group-head" aria-expanded="{{ $open ? 'true' : 'false' }}">
            <span class="g-ico">{{ $g['icon'] }}</span>
            <span class="g-title">{{ $g['title'] }}</span>
            <span class="g-chev">▾</span>
          </button>
          <div class="adm-sub">
            @foreach($g['items'] as $it)
              <a href="{{ $it['href'] }}"
                 class="{{ ! empty($it['active']) ? 'active' : '' }} {{ ! empty($it['ext']) ? 'ext' : '' }}"
                 @if(! empty($it['ext'])) target="_blank" rel="noopener" @endif>
                <span class="item-icon">{{ $icons[$it['label']] ?? '•' }}</span>
                <span>{{ $it['label'] }}</span>
              </a>
            @endforeach
          </div>
        </div>
      @endforeach
    </nav>

    <div class="adm-foot">
      <div class="adm-view-tools" aria-label="تنظیمات نمایش منو">
        <button type="button" id="admFontDown" title="فونت کوچک‌تر">A−</button>
        <button type="button" id="admFontUp" title="فونت بزرگ‌تر">A+</button>
        <button type="button" id="admDensity" title="حالت فشرده">☷</button>
      </div>
      <div class="adm-foot-user">{{ auth()->user()->email ?? auth()->user()->name ?? '' }}</div>
      <form action="{{ url('/logout') }}" method="post">@csrf
        <button class="adm-logout-btn adm-logout-block" type="submit">خروج از حساب</button>
      </form>
    </div>
  </aside>

  <div class="admin-main">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
    @yield('content')
  </div>
</div>
<script>
(function(){
  var nav = document.getElementById('admNav');
  if (nav) {
    var key = 'hdl_staff_menu_open_v1';
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(key) || '{}') || {}; } catch (e) {}

    nav.querySelectorAll('.adm-group').forEach(function (g) {
      var id = g.getAttribute('data-group');
      if (saved[id] === true) g.classList.add('is-open');
      if (saved[id] === false && !g.classList.contains('is-active')) g.classList.remove('is-open');

      var head = g.querySelector('.adm-group-head');
      if (!head) return;
      head.addEventListener('click', function () {
        g.classList.toggle('is-open');
        head.setAttribute('aria-expanded', g.classList.contains('is-open') ? 'true' : 'false');
        try {
          var map = JSON.parse(localStorage.getItem(key) || '{}') || {};
          map[id] = g.classList.contains('is-open');
          localStorage.setItem(key, JSON.stringify(map));
        } catch (e) {}
      });
    });

    var search = document.getElementById('admNavSearch');
    search?.addEventListener('input', function () {
      var q = (this.value || '').trim().toLocaleLowerCase('fa');
      nav.querySelectorAll('.adm-group').forEach(function (group) {
        var matches = 0;
        group.querySelectorAll('.adm-sub a').forEach(function (link) {
          var show = !q || link.textContent.toLocaleLowerCase('fa').includes(q);
          link.style.display = show ? '' : 'none';
          if (show) matches++;
        });
        var title = group.querySelector('.g-title')?.textContent.toLocaleLowerCase('fa') || '';
        var groupMatch = !q || matches > 0 || title.includes(q);
        group.style.display = q ? (groupMatch ? '' : 'none') : '';
        if (q && groupMatch) group.classList.add('is-open');
      });
    });
  }

  var uiScale = parseInt(localStorage.getItem('hdl_staff_font') || localStorage.getItem('hdl_admin_font') || '94', 10);
  if (localStorage.getItem('hdl_staff_compact') === null) {
    localStorage.setItem('hdl_staff_compact', '1');
  }
  function applyStaffView() {
    uiScale = Math.max(88, Math.min(118, uiScale));
    document.documentElement.style.setProperty('--admin-ui-scale', uiScale / 100);
    document.body.classList.toggle('adm-compact', localStorage.getItem('hdl_staff_compact') !== '0');
  }
  document.getElementById('admFontDown')?.addEventListener('click', function () {
    uiScale -= 6; localStorage.setItem('hdl_staff_font', uiScale); applyStaffView();
  });
  document.getElementById('admFontUp')?.addEventListener('click', function () {
    uiScale += 6; localStorage.setItem('hdl_staff_font', uiScale); applyStaffView();
  });
  document.getElementById('admDensity')?.addEventListener('click', function () {
    localStorage.setItem('hdl_staff_compact', document.body.classList.contains('adm-compact') ? '0' : '1');
    applyStaffView();
  });
  applyStaffView();

  var shell = document.getElementById('adminShell');
  var btn = document.getElementById('admMenuBtn');
  var backdrop = document.getElementById('admBackdrop');
  function closeSide() { if (shell) shell.classList.remove('side-open'); if (backdrop) backdrop.hidden = true; }
  function openSide() { if (shell) shell.classList.add('side-open'); if (backdrop) backdrop.hidden = false; }
  if (btn) {
    btn.addEventListener('click', function () {
      shell && shell.classList.contains('side-open') ? closeSide() : openSide();
    });
  }
  if (backdrop) backdrop.addEventListener('click', closeSide);
})();
</script>
</body>
</html>
