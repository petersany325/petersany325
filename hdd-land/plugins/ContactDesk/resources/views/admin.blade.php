@extends('layouts.admin')
@section('title', 'صفحه تماس')
@section('content')
<style>
  .ct-ad{max-width:920px;display:grid;gap:1rem}
  .ct-ad .panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.1rem}
  .ct-ad h2{margin:0 0 .75rem;font-size:1.02rem}
  .ct-ad label{display:grid;gap:.3rem;margin-bottom:.7rem;font-size:.82rem;color:#475569}
  .ct-ad input,.ct-ad textarea{border:1px solid #d0dbe3;border-radius:10px;padding:.55rem .7rem;font:inherit}
  .ct-ad .row{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
  @media(max-width:720px){.ct-ad .row{grid-template-columns:1fr}}
</style>
<div class="ct-ad">
  <div>
    <h1>ویرایش صفحه تماس</h1>
    <p><a href="{{ url('/contact') }}" target="_blank" rel="noopener">مشاهده صفحه</a></p>
  </div>
  <form method="post" action="{{ url('/admin/contact-page') }}">
    @csrf
    <div class="panel">
      <h2>سرصفحه</h2>
      <div class="row">
        <label>عنوان کوتاه<input name="kicker" value="{{ $copy['kicker'] }}"></label>
        <label>عنوان<input name="title" value="{{ $copy['title'] }}"></label>
      </div>
      <label>متن معرفی<textarea name="lead" rows="3">{{ $copy['lead'] }}</textarea></label>
    </div>
    <div class="panel">
      <h2>تلفن و پیام‌رسان</h2>
      <div class="row">
        <label>برچسب تلفن<input name="phone_label" value="{{ $copy['phone_label'] }}"></label>
        <label>شماره تلفن (برای تماس)<input name="phone" value="{{ $copy['phone'] }}" dir="ltr"></label>
        <label>نمایش تلفن<input name="phone_display" value="{{ $copy['phone_display'] }}"></label>
        <label>برچسب واتساپ ۱<input name="wa1_label" value="{{ $copy['wa1_label'] }}"></label>
        <label>واتساپ ۱<input name="wa1" value="{{ $copy['wa1'] }}" dir="ltr"></label>
        <label>نمایش واتساپ ۱<input name="wa1_display" value="{{ $copy['wa1_display'] }}"></label>
        <label>برچسب واتساپ ۲<input name="wa2_label" value="{{ $copy['wa2_label'] }}"></label>
        <label>واتساپ ۲<input name="wa2" value="{{ $copy['wa2'] }}" dir="ltr"></label>
        <label>نمایش واتساپ ۲<input name="wa2_display" value="{{ $copy['wa2_display'] }}"></label>
        <label>برچسب تلگرام<input name="tg_label" value="{{ $copy['tg_label'] }}"></label>
        <label>آیدی تلگرام<input name="tg" value="{{ $copy['tg'] }}" dir="ltr"></label>
        <label>برچسب اینستاگرام<input name="ig_label" value="{{ $copy['ig_label'] }}"></label>
        <label>آیدی اینستاگرام<input name="ig" value="{{ $copy['ig'] }}" dir="ltr"></label>
      </div>
    </div>
    <div class="panel">
      <h2>دفتر و تیکت</h2>
      <label>برچسب دفتر<input name="office_label" value="{{ $copy['office_label'] }}"></label>
      <label>آدرس<textarea name="office" rows="3">{{ $copy['office'] }}</textarea></label>
      <div class="row">
        <label>برچسب ساعت<input name="hours_label" value="{{ $copy['hours_label'] }}"></label>
        <label>ساعت پاسخگویی<input name="hours" value="{{ $copy['hours'] }}"></label>
        <label>متن دکمه نقشه<input name="map_label" value="{{ $copy['map_label'] }}"></label>
        <label>لینک نقشه<input name="map_url" value="{{ $copy['map_url'] }}" dir="ltr"></label>
        <label>عنوان تیکت<input name="ticket_title" value="{{ $copy['ticket_title'] }}"></label>
        <label>متن دکمه تیکت<input name="ticket_cta" value="{{ $copy['ticket_cta'] }}"></label>
      </div>
      <label>توضیح تیکت<textarea name="ticket_text" rows="2">{{ $copy['ticket_text'] }}</textarea></label>
      <label>لینک تیکت<input name="ticket_url" value="{{ $copy['ticket_url'] }}" dir="ltr"></label>
      <button class="btn" type="submit">ذخیره صفحه تماس</button>
    </div>
  </form>
</div>
@endsection
