@extends('layouts.admin')
@section('title','صفحه اول شرکتی')
@section('content')
@php($f=fn($k)=>old($k,$s[$k]??''))
<style>
.ch-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.ch-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1rem 1.1rem}
.ch-card h2{margin:0 0 .35rem;font-size:1rem}
.ch-card label{display:grid;gap:.3rem;margin:.7rem 0;font-weight:700;font-size:.86rem}
.ch-card input,.ch-card textarea{width:100%;font-weight:400}
.ch-hint{margin:.2rem 0 .6rem;color:#64748b;font-size:.78rem;font-weight:500}
.ch-full{grid-column:1/-1}
@media(max-width:900px){.ch-grid{grid-template-columns:1fr}}
</style>
<div class="vb-page">
  <div class="vb-page-head">
    <div>
      <h1>صفحه اول شرکتی</h1>
      <p>تنظیم بنر، بخش‌های صفحه اول و فوتر قالب جدید</p>
    </div>
    <div class="vb-actions">
      <a class="btn btn-outline" target="_blank" href="{{ url('/') }}">مشاهده سایت</a>
      <a class="btn btn-outline" href="{{ url('/admin/footer-settings') }}">فوتر مدرن (قدیمی)</a>
    </div>
  </div>

  @if(session('success'))
    <div class="vb-note" style="margin-bottom:1rem">{{ session('success') }}</div>
  @endif

  <nav class="vb-tabs">
    <a class="{{ $tab==='banner'?'on':'' }}" href="{{ url('/admin/corporate-home?tab=banner') }}">بنر و هدر</a>
    <a class="{{ $tab==='home'?'on':'' }}" href="{{ url('/admin/corporate-home?tab=home') }}">صفحه اول</a>
    <a class="{{ $tab==='footer'?'on':'' }}" href="{{ url('/admin/corporate-home?tab=footer') }}">فوتر</a>
  </nav>

  <form method="post" action="{{ url('/admin/corporate-home') }}">
    @csrf
    <input type="hidden" name="tab" value="{{ $tab }}">

    @if($tab==='banner')
      <div class="ch-grid">
        <section class="ch-card ch-full">
          <h2>بنر صفحه اول</h2>
          <p class="ch-hint">عنوان می‌تواند شامل تگ &lt;em&gt; برای تأکید باشد.</p>
          <label>کیکر<input name="banner_kicker" value="{{ $f('banner_kicker') }}"></label>
          <label>عنوان اصلی<textarea name="banner_title" rows="2">{{ $f('banner_title') }}</textarea></label>
          <label>توضیح کوتاه<textarea name="banner_lead" rows="3">{{ $f('banner_lead') }}</textarea></label>
        </section>
        <section class="ch-card">
          <h2>دکمه‌های بنر</h2>
          <label>متن دکمه قرمز<input name="banner_cta_red_label" value="{{ $f('banner_cta_red_label') }}"></label>
          <label>لینک دکمه قرمز<input name="banner_cta_red_url" value="{{ $f('banner_cta_red_url') }}" dir="ltr"></label>
          <label>متن دکمه آبی<input name="banner_cta_blue_label" value="{{ $f('banner_cta_blue_label') }}"></label>
          <label>لینک دکمه آبی<input name="banner_cta_blue_url" value="{{ $f('banner_cta_blue_url') }}" dir="ltr"></label>
        </section>
        <section class="ch-card">
          <h2>دو مسیر زیر بنر + هدر</h2>
          <label>عنوان مسیر شرکتی<input name="banner_gate1_title" value="{{ $f('banner_gate1_title') }}"></label>
          <label>متن مسیر شرکتی<input name="banner_gate1_text" value="{{ $f('banner_gate1_text') }}"></label>
          <label>عنوان مسیر فروشگاهی<input name="banner_gate2_title" value="{{ $f('banner_gate2_title') }}"></label>
          <label>متن مسیر فروشگاهی<input name="banner_gate2_text" value="{{ $f('banner_gate2_text') }}"></label>
          <label>متن دکمه هدر<input name="header_cta_label" value="{{ $f('header_cta_label') }}"></label>
          <label>لینک دکمه هدر<input name="header_cta_url" value="{{ $f('header_cta_url') }}" dir="ltr"></label>
        </section>
        <section class="ch-card">
          <h2>نمودار چرخشی بنر</h2>
          <label>عنوان وسط<input name="banner_orbit_title" value="{{ $f('banner_orbit_title') }}"></label>
          <label>زیرعنوان وسط<input name="banner_orbit_subtitle" value="{{ $f('banner_orbit_subtitle') }}"></label>
        </section>
      </div>
    @endif

    @if($tab==='home')
      <div class="ch-grid">
        <section class="ch-card ch-full">
          <h2>بخش دو مسیر</h2>
          <label>عنوان بخش<input name="paths_heading" value="{{ $f('paths_heading') }}"></label>
          <label>توضیح بخش<textarea name="paths_lead" rows="2">{{ $f('paths_lead') }}</textarea></label>
        </section>
        <section class="ch-card">
          <h2>کارت شرکتی</h2>
          <label>کد<input name="corp_code" value="{{ $f('corp_code') }}"></label>
          <label>عنوان<input name="corp_title" value="{{ $f('corp_title') }}"></label>
          <label>متن<textarea name="corp_text" rows="3">{{ $f('corp_text') }}</textarea></label>
          <label>دکمه ۱ — متن<input name="corp_cta1_label" value="{{ $f('corp_cta1_label') }}"></label>
          <label>دکمه ۱ — لینک<input name="corp_cta1_url" value="{{ $f('corp_cta1_url') }}" dir="ltr"></label>
          <label>دکمه ۲ — متن<input name="corp_cta2_label" value="{{ $f('corp_cta2_label') }}"></label>
          <label>دکمه ۲ — لینک<input name="corp_cta2_url" value="{{ $f('corp_cta2_url') }}" dir="ltr"></label>
        </section>
        <section class="ch-card">
          <h2>کارت فروشگاه</h2>
          <label>کد<input name="shop_code" value="{{ $f('shop_code') }}"></label>
          <label>عنوان<input name="shop_title" value="{{ $f('shop_title') }}"></label>
          <label>متن<textarea name="shop_text" rows="3">{{ $f('shop_text') }}</textarea></label>
          <label>دکمه ۱ — متن<input name="shop_cta1_label" value="{{ $f('shop_cta1_label') }}"></label>
          <label>دکمه ۱ — لینک<input name="shop_cta1_url" value="{{ $f('shop_cta1_url') }}" dir="ltr"></label>
          <label>دکمه ۲ — متن<input name="shop_cta2_label" value="{{ $f('shop_cta2_label') }}"></label>
          <label>دکمه ۲ — لینک<input name="shop_cta2_url" value="{{ $f('shop_cta2_url') }}" dir="ltr"></label>
        </section>
        <section class="ch-card ch-full">
          <h2>زیرمسیرها</h2>
          <p class="ch-hint">هر خط: عنوان|توضیح|لینک|متن لینک</p>
          <label><textarea name="sub_paths" rows="6">{{ $f('sub_paths') }}</textarea></label>
        </section>
        <section class="ch-card">
          <h2>پوشش دستگاه‌ها</h2>
          <label>عنوان<input name="devices_heading" value="{{ $f('devices_heading') }}"></label>
          <label>توضیح<textarea name="devices_lead" rows="2">{{ $f('devices_lead') }}</textarea></label>
          <p class="ch-hint">هر خط: عنوان|زیرعنوان</p>
          <label><textarea name="devices" rows="8">{{ $f('devices') }}</textarea></label>
        </section>
        <section class="ch-card">
          <h2>نوار فراخوان پایین</h2>
          <label>عنوان<input name="cta_heading" value="{{ $f('cta_heading') }}"></label>
          <label>توضیح<textarea name="cta_lead" rows="2">{{ $f('cta_lead') }}</textarea></label>
          <label>دکمه قرمز — متن<input name="cta_red_label" value="{{ $f('cta_red_label') }}"></label>
          <label>دکمه قرمز — لینک<input name="cta_red_url" value="{{ $f('cta_red_url') }}" dir="ltr"></label>
          <label>دکمه آبی — متن<input name="cta_blue_label" value="{{ $f('cta_blue_label') }}"></label>
          <label>دکمه آبی — لینک<input name="cta_blue_url" value="{{ $f('cta_blue_url') }}" dir="ltr"></label>
        </section>
      </div>
    @endif

    @if($tab==='footer')
      <div class="ch-grid">
        <section class="ch-card ch-full">
          <h2>برند فوتر</h2>
          <label>شعار کوتاه<input name="footer_tagline" value="{{ $f('footer_tagline') }}"></label>
          <label>توضیح برند<textarea name="footer_about" rows="3">{{ $f('footer_about') }}</textarea></label>
          <label>دکمه قرمز — متن<input name="footer_cta_red_label" value="{{ $f('footer_cta_red_label') }}"></label>
          <label>دکمه قرمز — لینک<input name="footer_cta_red_url" value="{{ $f('footer_cta_red_url') }}" dir="ltr"></label>
          <label>دکمه آبی — متن<input name="footer_cta_blue_label" value="{{ $f('footer_cta_blue_label') }}"></label>
          <label>دکمه آبی — لینک<input name="footer_cta_blue_url" value="{{ $f('footer_cta_blue_url') }}" dir="ltr"></label>
        </section>
        <section class="ch-card">
          <h2>ستون ۱</h2>
          <label>عنوان<input name="footer_col1_title" value="{{ $f('footer_col1_title') }}"></label>
          <p class="ch-hint">هر خط: عنوان|آدرس</p>
          <label><textarea name="footer_col1_links" rows="7">{{ $f('footer_col1_links') }}</textarea></label>
        </section>
        <section class="ch-card">
          <h2>ستون ۲</h2>
          <label>عنوان<input name="footer_col2_title" value="{{ $f('footer_col2_title') }}"></label>
          <label><textarea name="footer_col2_links" rows="7">{{ $f('footer_col2_links') }}</textarea></label>
        </section>
        <section class="ch-card">
          <h2>ستون ۳</h2>
          <label>عنوان<input name="footer_col3_title" value="{{ $f('footer_col3_title') }}"></label>
          <label><textarea name="footer_col3_links" rows="7">{{ $f('footer_col3_links') }}</textarea></label>
        </section>
        <section class="ch-card">
          <h2>تماس و کپی‌رایت</h2>
          <label>عنوان تماس<input name="footer_contact_title" value="{{ $f('footer_contact_title') }}"></label>
          <label>عنوان ساعات<input name="footer_hours_title" value="{{ $f('footer_hours_title') }}"></label>
          <label>متن ساعات<input name="footer_hours_text" value="{{ $f('footer_hours_text') }}"></label>
          <label>پسوند کپی‌رایت<input name="footer_copyright" value="{{ $f('footer_copyright') }}"></label>
          <p class="ch-hint">لینک‌های پایین — هر خط: عنوان|آدرس</p>
          <label><textarea name="footer_bottom_links" rows="4">{{ $f('footer_bottom_links') }}</textarea></label>
        </section>
      </div>
    @endif

    <div class="vb-actions" style="margin-top:1rem">
      <button class="btn btn-primary" type="submit">ذخیره تنظیمات</button>
    </div>
  </form>
</div>
@endsection
