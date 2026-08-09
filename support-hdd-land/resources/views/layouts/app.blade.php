<!DOCTYPE html>
<html lang="fa" dir="rtl" data-calendar="{{ app_calendar_type() }}">
<head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', shop_name())</title>
    <meta name="theme-color" content="#2b3340">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="app-calendar" content="{{ app_calendar_type() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}?v=hd1">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16.png') }}?v=hd1">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}?v=hd1">
    <link rel="manifest" href="{{ asset('pwa/staff-manifest.json') }}?v=st1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=erp20">
    <script>
    (function () {
        try {
            var forced = localStorage.getItem('staff_ui_mode');
            var mode = (forced === 'mobile' || forced === 'desktop')
                ? forced
                : (window.matchMedia('(max-width: 900px)').matches || (window.matchMedia('(pointer: coarse)').matches && window.innerWidth < 1100)
                    ? 'mobile' : 'desktop');
            document.documentElement.setAttribute('data-ui-mode', mode);
        } catch (e) {
            document.documentElement.setAttribute('data-ui-mode', 'desktop');
        }
    })();
    </script>
    @stack('head')
</head>
<body>
@auth
@php
    $menuGroups = \App\Support\NavMenu::forUser(auth()->user());
    $mobileTabs = \App\Support\NavMenu::mobilePrimary(auth()->user(), $menuGroups);
    $unreadNotes = auth()->user()->canAccess('notifications')
        ? \Illuminate\Support\Facades\Cache::remember('unread_notes_'.auth()->id(), 20, function () {
            return \App\Models\StaffNotification::query()
                ->where('user_id', auth()->id())
                ->whereNull('read_at')
                ->count();
        })
        : 0;
