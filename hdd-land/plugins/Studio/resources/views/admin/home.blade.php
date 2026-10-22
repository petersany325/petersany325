@extends('layouts.admin')
@section('title', 'صفحه اول استودیو')
@section('content')
@php
  $h = $home ?? [];
  $f = fn ($k, $d = '') => old($k, $h[$k] ?? $d);
  $on = fn ($k) => (bool) old($k, $h[$k] ?? false);
@endphp
<style>
.ag-adm h1{margin:0 0 .35rem;font-size:1.35rem}
.ag-adm .hint{color:#64748b;font-size:.9rem;margin:0 0 1rem}
.ag-adm .card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem;margin-bottom:.85rem}
.ag-adm .card h2{margin:0 0 .75rem;font-size:1rem}
.ag-adm .grid{display:grid;gap:.65rem}
.ag-adm label{display:grid;gap:.28rem;font-size:.82rem;font-weight:700;color:#0f172a}
.ag-adm input,.ag-adm textarea{width:100%;border:1px solid #dbe3ef;border-radius:10px;padding:.55rem .7rem;font:inherit;background:#f8fafc}
.ag-adm .row2{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}
.ag-adm .row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.65rem}
@media(max-width:800px){.ag-adm .row2,.ag-adm .row3{grid-template-columns:1fr}}
.ag-adm .sw{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.45rem 0}
.ag-adm .color{display:grid;grid-template-columns:42px 1fr;gap:.4rem;align-items:center}
.ag-adm input[type=color]{width:42px;height:38px;padding:0;border:1px solid #dbe3ef;border-radius:10px}
.ag-adm .actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;position:sticky;bottom:.75rem;background:rgba(248,250,252,.92);padding:.65rem;border:1px solid #e2e8f0;border-radius:12px}
</style>
<div class="ag-adm">
  <h1>صفحه اول استودیو — قالب آژانس</h1>
  <p class="hint">همان چیدمان عکس: هیرو دو ستونه، صحنه سه‌بعدی، سه خدمت، آمار و نمونه کار. همه فیلدها از اینجا عوض می‌شوند.</p>
  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem">{{ session('success') }}</div>
  @endif
  <form method="post" action="{{ url('/admin/studio-home') }}">
    @csrf
    <div class="card">
      <h2>نمایش</h2>
      <label class="sw"><span>فعال بودن این قالب روی دامنه استودیو</span><input type="checkbox" name="enabled" value="1" @checked($on('enabled'))></label>
      <label class="sw"><span>نمایش جستجو در هدر</span><input type="checkbox" name="show_search" value="1" @checked($on('show_search'))></label>
      <label class="sw"><span>وب‌اپ موبایل (نوار پایین + نصب روی گوشی)</span><input type="checkbox" name="mobile_webapp" value="1" @checked($on('mobile_webapp'))></label>
      <label class="sw"><span>کارت‌های خدمات</span><input type="checkbox" name="services_enabled" value="1" @checked($on('services_enabled'))></label>
      <label class="sw"><span>ردیف آمار</span><input type="checkbox" name="stats_enabled" value="1" @checked($on('stats_enabled'))></label>
      <label class="sw"><span>نمونه کارها</span><input type="checkbox" name="works_enabled" value="1" @checked($on('works_enabled'))></label>
    </div>
    <div class="card">
      <h2>هویت و هدر</h2>
      <div class="grid">
        <div class="row2">
          <label>نام برند<input name="brand" value="{{ $f('brand') }}"></label>
          <label>زیرعنوان تخصص<input name="tagline" value="{{ $f('tagline') }}"></label>
        </div>
        <div class="row2">
          <label>تلفن<input name="phone" value="{{ $f('phone') }}"></label>
          <label>متن جستجو<input name="search_placeholder" value="{{ $f('search_placeholder') }}"></label>
        </div>
        <label>منوی هدر — هر خط: عنوان|آدرس<textarea name="nav" rows="7">{{ $f('nav') }}</textarea></label>
        <p class="hint">منوی «طراحی سایت» زیرمنوی خدمات فراز نت آمل را می‌گیرد. «حسابداری وب» تعمیرگاه را هم داخل خودش دارد. صنوف دیگر در «حسابداری کامپیوتر» با SQL Server 2022 می‌آیند.</p>
        <div class="row2">
          <label>رنگ تأکید
            <span class="color">
              <input type="color" name="accent" value="{{ $f('accent','#7c5cff') }}" oninput="this.nextElementSibling.value=this.value">
              <input type="text" value="{{ $f('accent','#7c5cff') }}" maxlength="7" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value))this.previousElementSibling.value=this.value">
            </span>
          </label>
          <label>رنگ پس‌زمینه
            <span class="color">
              <input type="color" name="bg" value="{{ $f('bg','#070b18') }}" oninput="this.nextElementSibling.value=this.value">
              <input type="text" value="{{ $f('bg','#070b18') }}" maxlength="7" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value))this.previousElementSibling.value=this.value">
            </span>
          </label>
        </div>
      </div>
    </div>
    <div class="card">
      <h2>هیرو</h2>
      <div class="grid">
        <label>عنوان<input name="hero_title" value="{{ $f('hero_title') }}"></label>
        <label>کلمه رنگی داخل عنوان<input name="hero_em" value="{{ $f('hero_em') }}"></label>
        <label>توضیح<textarea name="hero_text" rows="3">{{ $f('hero_text') }}</textarea></label>
        <div class="row2">
          <label>دکمه اصلی<input name="cta1_label" value="{{ $f('cta1_label') }}"></label>
          <label>لینک دکمه اصلی<input name="cta1_url" value="{{ $f('cta1_url') }}"></label>
        </div>
        <div class="row2">
          <label>دکمه خطی<input name="cta2_label" value="{{ $f('cta2_label') }}"></label>
          <label>لینک دکمه خطی<input name="cta2_url" value="{{ $f('cta2_url') }}"></label>
        </div>
      </div>
    </div>
    <div class="card">
      <h2>صحنه سه‌بعدی (خانه + لپ‌تاپ + نقاط)</h2>
      <div class="grid">
        <label>عکس کل صحنه (لپ‌تاپ + خانه)<input name="scene_image" value="{{ $f('scene_image') }}" placeholder="images/studio/... یا https:// — خالی = ماکت جدا"></label>
        <div class="row2">
          <label>عنوان داخل لپ‌تاپ<input name="laptop_title" value="{{ $f('laptop_title') }}"></label>
          <label>عکس لپ‌تاپ (اگر صحنه خالی باشد)<input name="laptop_image" value="{{ $f('laptop_image') }}" placeholder="images/... یا https://"></label>
        </div>
        <label>عکس خانه (اختیاری)<input name="house_image" value="{{ $f('house_image') }}" placeholder="خالی = ماکت داخلی"></label>
        <div class="row2">
          <label>نقطه ۱<input name="node_1" value="{{ $f('node_1') }}"></label>
          <label>نقطه ۲<input name="node_2" value="{{ $f('node_2') }}"></label>
        </div>
        <div class="row2">
          <label>نقطه ۳<input name="node_3" value="{{ $f('node_3') }}"></label>
          <label>نقطه ۴<input name="node_4" value="{{ $f('node_4') }}"></label>
        </div>
      </div>
    </div>
    <div class="card">
      <h2>سه خدمت</h2>
      <div class="grid">
        <label>متن لینک جزئیات<input name="svc_more_label" value="{{ $f('svc_more_label') }}"></label>
        @for($i=1;$i<=3;$i++)
          <div class="row3">
            <label>عنوان {{ $i }}<input name="svc_{{ $i }}_title" value="{{ $f('svc_'.$i.'_title') }}"></label>
            <label>متن {{ $i }}<input name="svc_{{ $i }}_text" value="{{ $f('svc_'.$i.'_text') }}"></label>
            <label>لینک {{ $i }}<input name="svc_{{ $i }}_url" value="{{ $f('svc_'.$i.'_url') }}"></label>
          </div>
        @endfor
      </div>
    </div>
    <div class="card">
      <h2>آمار</h2>
      <div class="grid">
        @for($i=1;$i<=4;$i++)
          <div class="row2">
            <label>عدد {{ $i }}<input name="stat_{{ $i }}_value" value="{{ $f('stat_'.$i.'_value') }}"></label>
            <label>برچسب {{ $i }}<input name="stat_{{ $i }}_label" value="{{ $f('stat_'.$i.'_label') }}"></label>
          </div>
        @endfor
      </div>
    </div>
    <div class="card">
      <h2>نمونه کارها</h2>
      <div class="grid">
        <div class="row2">
          <label>عنوان بخش<input name="works_title" value="{{ $f('works_title') }}"></label>
          <label>زیرعنوان<input name="works_subtitle" value="{{ $f('works_subtitle') }}"></label>
        </div>
        <div class="row2">
          <label>متن لینک همه<input name="works_more_label" value="{{ $f('works_more_label') }}"></label>
          <label>لینک همه<input name="works_more_url" value="{{ $f('works_more_url') }}"></label>
        </div>
        @for($i=1;$i<=4;$i++)
          <div class="row2">
            <label>کار {{ $i }} — عنوان<input name="work_{{ $i }}_title" value="{{ $f('work_'.$i.'_title') }}"></label>
            <label>دسته<input name="work_{{ $i }}_cat" value="{{ $f('work_'.$i.'_cat') }}"></label>
            <label>لینک<input name="work_{{ $i }}_url" value="{{ $f('work_'.$i.'_url') }}"></label>
            <label>عکس<input name="work_{{ $i }}_image" value="{{ $f('work_'.$i.'_image') }}" placeholder="images/... یا https://"></label>
          </div>
        @endfor
      </div>
    </div>
    <div class="actions">
      <button class="btn btn-primary" type="submit">ذخیره صفحه اول</button>
      <a class="btn" href="{{ url('/') }}" target="_blank" rel="noopener">مشاهده سایت</a>
      <a class="btn" href="{{ url('/admin/homepage-settings') }}">بنر فروشگاه</a>
    </div>
  </form>
</div>
@endsection
