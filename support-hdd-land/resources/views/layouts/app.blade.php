<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'سرزمین هارد')</title>
    <meta name="theme-color" content="#2b3340">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}?v=hd1">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16.png') }}?v=hd1">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}?v=hd1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=erp2">
    @stack('head')
</head>
<body>
@auth
@php
    $menuGroups = \App\Support\NavMenu::forUser(auth()->user());
    $unreadNotes = auth()->user()->canAccess('notifications')
        ? \App\Models\StaffNotification::query()->where('user_id', auth()->id())->whereNull('read_at')->count()
        : 0;
@endphp
<div class="app-shell">
    {{-- نوار عنوان ویندوز --}}
    <div class="win-app-caption">
        <div class="win-app-caption-title">
            <img class="brand-logo" src="{{ asset('images/logo-header.png') }}?v=hd1" alt="HDD LAND" width="120" height="28">
            <span>سرزمین هارد — سیستم مدیریت تعمیرات</span>
        </div>
        <div class="win-app-caption-user">
            @if(auth()->user()->canAccess('notifications'))
                <a href="{{ route('notifications.index') }}" class="win-caption-btn" title="اعلان‌ها" style="text-decoration:none;margin-left:6px;">
                    ✉ @if($unreadNotes)<b>{{ $unreadNotes }}</b>@endif
                </a>
            @endif
            {{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }})
            <form method="POST" action="{{ route('logout') }}" class="win-logout-form">
                @csrf
                <button class="win-caption-btn" type="submit" title="خروج">×</button>
            </form>
        </div>
    </div>

    {{-- منوبار کلاسیک ویندوز --}}
    <div class="win-menubar" id="win-menubar">
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
                            <span class="win-menu-dot"></span>
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
                            <span class="win-menu-dot"></span>
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
        <div class="user-chip">
            <div class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
            <div>{{ auth()->user()->name }}</div>
        </div>
    </div>

    <div class="app-workspace">
        <div class="win-frame">
            <div class="win-titlebar">
                <div>
                    @hasSection('window_title')
                        @yield('window_title')
                    @else
                        @yield('page_title', 'پنجره کار')
                    @endif
                </div>
                <div class="muted">{{ now()->format('Y/m/d H:i') }}</div>
            </div>
            <div class="win-body">
                @include('partials.flash')
                @yield('content')
            </div>
        </div>
    </div>

    <div class="app-statusbar">
        <div>آماده</div>
        <div>کاربر: {{ auth()->user()->name }} | سرزمین هارد</div>
    </div>
</div>
@else
    @yield('content')
@endauth
<script src="{{ asset('js/app.js') }}?v=erp3"></script>
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

        // مثل ویندوز: وقتی یک منو باز است، هاور روی بقیه جابه‌جا می‌کند
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