@endphp
<div class="app-shell">
    {{-- نوار عنوان ویندوز / موبایل --}}
    <div class="win-app-caption">
        <div class="win-app-caption-title">
            <img class="brand-logo" src="{{ shop_logo_url('header') }}" alt="{{ shop_name() }}" width="120" height="28">
            <span class="caption-full">{{ shop_name() }} — {{ shop_tagline() }}</span>
            <span class="caption-short">{{ shop_name() }}</span>
        </div>
        <div class="win-app-caption-user">
            <button type="button" class="win-caption-btn ui-mode-toggle" data-ui-mode-toggle title="تعویض نمای موبایل / کامپیوتر">⇄</button>
            @if(auth()->user()->canAccess('notifications'))
                <a href="{{ route('notifications.index') }}" class="win-caption-btn" title="اعلان‌ها" style="text-decoration:none;margin-left:6px;">
                    ✉ @if($unreadNotes)<b>{{ $unreadNotes }}</b>@endif
                </a>
            @endif
            <span class="caption-user-name">{{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }})</span>
            <form method="POST" action="{{ route('logout') }}" class="win-logout-form">
                @csrf
                <button class="win-caption-btn" type="submit" title="خروج">×</button>
            </form>
        </div>
    </div>

    {{-- منوبار کلاسیک ویندوز (دسکتاپ) --}}
    <div class="win-menubar desktop-only" id="win-menubar">
        <nav class="win-menu-row" id="module-tabs">
            @foreach($menuGroups as $group)
                @php
                    $groupActive = \App\Support\NavMenu::isActive($group['match']);
                    $hasKids = count($group['children']) > 0;
                    $href = !empty($group['route']) ? route($group['route']) : '#';
                @endphp
                <div class="win-menu tone-{{ \App\Support\NavMenu::tone($group['key']) }} {{ $groupActive ? 'is-current' : '' }} {{ $hasKids ? 'has-popup' : '' }}"
                     data-menu-group
                     data-menu-label="{{ $group['label'] }} {{ collect($group['children'])->pluck('label')->implode(' ') }}">
                    @if($hasKids)
                        <button type="button"
                                class="win-menu-btn {{ $groupActive ? 'is-current' : '' }}"
                                data-menu-toggle
                                aria-haspopup="true"
                                aria-expanded="false">
                            <span class="win-menu-dot">{{ $group['mark'] ?? '•' }}</span>
                            {{ $group['label'] }}
                            <span class="win-menu-chevron">▾</span>
                        </button>
                        <div class="win-popup" role="menu">
                            <div class="win-popup-head">{{ $group['label'] }}</div>
                            @foreach($group['children'] as $i => $child)
                                @if($i > 0 && (($child['sep'] ?? false) || ($group['children'][$i-1]['sep_after'] ?? false)))
                                    <div class="win-popup-sep"></div>
                                @endif
                                <a href="{{ route($child['route']) }}"
                                   class="win-popup-item {{ \App\Support\NavMenu::isActive($child['match']) ? 'is-active' : '' }}"
                                   role="menuitem"
                                   data-menu-label="{{ $child['label'] }} {{ $group['label'] }}">
                                    <span class="win-popup-ico">{{ $child['mark'] ?? '•' }}</span>
                                    <span class="win-popup-text">
                                        <span class="win-popup-label">{{ $child['label'] }}</span>
                                        @if(!empty($child['hint']))
                                            <span class="win-popup-hint">{{ $child['hint'] }}</span>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a href="{{ $href }}"
                           class="win-menu-btn link {{ $groupActive ? 'is-current' : '' }}"
                           data-menu-label="{{ $group['label'] }}">
                            <span class="win-menu-dot">{{ $group['mark'] ?? '•' }}</span>
                            {{ $group['label'] }}
                        </a>
                    @endif
                </div>
            @endforeach
        </nav>
        <div class="win-menubar-search">
            <input type="search" id="module-menu-search" placeholder="جستجوی منو..." autocomplete="off">
        </div>
    </div>

    {{-- نوار ابزار --}}
    <div class="app-toolbar">
        <div class="page-caption">@yield('page_title', 'میز کار')</div>
        <button type="button" class="btn btn-ghost mobile-only staff-more-btn" data-staff-drawer-open>منوها</button>
    </div>

    <div class="app-workspace">
        <div class="win-frame">
            <div class="win-titlebar desktop-only">
                <div>
                    @hasSection('window_title')
                        @yield('window_title')
                    @else
                        @yield('page_title', 'پنجره کار')
                    @endif
                </div>
                <div class="muted">{{ jalali_like(now()) }}</div>
            </div>
            <div class="win-body">
                @include('partials.flash')
                @yield('content')
            </div>
        </div>
    </div>

    <div class="app-statusbar desktop-only">
        <div>آماده · تشخیص خودکار موبایل/کامپیوتر</div>
        <div>
            کاربر: {{ auth()->user()->name }} | {{ shop_name() }}
            @php $licBar = \App\Support\LicenseStatus::current(); @endphp
            @if(!empty($licBar['enabled']))
                <span style="margin-right:10px;opacity:.9;">· لایسنس: {{ $licBar['plan_text'] }}@if(!empty($licBar['expires_jalali'])) تا {{ $licBar['expires_jalali'] }}@elseif(!empty($licBar['lifetime'])) (مادام‌العمر)@endif</span>
            @endif
        </div>
    </div>

    {{-- تب‌بار موبایل --}}
    <nav class="staff-tabbar mobile-only" aria-label="منوی موبایل کارمند">
        @foreach($mobileTabs as $tab)
            <a href="{{ route($tab['route']) }}"
               class="{{ \App\Support\NavMenu::isActive($tab['match']) ? 'is-on' : '' }}">
                <span class="staff-tab-mark">{{ $tab['mark'] }}</span>
                <small>{{ $tab['label'] }}</small>
            </a>
        @endforeach
        <button type="button" class="staff-tab-more" data-staff-drawer-open>
            <span class="staff-tab-mark">≡</span>
            <small>بیشتر</small>
        </button>
    </nav>

    {{-- کشوی همه منوها (موبایل) --}}
    <div class="staff-drawer" id="staff-drawer" hidden>
        <div class="staff-drawer-backdrop" data-staff-drawer-close></div>
        <aside class="staff-drawer-panel" role="dialog" aria-label="منوهای کارمند">
            <div class="staff-drawer-head">
                <div>
                    <strong>منوهای کارمند</strong>
                    <div class="muted" style="font-size:11px;">{{ auth()->user()->name }} · {{ auth()->user()->roleLabel() }}</div>
                </div>
                <button type="button" class="btn btn-ghost" data-staff-drawer-close>بستن</button>
            </div>
            <div class="staff-drawer-mode">
                <span>نمای فعلی:</span>
                <strong data-ui-mode-label>خودکار</strong>
                <button type="button" class="btn btn-secondary" data-ui-mode-set="auto">خودکار</button>
                <button type="button" class="btn btn-ghost" data-ui-mode-set="mobile">موبایل</button>
                <button type="button" class="btn btn-ghost" data-ui-mode-set="desktop">کامپیوتر</button>
            </div>
            <div class="staff-drawer-search">
                <input type="search" id="staff-drawer-search" placeholder="جستجوی منو..." autocomplete="off">
            </div>
            <div class="staff-drawer-list">
                @foreach($menuGroups as $group)
                    <div class="staff-drawer-group" data-drawer-group data-menu-label="{{ $group['label'] }} {{ collect($group['children'])->pluck('label')->implode(' ') }}">
                        @if(count($group['children']) > 0)
                            <div class="staff-drawer-group-title">{{ $group['label'] }}</div>
                            @foreach($group['children'] as $child)
                                <a href="{{ route($child['route']) }}"
                                   class="staff-drawer-item {{ \App\Support\NavMenu::isActive($child['match']) ? 'is-on' : '' }}"
                                   data-menu-label="{{ $child['label'] }} {{ $group['label'] }}">
                                    <span class="staff-drawer-ico">{{ $child['mark'] ?? '•' }}</span>
                                    <span>
                                        <strong>{{ $child['label'] }}</strong>
                                        @if(!empty($child['hint']))
                                            <small>{{ $child['hint'] }}</small>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        @elseif(!empty($group['route']))
                            <a href="{{ route($group['route']) }}"
                               class="staff-drawer-item {{ \App\Support\NavMenu::isActive($group['match']) ? 'is-on' : '' }}"
                               data-menu-label="{{ $group['label'] }}">
                                <span class="staff-drawer-ico">{{ $group['mark'] }}</span>
                                <span>
                                    <strong>{{ $group['label'] }}</strong>
                                    @if(!empty($group['hint']))
                                        <small>{{ $group['hint'] }}</small>
                                    @endif
                                </span>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</div>
