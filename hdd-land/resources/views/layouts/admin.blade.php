<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل مدیریت')</title>
    <!-- ADMIN-MENU-V11-WEBAPP -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v=43">
    <link rel="stylesheet" href="{{ asset('css/admin-nav.css') }}?v=8">
    <link rel="stylesheet" href="{{ asset('css/admin-settings.css') }}?v=1">
</head>
<body>
@php
  $u = fn (string $path) => url('/admin/'.ltrim($path, '/'));
  $path = trim(request()->path(), '/');
  $tab = (string) request('tab', '');
  $is = function (string ...$needles) use ($path): bool {
      foreach ($needles as $n) {
          if ($n !== '' && str_contains($path, $n)) {
              return true;
          }
      }
      return false;
  };

  $groups = [
    'users' => [
      'title' => 'کاربران و عضویت',
      'icon' => '👤',
      'open' => $is('customers', 'auth-settings', 'auth-register', 'auth-fields', 'auth-terms', 'auth-2fa', 'auth-sms', 'auth-notify', 'wallet-settings'),
      'items' => [
        ['label' => 'لیست مشتریان', 'href' => $u('customers'), 'active' => $is('customers')],
        ['label' => 'کیف پول مشتریان', 'href' => $u('wallet-settings'), 'active' => $is('wallet-settings')],
        ['label' => 'تنظیمات ثبت‌نام', 'href' => $u('auth-settings').'?tab=register', 'active' => $is('auth-settings') && ($tab === '' || $tab === 'register')],
        ['label' => 'فیلدهای ثبت‌نام', 'href' => $u('auth-settings').'?tab=fields', 'active' => $is('auth-settings') && $tab === 'fields'],
        ['label' => 'عودت وجه / شبا', 'href' => $u('auth-settings').'?tab=refund', 'active' => $is('auth-settings') && $tab === 'refund'],
        ['label' => 'قوانین سایت', 'href' => $u('auth-settings').'?tab=terms', 'active' => $is('auth-settings') && $tab === 'terms'],
        ['label' => 'ورود دو مرحله‌ای', 'href' => $u('auth-settings').'?tab=2fa', 'active' => $is('auth-settings') && $tab === '2fa'],
        ['label' => 'پنل SMS', 'href' => $u('auth-settings').'?tab=sms', 'active' => $is('auth-settings') && $tab === 'sms'],
        ['label' => 'اعلان‌های SMS', 'href' => $u('auth-settings').'?tab=notify', 'active' => $is('auth-settings') && $tab === 'notify'],
        ['label' => 'صفحه ورود', 'href' => url('/login'), 'active' => false, 'ext' => true],
        ['label' => 'صفحه ثبت‌نام', 'href' => url('/register'), 'active' => false, 'ext' => true],
        ['label' => 'فراموشی رمز', 'href' => url('/forgot-password'), 'active' => false, 'ext' => true],
        ['label' => 'قوانین', 'href' => url('/terms'), 'active' => false, 'ext' => true],
      ],
    ],
    'shop' => [
      'title' => 'فروشگاه',
      'icon' => '🛒',
      'open' => $is('products', 'categories', 'orders', 'media') || str_contains($path, 'products/display-settings'),
      'items' => [
        ['label' => 'محصولات', 'href' => $u('products'), 'active' => $is('products') && ! request()->is('admin/products/create') && ! request()->is('admin/products/*/edit') && ! request()->is('admin/products/*/serials') && ! str_contains($path, 'products/display-settings')],
        ['label' => 'افزودن محصول', 'href' => $u('products/create'), 'active' => request()->is('admin/products/create') && ! request()->boolean('with_serial') && ! request()->boolean('with_warranty')],
        ['label' => 'تنظیمات نمایش محصول', 'href' => $u('products/display-settings'), 'active' => str_contains($path, 'products/display-settings')],
        ['label' => 'دسته‌بندی‌ها', 'href' => $u('categories'), 'active' => $is('categories') && ! str_contains($path, 'categories/settings')],
        ['label' => 'تنظیمات دسته‌ها', 'href' => $u('categories/settings'), 'active' => str_contains($path, 'categories/settings')],
        ['label' => 'کتابخانه رسانه', 'href' => $u('media'), 'active' => $is('media') && ! str_contains($path, 'media/settings')],
        ['label' => 'تنظیمات کتابخانه', 'href' => $u('media/settings'), 'active' => str_contains($path, 'media/settings')],
        ['label' => 'سفارش‌ها', 'href' => $u('orders'), 'active' => $is('orders')],
      ],
    ],
    'shipping' => [
      'title' => 'ارسال و حمل',
      'icon' => '📦',
      'open' => $is('shipping-carriers', 'shipping-post', 'shipping-tipax', 'invoice-design'),
      'items' => [
        ['label' => 'شرکت‌های باربری', 'href' => $u('shipping-carriers'), 'active' => $is('shipping-carriers')],
        ['label' => 'طراحی فاکتور', 'href' => $u('invoice-design'), 'active' => $is('invoice-design')],
        ['label' => 'پست پیشتاز (قدیمی)', 'href' => $u('shipping-post'), 'active' => $is('shipping-post')],
        ['label' => 'تیپاکس (قدیمی)', 'href' => $u('shipping-tipax'), 'active' => $is('shipping-tipax')],
      ],
    ],
    'sales' => [
      'title' => 'فروش، سریال و گارانتی',
      'icon' => '🏷️',
      'open' => $is('serial-sales', 'serial-warranties', 'warranty-companies', 'reports') || request()->is('admin/products/*/serials') || (request()->is('admin/products/create') && (request()->boolean('with_serial') || request()->boolean('with_warranty'))),
      'items' => [
        ['label' => 'لیست و ثبت سریال', 'href' => $u('serial-sales'), 'active' => $is('serial-sales') && ! $is('serial-warranties')],
        ['label' => 'ثبت سریع با بارکدخوان', 'href' => $u('serial-sales').'?tab=add', 'active' => $is('serial-sales') && $tab === 'add'],
        ['label' => 'محصول با گارانتی/سریال', 'href' => url('/admin/products/create?with_warranty=1&with_serial=1'), 'active' => request()->is('admin/products/create') && (request()->boolean('with_serial') || request()->boolean('with_warranty'))],
        ['label' => 'شرکت‌های گارانتی', 'href' => $u('warranty-companies'), 'active' => $is('warranty-companies')],
        ['label' => 'لیست گارانتی‌ها', 'href' => $u('serial-warranties'), 'active' => $is('serial-warranties')],
        ['label' => 'گزارشات فروش', 'href' => $u('reports'), 'active' => $is('reports')],
        ['label' => 'فروش و کمیسیون کارمندان', 'href' => $u('staff/reports'), 'active' => $is('staff/reports')],
        ['label' => 'گزارش حسابداری فروش', 'href' => $u('accounting/reports'), 'active' => $is('accounting/reports')],
      ],
    ],
    'accounting' => [
      'title' => 'حسابداری',
      'icon' => '📊',
      'open' => $is('accounting'),
      'items' => [
        ['label' => 'داشبورد حسابداری', 'href' => $u('accounting'), 'active' => $is('accounting') && ! str_contains($path, 'accounting/')],
        ['label' => 'فاکتور و پیش‌فاکتور', 'href' => $u('accounting/documents'), 'active' => str_contains($path, 'accounting/documents') && ! str_contains($path, 'create')],
        ['label' => 'فاکتور فروش جدید', 'href' => $u('accounting/documents/create').'?type=invoice', 'active' => str_contains($path, 'documents/create') && request('type','invoice')==='invoice'],
        ['label' => 'پیش‌فاکتور جدید', 'href' => $u('accounting/documents/create').'?type=proforma', 'active' => request('type')==='proforma'],
        ['label' => 'فاکتور خرید', 'href' => $u('accounting/documents/create').'?type=purchase', 'active' => request('type')==='purchase'],
        ['label' => 'دفتر روزنامه', 'href' => $u('accounting/ledger'), 'active' => $is('accounting/ledger')],
        ['label' => 'ثبت سند دستی', 'href' => $u('accounting/create'), 'active' => $is('accounting/create')],
        ['label' => 'گزارش خرید و فروش', 'href' => $u('accounting/reports'), 'active' => $is('accounting/reports')],
        ['label' => 'تنظیمات حسابداری', 'href' => $u('accounting/settings'), 'active' => $is('accounting/settings')],
      ],
    ],
    'ops' => [
      'title' => 'عملیات و پشتیبانی',
      'icon' => '🧑‍💼',
      'open' => $is('staff', 'smart-chat', 'tickets'),
      'items' => [
        ['label' => 'تیکت‌های پشتیبانی', 'href' => $u('tickets'), 'active' => $is('tickets') && ! $is('tickets/settings')],
        ['label' => 'تنظیمات تیکت', 'href' => $u('tickets/settings'), 'active' => $is('tickets/settings')],
        ['label' => 'کارمندان', 'href' => $u('staff'), 'active' => $is('staff') && ! $is('staff/reports') && ! $is('staff/activity') && ! $is('staff/create') && ! request()->is('admin/staff/*/edit')],
        ['label' => 'افزودن کارمند', 'href' => $u('staff/create'), 'active' => $is('staff/create')],
        ['label' => 'گزارش سود/کمیسیون', 'href' => $u('staff/reports'), 'active' => $is('staff/reports')],
        ['label' => 'گزارش کار و ورود/خروج', 'href' => $u('staff/activity'), 'active' => $is('staff/activity')],
        ['label' => 'کپی لینک ورود (امن)', 'href' => $u('staff'), 'active' => false],
        ['label' => 'تنظیمات چت و شناسایی مشتری', 'href' => $u('smart-chat'), 'active' => $is('smart-chat')],
        ['label' => 'مرکز رشد و کانال‌های فروش', 'href' => $u('marketing-hub'), 'active' => $is('marketing-hub')],
      ],
    ],
    'theme' => [
      'title' => 'تنظیمات قالب',
      'icon' => '🎨',
      'open' => $is('theme-builder', 'theme-templates', 'page-builder', 'mega-menu', 'corporate-home', 'footer-settings'),
      'items' => [
        ['label' => 'استودیو قالب صفحه اول', 'href' => $u('theme-builder'), 'active' => $is('theme-builder') && ! $is('theme-templates')],
        ['label' => 'بنرساز Revolution', 'href' => $u('theme-builder').'#pane-banner', 'active' => false],
        ['label' => 'نصب / آپدیت قالب', 'href' => $u('theme-templates'), 'active' => $is('theme-templates')],
        ['label' => 'صفحه‌ساز Elementor', 'href' => $u('page-builder'), 'active' => $is('page-builder')],
        ['label' => 'مگامنو', 'href' => $u('mega-menu'), 'active' => $is('mega-menu')],
        ['label' => 'بنر / صفحه اول / فوتر شرکتی', 'href' => $u('corporate-home'), 'active' => $is('corporate-home')],
        ['label' => 'فوتر مدرن', 'href' => $u('footer-settings'), 'active' => $is('footer-settings')],
      ],
    ],
    'webapp' => [
      'title' => 'وب‌سرویس',
      'icon' => '📱',
      'open' => $is('web-app'),
      'items' => [
        ['label' => 'تنظیمات وب‌اپ / PWA', 'href' => $u('web-app'), 'active' => $is('web-app')],
        ['label' => 'پیش‌نمایش وب‌اپ', 'href' => url('/app'), 'active' => false, 'ext' => true],
        ['label' => 'فایل Manifest', 'href' => url('/manifest.webmanifest'), 'active' => false, 'ext' => true],
      ],
    ],
    'payment' => [
      'title' => 'تنظیمات پرداخت',
      'icon' => '💳',
      'open' => $is('payment'),
      'items' => [
        ['label' => 'درگاه زرین‌پال', 'href' => $u('payment'), 'active' => $is('payment')],
        ['label' => 'تنظیمات عمومی فروشگاه', 'href' => $u('settings'), 'active' => false],
      ],
    ],
    'system' => [
      'title' => 'سیستم',
      'icon' => '⚙️',
      'open' => $is('system-tools', 'plugins') || ($is('settings') && ! $is('auth-settings') && ! $is('payment')),
      'items' => [
        ['label' => 'تنظیمات فروشگاه', 'href' => $u('settings'), 'active' => $is('settings') && ! $is('auth-settings')],
        ['label' => 'افزونه‌ها', 'href' => $u('plugins'), 'active' => $is('plugins')],
        ['label' => 'استودیوی توسعه افزونه', 'href' => $u('developer-studio'), 'active' => $is('developer-studio')],
        ['label' => 'تعمیر و نگهداری', 'href' => $u('system-tools'), 'active' => $is('system-tools')],
        ['label' => 'سلامت و بهینه‌سازی سرعت', 'href' => $u('system-tools').'#performance', 'active' => false],
        ['label' => 'مشاهده فروشگاه', 'href' => url('/'), 'active' => false, 'ext' => true],
      ],
    ],
  ];

  // مرکز یکپارچه کالا، موجودی و سریال
  $groups['shop'] = [
    'title' => 'کالا، انبار و سریال',
    'icon' => '▦',
    'open' => $is('products', 'featured-products', 'categories', 'orders', 'media', 'serial-sales', 'serial-warranties', 'warranty-companies', 'reports') || request()->is('admin/products/*/serials') || str_contains($path, 'products/display-settings'),
    'items' => [
      ['icon'=>'▤','label'=>'مدیریت کالاها','href'=>$u('products'),'active'=>$is('products') && !$is('featured-products') && !request()->is('admin/products/create') && !request()->is('admin/products/*/serials') && !str_contains($path,'products/display-settings')],
      ['icon'=>'★','label'=>'محصولات ویژه','href'=>$u('featured-products'),'active'=>$is('featured-products')],
      ['icon'=>'＋','label'=>'افزودن کالای جدید','href'=>$u('products/create'),'active'=>request()->is('admin/products/create') && !request()->boolean('with_serial')],
      ['icon'=>'▣','label'=>'کالای سریال‌دار','href'=>url('/admin/products/create?with_warranty=1&with_serial=1'),'active'=>request()->is('admin/products/create') && request()->boolean('with_serial')],
      ['icon'=>'⌁','label'=>'ثبت با بارکدخوان','href'=>$u('serial-sales').'?tab=add','active'=>$is('serial-sales') && $tab==='add'],
      ['icon'=>'≡','label'=>'لیست سریال‌ها','href'=>$u('serial-sales').'?tab=list','active'=>$is('serial-sales') && ($tab==='' || $tab==='list')],
      ['icon'=>'✓','label'=>'گارانتی‌ها','href'=>$u('serial-warranties'),'active'=>$is('serial-warranties')],
      ['icon'=>'◆','label'=>'شرکت‌های گارانتی','href'=>$u('warranty-companies'),'active'=>$is('warranty-companies')],
      ['icon'=>'▥','label'=>'دسته‌بندی کالا','href'=>$u('categories'),'active'=>$is('categories') && !str_contains($path,'categories/settings')],
      ['icon'=>'▧','label'=>'کتابخانه تصاویر','href'=>$u('media'),'active'=>$is('media') && !str_contains($path,'media/settings')],
      ['icon'=>'▨','label'=>'سفارش‌ها','href'=>$u('orders'),'active'=>$is('orders')],
      ['icon'=>'↗','label'=>'گزارش فروش','href'=>$u('reports'),'active'=>$is('reports')],
      ['icon'=>'◎','label'=>'تنظیمات نمایش محصول','href'=>$u('products/display-settings'),'active'=>str_contains($path,'products/display-settings')],
      ['icon'=>'⚙','label'=>'تنظیمات کالا و رسانه','href'=>$u('media/settings'),'active'=>str_contains($path,'media/settings') || str_contains($path,'categories/settings')],
    ],
  ];
  unset($groups['sales']);
  $groupSections = [
    'shop'=>'commerce','shipping'=>'commerce',
    'users'=>'manage','accounting'=>'manage','ops'=>'manage',
    'theme'=>'design','webapp'=>'design',
    'payment'=>'system','system'=>'system',
  ];

  // کارمند: منوی ادمین را محدود کن و لینک بازگشت به پنل کارمند بگذار
  $isStaffOnly = auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff() && ! auth()->user()->isAdmin();
  $staffCan = fn (string $perm): bool => ! $isStaffOnly || auth()->user()->hasStaffPermission($perm);
  $staffCanSystemTools = $isStaffOnly && auth()->user()->hasStaffPermission('system_tools');
  if ($isStaffOnly) {
    unset($groups['users'], $groups['payment'], $groups['accounting']);

    // تنظیمات قالب — هر آیتم با سوئیچ جدا
    $themeItems = [];
    if ($staffCan('site.theme_builder')) {
      $themeItems[] = ['label' => 'استودیو قالب صفحه اول', 'href' => $u('theme-builder'), 'active' => $is('theme-builder') && ! $is('theme-templates')];
      $themeItems[] = ['label' => 'بنرساز Revolution', 'href' => $u('theme-builder').'#pane-banner', 'active' => false];
    }
    if ($staffCan('site.theme_templates')) {
      $themeItems[] = ['label' => 'نصب / آپدیت قالب', 'href' => $u('theme-templates'), 'active' => $is('theme-templates')];
    }
    if ($staffCan('site.page_builder')) {
      $themeItems[] = ['label' => 'صفحه‌ساز Elementor', 'href' => $u('page-builder'), 'active' => $is('page-builder')];
    }
    if ($staffCan('site.mega_menu')) {
      $themeItems[] = ['label' => 'مگامenu', 'href' => $u('mega-menu'), 'active' => $is('mega-menu')];
    }
    if ($staffCan('site.homepage')) {
      $themeItems[] = ['label' => 'بنر / صفحه اول / فوتر شرکتی', 'href' => $u('corporate-home'), 'active' => $is('corporate-home')];
    }
    if ($staffCan('site.footer')) {
      $themeItems[] = ['label' => 'فوتر مدرن', 'href' => $u('footer-settings'), 'active' => $is('footer-settings')];
    }
    if ($themeItems) {
      $groups['theme'] = [
        'title' => 'تنظیمات قالب',
        'icon' => '🎨',
        'open' => $is('theme-builder', 'theme-templates', 'page-builder', 'mega-menu', 'corporate-home', 'footer-settings'),
        'items' => $themeItems,
      ];
    } else {
      unset($groups['theme']);
    }

    if ($staffCan('site.webapp')) {
      $groups['webapp'] = [
        'title' => 'وب‌سرویس',
        'icon' => '📱',
        'open' => $is('web-app'),
        'items' => [
          ['label' => 'تنظیمات وب‌اپ / PWA', 'href' => $u('web-app'), 'active' => $is('web-app')],
          ['label' => 'پیش‌نمایش وب‌اپ', 'href' => url('/app'), 'active' => false, 'ext' => true],
          ['label' => 'فایل Manifest', 'href' => url('/manifest.webmanifest'), 'active' => false, 'ext' => true],
        ],
      ];
    } else {
      unset($groups['webapp']);
    }

    $systemItems = [];
    if ($staffCan('site.shop_settings')) {
      $systemItems[] = ['label' => 'تنظیمات فروشگاه', 'href' => $u('settings'), 'active' => $is('settings') && ! $is('auth-settings')];
    }
    if ($staffCanSystemTools) {
      $systemItems[] = ['label' => 'تعمیر و نگهداری', 'href' => $u('system-tools'), 'active' => $is('system-tools')];
      $systemItems[] = ['label' => 'سلامت و بهینه‌سازی سرعت', 'href' => $u('system-tools').'#performance', 'active' => false];
    }
    $systemItems[] = ['label' => 'مشاهده فروشگاه', 'href' => url('/'), 'active' => false, 'ext' => true];
    if ($systemItems) {
      $groups['system'] = [
        'title' => 'سیستم',
        'icon' => '⚙️',
        'open' => $is('system-tools') || ($is('settings') && ! $is('auth-settings') && ! $is('payment')),
        'items' => $systemItems,
      ];
    } else {
      unset($groups['system']);
    }

    if (isset($groups['ops']['items'])) {
      $groups['ops']['items'] = array_values(array_filter($groups['ops']['items'], function ($it) {
        return ! str_contains($it['href'] ?? '', '/admin/staff');
      }));
    }
  }
