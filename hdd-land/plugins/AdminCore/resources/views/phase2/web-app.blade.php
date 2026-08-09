@extends('layouts.admin')
@section('title','وب‌سرویس / وب‌اپ')
@section('content')
@php
  $s = $s ?? [];
  $f = fn ($k, $d = null) => old($k, $s[$k] ?? $d);
  $on = fn ($k) => ! empty(old($k, $s[$k] ?? false));
@endphp

<div class="vb-page">
  <div class="vb-page-head">
    <div>
      <h1>وب‌سرویس / وب‌اپ (کامل)</h1>
      <p>خانه، فروشگاه، محصول، سبد، حساب، جستجو، نصب PWA و ظاهر — همه از این صفحه.</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <a class="btn" href="{{ url('/app') }}" target="_blank" rel="noopener">خانه اپ</a>
      <a class="btn" href="{{ url('/app/shop') }}" target="_blank" rel="noopener">فروشگاه</a>
      <a class="btn" href="{{ url('/app/cart') }}" target="_blank" rel="noopener">سبد</a>
    </div>
  </div>

  <form method="post" action="{{ url('/admin/web-app') }}">@csrf

    <div class="vb-block">
      <div class="vb-block-head"><span>وضعیت و هویت</span><span class="tag">Core</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">فعال بودن وب‌اپ</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="enabled" value="1" @checked($on('enabled'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">نام اپ</span></div><div class="vb-ctrl"><input type="text" name="app_name" value="{{ $f('app_name') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">نام کوتاه</span></div><div class="vb-ctrl"><input type="text" name="short_name" value="{{ $f('short_name') }}"></div></div>
        <div class="vb-opt vb-opt-stack"><div><span class="vb-title">توضیح</span></div><div class="vb-ctrl"><textarea name="description" rows="2">{{ $f('description') }}</textarea></div></div>
        <div class="vb-opt"><div><span class="vb-title">start_url</span></div><div class="vb-ctrl"><input type="text" name="start_url" value="{{ $f('start_url','/app') }}" dir="ltr"></div></div>
        <div class="vb-opt"><div><span class="vb-title">حالت نمایش</span></div><div class="vb-ctrl">
          <select name="display">
            @foreach(['standalone','fullscreen','minimal-ui','browser'] as $d)
              <option value="{{ $d }}" @selected($f('display')===$d)>{{ $d }}</option>
            @endforeach
          </select>
        </div></div>
        <div class="vb-opt"><div><span class="vb-title">جهت</span></div><div class="vb-ctrl">
          <select name="orientation">
            @foreach(['portrait-primary','portrait','landscape','any'] as $d)
              <option value="{{ $d }}" @selected($f('orientation')===$d)>{{ $d }}</option>
            @endforeach
          </select>
        </div></div>
      </div>
    </div>

    <div class="vb-block">
      <div class="vb-block-head"><span>رنگ و ظاهر</span><span class="tag">Theme</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">رنگ برند</span></div><div class="vb-ctrl"><input type="color" name="theme_color" value="{{ $f('theme_color','#e23d12') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">پس‌زمینه صفحه</span></div><div class="vb-ctrl"><input type="color" name="background_color" value="{{ $f('background_color','#f4f6f9') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">رنگ سطح کارت‌ها</span></div><div class="vb-ctrl"><input type="color" name="surface_color" value="{{ $f('surface_color','#ffffff') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">رنگ متن</span></div><div class="vb-ctrl"><input type="color" name="text_color" value="{{ $f('text_color','#1a1d23') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">انیمیشن ورود</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="animations" value="1" @checked($on('animations'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">کارت‌های فشرده</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="compact_cards" value="1" @checked($on('compact_cards'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">آیکون ۱۹۲ URL</span></div><div class="vb-ctrl"><input type="text" name="icon_192" value="{{ $f('icon_192') }}" dir="ltr"></div></div>
        <div class="vb-opt"><div><span class="vb-title">آیکون ۵۱۲ URL</span></div><div class="vb-ctrl"><input type="text" name="icon_512" value="{{ $f('icon_512') }}" dir="ltr"></div></div>
      </div>
    </div>

    <div class="vb-block">
      <div class="vb-block-head"><span>صفحه خانه</span><span class="tag">Home</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">هیرو</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="hero_enabled" value="1" @checked($on('hero_enabled'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">عنوان هیرو</span></div><div class="vb-ctrl"><input type="text" name="hero_title" value="{{ $f('hero_title') }}"></div></div>
        <div class="vb-opt vb-opt-stack"><div><span class="vb-title">متن هیرو</span></div><div class="vb-ctrl"><textarea name="hero_text" rows="2">{{ $f('hero_text') }}</textarea></div></div>
        <div class="vb-opt"><div><span class="vb-title">دکمه هیرو</span></div><div class="vb-ctrl"><input type="text" name="hero_cta_label" value="{{ $f('hero_cta_label') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">لینک دکمه</span></div><div class="vb-ctrl"><input type="text" name="hero_cta_url" value="{{ $f('hero_cta_url','/app/shop') }}" dir="ltr"></div></div>
        <div class="vb-opt"><div><span class="vb-title">جستجو</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="show_search" value="1" @checked($on('show_search'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">دسته‌ها</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="show_categories" value="1" @checked($on('show_categories'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">محصولات ویژه</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="show_featured" value="1" @checked($on('show_featured'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">عنوان محصولات ویژه</span></div><div class="vb-ctrl"><input type="text" name="featured_title" value="{{ $f('featured_title','محصولات ویژه') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">تعداد محصولات ویژه</span></div><div class="vb-ctrl"><input type="number" name="featured_limit" min="2" max="12" value="{{ $f('featured_limit',4) }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">لینک‌های سریع</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="show_quick_links" value="1" @checked($on('show_quick_links'))><span class="vb-slider"></span></label></div></div>
        @foreach([1,2,3] as $i)
          <div class="vb-opt"><div><span class="vb-title">لینک سریع {{ $i }} — عنوان</span></div><div class="vb-ctrl"><input type="text" name="quick_link_{{ $i }}_label" value="{{ $f('quick_link_'.$i.'_label') }}"></div></div>
          <div class="vb-opt"><div><span class="vb-title">لینک سریع {{ $i }} — URL</span></div><div class="vb-ctrl"><input type="text" name="quick_link_{{ $i }}_url" value="{{ $f('quick_link_'.$i.'_url') }}" dir="ltr"></div></div>
        @endforeach
        <div class="vb-opt"><div><span class="vb-title">تعداد دسته</span></div><div class="vb-ctrl"><input type="number" name="categories_limit" min="0" max="24" value="{{ $f('categories_limit',10) }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">تعداد محصول خانه (قدیمی)</span></div><div class="vb-ctrl"><input type="number" name="products_limit" min="4" max="48" value="{{ $f('products_limit',16) }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">تعداد در فروشگاه</span></div><div class="vb-ctrl"><input type="number" name="shop_per_page" min="8" max="60" value="{{ $f('shop_per_page',24) }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">عنوان فروشگاه</span></div><div class="vb-ctrl"><input type="text" name="shop_title" value="{{ $f('shop_title') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">متن خالی بودن</span></div><div class="vb-ctrl"><input type="text" name="empty_products_text" value="{{ $f('empty_products_text') }}"></div></div>
      </div>
    </div>

    <div class="vb-block">
      <div class="vb-block-head"><span>نوار پایین</span><span class="tag">Nav</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">نوار پایین داخل وب‌اپ</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="mobile_bottom_nav" value="1" @checked($on('mobile_bottom_nav'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">نوار پایین روی فروشگاه دسکتاپ/موبایل سایت</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="storefront_bottom_nav" value="1" @checked($on('storefront_bottom_nav'))><span class="vb-slider"></span></label></div></div>
        @foreach(['home'=>'خانه','shop'=>'فروشگاه','cart'=>'سبد','account'=>'حساب'] as $k=>$lab)
          <div class="vb-opt">
            <div><span class="vb-title">{{ $lab }}</span></div>
            <div class="vb-ctrl" style="display:flex;gap:.5rem;align-items:center">
              <label class="vb-switch"><input type="checkbox" name="show_nav_{{ $k }}" value="1" @checked($on('show_nav_'.$k))><span class="vb-slider"></span></label>
              <input type="text" name="nav_{{ $k }}_label" value="{{ $f('nav_'.$k.'_label',$lab) }}" style="width:7rem">
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="vb-block">
      <div class="vb-block-head"><span>محصول / سبد / حساب</span><span class="tag">Commerce</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">افزودن به سبد</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="product_show_add_cart" value="1" @checked($on('product_show_add_cart'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">خرید سریع</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="product_show_buy_now" value="1" @checked($on('product_show_buy_now'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">دکمه پرداخت در سبد</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="cart_show_checkout" value="1" @checked($on('cart_show_checkout'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">سفارش‌ها در حساب</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="account_show_orders" value="1" @checked($on('account_show_orders'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">کیف پول</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="account_show_wallet" value="1" @checked($on('account_show_wallet'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">پروفایل</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="account_show_profile" value="1" @checked($on('account_show_profile'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">تیکت</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="account_show_tickets" value="1" @checked($on('account_show_tickets'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">پیگیری سفارش</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="account_show_track" value="1" @checked($on('account_show_track'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">لینک نسخه کامل سایت</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="account_show_full_site" value="1" @checked($on('account_show_full_site'))><span class="vb-slider"></span></label></div></div>
      </div>
    </div>

    <div class="vb-block">
      <div class="vb-block-head"><span>نصب و آفلاین</span><span class="tag">PWA</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">بنر نصب</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="show_install_banner" value="1" @checked($on('show_install_banner'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">نصب هوشمند</span><span class="vb-desc">تشخیص نصب‌بودن، پلتفرم، و زمان مناسب نمایش</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="smart_install" value="1" @checked($on('smart_install'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">مخفی کردن بنر اگر نصب شده</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="hide_install_when_installed" value="1" @checked($on('hide_install_when_installed'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">فقط روی موبایل نشان بده</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="install_only_mobile" value="1" @checked($on('install_only_mobile'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">متن بنر</span></div><div class="vb-ctrl"><input type="text" name="install_banner_text" value="{{ $f('install_banner_text') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">متن «آماده نصب»</span></div><div class="vb-ctrl"><input type="text" name="install_ready_text" value="{{ $f('install_ready_text') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">متن «نصب‌شده»</span></div><div class="vb-ctrl"><input type="text" name="installed_badge_text" value="{{ $f('installed_badge_text') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">راهنمای اندروید</span></div><div class="vb-ctrl"><input type="text" name="install_help_android" value="{{ $f('install_help_android') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">راهنمای iOS</span></div><div class="vb-ctrl"><input type="text" name="install_help_ios" value="{{ $f('install_help_ios') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">کش آفلاین</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="offline_cache" value="1" @checked($on('offline_cache'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt vb-opt-stack"><div><span class="vb-title">پیام آفلاین</span></div><div class="vb-ctrl"><textarea name="offline_message" rows="2">{{ $f('offline_message') }}</textarea></div></div>
        <div class="vb-opt"><div><span class="vb-title">هدایت خودکار موبایل به /app</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="force_app_on_mobile" value="1" @checked($on('force_app_on_mobile'))><span class="vb-slider"></span></label></div></div>
      </div>
    </div>

    <div class="vb-block">
      <div class="vb-block-head"><span>منوی کشویی اختصاصی وب‌اپ</span><span class="tag">Drawer</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">فعال بودن منوی کشویی</span><span class="vb-desc">دکمه ☰ کنار برند — منوی مخصوص موبایل/وب‌اپ</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="drawer_menu_enabled" value="1" @checked($on('drawer_menu_enabled'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">جهت باز شدن</span></div><div class="vb-ctrl">
          <select name="drawer_side">
            <option value="right" @selected($f('drawer_side','right')==='right')>از سمت راست</option>
            <option value="left" @selected($f('drawer_side')==='left')>از سمت چپ</option>
          </select>
        </div></div>
        <div class="vb-opt"><div><span class="vb-title">عنوان منو</span></div><div class="vb-ctrl"><input type="text" name="drawer_title" value="{{ $f('drawer_title','منوی وب‌اپ') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">زیرعنوان</span></div><div class="vb-ctrl"><input type="text" name="drawer_subtitle" value="{{ $f('drawer_subtitle','دسترسی سریع فروشگاه موبایل') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">نمایش برند در سر منو</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="drawer_show_brand" value="1" @checked($on('drawer_show_brand'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">لینک نسخه کامل سایت در پایین کشو</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="drawer_show_full_site" value="1" @checked($on('drawer_show_full_site'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">متن لینک سایت کامل</span></div><div class="vb-ctrl"><input type="text" name="drawer_full_site_label" value="{{ $f('drawer_full_site_label','نسخه کامل سایت') }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">آدرس لینک سایت کامل</span></div><div class="vb-ctrl"><input type="text" name="drawer_full_site_url" value="{{ $f('drawer_full_site_url','/') }}" dir="ltr"></div></div>

        <p class="vb-desc" style="margin:.85rem 0 .35rem;color:#64748b;font-weight:800">آیتم‌های منو (فعال / برچسب / آدرس / آیکون)</p>
        @foreach([
          'home' => 'خانه',
          'shop' => 'فروشگاه',
          'cart' => 'سبد خرید',
          'account' => 'حساب من',
          'track' => 'پیگیری سفارش',
          'warranty' => 'گارانتی',
          'support' => 'پشتیبانی',
          'contact' => 'تماس با ما',
        ] as $key => $lab)
          <div class="vb-opt">
            <div><span class="vb-title">{{ $lab }}</span></div>
            <div class="vb-ctrl" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;justify-content:flex-end">
              <label class="vb-switch"><input type="checkbox" name="drawer_item_{{ $key }}" value="1" @checked($on('drawer_item_'.$key))><span class="vb-slider"></span></label>
              <input type="text" name="drawer_{{ $key }}_label" value="{{ $f('drawer_'.$key.'_label',$lab) }}" style="width:7rem" placeholder="برچسب">
              <input type="text" name="drawer_{{ $key }}_url" value="{{ $f('drawer_'.$key.'_url') }}" style="width:9rem" dir="ltr" placeholder="/url">
              <input type="text" name="drawer_{{ $key }}_icon" value="{{ $f('drawer_'.$key.'_icon') }}" style="width:3rem;text-align:center" placeholder="⌂">
            </div>
          </div>
        @endforeach

        <div class="vb-opt vb-opt-stack">
          <div><span class="vb-title">لینک‌های اضافه</span><span class="vb-desc">هر خط: برچسب|آدرس|آیکون — مثال: درباره ما|/about|ℹ</span></div>
          <div class="vb-ctrl"><textarea name="drawer_extra_links" rows="3" dir="rtl" placeholder="درباره ما|/about|ℹ">{{ $f('drawer_extra_links') }}</textarea></div>
        </div>
      </div>
    </div>

    <div class="vb-block">
      <div class="vb-block-head"><span>همگام‌سازی خودکار با سایت</span><span class="tag">Sync</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">منوی مگامنو → نوار چیپ افقی</span><span class="vb-desc">اختیاری؛ منوی اصلی وب‌اپ همان کشوی اختصاصی است</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="sync_menu_from_site" value="1" @checked($on('sync_menu_from_site'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">نمایش نوار چیپ افقی (سبک سایت دسکتاپ)</span><span class="vb-desc">معمولاً خاموش بماند</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="show_site_menu" value="1" @checked($on('show_site_menu'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">حداکثر آیتم نوار چیپ</span></div><div class="vb-ctrl"><input type="number" name="menu_limit" min="4" max="24" value="{{ $f('menu_limit',12) }}"></div></div>
        <div class="vb-opt"><div><span class="vb-title">لینک سریع از قالب (top menu)</span><span class="vb-desc">از ThemeBuilder؛ در غیر این صورت از مگامنو</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="sync_quick_links_from_theme" value="1" @checked($on('sync_quick_links_from_theme'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">نام/رنگ برند از تنظیمات سایت</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="sync_brand_from_site" value="1" @checked($on('sync_brand_from_site'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">نمایش فوتر در وب‌اپ</span><span class="vb-desc">فوتر فشرده زیر محتوا (بالای نوار پایین)</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="show_footer" value="1" @checked($on('show_footer'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">همگام‌سازی فوتر از تنظیمات فوتر سایت</span><span class="vb-desc">از «تنظیمات فوتر مدرن»؛ لینک‌ها برای /app نگاشت می‌شوند</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="sync_footer_from_site" value="1" @checked($on('sync_footer_from_site'))><span class="vb-slider"></span></label></div></div>
        <p class="vb-desc" style="margin:.5rem 0 0;color:#64748b">منوی اصلی وب‌اپ از بخش «منوی کشویی اختصاصی» کنترل می‌شود. نوار چیپ افقی فقط در صورت نیاز روشن شود.</p>
      </div>
    </div>

    <div class="vb-actions" style="margin-top:1rem">
      <button class="btn btn-primary" type="submit">ذخیره همه تنظیمات وب‌سرویس</button>
    </div>
  </form>
</div>
@endsection