@else
    @yield('content')
@endauth
<script src="{{ asset('js/app.js') }}?v=erp20"></script>
<script>
(function () {
    var bar = document.getElementById('win-menubar');
    var root = document.getElementById('module-tabs');
    if (!bar || !root) return;
    var sticky = false;

    function closeAll(except) {
        root.querySelectorAll('[data-menu-group].is-open').forEach(function (g) {
            if (except && g === except) return;
            g.classList.remove('is-open');
            var btn = g.querySelector('[data-menu-toggle]');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }

    function openGroup(group) {
        if (!group || !group.classList.contains('has-popup')) return;
        closeAll(group);
        group.classList.add('is-open');
        var btn = group.querySelector('[data-menu-toggle]');
        if (btn) btn.setAttribute('aria-expanded', 'true');
    }

    root.querySelectorAll('[data-menu-toggle]').forEach(function (btn) {
        var group = btn.closest('[data-menu-group]');

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var open = group.classList.contains('is-open');
            if (open) {
                sticky = false;
                closeAll();
            } else {
                sticky = true;
                openGroup(group);
            }
        });

        btn.addEventListener('mouseenter', function () {
            if (sticky) openGroup(group);
        });
    });

    document.addEventListener('click', function (e) {
        if (!bar.contains(e.target)) {
            sticky = false;
            closeAll();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            sticky = false;
            closeAll();
        }
    });

    var input = document.getElementById('module-menu-search');
    if (input) {
        input.addEventListener('input', function () {
            var q = (input.value || '').trim().toLowerCase();
            sticky = !!q;
            root.querySelectorAll('[data-menu-group]').forEach(function (g) {
                var label = (g.getAttribute('data-menu-label') || '').toLowerCase();
                var show = !q || label.indexOf(q) !== -1;
                g.style.display = show ? '' : 'none';
                if (show && q && g.classList.contains('has-popup')) openGroup(g);
                if (!q) {
                    g.classList.remove('is-open');
                    sticky = false;
                }
            });
            root.querySelectorAll('.win-popup-item[data-menu-label]').forEach(function (a) {
                var label = (a.getAttribute('data-menu-label') || '').toLowerCase();
                a.style.display = (!q || label.indexOf(q) !== -1) ? '' : 'none';
            });
        });
        input.addEventListener('click', function (e) { e.stopPropagation(); });
    }
})();
</script>
@stack('scripts')
</body>
</html>
