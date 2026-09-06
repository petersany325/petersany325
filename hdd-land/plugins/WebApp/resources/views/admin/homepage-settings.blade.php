@extends('layouts.admin')
@section('title', 'تنظیمات آنلاین صفحه اول')
@section('content')
@php
  $h = $home ?? [];
  $w = $web ?? [];
  $f = fn ($k, $d = '') => old($k, $h[$k] ?? $w[$k] ?? $d);
  $on = fn ($k, $d = false) => (bool) old($k, $h[$k] ?? $w[$k] ?? $d);
@endphp
<style>
.hp-wrap{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(300px,.85fr);gap:1rem;align-items:start}
@media(max-width:1100px){.hp-wrap{grid-template-columns:1fr}}
.hp-grid .row2{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}
@media(max-width:700px){.hp-grid .row2{grid-template-columns:1fr}}
.hp-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;position:sticky;bottom:.75rem;background:rgba(248,250,252,.92);backdrop-filter:blur(8px);padding:.65rem;border:1px solid #e2e8f0;border-radius:12px;z-index:5}
.hp-preview-tog{display:flex;gap:.35rem}
.hp-preview-tog button{border:1px solid #e2e8f0;background:#fff;border-radius:8px;padding:.35rem .65rem;font:inherit;font-size:.78rem;font-weight:700;cursor:pointer}
.hp-preview-tog button.on{background:#0b1220;color:#fff;border-color:#0b1220}
.hp-head{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}
.hp-head h1{margin:0;font-size:1.35rem}
.hp-head p{margin:.35rem 0 0;color:#64748b;font-size:.9rem}
.hp-tabs{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.85rem}
.hp-tabs button{border:1px solid #e2e8f0;background:#fff;border-radius:999px;padding:.4rem .85rem;font:inherit;font-size:.82rem;font-weight:700;cursor:pointer;color:#334155}
.hp-tabs button.on{background:#e23d12;border-color:#e23d12;color:#fff}
.hp-pane{display:none}.hp-pane.on{display:block}
.hp-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem;margin-bottom:.85rem}
.hp-card h2{margin:0 0 .75rem;font-size:1rem}
.hp-grid{display:grid;gap:.65rem}
.hp-grid label{display:grid;gap:.28rem;font-size:.82rem;font-weight:700;color:#0f172a}
.hp-grid input,.hp-grid textarea{width:100%;border:1px solid #dbe3ef;border-radius:10px;padding:.55rem .7rem;font:inherit;background:#f8fafc}
.hp-grid .row2{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}
@media(max-width:700px){.hp-grid .row2{grid-template-columns:1fr}}
.hp-switch{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.55rem 0;border-bottom:1px dashed #eef2f7}
.hp-switch:last-child{border-bottom:0}
.hp-switch span{font-size:.88rem;font-weight:700}
.hp-preview{position:sticky;top:1rem}
.hp-preview-bar{display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.55rem}
.hp-preview-bar strong{font-size:.9rem}
.hp-preview-tog{display:flex;gap:.35rem}
.hp-preview-tog button{border:1px solid #e2e8f0;background:#fff;border-radius:8px;padding:.35rem .65rem;font:inherit;font-size:.78rem;font-weight:700;cursor:pointer}
.hp-preview-tog button.on{background:#0b1220;color:#fff;border-color:#0b1220}
.hp-phone{margin:0 auto;width:min(100%,360px);border:10px solid #0b1220;border-radius:28px;overflow:hidden;background:#0b1220;box-shadow:0 18px 50px rgba(15,23,42,.18)}
.hp-phone.desktop{width:100%;border-radius:14px;border-width:8px}
.hp-phone iframe{display:block;width:100%;height:640px;border:0;background:#fff}
.hp-phone.desktop iframe{height:520px}
.hp-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;position:sticky;bottom:.75rem;background:rgba(248,250,252,.92);backdrop-filter:blur(8px);padding:.65rem;border:1px solid #e2e8f0;border-radius:12px;z-index:5}
.hp-hint{font-size:.78rem;color:#64748b;line-height:1.6;margin:0 0 .65rem}
.hp-map{display:grid;gap:.4rem;margin:.5rem 0 0}
.hp-map div{display:flex;justify-content:space-between;gap:.5rem;font-size:.78rem;padding:.4rem .55rem;background:#f8fafc;border-radius:8px;border:1px solid #eef2f7}
.hp-map b{color:#0f172a}.hp-map span{color:#64748b}
.hp-sw{position:relative;width:46px;height:26px;flex:0 0 auto}
.hp-sw input{opacity:0;width:0;height:0}
.hp-sw i{position:absolute;inset:0;background:#cbd5e1;border-radius:999px;transition:.2s}
.hp-sw i:before{content:"";position:absolute;width:20px;height:20px;right:3px;top:3px;background:#fff;border-radius:50%;transition:.2s}
.hp-sw input:checked + i{background:#e23d12}
.hp-sw input:checked + i:before{transform:translateX(-20px)}
</style>

<div class="hp-head">
  <div>
    <h1>تنظیمات آنلاین صفحه اول</h1>
    <p>هر بلوک صفحه اول (بنر، اعتماد، جستجو، آموزش، معرفی، سازمانی، نصب وب‌اپ) از اینجا قابل ویرایش و پیش‌نمایش است. بنرساز Revolution دیگر منبع بنر زنده نیست.</p>
  </div>
  <div style="display:flex;gap:.45rem;flex-wrap:wrap">
    <a class="btn" href="{{ url('/') }}" target="_blank" rel="noopener">صفحه اول سایت</a>
    <a class="btn" href="{{ url('/app') }}" target="_blank" rel="noopener">خانه وب‌اپ</a>
    <a class="btn" href="{{ url('/admin/web-app') }}">سایر تنظیمات وب‌اپ</a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:1rem">{{ session('success') }}</div>
@endif

<form method="post" action="{{ url('/admin/homepage-settings') }}" id="hp-form">
  @csrf
  <div class="hp-wrap">
    <div>
      <div class="hp-tabs" id="hp-tabs">
        <button type="button" class="on" data-pane="hero">بنر / هیرو</button>
        <button type="button" data-pane="trust">اعتماد</button>
        <button type="button" data-pane="search">جستجو و خانه</button>
        <button type="button" data-pane="edu">آموزش و معرفی</button>
        <button type="button" data-pane="corp">سازمانی</button>
        <button type="button" data-pane="pwa">نصب و نوار پایین</button>
      </div>

      <div class="hp-pane on" data-pane="hero">
        <div class="hp-card">
          <h2>بنر صفحه اول</h2>
          <p class="hp-hint">همان بنر بزرگ بالای صفحه که قبلاً در ادمین نبود چون در قالب hard-code شده بود.</p>
          <div class="hp-switch"><span>نمایش بنر</span><label class="hp-sw"><input type="checkbox" name="hero_enabled" value="1" @checked($on('hero_enabled', true))><i></i></label></div>
          <div class="hp-grid" style="margin-top:.75rem">
            <label>کیکر<input name="hero_kicker" value="{{ $f('hero_kicker') }}"></label>
            <label>عنوان اصلی<input name="hero_title" value="{{ $f('hero_title') }}"></label>
            <label>کلمه تأکید (em)<input name="hero_title_em" value="{{ $f('hero_title_em') }}"></label>
            <label>متن<textarea name="hero_text" rows="3">{{ $f('hero_text') }}</textarea></label>
            <label>تصویر بنر<input name="hero_image" value="{{ $f('hero_image') }}" dir="ltr"></label>
            <div class="row2">
              <label>دکمه ۱ — متن<input name="hero_cta1_label" value="{{ $f('hero_cta1_label') }}"></label>
              <label>دکمه ۱ — لینک سایت<input name="hero_cta1_url" value="{{ $f('hero_cta1_url') }}" dir="ltr"></label>
            </div>
            <div class="row2">
              <label>دکمه ۲ — متن<input name="hero_cta2_label" value="{{ $f('hero_cta2_label') }}"></label>
              <label>دکمه ۲ — لینک<input name="hero_cta2_url" value="{{ $f('hero_cta2_url') }}" dir="ltr"></label>
            </div>
            <label>لینک دکمه ۱ در وب‌اپ<input name="hero_webapp_cta1_url" value="{{ $f('hero_webapp_cta1_url') }}" dir="ltr"></label>
            <div class="hp-switch"><span>همگام‌سازی با وب‌اپ</span><label class="hp-sw"><input type="checkbox" name="sync_webapp" value="1" @checked($on('sync_webapp', true))><i></i></label></div>
          </div>
        </div>
      </div>

      <div class="hp-pane" data-pane="trust">
        <div class="hp-card">
          <h2>نوار اعتماد</h2>
          <div class="hp-switch"><span>نمایش</span><label class="hp-sw"><input type="checkbox" name="trust_enabled" value="1" @checked($on('trust_enabled', true))><i></i></label></div>
          <div class="hp-grid" style="margin-top:.75rem">
            @foreach([1,2,3,4] as $i)
              <div class="row2">
                <label>عنوان {{ $i }}<input name="trust_{{ $i }}_title" value="{{ $f('trust_'.$i.'_title') }}"></label>
                <label>متن {{ $i }}<input name="trust_{{ $i }}_text" value="{{ $f('trust_'.$i.'_text') }}"></label>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="hp-pane" data-pane="search">
        <div class="hp-card">
          <h2>جستجو و بلوک‌های خانه وب‌اپ</h2>
          <div class="hp-grid">
            <label>placeholder جستجو<input name="search_placeholder" value="{{ $f('search_placeholder') }}"></label>
            <div class="hp-switch"><span>جستجو</span><label class="hp-sw"><input type="checkbox" name="show_search" value="1" @checked($on('show_search', true))><i></i></label></div>
            <div class="hp-switch"><span>دسته‌ها</span><label class="hp-sw"><input type="checkbox" name="show_categories" value="1" @checked($on('show_categories', true))><i></i></label></div>
            <div class="hp-switch"><span>محصولات ویژه</span><label class="hp-sw"><input type="checkbox" name="show_featured" value="1" @checked($on('show_featured', true))><i></i></label></div>
            <div class="hp-switch"><span>لینک‌های سریع</span><label class="hp-sw"><input type="checkbox" name="show_quick_links" value="1" @checked($on('show_quick_links', true))><i></i></label></div>
            <label>عنوان محصولات ویژه<input name="featured_title" value="{{ $f('featured_title', 'محصولات ویژه') }}"></label>
          </div>
        </div>
      </div>

      <div class="hp-pane" data-pane="edu">
        <div class="hp-card">
          <h2>آموزش‌ها</h2>
          <div class="hp-switch"><span>نمایش</span><label class="hp-sw"><input type="checkbox" name="edu_enabled" value="1" @checked($on('edu_enabled', true))><i></i></label></div>
          <div class="hp-grid" style="margin-top:.75rem">
            <label>عنوان<input name="edu_title" value="{{ $f('edu_title') }}"></label>
            <label>زیرعنوان<textarea name="edu_subtitle" rows="2">{{ $f('edu_subtitle') }}</textarea></label>
            <div class="row2">
              <label>متن لینک همه<input name="edu_more_label" value="{{ $f('edu_more_label') }}"></label>
              <label>آدرس لینک همه<input name="edu_more_url" value="{{ $f('edu_more_url') }}" dir="ltr"></label>
            </div>
            @foreach([1,2,3] as $i)
              <div class="row2">
                <label>کارت {{ $i }} عنوان<input name="edu_{{ $i }}_title" value="{{ $f('edu_'.$i.'_title') }}"></label>
                <label>کارت {{ $i }} لینک<input name="edu_{{ $i }}_url" value="{{ $f('edu_'.$i.'_url') }}" dir="ltr"></label>
              </div>
              <label>کارت {{ $i }} متن<textarea name="edu_{{ $i }}_text" rows="2">{{ $f('edu_'.$i.'_text') }}</textarea></label>
              <label>کارت {{ $i }} تصویر<input name="edu_{{ $i }}_image" value="{{ $f('edu_'.$i.'_image') }}" dir="ltr"></label>
            @endforeach
          </div>
        </div>
        <div class="hp-card">
          <h2>معرفی</h2>
          <div class="hp-switch"><span>نمایش</span><label class="hp-sw"><input type="checkbox" name="about_enabled" value="1" @checked($on('about_enabled', true))><i></i></label></div>
          <div class="hp-grid" style="margin-top:.75rem">
            <label>عنوان<input name="about_title" value="{{ $f('about_title') }}"></label>
            <label>متن<textarea name="about_text" rows="4">{{ $f('about_text') }}</textarea></label>
            <label>تصویر<input name="about_image" value="{{ $f('about_image') }}" dir="ltr"></label>
            @foreach([1,2,3] as $i)
              <div class="row2">
                <label>آمار {{ $i }} عنوان<input name="about_stat{{ $i }}_title" value="{{ $f('about_stat'.$i.'_title') }}"></label>
                <label>آمار {{ $i }} متن<input name="about_stat{{ $i }}_text" value="{{ $f('about_stat'.$i.'_text') }}"></label>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="hp-pane" data-pane="corp">
        <div class="hp-card">
          <h2>سازمانی (سایت)</h2>
          <div class="hp-switch"><span>نمایش</span><label class="hp-sw"><input type="checkbox" name="corp_enabled" value="1" @checked($on('corp_enabled', true))><i></i></label></div>
          <div class="hp-grid" style="margin-top:.75rem">
            <label>عنوان<input name="corp_title" value="{{ $f('corp_title') }}"></label>
            <label>زیرعنوان<textarea name="corp_subtitle" rows="2">{{ $f('corp_subtitle') }}</textarea></label>
            @foreach([1,2,3] as $i)
              <div class="row2">
                <label>کارت {{ $i }} عنوان<input name="corp_{{ $i }}_title" value="{{ $f('corp_'.$i.'_title') }}"></label>
                <label>کارت {{ $i }} لینک<input name="corp_{{ $i }}_url" value="{{ $f('corp_'.$i.'_url') }}" dir="ltr"></label>
              </div>
              <label>کارت {{ $i }} متن<textarea name="corp_{{ $i }}_text" rows="2">{{ $f('corp_'.$i.'_text') }}</textarea></label>
              <label>کارت {{ $i }} تصویر<input name="corp_{{ $i }}_image" value="{{ $f('corp_'.$i.'_image') }}" dir="ltr"></label>
            @endforeach
            <label>عنوان CTA<input name="corp_cta_title" value="{{ $f('corp_cta_title') }}"></label>
            <label>متن CTA<textarea name="corp_cta_text" rows="2">{{ $f('corp_cta_text') }}</textarea></label>
            <div class="row2">
              <label>دکمه CTA<input name="corp_cta_label" value="{{ $f('corp_cta_label') }}"></label>
              <label>لینک CTA<input name="corp_cta_url" value="{{ $f('corp_cta_url') }}" dir="ltr"></label>
            </div>
          </div>
        </div>
        <div class="hp-card">
          <h2>کارت سازمانی وب‌اپ</h2>
          <div class="hp-grid">
            <label>عنوان<input name="webapp_corp_title" value="{{ $f('webapp_corp_title') }}"></label>
            <label>متن<textarea name="webapp_corp_text" rows="2">{{ $f('webapp_corp_text') }}</textarea></label>
            <label>تصویر<input name="webapp_corp_image" value="{{ $f('webapp_corp_image') }}" dir="ltr"></label>
            <div class="row2">
              <label>دکمه<input name="webapp_corp_cta_label" value="{{ $f('webapp_corp_cta_label') }}"></label>
              <label>لینک<input name="webapp_corp_cta_url" value="{{ $f('webapp_corp_cta_url') }}" dir="ltr"></label>
            </div>
          </div>
        </div>
        <div class="hp-card">
          <h2>برندها</h2>
          <div class="hp-switch"><span>نمایش</span><label class="hp-sw"><input type="checkbox" name="brands_enabled" value="1" @checked($on('brands_enabled', true))><i></i></label></div>
          <div class="hp-grid" style="margin-top:.75rem">
            <label>هر خط یک برند<textarea name="brands_text" rows="5">{{ $f('brands_text') }}</textarea></label>
          </div>
        </div>
      </div>

      <div class="hp-pane" data-pane="pwa">
        <div class="hp-card">
          <h2>بنر نصب و نوار پایین</h2>
          <p class="hp-hint">بنر قرمز نصب وب‌اپ و منوی پایین صفحه اول موبایل.</p>
          <div class="hp-switch"><span>بنر نصب</span><label class="hp-sw"><input type="checkbox" name="show_install_banner" value="1" @checked($on('show_install_banner', true))><i></i></label></div>
          <div class="hp-switch"><span>نوار پایین وب‌اپ</span><label class="hp-sw"><input type="checkbox" name="mobile_bottom_nav" value="1" @checked($on('mobile_bottom_nav', true))><i></i></label></div>
          <div class="hp-grid" style="margin-top:.75rem">
            <label>متن بنر نصب<input name="install_banner_text" value="{{ $f('install_banner_text') }}"></label>
          </div>
        </div>
      </div>

      <div class="hp-actions">
        <button class="btn btn-primary" type="submit">ذخیره تنظیمات آنلاین</button>
        <button class="btn" type="button" id="hp-refresh-preview">به‌روزرسانی پیش‌نمایش</button>
      </div>
    </div>

    <aside class="hp-preview">
      <div class="hp-preview-bar">
        <strong>پیش‌نمایش زنده صفحه اول</strong>
        <div class="hp-preview-tog">
          <button type="button" class="on" data-preview="app">وب‌اپ</button>
          <button type="button" data-preview="desktop">سایت</button>
        </div>
      </div>
      <div class="hp-phone" id="hp-phone">
        <iframe id="hp-frame" title="پیش‌نمایش صفحه اول" src="{{ $previewApp }}?admin_preview=1"></iframe>
      </div>
      <p class="hp-hint" style="margin-top:.65rem">بعد از ذخیره، پیش‌نمایش را رفرش کنید. همه بلوک‌های صفحه اول از همین پنل کنترل می‌شوند.</p>
    </aside>
  </div>
</form>

<script>
(function () {
  const tabs = document.getElementById('hp-tabs');
  const panes = document.querySelectorAll('.hp-pane');
  tabs?.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-pane]');
    if (!btn) return;
    tabs.querySelectorAll('button').forEach(function (b) { b.classList.toggle('on', b === btn); });
    panes.forEach(function (p) { p.classList.toggle('on', p.dataset.pane === btn.dataset.pane); });
  });
  const phone = document.getElementById('hp-phone');
  const frame = document.getElementById('hp-frame');
  const appUrl = @json($previewApp . '?admin_preview=1');
  const deskUrl = @json($previewDesktop . '?admin_preview=1');
  document.querySelectorAll('.hp-preview-tog button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.hp-preview-tog button').forEach(function (b) { b.classList.remove('on'); });
      btn.classList.add('on');
      const mode = btn.dataset.preview;
      phone.classList.toggle('desktop', mode === 'desktop');
      frame.src = (mode === 'desktop' ? deskUrl : appUrl) + '&t=' + Date.now();
    });
  });
  document.getElementById('hp-refresh-preview')?.addEventListener('click', function () {
    frame.src = frame.src.split('&t=')[0] + '&t=' + Date.now();
  });
})();
</script>
@endsection
