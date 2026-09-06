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

.hp-grid select{width:100%;border:1px solid #dbe3ef;border-radius:10px;padding:.55rem .7rem;font:inherit;background:#f8fafc}
.hp-grid .row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.65rem}
.hp-grid .row4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem}
@media(max-width:900px){.hp-grid .row3,.hp-grid .row4{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.hp-grid .row3,.hp-grid .row4{grid-template-columns:1fr}}
.hp-color{display:grid;grid-template-columns:42px 1fr;gap:.4rem;align-items:center}
.hp-color input[type=color]{width:42px;height:38px;padding:0;border:1px solid #dbe3ef;border-radius:10px;background:#fff;cursor:pointer}
.bd-wrap{border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;background:#0b1220}
.bd-bar{display:flex;flex-wrap:wrap;gap:.35rem;padding:.55rem .65rem;background:#111827;border-bottom:1px solid #1f2937}
.bd-bar button{border:1px solid #374151;background:#1f2937;color:#e5e7eb;border-radius:8px;padding:.35rem .6rem;font:inherit;font-size:.75rem;font-weight:700;cursor:pointer}
.bd-bar button.on{background:#e23d12;border-color:#e23d12;color:#fff}
.bd-canvas{position:relative;width:100%;height:var(--bd-h,420px);background:var(--bd-bg,#fff);font-family:var(--bd-font,Vazirmatn,Tahoma,sans-serif);overflow:hidden;user-select:none;touch-action:none}
.bd-canvas.is-overlay,.bd-canvas.is-free{background:#0b1220}
.bd-scrim{position:absolute;inset:0;background:var(--bd-scrim,rgba(10,15,25,.45));z-index:2;display:none;pointer-events:none}
.bd-canvas.is-overlay .bd-scrim,.bd-canvas.is-free .bd-scrim{display:block}
.bd-layer{position:absolute;box-sizing:border-box;cursor:move;border:2px solid transparent;border-radius:10px}
.bd-layer.on{border-color:#38bdf8;box-shadow:0 0 0 1px rgba(56,189,248,.35)}
.bd-handle{position:absolute;width:12px;height:12px;right:-6px;bottom:-6px;background:#38bdf8;border:2px solid #fff;border-radius:3px;cursor:nwse-resize;display:none;z-index:6}
.bd-layer.on .bd-handle{display:block}
.bd-tag{position:absolute;top:-18px;right:0;font-size:.65rem;background:#0ea5e9;color:#fff;padding:.1rem .35rem;border-radius:4px;font-weight:800}
.bd-copy{left:var(--bd-copy-x,4%);top:var(--bd-copy-y,18%);width:var(--bd-copy-w,46%);z-index:3;padding:.85rem}
.bd-copy .k{margin:0 0 .35rem;color:var(--bd-kicker,#e23d12);font-size:var(--bd-kicker-size,14px);font-weight:800}
.bd-copy h3{margin:0;color:var(--bd-title,#0b1220);font-size:var(--bd-title-size,28px);line-height:1.3}
.bd-copy h3 em{color:var(--bd-em,#e23d12);font-style:normal}
.bd-copy p{margin:.5rem 0 .75rem;color:var(--bd-text,#475569);font-size:var(--bd-text-size,14px);line-height:1.7}
.bd-cta{display:flex;gap:.4rem;flex-wrap:wrap}
.bd-cta span{display:inline-flex;align-items:center;padding:.4rem .75rem;border-radius:8px;font-size:var(--bd-cta-size,13px);font-weight:800}
.bd-cta .a{background:var(--bd-cta1-bg,#e23d12);color:var(--bd-cta1,#fff)}
.bd-cta .b{background:var(--bd-cta2-bg,#fff);color:var(--bd-cta2,#e23d12);border:1.5px solid var(--bd-cta2-border,#e23d12)}
.bd-canvas.is-overlay .bd-copy h3,.bd-canvas.is-free .bd-copy h3,.bd-canvas.is-overlay .bd-copy p,.bd-canvas.is-free .bd-copy p{color:#fff}
.bd-canvas.is-overlay .bd-copy .k,.bd-canvas.is-free .bd-copy .k{color:#ffd7cb}
.bd-media{left:var(--bd-media-x,52%);top:var(--bd-media-y,0%);width:var(--bd-media-w,48%);height:var(--bd-media-h,100%);z-index:1;overflow:hidden;border-radius:12px;background:#e2e8f0}
.bd-canvas.is-overlay .bd-media{inset:0;left:0;top:0;width:100%;height:100%;border-radius:0}
.bd-media img,.bd-merge img{width:100%;height:100%;display:block;pointer-events:none}
.bd-media img{object-fit:var(--bd-fit,cover);object-position:var(--bd-pos,center)}
.bd-merge{left:var(--bd-merge-x,58%);top:var(--bd-merge-y,12%);width:var(--bd-merge-w,36%);height:var(--bd-merge-h,70%);z-index:4;opacity:var(--bd-merge-op,.7);mix-blend-mode:var(--bd-merge-blend,overlay)}
.bd-merge img{object-fit:contain}
.bd-canvas.is-stacked .bd-media{left:8%;top:8%;width:84%;height:42%}
.bd-canvas.is-stacked .bd-copy{left:8%;top:54%;width:84%}

</style>

<div class="hp-head">
  <div>
    <h1>تنظیمات آنلاین صفحه اول و طراحی بنر</h1>
    <p>سایز، فونت، رنگ، درگ‌ودراپ متن/منو/عکس، ریسایز بنر و ادغام تصویر — به‌همراه همه بلوک‌های صفحه اول.</p>
  </div>
  <div style="display:flex;gap:.45rem;flex-wrap:wrap">
    <a class="btn" href="{{ url('/') }}" target="_blank" rel="noopener">صفحه اول سایت</a>
    <a class="btn" href="{{ url('/app') }}" target="_blank" rel="noopener">خانه وب‌اپ</a>
    <a class="btn" href="{{ url('/admin/mega-menu') }}">مگامنو</a>
    <a class="btn" href="{{ url('/admin/footer-settings') }}">فوتر</a>
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
        <button type="button" class="on" data-pane="design">طراحی بنر</button>
        <button type="button" data-pane="hero">متن بنر</button>
        <button type="button" data-pane="trust">اعتماد</button>
        <button type="button" data-pane="search">جستجو و خانه</button>
        <button type="button" data-pane="edu">آموزش و معرفی</button>
        <button type="button" data-pane="corp">سازمانی</button>
        <button type="button" data-pane="menus">منوها</button>
        <button type="button" data-pane="pwa">نصب و نوار پایین</button>
      </div>


      <div class="hp-pane on" data-pane="design">
        <div class="hp-card">
          <h2>کانوس طراحی (درگ / ریسایز / ادغام)</h2>
          <p class="hp-hint">لایه متن، عکس اصلی و عکس ادغامی را بکشید. گوشه پایین‌راست برای ریسایز است. برای آزادی کامل، چیدمان «آزاد» را انتخاب کنید.</p>
          <div class="bd-wrap">
            <div class="bd-bar">
              <button type="button" class="on" data-layer-pick="copy">متن</button>
              <button type="button" data-layer-pick="media">عکس بنر</button>
              <button type="button" data-layer-pick="merge">عکس ادغام</button>
              <button type="button" id="bd-reset-pos">بازنشانی موقعیت</button>
            </div>
            <div class="bd-canvas is-split" id="bd-canvas" data-asset="{{ asset('') }}">
              <div class="bd-scrim"></div>
              <div class="bd-layer bd-media on" data-layer="media" id="bd-media">
                <span class="bd-tag">عکس</span>
                <img id="bd-media-img" alt="">
                <i class="bd-handle" data-resize="media"></i>
              </div>
              <div class="bd-layer bd-merge" data-layer="merge" id="bd-merge" hidden>
                <span class="bd-tag">ادغام</span>
                <img id="bd-merge-img" alt="">
                <i class="bd-handle" data-resize="merge"></i>
              </div>
              <div class="bd-layer bd-copy" data-layer="copy" id="bd-copy">
                <span class="bd-tag">متن</span>
                <div class="k" id="bd-kicker">کیکر</div>
                <h3 id="bd-title">عنوان</h3>
                <p id="bd-text">متن</p>
                <div class="bd-cta"><span class="a" id="bd-cta1">دکمه ۱</span><span class="b" id="bd-cta2">دکمه ۲</span></div>
                <i class="bd-handle" data-resize="copy"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="hp-card">
          <h2>سایز و چیدمان</h2>
          <div class="hp-grid">
            <label>چیدمان
              <select name="hero_layout" id="hero_layout">
                <option value="split-rtl" @selected($f('hero_layout','split-rtl')==='split-rtl')>دو ستونه (متن راست)</option>
                <option value="split-ltr" @selected($f('hero_layout')==='split-ltr')>دو ستونه (عکس راست)</option>
                <option value="overlay" @selected($f('hero_layout')==='overlay')>پوشش کامل + متن روی عکس</option>
                <option value="stacked" @selected($f('hero_layout')==='stacked')>ستونی</option>
                <option value="free" @selected($f('hero_layout')==='free')>آزاد (درگ و ریسایز)</option>
              </select>
            </label>
            <div class="row4">
              <label>ارتفاع بنر (px)<input type="number" min="220" max="720" name="hero_height" id="hero_height" value="{{ $f('hero_height',420) }}"></label>
              <label>گردی گوشه<input type="number" min="0" max="40" name="hero_radius" id="hero_radius" value="{{ $f('hero_radius',22) }}"></label>
              <label>پدینگ عمودی<input type="number" min="0" max="80" name="hero_pad_y" id="hero_pad_y" value="{{ $f('hero_pad_y',24) }}"></label>
              <label>پدینگ افقی<input type="number" min="0" max="80" name="hero_pad_x" id="hero_pad_x" value="{{ $f('hero_pad_x',8) }}"></label>
            </div>
            <div class="row2">
              <label>برش عکس
                <select name="hero_image_fit" id="hero_image_fit">
                  <option value="cover" @selected($f('hero_image_fit','cover')==='cover')>پوشش (cover)</option>
                  <option value="contain" @selected($f('hero_image_fit')==='contain')>کامل (contain)</option>
                </select>
              </label>
              <label>جایگاه عکس
                <select name="hero_image_pos" id="hero_image_pos">
                  @foreach(['center'=>'وسط','top'=>'بالا','bottom'=>'پایین','left'=>'چپ','right'=>'راست'] as $val=>$lab)
                    <option value="{{ $val }}" @selected($f('hero_image_pos','center')===$val)>{{ $lab }}</option>
                  @endforeach
                </select>
              </label>
            </div>
          </div>
        </div>

        <div class="hp-card">
          <h2>فونت و سایز متن</h2>
          <div class="hp-grid">
            <label>فونت بنر
              <select name="hero_font" id="hero_font">
                @foreach(['Vazirmatn','Estedad','IRANSansX','Dana','system-ui'] as $font)
                  <option value="{{ $font }}" @selected($f('hero_font','Vazirmatn')===$font)>{{ $font }}</option>
                @endforeach
              </select>
            </label>
            <div class="row4">
              <label>کیکر<input type="number" min="10" max="28" name="hero_kicker_size" id="hero_kicker_size" value="{{ $f('hero_kicker_size',14) }}"></label>
              <label>عنوان<input type="number" min="18" max="64" name="hero_title_size" id="hero_title_size" value="{{ $f('hero_title_size',34) }}"></label>
              <label>متن<input type="number" min="11" max="28" name="hero_text_size" id="hero_text_size" value="{{ $f('hero_text_size',15) }}"></label>
              <label>دکمه‌ها<input type="number" min="11" max="24" name="hero_cta_size" id="hero_cta_size" value="{{ $f('hero_cta_size',14) }}"></label>
            </div>
          </div>
        </div>

        <div class="hp-card">
          <h2>رنگ‌بندی</h2>
          <div class="hp-grid">
            <div class="row3">
              @foreach([
                'hero_bg' => ['پس‌زمینه','#ffffff'],
                'hero_kicker_color' => ['کیکر','#e23d12'],
                'hero_title_color' => ['عنوان','#0b1220'],
                'hero_em_color' => ['تأکید','#e23d12'],
                'hero_text_color' => ['متن','#475569'],
                'hero_cta1_bg' => ['دکمه۱ پس‌زمینه','#e23d12'],
                'hero_cta1_color' => ['دکمه۱ متن','#ffffff'],
                'hero_cta2_bg' => ['دکمه۲ پس‌زمینه','#ffffff'],
                'hero_cta2_color' => ['دکمه۲ متن','#e23d12'],
                'hero_cta2_border' => ['دکمه۲ حاشیه','#e23d12'],
                'hero_overlay_color' => ['پوشش تیره','#0a0f19'],
              ] as $name => [$lab, $def])
                <label>{{ $lab }}
                  <span class="hp-color">
                    <input type="color" data-color-for="{{ $name }}" value="{{ $f($name, $def) }}">
                    <input name="{{ $name }}" id="{{ $name }}" value="{{ $f($name, $def) }}" dir="ltr">
                  </span>
                </label>
              @endforeach
              <label>شفافیت پوشش (%)
                <input type="number" min="0" max="90" name="hero_overlay_opacity" id="hero_overlay_opacity" value="{{ $f('hero_overlay_opacity',55) }}">
              </label>
            </div>
          </div>
        </div>

        <div class="hp-card">
          <h2>موقعیت لایه‌ها (٪)</h2>
          <div class="hp-grid">
            <div class="row4">
              <label>متن X<input type="number" min="0" max="80" name="hero_copy_x" id="hero_copy_x" value="{{ $f('hero_copy_x',4) }}"></label>
              <label>متن Y<input type="number" min="0" max="70" name="hero_copy_y" id="hero_copy_y" value="{{ $f('hero_copy_y',18) }}"></label>
              <label>عرض متن<input type="number" min="20" max="80" name="hero_copy_w" id="hero_copy_w" value="{{ $f('hero_copy_w',46) }}"></label>
              <label></label>
            </div>
            <div class="row4">
              <label>عکس X<input type="number" min="0" max="80" name="hero_media_x" id="hero_media_x" value="{{ $f('hero_media_x',52) }}"></label>
              <label>عکس Y<input type="number" min="0" max="70" name="hero_media_y" id="hero_media_y" value="{{ $f('hero_media_y',0) }}"></label>
              <label>عرض عکس<input type="number" min="20" max="80" name="hero_media_w" id="hero_media_w" value="{{ $f('hero_media_w',48) }}"></label>
              <label>ارتفاع عکس<input type="number" min="30" max="100" name="hero_media_h" id="hero_media_h" value="{{ $f('hero_media_h',100) }}"></label>
            </div>
          </div>
        </div>

        <div class="hp-card">
          <h2>ادغام عکس روی بنر</h2>
          <div class="hp-switch"><span>فعال‌سازی ادغام عکس</span><label class="hp-sw"><input type="checkbox" name="hero_merge_enabled" id="hero_merge_enabled" value="1" @checked($on('hero_merge_enabled', false))><i></i></label></div>
          <div class="hp-grid" style="margin-top:.75rem">
            <label>مسیر عکس ادغامی<input name="hero_merge_image" id="hero_merge_image" value="{{ $f('hero_merge_image') }}" dir="ltr" placeholder="images/home/merge.png"></label>
            <div class="row2">
              <label>شفافیت (%)<input type="number" min="0" max="100" name="hero_merge_opacity" id="hero_merge_opacity" value="{{ $f('hero_merge_opacity',70) }}"></label>
              <label>حالت ترکیب
                <select name="hero_merge_blend" id="hero_merge_blend">
                  @foreach(['overlay','soft-light','multiply','screen','normal'] as $b)
                    <option value="{{ $b }}" @selected($f('hero_merge_blend','overlay')===$b)>{{ $b }}</option>
                  @endforeach
                </select>
              </label>
            </div>
            <div class="row4">
              <label>X<input type="number" min="0" max="90" name="hero_merge_x" id="hero_merge_x" value="{{ $f('hero_merge_x',58) }}"></label>
              <label>Y<input type="number" min="0" max="90" name="hero_merge_y" id="hero_merge_y" value="{{ $f('hero_merge_y',12) }}"></label>
              <label>عرض<input type="number" min="10" max="90" name="hero_merge_w" id="hero_merge_w" value="{{ $f('hero_merge_w',36) }}"></label>
              <label>ارتفاع<input type="number" min="10" max="100" name="hero_merge_h" id="hero_merge_h" value="{{ $f('hero_merge_h',70) }}"></label>
            </div>
          </div>
        </div>
      </div>

      <div class="hp-pane" data-pane="hero">
        <div class="hp-card">
          <h2>بنر صفحه اول</h2>
          <p class="hp-hint">همان بنر بزرگ بالای صفحه که قبلاً در ادمین نبود چون در قالب hard-code شده بود.</p>
          <div class="hp-switch"><span>نمایش بنر</span><label class="hp-sw"><input type="checkbox" name="hero_enabled" value="1" @checked($on('hero_enabled', true))><i></i></label></div>
          <div class="hp-grid" style="margin-top:.75rem">
            <label>کیکر<input name="hero_kicker" id="hero_kicker" value="{{ $f('hero_kicker') }}"></label>
            <label>عنوان اصلی<input name="hero_title" id="hero_title" value="{{ $f('hero_title') }}"></label>
            <label>کلمه تأکید (em)<input name="hero_title_em" id="hero_title_em" value="{{ $f('hero_title_em') }}"></label>
            <label>متن<textarea name="hero_text" id="hero_text" rows="3">{{ $f('hero_text') }}</textarea></label>
            <label>تصویر بنر<input name="hero_image" id="hero_image" value="{{ $f('hero_image') }}" dir="ltr"></label>
            <div class="row2">
              <label>دکمه ۱ — متن<input name="hero_cta1_label" id="hero_cta1_label" value="{{ $f('hero_cta1_label') }}"></label>
              <label>دکمه ۱ — لینک سایت<input name="hero_cta1_url" value="{{ $f('hero_cta1_url') }}" dir="ltr"></label>
            </div>
            <div class="row2">
              <label>دکمه ۲ — متن<input name="hero_cta2_label" id="hero_cta2_label" value="{{ $f('hero_cta2_label') }}"></label>
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


      <div class="hp-pane" data-pane="menus">
        <div class="hp-card">
          <h2>بررسی و به‌روزرسانی منوها</h2>
          <p class="hp-hint">مسیرهای بنر و صفحه اول یکپارچه شده‌اند. ساختار منوها را از لینک‌های زیر مدیریت کنید.</p>
          <div class="hp-map">
            <div><b>طراحی بنر / صفحه اول</b><span>همین صفحه</span></div>
            <div><b>مگامنو هدر</b><span><a href="{{ url('/admin/mega-menu') }}">/admin/mega-menu</a></span></div>
            <div><b>فوتر و لینک‌ها</b><span><a href="{{ url('/admin/footer-settings') }}">/admin/footer-settings</a></span></div>
            <div><b>استودیو قالب</b><span><a href="{{ url('/admin/theme-builder') }}">/admin/theme-builder</a></span></div>
            <div><b>نوار پایین وب‌اپ</b><span><a href="{{ url('/admin/web-app') }}">/admin/web-app</a></span></div>
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

  document.querySelectorAll('[data-color-for]').forEach(function (picker) {
    const target = document.getElementById(picker.dataset.colorFor);
    if (!target) return;
    picker.addEventListener('input', function () { target.value = picker.value; syncCanvas(); });
    target.addEventListener('input', function () {
      if (/^#[0-9a-fA-F]{6}$/.test(target.value)) picker.value = target.value;
      syncCanvas();
    });
  });

  const canvas = document.getElementById('bd-canvas');
  if (!canvas) return;
  const assetBase = (canvas.dataset.asset || '').replace(/\/$/, '');
  const el = function (id) { return document.getElementById(id); };
  const val = function (id, fallback) {
    const n = el(id);
    if (!n) return fallback;
    if (n.type === 'checkbox') return n.checked;
    return n.value === '' || n.value == null ? fallback : n.value;
  };
  const setVal = function (id, v) { const n = el(id); if (n) n.value = String(v); };

  function imgUrl(path) {
    path = String(path || '').trim();
    if (!path) return '';
    if (/^(https?:)?\/\//i.test(path) || path.indexOf('data:') === 0) return path;
    return assetBase + '/' + path.replace(/^\//, '');
  }
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
    });
  }
  function titleHtml(title, em) {
    title = String(title || ''); em = String(em || '').trim();
    if (em && title.indexOf(em) !== -1) {
      const parts = title.split(em);
      return escapeHtml(parts[0]) + '<em>' + escapeHtml(em) + '</em>' + escapeHtml(parts.slice(1).join(em));
    }
    return escapeHtml(title);
  }
  function hexToRgba(hex, op) {
    hex = String(hex || '#0a0f19').replace('#', '');
    if (hex.length !== 6) hex = '0a0f19';
    return 'rgba(' + parseInt(hex.slice(0,2),16) + ',' + parseInt(hex.slice(2,4),16) + ',' + parseInt(hex.slice(4,6),16) + ',' + op + ')';
  }

  function syncCanvas() {
    const layout = val('hero_layout', 'split-rtl');
    canvas.classList.remove('is-split','is-overlay','is-free','is-stacked');
    if (layout === 'overlay') canvas.classList.add('is-overlay');
    else if (layout === 'free') canvas.classList.add('is-free');
    else if (layout === 'stacked') canvas.classList.add('is-stacked');
    else canvas.classList.add('is-split');

    const h = Number(val('hero_height', 420)) || 420;
    canvas.style.setProperty('--bd-h', h + 'px');
    canvas.style.height = h + 'px';
    canvas.style.borderRadius = (Number(val('hero_radius', 22)) || 22) + 'px';
    canvas.style.setProperty('--bd-bg', val('hero_bg', '#ffffff'));
    const font = val('hero_font', 'Vazirmatn');
    canvas.style.setProperty('--bd-font', font === 'system-ui' ? 'system-ui,Tahoma,sans-serif' : ("'" + font + "', Vazirmatn, Tahoma, sans-serif"));
    canvas.style.setProperty('--bd-kicker-size', val('hero_kicker_size', 14) + 'px');
    canvas.style.setProperty('--bd-title-size', val('hero_title_size', 34) + 'px');
    canvas.style.setProperty('--bd-text-size', val('hero_text_size', 15) + 'px');
    canvas.style.setProperty('--bd-cta-size', val('hero_cta_size', 14) + 'px');
    canvas.style.setProperty('--bd-kicker', val('hero_kicker_color', '#e23d12'));
    canvas.style.setProperty('--bd-title', val('hero_title_color', '#0b1220'));
    canvas.style.setProperty('--bd-em', val('hero_em_color', '#e23d12'));
    canvas.style.setProperty('--bd-text', val('hero_text_color', '#475569'));
    canvas.style.setProperty('--bd-cta1-bg', val('hero_cta1_bg', '#e23d12'));
    canvas.style.setProperty('--bd-cta1', val('hero_cta1_color', '#ffffff'));
    canvas.style.setProperty('--bd-cta2-bg', val('hero_cta2_bg', '#ffffff'));
    canvas.style.setProperty('--bd-cta2', val('hero_cta2_color', '#e23d12'));
    canvas.style.setProperty('--bd-cta2-border', val('hero_cta2_border', '#e23d12'));
    canvas.style.setProperty('--bd-scrim', hexToRgba(val('hero_overlay_color', '#0a0f19'), (Number(val('hero_overlay_opacity', 55)) || 55) / 100));
    canvas.style.setProperty('--bd-fit', val('hero_image_fit', 'cover'));
    canvas.style.setProperty('--bd-pos', val('hero_image_pos', 'center'));
    canvas.style.setProperty('--bd-copy-x', val('hero_copy_x', 4) + '%');
    canvas.style.setProperty('--bd-copy-y', val('hero_copy_y', 18) + '%');
    canvas.style.setProperty('--bd-copy-w', val('hero_copy_w', 46) + '%');
    canvas.style.setProperty('--bd-media-x', val('hero_media_x', 52) + '%');
    canvas.style.setProperty('--bd-media-y', val('hero_media_y', 0) + '%');
    canvas.style.setProperty('--bd-media-w', val('hero_media_w', 48) + '%');
    canvas.style.setProperty('--bd-media-h', val('hero_media_h', 100) + '%');
    canvas.style.setProperty('--bd-merge-x', val('hero_merge_x', 58) + '%');
    canvas.style.setProperty('--bd-merge-y', val('hero_merge_y', 12) + '%');
    canvas.style.setProperty('--bd-merge-w', val('hero_merge_w', 36) + '%');
    canvas.style.setProperty('--bd-merge-h', val('hero_merge_h', 70) + '%');
    canvas.style.setProperty('--bd-merge-op', (Number(val('hero_merge_opacity', 70)) || 70) / 100);
    canvas.style.setProperty('--bd-merge-blend', val('hero_merge_blend', 'overlay'));

    el('bd-kicker').textContent = val('hero_kicker', 'کیکر');
    el('bd-title').innerHTML = titleHtml(val('hero_title', 'عنوان'), val('hero_title_em', ''));
    el('bd-text').textContent = val('hero_text', '');
    el('bd-cta1').textContent = val('hero_cta1_label', 'دکمه ۱');
    el('bd-cta2').textContent = val('hero_cta2_label', 'دکمه ۲');
    const mediaSrc = imgUrl(val('hero_image', 'images/home/hero.jpg'));
    if (mediaSrc) el('bd-media-img').src = mediaSrc;
    const mergeOn = !!el('hero_merge_enabled')?.checked;
    const mergeSrc = imgUrl(val('hero_merge_image', ''));
    const mergeEl = el('bd-merge');
    if (mergeOn && mergeSrc) { mergeEl.hidden = false; el('bd-merge-img').src = mergeSrc; }
    else { mergeEl.hidden = true; }
  }

  ['hero_layout','hero_height','hero_radius','hero_pad_y','hero_pad_x','hero_font','hero_kicker_size','hero_title_size','hero_text_size','hero_cta_size',
   'hero_overlay_opacity','hero_image_fit','hero_image_pos','hero_copy_x','hero_copy_y','hero_copy_w','hero_media_x','hero_media_y','hero_media_w','hero_media_h',
   'hero_merge_opacity','hero_merge_blend','hero_merge_x','hero_merge_y','hero_merge_w','hero_merge_h','hero_kicker','hero_title','hero_title_em','hero_text',
   'hero_image','hero_cta1_label','hero_cta2_label','hero_merge_image','hero_merge_enabled',
   'hero_bg','hero_kicker_color','hero_title_color','hero_em_color','hero_text_color','hero_cta1_bg','hero_cta1_color','hero_cta2_bg','hero_cta2_color','hero_cta2_border','hero_overlay_color'
  ].forEach(function (id) {
    const n = el(id);
    if (!n) return;
    n.addEventListener('input', syncCanvas);
    n.addEventListener('change', syncCanvas);
  });

  let active = 'copy';
  function selectLayer(name) {
    active = name;
    document.querySelectorAll('.bd-layer').forEach(function (l) { l.classList.toggle('on', l.dataset.layer === name); });
    document.querySelectorAll('[data-layer-pick]').forEach(function (b) { b.classList.toggle('on', b.dataset.layerPick === name); });
  }
  document.querySelectorAll('[data-layer-pick]').forEach(function (b) {
    b.addEventListener('click', function () { selectLayer(b.dataset.layerPick); });
  });

  let drag = null;
  function pct(n, max) { return Math.max(0, Math.min(max, Math.round(n))); }
  function startDrag(e, layer) {
    const map = {
      copy: { x:'hero_copy_x', y:'hero_copy_y', xmax:80, ymax:70 },
      media: { x:'hero_media_x', y:'hero_media_y', xmax:80, ymax:70 },
      merge: { x:'hero_merge_x', y:'hero_merge_y', xmax:90, ymax:90 }
    }[layer];
    if (!map) return;
    const rect = canvas.getBoundingClientRect();
    drag = { mode:'move', map:map, startX:e.clientX, startY:e.clientY, ox:Number(val(map.x,0)), oy:Number(val(map.y,0)), rw:rect.width, rh:rect.height };
    e.preventDefault();
  }
  function startResize(e, layer) {
    const map = {
      copy: { w:'hero_copy_w', h:null, wmax:80 },
      media: { w:'hero_media_w', h:'hero_media_h', wmax:80, hmax:100 },
      merge: { w:'hero_merge_w', h:'hero_merge_h', wmax:90, hmax:100 }
    }[layer];
    if (!map) return;
    const rect = canvas.getBoundingClientRect();
    drag = { mode:'resize', map:map, startX:e.clientX, startY:e.clientY, ow:Number(val(map.w,40)), oh:map.h ? Number(val(map.h,40)) : 0, rw:rect.width, rh:rect.height };
    e.preventDefault();
  }
  document.querySelectorAll('.bd-layer').forEach(function (layer) {
    layer.addEventListener('pointerdown', function (e) {
      if (e.target.classList.contains('bd-handle')) return;
      selectLayer(layer.dataset.layer);
      startDrag(e, layer.dataset.layer);
    });
  });
  document.querySelectorAll('.bd-handle').forEach(function (h) {
    h.addEventListener('pointerdown', function (e) {
      e.stopPropagation();
      selectLayer(h.dataset.resize);
      startResize(e, h.dataset.resize);
    });
  });
  window.addEventListener('pointermove', function (e) {
    if (!drag) return;
    const dx = ((e.clientX - drag.startX) / drag.rw) * 100;
    const dy = ((e.clientY - drag.startY) / drag.rh) * 100;
    if (drag.mode === 'move') {
      setVal(drag.map.x, pct(drag.ox + dx, drag.map.xmax));
      setVal(drag.map.y, pct(drag.oy + dy, drag.map.ymax));
    } else {
      setVal(drag.map.w, Math.max(10, pct(drag.ow + dx, drag.map.wmax)));
      if (drag.map.h) setVal(drag.map.h, Math.max(10, pct(drag.oh + dy, drag.map.hmax)));
    }
    syncCanvas();
  });
  window.addEventListener('pointerup', function () { drag = null; });
  window.addEventListener('pointercancel', function () { drag = null; });

  el('bd-reset-pos')?.addEventListener('click', function () {
    setVal('hero_copy_x',4); setVal('hero_copy_y',18); setVal('hero_copy_w',46);
    setVal('hero_media_x',52); setVal('hero_media_y',0); setVal('hero_media_w',48); setVal('hero_media_h',100);
    setVal('hero_merge_x',58); setVal('hero_merge_y',12); setVal('hero_merge_w',36); setVal('hero_merge_h',70);
    syncCanvas();
  });

  selectLayer('copy');
  syncCanvas();
})();
</script>
@endsection
