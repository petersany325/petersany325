@extends('layouts.admin')

@section('title', 'استودیو هیرو مدرن')

@section('content')
@php
  $h = $home ?? [];
  $v = fn (string $key, $default = '') => old($key, $h[$key] ?? $default);
  $on = fn (string $key, bool $default = true) => (bool) old($key, array_key_exists($key, $h) ? $h[$key] : $default);
  $layouts = [
    'split-rtl' => 'دو ستونه — متن راست',
    'split-ltr' => 'دو ستونه — متن چپ',
    'overlay' => 'تصویر تمام‌عرض + متن روی تصویر',
    'stacked' => 'چیدمان عمودی (موبایل‌اول)',
  ];
  $layout = (string) $v('hero_layout', 'split-rtl');
  $image = (string) $v('hero_image', 'images/home/hero.jpg');
  $imageUrl = str_starts_with($image, 'http') ? $image : asset(ltrim($image, '/'));
@endphp

<style>
.hs{--ink:#0b1220;--muted:#64748b;--line:#e2e8f0;--brand:#e23d12;font-family:Vazirmatn,Tahoma,sans-serif}
.hs-top{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}
.hs-top h1{margin:0;font-size:1.35rem;color:var(--ink)}
.hs-top p{margin:.35rem 0 0;color:var(--muted);font-size:.9rem;max-width:44rem;line-height:1.7}
.hs-links{display:flex;gap:.45rem;flex-wrap:wrap}
.hs-note{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:12px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.88rem;line-height:1.7}
.hs-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:1rem;align-items:start}
@media(max-width:980px){.hs-grid{grid-template-columns:1fr}}
.hs-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:1rem;margin-bottom:.85rem}
.hs-card h2{margin:0 0 .75rem;font-size:1rem}
.hs-fields{display:grid;gap:.65rem}
.hs-fields label{display:grid;gap:.28rem;font-size:.82rem;font-weight:700}
.hs-fields input,.hs-fields textarea,.hs-fields select{width:100%;border:1px solid #dbe3ef;border-radius:10px;padding:.55rem .7rem;font:inherit;background:#f8fafc}
.hs-2{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}
@media(max-width:640px){.hs-2{grid-template-columns:1fr}}
.hs-switch{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.55rem 0;border-bottom:1px dashed #eef2f7}
.hs-switch:last-child{border-bottom:0}
.hs-tog{position:relative;width:46px;height:26px;flex:0 0 auto}
.hs-tog input{opacity:0;width:0;height:0}
.hs-tog i{position:absolute;inset:0;background:#cbd5e1;border-radius:999px;transition:.2s}
.hs-tog i:before{content:"";position:absolute;width:20px;height:20px;right:3px;top:3px;background:#fff;border-radius:50%;transition:.2s}
.hs-tog input:checked + i{background:var(--brand)}
.hs-tog input:checked + i:before{transform:translateX(-20px)}
.hs-presets{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}
.hs-presets button{border:1px solid var(--line);background:#fff;border-radius:12px;padding:.7rem;text-align:right;cursor:pointer;font:inherit}
.hs-presets button strong{display:block;font-size:.85rem;margin-bottom:.2rem}
.hs-presets button span{font-size:.75rem;color:var(--muted);line-height:1.5}
.hs-presets button.on{border-color:var(--brand);box-shadow:0 0 0 2px rgba(226,61,18,.12)}
.hs-actions{display:flex;gap:.5rem;flex-wrap:wrap;position:sticky;bottom:.75rem;background:rgba(248,250,252,.94);backdrop-filter:blur(8px);padding:.65rem;border:1px solid var(--line);border-radius:12px}
.hs-preview{position:sticky;top:1rem}
.hs-frame{border:1px solid var(--line);border-radius:16px;overflow:hidden;background:#0b1220;aspect-ratio:16/10;position:relative}
.hs-frame img{width:100%;height:100%;object-fit:cover;opacity:.5}
.hs-copy{position:absolute;inset:auto 8% 12% 8%;z-index:2;color:#fff}
.hs-copy .k{font-size:.75rem;font-weight:800;color:#fdba8c;margin:0 0 .35rem}
.hs-copy h3{margin:0;font-size:1.2rem;line-height:1.35}
.hs-copy p{margin:.45rem 0 .7rem;font-size:.85rem;opacity:.92;line-height:1.6}
.hs-copy .cta{display:inline-flex;background:var(--brand);color:#fff;border-radius:10px;padding:.45rem .8rem;font-size:.8rem;font-weight:800}
.hs-alert{padding:.75rem 1rem;border-radius:12px;margin-bottom:1rem}
.hs-ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.hs-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
</style>

<div class="hs">
  <div class="hs-top">
    <div>
      <h1>استودیو هیرو مدرن</h1>
      <p>جایگزین بنرساز قدیمی Revolution. یک پیام، یک تصویر، یک یا دو دکمه — سریع، پایدار، مناسب موبایل.</p>
    </div>
    <div class="hs-links">
      <a class="btn" href="{{ $previewDesktop }}" target="_blank" rel="noopener">سایت</a>
      <a class="btn" href="{{ $previewApp }}" target="_blank" rel="noopener">وب‌اپ</a>
      <a class="btn" href="{{ url('/admin/homepage-settings') }}">سایر بلوک‌های صفحه اول</a>
    </div>
  </div>

  <div class="hs-note">
    بنرساز لایه‌ای Revolution از مسیر ادمین به این استودیو هدایت می‌شود تا خطای ۵۰۰ هنگام تغییر گزینه تکرار نشود.
    فروشگاه‌های مدرن معمولاً به‌جای اسلایدر سنگین، هیرو ثابت با یک CTA دارند.
  </div>

  @if(session('success'))
    <div class="hs-alert hs-ok">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="hs-alert hs-err">{{ session('error') }}</div>
  @endif

  <form method="post" action="{{ url('/admin/hero-studio') }}">
    @csrf
    <div class="hs-grid">
      <div>
        <div class="hs-card">
          <h2>وضعیت</h2>
          <div class="hs-switch">
            <span>نمایش هیرو</span>
            <label class="hs-tog"><input type="checkbox" name="hero_enabled" value="1" @checked($on('hero_enabled', true))><i></i></label>
          </div>
          <div class="hs-switch">
            <span>موتور قدیمی Revolution (پیشنهاد: خاموش)</span>
            <label class="hs-tog"><input type="checkbox" name="use_legacy_banner" value="1" @checked(! empty($useLegacyBanner))><i></i></label>
          </div>
        </div>

        <div class="hs-card">
          <h2>چیدمان</h2>
          <div class="hs-presets" id="hs-layouts">
            @foreach($layouts as $key => $label)
              <button type="button" data-layout="{{ $key }}" class="{{ $layout === $key ? 'on' : '' }}">
                <strong>{{ $label }}</strong>
                <span>{{ $key }}</span>
              </button>
            @endforeach
          </div>
          <input type="hidden" name="hero_layout" id="hs-layout-input" value="{{ $layout }}">
        </div>

        <div class="hs-card">
          <h2>محتوا</h2>
          <div class="hs-fields">
            <label>کیکر<input name="hero_kicker" id="hs-kicker" value="{{ $v('hero_kicker') }}"></label>
            <label>عنوان<input name="hero_title" id="hs-title" value="{{ $v('hero_title') }}" required></label>
            <label>کلمهٔ برجسته<input name="hero_title_em" value="{{ $v('hero_title_em') }}"></label>
            <label>توضیح<textarea name="hero_text" id="hs-text" rows="3">{{ $v('hero_text') }}</textarea></label>
            <label>تصویر هیرو<input name="hero_image" id="hs-image" value="{{ $v('hero_image') }}" dir="ltr" placeholder="images/home/hero.jpg"></label>
            <div class="hs-2">
              <label>متن دکمه ۱<input name="hero_cta1_label" id="hs-cta" value="{{ $v('hero_cta1_label') }}"></label>
              <label>لینک دکمه ۱<input name="hero_cta1_url" value="{{ $v('hero_cta1_url') }}" dir="ltr"></label>
            </div>
            <div class="hs-2">
              <label>متن دکمه ۲<input name="hero_cta2_label" value="{{ $v('hero_cta2_label') }}"></label>
              <label>لینک دکمه ۲<input name="hero_cta2_url" value="{{ $v('hero_cta2_url') }}" dir="ltr"></label>
            </div>
            <label>لینک وب‌اپ<input name="hero_webapp_cta1_url" value="{{ $v('hero_webapp_cta1_url') }}" dir="ltr"></label>
          </div>
        </div>

        <div class="hs-card">
          <h2>ظاهر</h2>
          <div class="hs-fields">
            <div class="hs-2">
              <label>ارتفاع (px)<input type="number" name="hero_height" min="220" max="720" value="{{ $v('hero_height', 420) }}"></label>
              <label>شعاع گوشه<input type="number" name="hero_radius" min="0" max="40" value="{{ $v('hero_radius', 22) }}"></label>
            </div>
            <div class="hs-2">
              <label>فونت
                <select name="hero_font">
                  @foreach(['Vazirmatn','Estedad','IRANSansX','Dana','system-ui'] as $font)
                    <option value="{{ $font }}" @selected($v('hero_font', 'Vazirmatn') === $font)>{{ $font }}</option>
                  @endforeach
                </select>
              </label>
              <label>اندازه عنوان<input type="number" name="hero_title_size" min="18" max="64" value="{{ $v('hero_title_size', 34) }}"></label>
            </div>
            <div class="hs-2">
              <label>رنگ عنوان<input type="color" name="hero_title_color" value="{{ $v('hero_title_color', '#0b1220') }}"></label>
              <label>رنگ دکمه ۱<input type="color" name="hero_cta1_bg" value="{{ $v('hero_cta1_bg', '#e23d12') }}"></label>
            </div>
          </div>
        </div>

        <div class="hs-actions">
          <button class="btn btn-primary" type="submit">ذخیره هیرو</button>
          <a class="btn" href="{{ url('/admin/homepage-settings') }}">بلوک‌های صفحه اول</a>
        </div>
      </div>

      <aside class="hs-preview">
        <div class="hs-card">
          <h2>پیش‌نمایش</h2>
          <div class="hs-frame">
            <img id="hs-preview-img" src="{{ $imageUrl }}" alt="">
            <div class="hs-copy">
              <p class="k" id="hs-preview-kicker">{{ $v('hero_kicker') }}</p>
              <h3 id="hs-preview-title">{{ $v('hero_title') }}</h3>
              <p id="hs-preview-text">{{ $v('hero_text') }}</p>
              <span class="cta" id="hs-preview-cta">{{ $v('hero_cta1_label', 'ورود به فروشگاه') }}</span>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </form>
</div>

<script>
(function () {
  var input = document.getElementById('hs-layout-input');
  document.querySelectorAll('#hs-layouts button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('#hs-layouts button').forEach(function (b) { b.classList.remove('on'); });
      btn.classList.add('on');
      input.value = btn.getAttribute('data-layout');
    });
  });
  function live(id, target, asSrc) {
    var el = document.getElementById(id);
    var out = document.getElementById(target);
    if (!el || !out) return;
    el.addEventListener('input', function () {
      if (asSrc) {
        var value = el.value.trim();
        out.src = /^https?:\/\//i.test(value) ? value : ('/' + value.replace(/^\//, ''));
      } else {
        out.textContent = el.value;
      }
    });
  }
  live('hs-kicker', 'hs-preview-kicker');
  live('hs-title', 'hs-preview-title');
  live('hs-text', 'hs-preview-text');
  live('hs-cta', 'hs-preview-cta');
  live('hs-image', 'hs-preview-img', true);
})();
</script>
@endsection
