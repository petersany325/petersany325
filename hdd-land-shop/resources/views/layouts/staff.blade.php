<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل کارمندان')</title>
    <!-- STAFF-UI-ADMIN-MATCH-V2 -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v=46">
    <link rel="stylesheet" href="{{ asset('css/admin-nav.css') }}?v=10">
    <link rel="stylesheet" href="{{ asset('css/admin-settings.css') }}?v=2">
    <style>
      /* مثل ادمین: رنگ متن همیشه خوانا + تراکم */
      :root { --admin-ui-scale: 1; }
      body {
        color: #0f172a;
        font-family: Vazirmatn, Tahoma, sans-serif;
        background: #f6f5f2;
      }
      .admin-shell { color: #0f172a; }
      .admin-main {
        color: #0f172a !important;
        background: #f6f5f2 !important;
        padding: 1.1rem 1.25rem 1.75rem;
        min-width: 0;
      }
      body.adm-compact .admin-main { padding: .85rem 1rem 1.4rem; }
      .admin-main h1,
      .admin-main h2,
      .admin-main h3,
      .admin-main p,
      .admin-main label,
      .admin-main td,
      .admin-main th,
      .admin-main span,
      .admin-main strong,
      .admin-main div {
        color: inherit;
      }
      .admin-main h1 {
        margin: 0 0 .4rem;
        font-size: 1.28rem;
        font-weight: 800;
        color: #b45309 !important;
      }
      .admin-main h2 { font-size: 1.05rem; font-weight: 800; color: #0f172a !important; }
      .admin-main h3 { font-size: .95rem; font-weight: 800; color: #0f172a !important; }
      .admin-main .muted,
      .admin-main .muted * { color: #64748b !important; }
      .admin-main .panel {
        border-radius: 8px !important;
        padding: .75rem .85rem !important;
        border: 1px solid #d8dee8 !important;
        box-shadow: 0 1px 2px rgba(15,23,42,.03) !important;
        background: #fff !important;
        color: #0f172a !important;
      }
      .admin-main .staff-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: .55rem;
        margin: .65rem 0;
      }
      .admin-main .staff-stat {
        background: #fff !important;
        border: 1px solid #d7dee8 !important;
        border-radius: 6px !important;
        padding: .55rem .65rem !important;
        color: #0f172a !important;
      }
      .admin-main .staff-stat strong {
        display: block;
        font-size: 1.08rem;
        margin-top: .15rem;
        font-weight: 800;
        color: #0f172a !important;
      }
      .admin-main .table { color: #0f172a; }
      .admin-main .table th,
      .admin-main .table td {
        padding: .45rem .55rem;
        font-size: .84rem;
        color: #0f172a !important;
        border-color: #e5e7eb;
      }
      .admin-main .btn { border-radius: 7px; font-size: .82rem; }

      /* زیر منو کارمند: متن تیره روی پس‌زمینه روشن (مثل ادمین) */
      .adm-group[data-group^="staff-"] .adm-sub {
        display: none;
        background: #f4f6f8 !important;
        border: 0 !important;
        margin: 0 !important;
        padding: .2rem !important;
        border-radius: 0 0 7px 7px;
        grid-template-columns: none !important;
      }
      .adm-group[data-group^="staff-"].is-open > .adm-sub { display: block !important; }
      .adm-group[data-group^="staff-"] .adm-sub a {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: .45rem !important;
        min-height: auto !important;
        text-align: right !important;
        padding: .5rem .65rem !important;
        margin: 0 !important;
        border: 0 !important;
        border-bottom: 1px solid #e2e6ea !important;
        border-radius: 4px !important;
        background: #fff !important;
        color: #1f2937 !important;
        font-size: .84rem !important;
        font-weight: 700 !important;
        line-height: 1.4 !important;
        opacity: 1 !important;
        visibility: visible !important;
      }
      .adm-group[data-group^="staff-"] .adm-sub a span {
        color: inherit !important;
        opacity: 1 !important;
        visibility: visible !important;
      }
      .adm-group[data-group^="staff-"] .adm-sub a .item-icon {
        width: 1.35rem !important;
        height: 1.35rem !important;
        border-radius: 6px !important;
        background: #e8f0f5 !important;
        color: #278db8 !important;
        font-size: .75rem !important;
      }
      .adm-group[data-group^="staff-"] .adm-sub a:hover {
        color: #167ca8 !important;
        background: #e5f4fb !important;
      }
      .adm-group[data-group^="staff-"] .adm-sub a:hover .item-icon {
        background: #167ca8 !important;
        color: #fff !important;
      }
      .adm-group[data-group^="staff-"] .adm-sub a.active {
        color: #fff !important;
        background: linear-gradient(90deg, #2d95c1, #4bb4df) !important;
        box-shadow: none !important;
      }
      .adm-group[data-group^="staff-"] .adm-sub a.active .item-icon {
        background: rgba(255,255,255,.22) !important;
        color: #fff !important;
      }
      .adm-group[data-group^="staff-"] .adm-sub a.ext::after {
        content: "↗";
        margin-right: auto;
        opacity: .55;
        font-size: .72rem;
        color: inherit;
      }

      /* خنثی‌سازی قانون قدیمی shop.css که متن سایدبار را سفید می‌کرد */
      .admin-side .adm-sub a,
      .admin-side .adm-sub a:hover,
      .admin-side .adm-sub a.active {
        /* رنگ‌ها در بلوک staff- بالا با !important تنظیم شده */
      }
      .admin-side a.adm-dash {
        color: #c9ced8 !important;
      }
      .admin-side a.adm-dash:hover,
      .admin-side a.adm-dash.active {
        color: #fff !important;
      }
      .adm-group-head {
        color: #f0f2f5 !important;
      }
      .adm-group-head .g-title,
      .adm-group-head .g-ico,
      .adm-group-head .g-chev {
        color: inherit !important;
        opacity: 1 !important;
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
    'مشاهده سایت' => '↗',
    'وب‌اپ فروشگاه' => '📱',
    'کارتابل مشتری' => '👤',
    'پنل مدیریت' => '⚙',
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
        <strong>کنترل پنل کارمند</strong>
        <span>HDD Land Staff</span>
      </div>
    </div>

    <nav class="adm-nav" id="admNav">
      <div class="adm-nav-search">
        <span>⌕</span>
        <input type="search" id="admNavSearch" placeholder="جستجو در منو…" autocomplete="off">
      </div>

      <a class="adm-dash {{ $path === 'staff' ? 'active' : '' }}" href="{{ url('/staff') }}">
        <span>🏠</span> داشبورد
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
          $open = $hasActive || in_array($gid, ['staff-cartable', 'staff-ops'], true);
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
                <span class="item-label">{{ $it['label'] }}</span>
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
    var key = 'hdl_staff_menu_open_v2';
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

  var rawFont = localStorage.getItem('hdl_staff_font') || '100';
  var uiScale = parseInt(rawFont, 10);
  if (!Number.isFinite(uiScale) || uiScale < 80 || uiScale > 130) uiScale = 100;
  if (localStorage.getItem('hdl_staff_compact') === null) {
    localStorage.setItem('hdl_staff_compact', '1');
  }
  function applyStaffView() {
    uiScale = Math.max(88, Math.min(118, uiScale));
    document.documentElement.style.setProperty('--admin-ui-scale', String(uiScale / 100));
    document.body.classList.toggle('adm-compact', localStorage.getItem('hdl_staff_compact') !== '0');
  }
  document.getElementById('admFontDown')?.addEventListener('click', function () {
    uiScale -= 6; localStorage.setItem('hdl_staff_font', String(uiScale)); applyStaffView();
  });
  document.getElementById('admFontUp')?.addEventListener('click', function () {
    uiScale += 6; localStorage.setItem('hdl_staff_font', String(uiScale)); applyStaffView();
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
