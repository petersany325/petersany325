@extends('layouts.admin')
@section('title', 'صفحه آموزش')
@section('content')
<style>
  .tr-ad{max-width:960px;display:grid;gap:1rem}
  .tr-ad h1{margin:0 0 .2rem}
  .tr-ad .panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.1rem}
  .tr-ad h2{margin:0 0 .75rem;font-size:1.02rem}
  .tr-ad label{display:grid;gap:.3rem;margin-bottom:.7rem;font-size:.82rem;color:#475569}
  .tr-ad input,.tr-ad textarea{border:1px solid #d0dbe3;border-radius:10px;padding:.55rem .7rem;font:inherit}
  .tr-ad .row{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
  .tr-ad .chk{display:flex;align-items:center;gap:.4rem;font-size:.9rem;margin-bottom:.6rem}
  .tr-ad small{color:#64748b}
  @media(max-width:720px){.tr-ad .row{grid-template-columns:1fr}}
</style>
@php
  $labels = [
    'rec' => 'بازیابی اطلاعات',
    'fix' => 'تعمیرات هارد دیسک',
    'ssd' => 'SSD / M.2 / NVMe',
    'srv' => 'سرور و استوریج',
  ];
@endphp
<div class="tr-ad">
  <div>
    <h1>ویرایش آکادمی آموزش</h1>
    <p>
      <a href="{{ url('/training') }}" target="_blank" rel="noopener">صفحه اصلی آموزش</a>
      ·
      <a href="{{ url('/training/data-recovery') }}" target="_blank" rel="noopener">بازیابی</a>
      ·
      <a href="{{ url('/training/hdd-repair') }}" target="_blank" rel="noopener">تعمیرات</a>
      ·
      <a href="{{ url('/training/ssd-nvme') }}" target="_blank" rel="noopener">SSD</a>
      ·
      <a href="{{ url('/training/server-storage') }}" target="_blank" rel="noopener">سرور</a>
    </p>
  </div>
  <form method="post" action="{{ url('/admin/training-page') }}">
    @csrf
    <div class="panel">
      <h2>سرصفحه آکادمی</h2>
      <div class="row">
        <label>عنوان کوتاه<input name="hub_kicker" value="{{ $copy['hub_kicker'] }}"></label>
        <label>عنوان صفحه<input name="hub_title" value="{{ $copy['hub_title'] }}"></label>
      </div>
      <label>متن معرفی<textarea name="hub_lead" rows="3">{{ $copy['hub_lead'] }}</textarea></label>
      <label>یادداشت زیر هیرو<textarea name="hub_intro" rows="3">{{ $copy['hub_intro'] }}</textarea></label>
      <div class="row">
        <label>آمار ۱<input name="hub_stat_1" value="{{ $copy['hub_stat_1'] }}"></label>
        <label>آمار ۲<input name="hub_stat_2" value="{{ $copy['hub_stat_2'] }}"></label>
        <label>آمار ۳<input name="hub_stat_3" value="{{ $copy['hub_stat_3'] }}"></label>
        <label>آمار ۴<input name="hub_stat_4" value="{{ $copy['hub_stat_4'] }}"></label>
      </div>
    </div>

    @foreach($prefixes as $slug => $prefix)
      <div class="panel">
        <h2>{{ $labels[$prefix] ?? $prefix }} — /training/{{ $slug }}</h2>
        <label class="chk"><input type="checkbox" name="{{ $prefix }}_on" value="1" @checked(!empty($copy[$prefix.'_on']))> نمایش این دوره در منو و سایت</label>
        <div class="row">
          <label>عنوان کوتاه<input name="{{ $prefix }}_kicker" value="{{ $copy[$prefix.'_kicker'] }}"></label>
          <label>عنوان دوره<input name="{{ $prefix }}_title" value="{{ $copy[$prefix.'_title'] }}"></label>
          <label>مدت<input name="{{ $prefix }}_duration" value="{{ $copy[$prefix.'_duration'] }}"></label>
          <label>سطح<input name="{{ $prefix }}_level" value="{{ $copy[$prefix.'_level'] }}"></label>
        </div>
        <label>متن کارت در صفحه اصلی<textarea name="{{ $prefix }}_card" rows="2">{{ $copy[$prefix.'_card'] }}</textarea></label>
        <label>معرفی هیرو<textarea name="{{ $prefix }}_lead" rows="3">{{ $copy[$prefix.'_lead'] }}</textarea></label>
        <label>تعریف کامل دوره<textarea name="{{ $prefix }}_intro" rows="4">{{ $copy[$prefix.'_intro'] }}</textarea></label>
        <label>مخاطب<input name="{{ $prefix }}_audience" value="{{ $copy[$prefix.'_audience'] }}"></label>
        <label>جدول دوره‌ها و هزینه <small>هر خط: برند|عنوان|مدت|سطح|تماس بگیرید — سلول هزینه به صفحه تماس لینک می‌شود</small>
          <textarea name="{{ $prefix }}_table" rows="8" dir="rtl">{{ $copy[$prefix.'_table'] }}</textarea>
        </label>
        <label>سیلابس (هر خط یک مورد)<textarea name="{{ $prefix }}_syllabus" rows="8">{{ $copy[$prefix.'_syllabus'] }}</textarea></label>
        <div class="row">
          <label>پیش‌نیاز (هر خط یکی)<textarea name="{{ $prefix }}_prereq" rows="4">{{ $copy[$prefix.'_prereq'] }}</textarea></label>
          <label>شامل دوره (هر خط یکی)<textarea name="{{ $prefix }}_includes" rows="4">{{ $copy[$prefix.'_includes'] }}</textarea></label>
        </div>
        <label>پرسش‌ها <small>هر خط: سؤال|جواب</small>
          <textarea name="{{ $prefix }}_faq" rows="4">{{ $copy[$prefix.'_faq'] }}</textarea>
        </label>
        <label>متن دکمه ثبت‌نام<input name="{{ $prefix }}_cta" value="{{ $copy[$prefix.'_cta'] }}"></label>
      </div>
    @endforeach

    <div class="panel">
      <h2>آزمایشگاه و تماس</h2>
      <label>عنوان تجهیزات<input name="tools_title" value="{{ $copy['tools_title'] }}"></label>
      <label>تجهیزات (هر خط یکی)<textarea name="tools" rows="5">{{ $copy['tools'] }}</textarea></label>
      <label>یادداشت هزینه<textarea name="price_note" rows="3">{{ $copy['price_note'] }}</textarea></label>
      <div class="row">
        <label>عنوان نوار پایین<input name="cta_title" value="{{ $copy['cta_title'] }}"></label>
        <label>متن نوار پایین<input name="cta_text" value="{{ $copy['cta_text'] }}"></label>
        <label>دکمه اصلی<input name="cta_label" value="{{ $copy['cta_label'] }}"></label>
        <label>لینک اصلی<input name="cta_url" value="{{ $copy['cta_url'] }}" dir="ltr"></label>
        <label>دکمه دوم<input name="alt_label" value="{{ $copy['alt_label'] }}"></label>
        <label>لینک دوم<input name="alt_url" value="{{ $copy['alt_url'] }}" dir="ltr"></label>
      </div>
      <button class="btn" type="submit">ذخیره آکادمی آموزش</button>
    </div>
  </form>
</div>
@endsection
