@extends('layouts.admin')
@section('title','تنظیمات نمایش محصول')
@section('content')
@php
  $s = $s ?? [];
  $f = fn($k,$d=null) => old($k, $s[$k] ?? $d);
  $on = fn($k) => !empty(old($k, $s[$k] ?? false));
@endphp
<div class="vb-page">
  <div class="row" style="justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.6rem">
    <div>
      <h1 style="margin:0">تنظیمات نمایش صفحه محصول</h1>
      <p class="muted">کنترل برند، دکمه‌ها، کرکره سریال، مشخصات، HTML توضیحات و چیدمان فشرده — فروشگاه و وب‌اپ</p>
    </div>
    <div class="row" style="gap:.4rem">
      <a class="btn btn-outline" href="{{ url('/admin/products') }}">محصولات</a>
      <a class="btn btn-outline" href="{{ url('/products') }}" target="_blank" rel="noopener">پیش‌نمایش فروشگاه</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <form method="post" action="{{ url('/admin/products/display-settings') }}">@csrf
    <div class="vb-block">
      <div class="vb-block-head"><span>برند و هدر محصول</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">نمایش برند روی PDP</span><span class="vb-desc">خط برند بالای عنوان</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_brand" value="1" @checked($on('pdp_show_brand'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">متن برند</span><span class="vb-desc">خالی = نام فروشگاه (الان: {{ $brandPreview }})</span></div><div class="vb-ctrl"><input type="text" name="pdp_brand_label" value="{{ $f('pdp_brand_label','') }}" placeholder="مثلاً سرزمین هارد"></div></div>
        <div class="vb-opt"><div><span class="vb-title">نمایش دسته</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_category" value="1" @checked($on('pdp_show_category'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">توضیح کوتاه</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_lead" value="1" @checked($on('pdp_show_lead'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">چیپ مشخصات (ظرفیت/رابط…)</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_chips" value="1" @checked($on('pdp_show_chips'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">نشان‌های گارانتی/موجودی</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_badges" value="1" @checked($on('pdp_show_badges'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">عدد موجودی</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_stock_count" value="1" @checked($on('pdp_show_stock_count'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">سریال نمایشی</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_display_serial" value="1" @checked($on('pdp_show_display_serial'))><span class="vb-slider"></span></label></div></div>
      </div>
    </div>

    <div class="vb-block" style="margin-top:1rem">
      <div class="vb-block-head"><span>دکمه‌ها و سریال</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">افزودن به سبد</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_add_cart" value="1" @checked($on('pdp_show_add_cart'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">خرید سریع</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_buy_now" value="1" @checked($on('pdp_show_buy_now'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">پیش‌خرید (ناموجود)</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_preorder" value="1" @checked($on('pdp_show_preorder'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">لینک استعلام گارانتی</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_warranty_link" value="1" @checked($on('pdp_show_warranty_link'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">کرکره گرافیکی سریال</span><span class="vb-desc">اگر خاموش شود، select معمولی نشان داده می‌شود</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_custom_serial_dropdown" value="1" @checked($on('pdp_custom_serial_dropdown'))><span class="vb-slider"></span></label></div></div>
      </div>
    </div>

    <div class="vb-block" style="margin-top:1rem">
      <div class="vb-block-head"><span>چیدمان و تصویر</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">چیدمان فشرده (مانیتور)</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_compact_layout" value="1" @checked($on('pdp_compact_layout'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">نحوه نمایش عکس</span></div><div class="vb-ctrl">
          <select name="pdp_image_fit">
            <option value="contain" @selected($f('pdp_image_fit','contain')==='contain')">کامل داخل قاب (contain)</option>
            <option value="cover" @selected($f('pdp_image_fit','contain')==='cover')">پر کردن قاب (cover)</option>
          </select>
        </div></div>
        <div class="vb-opt"><div><span class="vb-title">عرض ستون عکس (px)</span></div><div class="vb-ctrl"><input type="number" name="pdp_media_width" min="180" max="420" value="{{ $f('pdp_media_width',280) }}"></div></div>
      </div>
    </div>

    <div class="vb-block" style="margin-top:1rem">
      <div class="vb-block-head"><span>مشخصات و توضیحات</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">نمایش مشخصات فنی</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_specs" value="1" @checked($on('pdp_show_specs'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">نمایش توضیحات بیشتر</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_description" value="1" @checked($on('pdp_show_description'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">رندر HTML در توضیحات</span><span class="vb-desc">تگ‌های امن مثل p/ul/strong نمایش داده شوند</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_render_html_description" value="1" @checked($on('pdp_render_html_description'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">محصولات مرتبط</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="pdp_show_related" value="1" @checked($on('pdp_show_related'))><span class="vb-slider"></span></label></div></div>
      </div>
    </div>

    <div class="vb-block" style="margin-top:1rem">
      <div class="vb-block-head"><span>وب‌اپ محصول</span></div>
      <div class="vb-block-body">
        <div class="vb-opt"><div><span class="vb-title">برند روی وب‌اپ</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="wa_show_brand" value="1" @checked($on('wa_show_brand'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">چیپ مشخصات وب‌اپ</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="wa_show_specs_chips" value="1" @checked($on('wa_show_specs_chips'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">کرکره گرافیکی سریال (وب‌اپ)</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="wa_custom_serial_dropdown" value="1" @checked($on('wa_custom_serial_dropdown'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-opt"><div><span class="vb-title">HTML توضیحات در وب‌اپ</span><span class="vb-desc">پیش‌فرض خاموش؛ متن کوتاه بدون تگ</span></div><div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="wa_render_html_description" value="1" @checked($on('wa_render_html_description'))><span class="vb-slider"></span></label></div></div>
        <div class="vb-actions"><button class="btn btn-primary" type="submit">ذخیره تنظیمات</button></div>
      </div>
    </div>
  </form>
</div>
@endsection
