@extends('layouts.admin')
@section('title', 'صفحه خدمات سازمانی')
@section('content')
<style>
  .sv-ad{max-width:920px;display:grid;gap:1rem}
  .sv-ad h1{margin:0 0 .2rem}
  .sv-ad .panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.1rem}
  .sv-ad h2{margin:0 0 .75rem;font-size:1.02rem}
  .sv-ad label{display:grid;gap:.3rem;margin-bottom:.7rem;font-size:.82rem;color:#475569}
  .sv-ad input,.sv-ad textarea{border:1px solid #d0dbe3;border-radius:10px;padding:.55rem .7rem;font:inherit}
  .sv-ad .row{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
  .sv-ad .chk{display:flex;align-items:center;gap:.4rem;font-size:.9rem;margin-bottom:.6rem}
  @media(max-width:720px){.sv-ad .row{grid-template-columns:1fr}}
</style>
<div class="sv-ad">
  <div>
    <h1>ویرایش صفحه خدمات سازمانی</h1>
    <p><a href="{{ url('/services') }}" target="_blank" rel="noopener">مشاهده صفحه</a></p>
  </div>
  <form method="post" action="{{ url('/admin/services-page') }}">
    @csrf
    <div class="panel">
      <h2>سرصفحه</h2>
      <div class="row">
        <label>عنوان کوتاه<input name="kicker" value="{{ $copy['kicker'] }}"></label>
        <label>عنوان صفحه<input name="title" value="{{ $copy['title'] }}"></label>
      </div>
      <label>متن معرفی<textarea name="lead" rows="3">{{ $copy['lead'] }}</textarea></label>
      <label>یادداشت زیر هیرو<textarea name="intro" rows="2">{{ $copy['intro'] }}</textarea></label>
    </div>
    @foreach([1=>'تأمین',2=>'بازیابی',3=>'تعمیر',4=>'گارانتی'] as $i => $lab)
      <div class="panel">
        <h2>خدمت {{ $i }} — {{ $lab }}</h2>
        <label class="chk"><input type="checkbox" name="s{{ $i }}_on" value="1" @checked(!empty($copy['s'.$i.'_on']))> نمایش این خدمت</label>
        <label>عنوان<input name="s{{ $i }}_title" value="{{ $copy['s'.$i.'_title'] }}"></label>
        <label>برای چه کسی<input name="s{{ $i }}_audience" value="{{ $copy['s'.$i.'_audience'] }}"></label>
        <label>شرح خروجی<textarea name="s{{ $i }}_text" rows="3">{{ $copy['s'.$i.'_text'] }}</textarea></label>
        <div class="row">
          <label>متن دکمه<input name="s{{ $i }}_cta" value="{{ $copy['s'.$i.'_cta'] }}"></label>
          <label>لینک دکمه<input name="s{{ $i }}_url" value="{{ $copy['s'.$i.'_url'] }}" dir="ltr"></label>
        </div>
      </div>
    @endforeach
    <div class="panel">
      <h2>آزمایشگاه و تماس</h2>
      <label>عنوان تجهیزات<input name="lab_title" value="{{ $copy['lab_title'] }}"></label>
      <label>تجهیزات (هر خط یکی)<textarea name="tools" rows="5">{{ $copy['tools'] }}</textarea></label>
      <div class="row">
        <label>عنوان نوار پایین<input name="cta_title" value="{{ $copy['cta_title'] }}"></label>
        <label>متن نوار پایین<input name="cta_text" value="{{ $copy['cta_text'] }}"></label>
        <label>دکمه اصلی<input name="cta_label" value="{{ $copy['cta_label'] }}"></label>
        <label>لینک اصلی<input name="cta_url" value="{{ $copy['cta_url'] }}" dir="ltr"></label>
        <label>دکمه دوم<input name="alt_label" value="{{ $copy['alt_label'] }}"></label>
        <label>لینک دوم<input name="alt_url" value="{{ $copy['alt_url'] }}" dir="ltr"></label>
      </div>
      <button class="btn" type="submit">ذخیره تنظیمات صفحه</button>
    </div>
  </form>
</div>
@endsection