@endphp
<div class="admin-topbar">
  <button type="button" class="adm-menu-btn" id="admMenuBtn" aria-label="منو">☰ منو</button>
  <div class="adm-top-user">
    <strong>{{ auth()->user()->name ?? 'مدیر' }}</strong>
    <form action="{{ url('/logout') }}" method="post">@csrf
      <button class="adm-logout-btn" type="submit">خروج</button>
    </form>
  </div>
</div>
<div class="admin-shell" id="adminShell">
    <div class="adm-backdrop" id="admBackdrop" hidden></div>
    <aside class="admin-side" id="adminSide">
        <div class="adm-brand">
            <span class="brand-mark">{{ !empty($isStaffOnly) ? 'ST' : 'AD' }}</span>
            <div>
              <strong>{{ !empty($isStaffOnly) ? 'پنل عملیاتی' : 'کنترل پنل' }}</strong>
              <span>{{ !empty($isStaffOnly) ? 'کارمند HDD Land' : 'HDD Land Admin' }}</span>
            </div>
        </div>

        <nav class="adm-nav" id="admNav">
            <div class="adm-nav-search">
              <span>⌕</span>
              <input type="search" id="admNavSearch" placeholder="جستجو در منو…" autocomplete="off">
            </div>
            <div class="adm-nav-tabs" role="tablist" aria-label="دسته‌های مدیریت">
              <button type="button" class="active" data-nav-section="commerce">فروشگاه</button>
              <button type="button" data-nav-section="manage">مدیریت</button>
              <button type="button" data-nav-section="design">طراحی</button>
              <button type="button" data-nav-section="system">سیستم</button>
            </div>
            @if(!empty($isStaffOnly))
              <a class="adm-dash" href="{{ url('/staff') }}">
                <span>🏠</span> بازگشت به پنل کارمند
              </a>
            @else
            <a class="adm-dash {{ $path === 'admin' ? 'active' : '' }}" href="{{ $u('/') }}">
              <span>🏠</span> داشبورد
            </a>
            @endif

            @foreach($groups as $gid => $g)
              @php
                $hasActive = collect($g['items'])->contains(fn ($it) => !empty($it['active']));
                $open = $g['open'] || $hasActive;
              @endphp
              <div class="adm-group {{ $open ? 'is-open' : '' }} {{ $hasActive ? 'is-active' : '' }}" data-group="{{ $gid }}" data-section="{{ $groupSections[$gid] ?? 'manage' }}">
                <button type="button" class="adm-group-head" aria-expanded="{{ $open ? 'true' : 'false' }}">
                  <span class="g-ico">{{ $g['icon'] }}</span>
                  <span class="g-title">{{ $g['title'] }}</span>
                  <span class="g-chev">▾</span>
                </button>
                <div class="adm-sub">
                  @foreach($g['items'] as $it)
                    <a href="{{ $it['href'] }}"
                       class="{{ !empty($it['active']) ? 'active' : '' }} {{ !empty($it['ext']) ? 'ext' : '' }}"
                       @if(!empty($it['ext'])) target="_blank" rel="noopener" @endif>
                      @if(!empty($it['icon']))<span class="item-icon">{{ $it['icon'] }}</span>@else<span class="dot"></span>@endif
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
        @if(\App\Models\Setting::getValue('maintenance_mode','0')==='1')
          <div class="alert alert-error">حالت تعمیر روشن است. از <a href="{{ $u('settings') }}">تنظیمات</a> خاموش کنید.</div>
        @endif
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
        @yield('content')
    </div>
