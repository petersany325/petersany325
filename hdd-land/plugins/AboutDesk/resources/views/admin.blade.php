@extends('layouts.admin')
@section('title', 'صفحه درباره ما')
@section('content')
<style>
  .ab-ad{max-width:880px;display:grid;gap:1rem}
  .ab-ad h1{margin:0 0 .2rem}
  .ab-ad .panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.1rem}
  .ab-ad label{display:grid;gap:.3rem;margin-bottom:.7rem;font-size:.82rem;color:#475569}
  .ab-ad input,.ab-ad textarea{border:1px solid #d0dbe3;border-radius:10px;padding:.55rem .7rem;font:inherit}
  .ab-ad .row{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
  @media(max-width:720px){.ab-ad .row{grid-template-columns:1fr}}
</style>
<div class="ab-ad">
  <div>
    <h1>ویرایش صفحه درباره ما</h1>
    <p><a href="{{ url('/about') }}" target="_blank" rel="noopener">مشاهده صفحه</a></p>
  </div>
  <form class="panel" method="post" action="{{ url('/admin/about-page') }}">
    @csrf
    <div class="row">
      <label>عنوان سه‌بعدی<input name="title_3d" value="{{ $copy['title_3d'] }}" maxlength="40"></label>
      <label>عنوان کوتاه<input name="kicker" value="{{ $copy['kicker'] }}" maxlength="80"></label>
      <label>برند<input name="brand" value="{{ $copy['brand'] }}"></label>
      <label>نام حقوقی<input name="legal" value="{{ $copy['legal'] }}"></label>
    </div>
    <label>بند ۱<textarea name="p1" rows="3">{{ $copy['p1'] }}</textarea></label>
    <label>بند ۲<textarea name="p2" rows="3">{{ $copy['p2'] }}</textarea></label>
    <label>بند ۳<textarea name="p3" rows="3">{{ $copy['p3'] }}</textarea></label>
    <label>بند ۴<textarea name="p4" rows="4">{{ $copy['p4'] }}</textarea></label>
    <label>بند ۵<textarea name="p5" rows="3">{{ $copy['p5'] }}</textarea></label>
    <label>عنوان آزمایشگاه<input name="lab_title" value="{{ $copy['lab_title'] }}"></label>
    <label>تجهیزات (هر خط یکی)<textarea name="tools" rows="6">{{ $copy['tools'] }}</textarea></label>
    <div class="row">
      <label>سایت ۱ — عنوان<input name="site_1_label" value="{{ $copy['site_1_label'] }}"></label>
      <label>سایت ۱ — آدرس<input name="site_1_url" value="{{ $copy['site_1_url'] }}" dir="ltr"></label>
      <label>سایت ۲ — عنوان<input name="site_2_label" value="{{ $copy['site_2_label'] }}"></label>
      <label>سایت ۲ — آدرس<input name="site_2_url" value="{{ $copy['site_2_url'] }}" dir="ltr"></label>
    </div>
    <button class="btn" type="submit">ذخیره متن صفحه</button>
  </form>
</div>
@endsection
