@extends('layouts.admin')
@section('title', $title)
@section('content')
<style>
  .cl-ad{max-width:960px;display:grid;gap:1rem}
  .cl-ad h1{margin:0 0 .2rem}
  .cl-ad .tabs{display:flex;gap:.4rem;flex-wrap:wrap}
  .cl-ad .tabs a{padding:.45rem .8rem;border-radius:10px;background:#e2e8f0;color:#0b1220;text-decoration:none;font-weight:750}
  .cl-ad .tabs a.on{background:#0b1220;color:#fff}
  .cl-ad .panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.1rem}
  .cl-ad h2{margin:0 0 .75rem;font-size:1.02rem}
  .cl-ad label{display:grid;gap:.3rem;margin-bottom:.7rem;font-size:.82rem;color:#475569}
  .cl-ad input,.cl-ad textarea{border:1px solid #d0dbe3;border-radius:10px;padding:.55rem .7rem;font:inherit}
  .cl-ad .row{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
  .cl-ad .hint{color:#64748b;font-size:.8rem;margin:0 0 .7rem}
  @media(max-width:720px){.cl-ad .row{grid-template-columns:1fr}}
</style>
<div class="cl-ad">
  <div>
    <h1>{{ $title }}</h1>
    <p class="hint">صفحه‌ساز کامل: هیرو، معرفی، کارت‌ها، مسیر سفارش، FAQ و دکمه تماس. هر خط «عنوان|متن».</p>
    <div class="tabs">
      <a class="{{ $page==='enterprise'?'on':'' }}" href="{{ url('/admin/corp-pages/enterprise') }}">تأمین هارد سازمانی</a>
      <a class="{{ $page==='cctv'?'on':'' }}" href="{{ url('/admin/corp-pages/cctv') }}">پروژه‌های CCTV</a>
      <a href="{{ $preview }}" target="_blank" rel="noopener">مشاهده صفحه</a>
    </div>
  </div>
  <form method="post" action="{{ url('/admin/corp-pages/'.$page) }}">
    @csrf
    <div class="panel">
      <h2>هیرو</h2>
      <div class="row">
        <label>عنوان کوتاه<input name="kicker" value="{{ $copy['kicker'] }}"></label>
        <label>تصویر هیرو<input name="hero_image" value="{{ $copy['hero_image'] }}" dir="ltr"></label>
      </div>
      <label>عنوان صفحه<input name="title" value="{{ $copy['title'] }}"></label>
      <label>متن معرفی<textarea name="lead" rows="3">{{ $copy['lead'] }}</textarea></label>
      <div class="row">
        <label>دکمه ۱<input name="cta1_label" value="{{ $copy['cta1_label'] }}"></label>
        <label>لینک ۱<input name="cta1_url" value="{{ $copy['cta1_url'] }}" dir="ltr"></label>
        <label>دکمه ۲<input name="cta2_label" value="{{ $copy['cta2_label'] }}"></label>
        <label>لینک ۲<input name="cta2_url" value="{{ $copy['cta2_url'] }}" dir="ltr"></label>
      </div>
    </div>
    <div class="panel">
      <h2>متن کامل</h2>
      <label>عنوان بخش معرفی<input name="intro_title" value="{{ $copy['intro_title'] }}"></label>
      <label>پاراگراف‌ها (هر خط یک پاراگراف)<textarea name="intro" rows="5">{{ $copy['intro'] }}</textarea></label>
    </div>
    <div class="panel">
      <h2>کارت‌های خروجی — عنوان|متن</h2>
      <label>عنوان بخش<input name="features_title" value="{{ $copy['features_title'] }}"></label>
      <label>کارت‌ها<textarea name="features" rows="7">{{ $copy['features'] }}</textarea></label>
    </div>
    <div class="panel">
      <h2>مسیر سفارش — عنوان|متن</h2>
      <label>عنوان بخش<input name="steps_title" value="{{ $copy['steps_title'] }}"></label>
      <label>مراحل<textarea name="steps" rows="5">{{ $copy['steps'] }}</textarea></label>
    </div>
    <div class="panel">
      <h2>شاخص‌ها و کاربری</h2>
      <label>آمار (عنوان|متن)<textarea name="stats" rows="4">{{ $copy['stats'] }}</textarea></label>
      <label>عنوان کاربری‌ها<input name="cases_title" value="{{ $copy['cases_title'] }}"></label>
      <label>کاربری‌ها<textarea name="cases" rows="5">{{ $copy['cases'] }}</textarea></label>
    </div>
    <div class="panel">
      <h2>برند و پرسش</h2>
      <label>عنوان برند<input name="brands_title" value="{{ $copy['brands_title'] }}"></label>
      <label>برندها (هر خط یکی)<textarea name="brands" rows="4">{{ $copy['brands'] }}</textarea></label>
      <label>عنوان FAQ<input name="faq_title" value="{{ $copy['faq_title'] }}"></label>
      <label>پرسش|پاسخ<textarea name="faq" rows="6">{{ $copy['faq'] }}</textarea></label>
    </div>
    <div class="panel">
      <h2>نوار تماس</h2>
      <div class="row">
        <label>عنوان<input name="cta_title" value="{{ $copy['cta_title'] }}"></label>
        <label>متن<input name="cta_text" value="{{ $copy['cta_text'] }}"></label>
        <label>دکمه اصلی<input name="cta_label" value="{{ $copy['cta_label'] }}"></label>
        <label>لینک اصلی<input name="cta_url" value="{{ $copy['cta_url'] }}" dir="ltr"></label>
        <label>دکمه دوم<input name="alt_label" value="{{ $copy['alt_label'] }}"></label>
        <label>لینک دوم<input name="alt_url" value="{{ $copy['alt_url'] }}" dir="ltr"></label>
      </div>
      <button class="btn" type="submit">ذخیره صفحه‌ساز</button>
    </div>
  </form>
</div>
@endsection