</div>
<script>
(function(){
  var nav = document.getElementById('admNav');
  if(nav){
    var key = 'hdl_admin_menu_open_v8';
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(key) || '{}') || {}; } catch(e) {}

    nav.querySelectorAll('.adm-group').forEach(function(g){
      var id = g.getAttribute('data-group');
      if(saved[id] === true) g.classList.add('is-open');
      if(saved[id] === false && !g.classList.contains('is-active')) g.classList.remove('is-open');

      var head = g.querySelector('.adm-group-head');
      if(!head) return;
      head.addEventListener('click', function(){
        g.classList.toggle('is-open');
        head.setAttribute('aria-expanded', g.classList.contains('is-open') ? 'true' : 'false');
        try {
          var map = JSON.parse(localStorage.getItem(key) || '{}') || {};
          map[id] = g.classList.contains('is-open');
          localStorage.setItem(key, JSON.stringify(map));
        } catch(e) {}
      });
    });
    var search = document.getElementById('admNavSearch');
    var activeSection = localStorage.getItem('hdl_admin_section') || 'commerce';
    function showSection(section){
      activeSection=section;
      nav.querySelectorAll('.adm-nav-tabs button').forEach(function(b){ b.classList.toggle('active',b.dataset.navSection===section); });
      nav.querySelectorAll('.adm-group').forEach(function(g){
        g.style.display='';
      });
      try{ localStorage.setItem('hdl_admin_section',section); }catch(e){}
    }
    var activeGroup=nav.querySelector('.adm-group.is-active');
    if(activeGroup) activeSection=activeGroup.dataset.section || activeSection;
    nav.querySelectorAll('.adm-nav-tabs button').forEach(function(button){
      button.addEventListener('click',function(){ search.value=''; showSection(button.dataset.navSection); });
    });
    showSection(activeSection);
    search?.addEventListener('input', function(){
      var q=(this.value||'').trim().toLocaleLowerCase('fa');
      nav.querySelectorAll('.adm-group').forEach(function(group){
        var matches=0;
        group.querySelectorAll('.adm-sub a').forEach(function(link){
          var show=!q || link.textContent.toLocaleLowerCase('fa').includes(q);
          link.style.display=show ? '' : 'none';
          if(show) matches++;
        });
        var title=group.querySelector('.g-title')?.textContent.toLocaleLowerCase('fa') || '';
        var groupMatch=!q || matches>0 || title.includes(q);
        group.style.display=q ? (groupMatch ? '' : 'none') : '';
        if(q && groupMatch) group.classList.add('is-open');
      });
    });
  }

  var uiScale=parseInt(localStorage.getItem('hdl_admin_font') || '100',10);
  function applyAdminView(){
    uiScale=Math.max(88,Math.min(118,uiScale));
    document.documentElement.style.setProperty('--admin-ui-scale',uiScale/100);
    document.body.classList.toggle('adm-compact',localStorage.getItem('hdl_admin_compact')==='1');
  }
  document.getElementById('admFontDown')?.addEventListener('click',function(){ uiScale-=6; localStorage.setItem('hdl_admin_font',uiScale); applyAdminView(); });
  document.getElementById('admFontUp')?.addEventListener('click',function(){ uiScale+=6; localStorage.setItem('hdl_admin_font',uiScale); applyAdminView(); });
  document.getElementById('admDensity')?.addEventListener('click',function(){ localStorage.setItem('hdl_admin_compact',document.body.classList.contains('adm-compact')?'0':'1'); applyAdminView(); });
  applyAdminView();

  var shell = document.getElementById('adminShell');
  var btn = document.getElementById('admMenuBtn');
  var backdrop = document.getElementById('admBackdrop');
  function closeSide(){ if(shell) shell.classList.remove('side-open'); if(backdrop) backdrop.hidden = true; }
  function openSide(){ if(shell) shell.classList.add('side-open'); if(backdrop) backdrop.hidden = false; }
  if(btn){ btn.addEventListener('click', function(){ shell && shell.classList.contains('side-open') ? closeSide() : openSide(); }); }
  if(backdrop){ backdrop.addEventListener('click', closeSide); }
})();
</script>
@include('partials.media-picker')
</body>
</html>
