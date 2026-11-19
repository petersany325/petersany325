<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل مدیریت')</title>
    <!-- ADMIN-MENU-V15-SITE-SKIN -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v=43">
    <link rel="stylesheet" href="{{ asset('css/admin-nav.css') }}?v=8">
    <link rel="stylesheet" href="{{ asset('css/admin-settings.css') }}?v=1">
    <style>
      .admin-shell{grid-template-columns:248px 1fr}
      .admin-side{background:#070b18 !important;border-left:1px solid rgba(255,255,255,.06);color:#eef2ff;font-family:Vazirmatn,Tahoma,sans-serif}
      .adm-brand{padding:.85rem .8rem .55rem}
      .adm-brand .brand-mark{width:1.7rem;height:1.7rem;border-radius:8px;background:rgba(124,92,255,.18);color:#c4b5fd;font-size:.68rem;font-weight:600}
      .admin-side .adm-brand strong{font-size:.78rem;font-weight:600;letter-spacing:0}
      .admin-side .adm-brand span{font-size:.62rem;font-weight:400;color:#9aa3b8}
      .adm-nav{padding:.35rem .55rem .8rem}
      .adm-nav-search{margin:0 .05rem .55rem;padding:.38rem .55rem;border-radius:999px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.03)}
      .adm-nav-search input{font-size:.72rem;font-weight:400}
      .adm-panels{display:grid!important;grid-template-columns:1fr 1fr;gap:.28rem;margin:0 0 .7rem;padding:.2rem;background:rgba(255,255,255,.03);border-radius:999px}
      .adm-panels.is-single{grid-template-columns:1fr}
      .adm-panels a{display:block;text-align:center;text-decoration:none;border-radius:999px;padding:.38rem .3rem;font-weight:500;font-size:.72rem;color:#b7c0d4;background:transparent;border:0}
      .adm-panels a.active{color:#fff;background:#7c5cff}
      .adm-dash{font-size:.74rem;font-weight:500;border-radius:10px;padding:.48rem .65rem}
      .adm-group{margin-bottom:.28rem;border:0 !important;background:transparent !important;border-radius:10px}
      .adm-group-head{font-size:.74rem !important;font-weight:500 !important;padding:.48rem .62rem !important;border-radius:10px !important;background:transparent !important;color:#d5dcf0 !important;box-shadow:none !important}
      .adm-group.is-open>.adm-group-head,.adm-group.is-active>.adm-group-head{background:rgba(124,92,255,.16) !important;color:#fff !important}
      .adm-group-head .g-chev{color:#9aa3b8}
      .adm-sub a{font-size:.72rem !important;font-weight:400 !important;color:#b7c0d4;padding:.34rem .55rem .34rem .4rem}
      .adm-sub a.active{background:#7c5cff !important;color:#fff !important}
      .adm-group[data-group="shop"] .adm-sub,
      .adm-group[data-group="shop"].is-open>.adm-sub{display:block !important;background:transparent !important;padding:.1rem 0 .3rem !important;border:0 !important}
      .adm-group[data-group="shop"] .adm-sub a{min-height:0 !important;flex-direction:row !important;justify-content:flex-start !important;text-align:right !important;font-size:.72rem !important;font-weight:400 !important;padding:.34rem .55rem !important;background:transparent !important;color:#b7c0d4 !important;border:0 !important;box-shadow:none !important}
      .adm-group[data-group="shop"] .adm-sub a.active{background:#7c5cff !important;color:#fff !important}
      .adm-foot{font-size:.68rem}
      .adm-logout-btn,.adm-logout-block{font-size:.72rem;font-weight:500}
      .adm-nav[data-panel="site"] .adm-group[data-panel="shop"],
      .adm-nav[data-panel="shop"] .adm-group[data-panel="site"]{display:none}
      .adm-nav.is-searching[data-panel] .adm-group[data-panel]{display:block}
      .adm-nav.is-searching[data-panel] .adm-group[data-panel].is-filter-hidden{display:none}
      @media(max-width:900px){.admin-shell{grid-template-columns:1fr}}
    </style>
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
      'open' => $is('serial-sales', 'serial-warranties', 'warranty-companies', 'warranty-register', 'reports') || request()->is('admin/products/*/serials') || (request()->is('admin/products/create') && (request()->boolean('with_serial') || request()->boolean('with_warranty'))),
      'items' => [
        ['label' => 'لیست و ثبت سریال', 'href' => $u('serial-sales'), 'active' => $is('serial-sales') && ! $is('serial-warranties')],
        ['label' => 'ثبت سریع با بارکدخوان', 'href' => $u('serial-sales').'?tab=add', 'active' => $is('serial-sales') && $tab === 'add'],
        ['label' => 'محصول با گارانتی/سریال', 'href' => url('/admin/products/create?with_warranty=1&with_serial=1'), 'active' => request()->is('admin/products/create') && (request()->boolean('with_serial') || request()->boolean('with_warranty'))],
        ['label' => 'شرکت‌های گارانتی', 'href' => $u('warranty-companies'), 'active' => $is('warranty-companies')],
        ['label' => 'لیست گارانتی‌ها', 'href' => $u('serial-warranties'), 'active' => $is('serial-warranties')],
        ['label' => 'ثبت گارانتی سازمانی', 'href' => $u('warranty-register'), 'active' => $is('warranty-register')],
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
        ['label' => 'داشبورد حسابداری', 'href' => $u('accounting'), 'active' => $is('accounting') && ! str_contains($path, 'accounting/'), 'perm' => 'accounting'],
        ['label' => 'فاکتور و پیش‌فاکتور', 'href' => $u('accounting/docs'), 'active' => $is('accounting/docs') && ! str_contains($path, 'docs/create'), 'perm' => 'accounting'],
        ['label' => 'فاکتور فروش جدید', 'href' => $u('accounting/docs/create').'?type=sale', 'active' => str_contains($path, 'docs/create') && request('type','sale')==='sale', 'perm' => 'accounting'],
        ['label' => 'پیش‌فاکتور جدید', 'href' => $u('accounting/docs/create').'?type=proforma', 'active' => request('type')==='proforma', 'perm' => 'accounting'],
        ['label' => 'فاکتور خرید', 'href' => $u('accounting/docs/create').'?type=purchase', 'active' => request('type')==='purchase', 'perm' => 'accounting'],
        ['label' => 'دفتر روزنامه', 'href' => $u('accounting/docs'), 'active' => false, 'perm' => 'accounting'],
        ['label' => 'ثبت سند دستی', 'href' => $u('accounting/docs/create').'?type=voucher', 'active' => request('type')==='voucher', 'perm' => 'accounting'],
        ['label' => 'چک‌ها', 'href' => $u('accounting/checks'), 'active' => $is('accounting/checks'), 'perm' => 'accounting.checks'],
        ['label' => 'اقساط مشتریان', 'href' => $u('accounting/installments'), 'active' => $is('accounting/installments'), 'perm' => 'accounting.installments'],
        ['label' => 'انبارها', 'href' => $u('accounting/warehouses'), 'active' => $is('accounting/warehouses'), 'perm' => 'inventory'],
        ['label' => 'حواله و رسید', 'href' => $u('accounting/stock'), 'active' => $is('accounting/stock'), 'perm' => 'inventory'],
        ['label' => 'بانک‌ها', 'href' => $u('accounting/banks'), 'active' => $is('accounting/banks'), 'perm' => 'accounting'],
        ['label' => 'هزینه‌ها', 'href' => $u('accounting/expenses'), 'active' => $is('accounting/expenses'), 'perm' => 'accounting'],
        ['label' => 'حقوق و دستمزد', 'href' => $u('accounting/payroll'), 'active' => $is('accounting/payroll'), 'perm' => 'accounting.payroll'],
        ['label' => 'کمیسیون فروش', 'href' => $u('accounting/commissions'), 'active' => $is('accounting/commissions'), 'perm' => 'accounting.payroll'],
        ['label' => 'گزارش خرید و فروش', 'href' => $u('accounting/reports/sales'), 'active' => $is('accounting/reports/sales'), 'perm' => 'accounting.reports'],
        ['label' => 'مرکز گزارش‌ها', 'href' => $u('accounting/reports'), 'active' => $is('accounting/reports') && ! str_contains($path, 'reports/'), 'perm' => 'accounting.reports'],
        ['label' => 'تنظیمات حسابداری', 'href' => $u('accounting/settings'), 'active' => $is('accounting/settings'), 'perm' => 'accounting.settings'],
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
      'open' => $is('hero-studio', 'theme-builder', 'theme-templates', 'page-builder', 'mega-menu', 'homepage-settings', 'online-home', 'banner-settings', 'about-page', 'services-page', 'contact-page', 'training-page'),
      'items' => [
        ['label' => 'استودیو هیرو مدرن', 'href' => $u('hero-studio'), 'active' => $is('hero-studio', 'theme-builder', 'banner-settings')],
        ['label' => 'بلوک‌ها و محتوای صفحه اول', 'href' => $u('homepage-settings'), 'active' => $is('homepage-settings', 'online-home')],
        ['label' => 'صفحه درباره ما', 'href' => $u('about-page'), 'active' => $is('about-page')],
        ['label' => 'صفحه خدمات سازمانی', 'href' => $u('services-page'), 'active' => $is('services-page')],
        ['label' => 'صفحه تماس', 'href' => $u('contact-page'), 'active' => $is('contact-page')],
        ['label' => 'آکادمی آموزش', 'href' => $u('training-page'), 'active' => $is('training-page')],
        ['label' => 'نصب / آپدیت قالب', 'href' => $u('theme-templates'), 'active' => $is('theme-templates')],
        ['label' => 'صفحه‌ساز Elementor', 'href' => $u('page-builder'), 'active' => $is('page-builder')],
        ['label' => 'مگامنو', 'href' => $u('mega-menu'), 'active' => $is('mega-menu')],
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
    'open' => $is('products', 'featured-products', 'categories', 'orders', 'media', 'serial-sales', 'serial-warranties', 'warranty-companies', 'warranty-register', 'reports') || request()->is('admin/products/*/serials') || str_contains($path, 'products/display-settings'),
    'items' => [
      ['icon'=>'▤','label'=>'مدیریت کالاها','href'=>$u('products'),'active'=>$is('products') && !$is('featured-products') && !request()->is('admin/products/create') && !request()->is('admin/products/*/serials') && !str_contains($path,'products/display-settings')],
      ['icon'=>'★','label'=>'محصولات ویژه','href'=>$u('featured-products'),'active'=>$is('featured-products')],
      ['icon'=>'＋','label'=>'افزودن کالای جدید','href'=>$u('products/create'),'active'=>request()->is('admin/products/create') && !request()->boolean('with_serial')],
      ['icon'=>'▣','label'=>'کالای سریال‌دار','href'=>url('/admin/products/create?with_warranty=1&with_serial=1'),'active'=>request()->is('admin/products/create') && request()->boolean('with_serial')],
      ['icon'=>'⌁','label'=>'ثبت با بارکدخوان','href'=>$u('serial-sales').'?tab=add','active'=>$is('serial-sales') && $tab==='add'],
      ['icon'=>'≡','label'=>'لیست سریال‌ها','href'=>$u('serial-sales').'?tab=list','active'=>$is('serial-sales') && ($tab==='' || $tab==='list')],
      ['icon'=>'✓','label'=>'گارانتی‌ها','href'=>$u('serial-warranties'),'active'=>$is('serial-warranties')],
      ['icon'=>'◆','label'=>'شرکت‌های گارانتی','href'=>$u('warranty-companies'),'active'=>$is('warranty-companies')],
      ['icon'=>'✎','label'=>'ثبت گارانتی سازمانی','href'=>$u('warranty-register'),'active'=>$is('warranty-register')],
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

  // کارمند: منوی ادمین را بر اساس ACL محدود کن (حسابداری + قالب + وب‌اپ)
  $isStaffOnly = auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff() && ! auth()->user()->isAdmin();
  $staffCan = fn (string $perm): bool => ! $isStaffOnly || auth()->user()->hasStaffPermission($perm);
  $staffCanAccountingChild = function (string $perm) use ($isStaffOnly, $staffCan): bool {
    if (! $isStaffOnly) {
      return true;
    }
    if ($staffCan($perm)) {
      return true;
    }
    // دسترسی پایه حسابداری، زیر‌منوهای عملیاتی را هم باز می‌کند (به‌جز تنظیمات)
    if ($perm !== 'accounting.settings' && str_starts_with($perm, 'accounting.') && $staffCan('accounting')) {
      return true;
    }
    if ($perm === 'inventory' && $staffCan('accounting')) {
      return true;
    }

    return false;
  };
  if ($isStaffOnly) {
    unset($groups['users'], $groups['payment']);

    // حسابداری — منوی موبایل/وب برای کارمند با سوئیچ‌های ادمین
    if ($staffCan('accounting') || $staffCan('accounting.settings') || $staffCan('accounting.reports') || $staffCan('accounting.checks') || $staffCan('accounting.installments') || $staffCan('accounting.payroll') || $staffCan('inventory')) {
      $accItems = [];
      foreach (($groups['accounting']['items'] ?? []) as $it) {
        $need = (string) ($it['perm'] ?? 'accounting');
        if ($staffCanAccountingChild($need)) {
          unset($it['perm']);
          $accItems[] = $it;
        }
      }
      if ($accItems) {
        $groups['accounting']['items'] = $accItems;
        $groups['accounting']['open'] = $is('accounting') || collect($accItems)->contains(fn ($it) => !empty($it['active']));
      } else {
        unset($groups['accounting']);
      }
    } else {
      unset($groups['accounting']);
    }

    // تنظیمات قالب — هر آیتم با سوئیچ جدا
    $themeItems = [];
    if ($staffCan('site.theme_builder') || $staffCan('site.homepage')) {
      $themeItems[] = ['label' => 'استودیو هیرو مدرن', 'href' => $u('hero-studio'), 'active' => $is('hero-studio', 'theme-builder', 'banner-settings')];
    }
    if ($staffCan('site.homepage')) {
      $themeItems[] = ['label' => 'بلوک‌ها و محتوای صفحه اول', 'href' => $u('homepage-settings'), 'active' => $is('homepage-settings', 'online-home')];
    }
    if ($staffCan('site.theme_templates')) {
      $themeItems[] = ['label' => 'نصب / آپدیت قالب', 'href' => $u('theme-templates'), 'active' => $is('theme-templates')];
    }
    if ($staffCan('site.page_builder')) {
      $themeItems[] = ['label' => 'صفحه‌ساز Elementor', 'href' => $u('page-builder'), 'active' => $is('page-builder')];
      $themeItems[] = ['label' => 'صفحه درباره ما', 'href' => $u('about-page'), 'active' => $is('about-page')];
      $themeItems[] = ['label' => 'صفحه خدمات سازمانی', 'href' => $u('services-page'), 'active' => $is('services-page')];
      $themeItems[] = ['label' => 'صفحه تماس', 'href' => $u('contact-page'), 'active' => $is('contact-page')];
      $themeItems[] = ['label' => 'آکادمی آموزش', 'href' => $u('training-page'), 'active' => $is('training-page')];
    }
    if ($staffCan('site.mega_menu')) {
      $themeItems[] = ['label' => 'مگامنو', 'href' => $u('mega-menu'), 'active' => $is('mega-menu')];
    }
    if ($staffCan('site.footer')) {
      $themeItems[] = ['label' => 'فوتر مدرن', 'href' => $u('footer-settings'), 'active' => $is('footer-settings')];
    }
    if ($themeItems) {
      $groups['theme'] = [
        'title' => 'تنظیمات قالب',
        'icon' => '🎨',
        'open' => $is('hero-studio', 'theme-builder', 'theme-templates', 'page-builder', 'mega-menu', 'homepage-settings', 'online-home', 'banner-settings', 'footer-settings', 'about-page', 'services-page', 'contact-page', 'training-page'),
        'items' => $themeItems,
      ];
    } else {
      unset($groups['theme']);
    }

    // وب‌اپ فقط PWA — هیرو/صفحه اول فقط در «تنظیمات قالب» می‌ماند تا منو تکراری نشود.
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
    if ($staffCan('system_tools')) {
      $systemItems[] = ['label' => 'تعمیر و نگهداری', 'href' => $u('system-tools'), 'active' => $is('system-tools')];
      $systemItems[] = ['label' => 'سلامت و بهینه‌سازی سرعت', 'href' => $u('system-tools').'#performance', 'active' => false];
    }
    $systemItems[] = ['label' => 'مشاهده فروشگاه', 'href' => url('/'), 'active' => false, 'ext' => true];
    if (count($systemItems) > 1 || $staffCan('site.shop_settings') || $staffCan('system_tools')) {
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

    // برای ادمین کامل، perm را از آیتم‌ها حذف کن
  }
  if (empty($isStaffOnly)) {
    $groups = [
      'access' => [
        'title' => 'سایت: طراحی، نرم‌افزار، هوش مصنوعی',
        'icon' => '☰',
        'open' => true,
        'items' => [
          ['label' => 'تنظیمات صفحه اول', 'href' => $u('studio-home'), 'active' => $is('studio-home')],
          ['label' => 'طراحی سایت', 'href' => url('/design'), 'active' => false, 'ext' => true],
          ['label' => 'نرم‌افزار', 'href' => url('/computer-accounting'), 'active' => false, 'ext' => true],
          ['label' => 'هوش مصنوعی', 'href' => url('/ai'), 'active' => false, 'ext' => true],
          ['label' => 'تنظیم درگاه پیامک', 'href' => $u('studio-sms'), 'active' => $is('studio-sms')],
          ['label' => 'مگامنو', 'href' => $u('mega-menu'), 'active' => $is('mega-menu')],
          ['label' => 'فوتر', 'href' => $u('footer-settings'), 'active' => $is('footer-settings')],
          ['label' => 'صفحه‌ساز', 'href' => $u('page-builder'), 'active' => $is('page-builder')],
          ['label' => 'تنظیمات ثبت‌نام مشتری', 'href' => $u('auth-settings').'?tab=register', 'active' => $is('auth-settings') && ($tab === '' || $tab === 'register')],
          ['label' => 'فیلدهای ثبت‌نام', 'href' => $u('auth-settings').'?tab=fields', 'active' => $is('auth-settings') && $tab === 'fields'],
          ['label' => 'عودت وجه / شبا', 'href' => $u('auth-settings').'?tab=refund', 'active' => $is('auth-settings') && $tab === 'refund'],
          ['label' => 'قوانین سایت', 'href' => $u('auth-settings').'?tab=terms', 'active' => $is('auth-settings') && $tab === 'terms'],
          ['label' => 'ورود دو مرحله‌ای', 'href' => $u('auth-settings').'?tab=2fa', 'active' => $is('auth-settings') && $tab === '2fa'],
          ['label' => 'پنل پیامک ثبت‌نام', 'href' => $u('auth-settings').'?tab=sms', 'active' => $is('auth-settings') && $tab === 'sms'],
          ['label' => 'اعلان‌های پیامک', 'href' => $u('auth-settings').'?tab=notify', 'active' => $is('auth-settings') && $tab === 'notify'],
          ['label' => 'کارمند و دسترسی‌ها', 'href' => $u('staff'), 'active' => $is('staff') && ! $is('staff/reports') && ! $is('staff/activity')],
          ['label' => 'منوی نمایندگان', 'href' => url('/staff/desk/reps'), 'active' => false],
        ],
      ],
      'shop-home' => [
        'title' => 'فروشگاه',
        'icon' => '🛒',
        'open' => true,
        'items' => [
          ['label' => 'بنر صفحه اول فروشگاه', 'href' => $u('homepage-settings'), 'active' => $is('homepage-settings')],
          ['label' => 'کالاها', 'href' => $u('products'), 'active' => $is('products') && ! request()->is('admin/products/create')],
          ['label' => 'سفارش‌ها', 'href' => $u('orders'), 'active' => $is('orders')],
          ['label' => 'تنظیمات فاکتور', 'href' => $u('invoice-design'), 'active' => $is('invoice-design')],
          ['label' => 'بارکد و سریال', 'href' => $u('serial-sales'), 'active' => $is('serial-sales')],
          ['label' => 'تنظیمات حسابداری', 'href' => $u('accounting/settings'), 'active' => $is('accounting/settings')],
          ['label' => 'تنظیمات فروشگاه', 'href' => $u('settings'), 'active' => $path === 'admin/settings'],
          ['label' => 'مشتریان', 'href' => $u('customers'), 'active' => $is('customers')],
        ],
      ],
    ] + $groups;
    $groupSections['access'] = 'site';
    $groupSections['shop-home'] = 'shop';
  }
  $canHome = empty($isStaffOnly) || $staffCan('site.homepage');
  if (isset($groups['theme']['items'])) {
    $themeKept = [];
    $bannerItem = null;
    foreach ($groups['theme']['items'] as $it) {
      $href = (string) ($it['href'] ?? '');
      if (str_contains($href, 'homepage-settings') || str_contains($href, 'online-home') || str_contains($href, 'banner-settings')) {
        if ($bannerItem === null && str_contains($href, 'homepage-settings')) {
          $bannerItem = $it;
        }
        continue;
      }
      $themeKept[] = $it;
    }
    if ($canHome) {
      array_unshift($themeKept, ['label' => 'تنظیمات صفحه اول سایت', 'href' => $u('studio-home'), 'active' => $is('studio-home')]);
    }
    $groups['theme']['items'] = $themeKept;
    if ($bannerItem && $canHome && ! isset($groups['shop-home'])) {
      $groups['shop-home'] = [
        'title' => 'فروشگاه',
        'icon' => '🛒',
        'open' => true,
        'items' => [$bannerItem],
      ];
      $groupSections['shop-home'] = 'shop';
    }
  }
  if (isset($groups['users']['items'])) {
    $authKept = [];
    $userRest = [];
    foreach ($groups['users']['items'] as $it) {
      $href = (string) ($it['href'] ?? '');
      $isAuth = str_contains($href, 'auth-settings') || str_contains($href, '/login') || str_contains($href, '/register') || str_contains($href, '/forgot-password') || str_contains($href, '/terms');
      if ($isAuth) {
        $authKept[] = $it;
      } else {
        $userRest[] = $it;
      }
    }
    $groups['users']['items'] = $userRest;
    if ($userRest === []) {
      unset($groups['users']);
    }
    if ($authKept) {
      if (! isset($groups['access'])) {
        $groups = ['access' => ['title' => 'عضویت سایت', 'icon' => '☰', 'open' => $is('auth-settings'), 'items' => []]] + $groups;
        $groupSections['access'] = 'site';
      }
      $have = array_map(fn ($it) => (string) ($it['href'] ?? ''), $groups['access']['items']);
      foreach ($authKept as $it) {
        if (! in_array((string) ($it['href'] ?? ''), $have, true)) {
          $groups['access']['items'][] = $it;
        }
      }
    }
  }
  if (isset($groups['system']['items'])) {
    $systemStay = [];
    $shopSettings = [];
    foreach ($groups['system']['items'] as $it) {
      $href = (string) ($it['href'] ?? '');
      $rel = ltrim((string) preg_replace('#^.*/admin/#', '', (string) (parse_url($href, PHP_URL_PATH) ?? '')), '/');
      if ($rel === 'settings' || str_starts_with($rel, 'settings/')) {
        $shopSettings[] = $it;
      } else {
        $systemStay[] = $it;
      }
    }
    $systemInside = array_values(array_filter($systemStay, fn ($it) => empty($it['ext'])));
    if ($systemInside === []) {
      unset($groups['system']);
    } else {
      $groups['system']['items'] = $systemStay;
    }
    if ($shopSettings) {
      if (! isset($groups['shop-home'])) {
        $groups['shop-home'] = ['title' => 'فروشگاه', 'icon' => '🛒', 'open' => true, 'items' => []];
        $groupSections['shop-home'] = 'shop';
      }
      $haveShop = array_map(fn ($it) => (string) ($it['href'] ?? ''), $groups['shop-home']['items']);
      foreach ($shopSettings as $it) {
        if (! in_array((string) ($it['href'] ?? ''), $haveShop, true)) {
          $groups['shop-home']['items'][] = $it;
        }
      }
    }
  }
  $shopPrefixes = ['products', 'categories', 'orders', 'media', 'featured-products', 'serial-sales', 'serial-warranties', 'warranty-companies', 'warranty-register', 'shipping-carriers', 'shipping-post', 'shipping-tipax', 'invoice-design', 'homepage-settings', 'online-home', 'banner-settings', 'payment', 'customers', 'wallet-settings', 'accounting', 'reports', 'settings'];
  $adminRel = preg_replace('#^admin/?#', '', $path) ?? '';
  $panel = 'site';
  foreach ($shopPrefixes as $pre) {
    if ($adminRel === $pre || str_starts_with($adminRel, $pre.'/')) {
      $panel = 'shop';
      break;
    }
  }
  $shopGroupIds = ['shop', 'shipping', 'accounting', 'payment', 'users', 'shop-home', 'sales'];
  $staffAllows = function (string $href) use ($isStaffOnly, $staffCan): bool {
    if (empty($isStaffOnly)) {
      return true;
    }
    $pathOnly = (string) (parse_url($href, PHP_URL_PATH) ?? '');
    if (! str_contains($pathOnly, '/admin/')) {
      return true;
    }
    $rel = ltrim((string) preg_replace('#^.*/admin/#', '', $pathOnly), '/');
    if ($rel === '') {
      return false;
    }
    foreach (['staff', 'plugins', 'customers', 'auth-settings', 'wallet-settings', 'payment', 'developer-studio'] as $locked) {
      if ($rel === $locked || str_starts_with($rel, $locked.'/')) {
        return false;
      }
    }
    if ($rel === 'tickets/settings' || str_starts_with($rel, 'tickets/settings/')) {
      return false;
    }
    $need = match (true) {
      str_starts_with($rel, 'system-tools') => 'system_tools',
      str_starts_with($rel, 'mega-menu') => 'site.mega_menu',
      str_starts_with($rel, 'theme-templates') => 'site.theme_templates',
      str_starts_with($rel, 'theme-builder') => 'site.theme_builder',
      str_starts_with($rel, 'page-builder'), str_starts_with($rel, 'about-page'), str_starts_with($rel, 'services-page'), str_starts_with($rel, 'contact-page'), str_starts_with($rel, 'training-page') => 'site.page_builder',
      str_starts_with($rel, 'homepage-settings'), str_starts_with($rel, 'studio-home'), str_starts_with($rel, 'studio-sms'), str_starts_with($rel, 'online-home'), str_starts_with($rel, 'banner-settings') => 'site.homepage',
      str_starts_with($rel, 'footer-settings') => 'site.footer',
      str_starts_with($rel, 'web-app') => 'site.webapp',
      $rel === 'settings' || str_starts_with($rel, 'settings/') => 'site.shop_settings',
      str_contains($rel, '/serials') => 'serials',
      str_contains($rel, '/edit') && str_starts_with($rel, 'products') => 'products.edit',
      $rel === 'products/create' || str_starts_with($rel, 'products/create/') => 'products.create',
      str_starts_with($rel, 'products') => 'products.view',
      str_starts_with($rel, 'orders') => 'orders',
      str_starts_with($rel, 'serial'), str_starts_with($rel, 'warranty') => 'serials',
      str_starts_with($rel, 'tickets') => 'support',
      str_starts_with($rel, 'media') => 'media',
      str_starts_with($rel, 'categories') => 'products.edit',
      str_starts_with($rel, 'reports') => 'reports',
      str_starts_with($rel, 'shipping'), str_starts_with($rel, 'invoice') => 'full_site',
      default => 'full_site',
    };
    if ($staffCan($need)) {
      return true;
    }
    if ($need === 'orders' && $staffCan('sales')) {
      return true;
    }
    if ($need === 'serials' && $staffCan('sales')) {
      return true;
    }
    if ($need === 'site.page_builder' && $staffCan('site.homepage')) {
      return true;
    }

    return false;
  };
  $hrefPanel = function (string $href) use ($shopPrefixes): string {
    $rel = ltrim((string) preg_replace('#^.*/admin/#', '', (string) (parse_url($href, PHP_URL_PATH) ?? '')), '/');
    foreach ($shopPrefixes as $pre) {
      if ($rel === $pre || str_starts_with($rel, $pre.'/')) {
        return 'shop';
      }
    }

    return 'site';
  };
  $firstPanelHref = function (string $want) use ($groups, $staffAllows, $hrefPanel): ?string {
    foreach ($groups as $g) {
      foreach ($g['items'] ?? [] as $it) {
        $href = (string) ($it['href'] ?? '');
        if ($href === '' || ! empty($it['ext']) || $hrefPanel($href) !== $want || ! $staffAllows($href)) {
          continue;
        }

        return $href;
      }
    }

    return null;
  };
  $siteTab = $firstPanelHref('site');
  $shopTab = $firstPanelHref('shop');

  // پاک‌سازی کلید perm از منوی ادمین
  foreach ($groups as $gid => &$g) {
    if (! empty($g['items'])) {
      $g['items'] = array_map(function ($it) {
        unset($it['perm']);
        return $it;
      }, $g['items']);
    }
  }
  unset($g);
@endphp
<div class="admin-topbar">
  <button type="button" class="adm-menu-btn" id="admMenuBtn" aria-label="منو">☰ منو</button>
  <a href="{{ url('/') }}" class="adm-view-site" style="margin-inline-start:.6rem;color:#e2e8f0;font-weight:750;text-decoration:none;white-space:nowrap">مشاهده سایت ←</a>
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
            <span class="brand-mark">{{ $panel === 'shop' ? 'SH' : 'ST' }}</span>
            <div>
              <strong>{{ $panel === 'shop' ? 'ادمین فروشگاه' : 'ادمین سایت' }}</strong>
              <span>{{ $panel === 'shop' ? 'کالا، سفارش، فاکتور' : 'طراحی، نرم‌افزار، هوش مصنوعی' }}</span>
            </div>
        </div>

        <nav class="adm-nav" id="admNav" data-panel="{{ $panel }}">
            <div class="adm-nav-search">
              <span>⌕</span>
              <input type="search" id="admNavSearch" placeholder="جستجو در منو…" autocomplete="off">
            </div>
            <div class="adm-panels {{ ($siteTab && $shopTab) ? '' : 'is-single' }}" role="tablist" aria-label="انتخاب ادمین">
              @if($siteTab)
              <a href="{{ $siteTab }}" class="{{ $panel === 'site' ? 'active' : '' }}" data-panel-btn="site">سایت</a>
              @endif
              @if($shopTab)
              <a href="{{ $shopTab }}" class="{{ $panel === 'shop' ? 'active' : '' }}" data-panel-btn="shop">فروشگاه</a>
              @endif
            </div>
            @if(!empty($isStaffOnly))
              <a class="adm-dash" href="{{ url('/staff') }}">
                <span>🏠</span> بازگشت به پنل کارمند
              </a>
            @elseif($panel === 'shop' && $shopTab)
            <a class="adm-dash" href="{{ $shopTab }}">
              <span>🏠</span> ورود فروشگاه
            </a>
            @elseif($siteTab)
            <a class="adm-dash" href="{{ $siteTab }}">
              <span>🏠</span> تنظیمات سایت
            </a>
            @endif

            @foreach($groups as $gid => $g)
              @php
                $hasActive = collect($g['items'])->contains(fn ($it) => !empty($it['active']));
                $open = $g['open'] || $hasActive;
              @endphp
              <div class="adm-group {{ $open ? 'is-open' : '' }} {{ $hasActive ? 'is-active' : '' }}" data-group="{{ $gid }}" data-panel="{{ in_array($gid, $shopGroupIds, true) ? 'shop' : 'site' }}" data-section="{{ $groupSections[$gid] ?? 'manage' }}">
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
    function applyPanel(q){
      q=(q||'').trim().toLocaleLowerCase('fa');
      nav.classList.toggle('is-searching', q.length>0);
      nav.querySelectorAll('.adm-group').forEach(function(group){
        var matches=0;
        group.querySelectorAll('.adm-sub a').forEach(function(link){
          var show=!q || link.textContent.toLocaleLowerCase('fa').includes(q);
          link.classList.toggle('is-filter-hidden', !show);
          link.style.display=show ? '' : 'none';
          if(show) matches++;
        });
        var title=group.querySelector('.g-title')?.textContent.toLocaleLowerCase('fa') || '';
        var groupMatch=!q || matches>0 || title.includes(q);
        group.classList.toggle('is-filter-hidden', q.length>0 && !groupMatch);
        group.style.display='';
        if(q && groupMatch) group.classList.add('is-open');
      });
    }
    applyPanel('');
    search?.addEventListener('input', function(){ applyPanel(this.value||''); });
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
