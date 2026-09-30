@extends('layouts.admin')
@section('title', '۴ گزینه صفحه اول')
@section('content')
@php
  $h = $home ?? [];
  $f = fn ($k, $d = '') => old($k, $h[$k] ?? $d);
  $on = ! empty(old('corp_enabled', $h['corp_enabled'] ?? true));
  $labels = [
    1 => 'تأمین هارد سازمانی',
    2 => 'فروش سایت تعمیرکاران',
    3 => 'پروژه‌های نظارتی و CCTV',
    4 => 'سایت فروشگاهی',
  ];
@endphp
<style>
.ho-wrap{max-width:1080px}
.ho-head{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}
.ho-head h1{margin:0;font-size:1.35rem}
.ho-head p{margin:.35rem 0 0;color:#64748b}
.ho-actions{display:flex;gap:.5rem;flex-wrap:wrap}
.ho-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem;margin-bottom:.85rem}
.ho-card h2{margin:0 0 .75rem;font-size:1rem}
.ho-grid{display:grid;gap:.65rem}
.ho-grid label{display:grid;gap:.28rem;font-size:.82rem;font-weight:700}
.ho-grid input,.ho-grid textarea{width:100%;border:1px solid #dbe3ef;border-radius:10px;padding:.55rem .7rem;font:inherit;background:#f8fafc}
.ho-row{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}
@media(max-width:720px){.ho-row{grid-template-columns:1fr}}
.ho-prev{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem;padding:.4rem 0}
@media(max-width:800px){.ho-prev{grid-template-columns:repeat(2,minmax(0,1fr))}}
.ho-tile{display:flex;flex-direction:column;align-items:center;gap:.4rem;text-align:center;text-decoration:none;color:inherit}
.ho-tile i{width:88px;height:88px;border-radius:50%;overflow:hidden;display:block;background:#123;box-shadow:0 0 0 3px rgba(120,180,255,.3)}
.ho-tile i img{width:100%;height:100%;object-fit:cover}
.ho-tile strong{font-size:.82rem}
.ho-tile span{font-size:.72rem;color:#2563eb}
.ho-sw{display:flex;align-items:center;justify-content:space-between;gap:.75rem}
.ho-hint{font-size:.8rem;color:#64748b;line-height:1.6}
.btn-apply{background:#0b1220;color:#fff;border:0;border-radius:10px;padding:.55rem .9rem;font:inherit;font-weight:800;cursor:pointer}
</style>
<div class="ho-wrap">
  <div class="ho-head">
    <div>
      <h1>۴ گزینه صفحه اول</h1>
      <p>همین چهار دایره روی خانه سایت و ورودی وب‌اپ نمایش داده می‌شود. عنوان، متن، عکس و لینک هر گزینه از اینجا عوض می‌شود.</p>
    </div>
    <div class="ho-actions">
      <a class="btn" href="{{ $preview }}" target="_blank" rel="noopener">مشاهده صفحه اول</a>
      <a class="btn" href="{{ url('/admin/homepage-settings') }}">سایر بلوک‌های خانه</a>
    </div>
  </div>

  <div class="ho-card">
    <h2>پیش‌نمایش زنده</h2>
    <div class="ho-prev">
      @foreach(($tiles ?? []) as $tile)
        <a class="ho-tile" href="{{ url($tile['url']) }}" target="_blank" rel="noopener">
          <i>@if($tile['image'] !== '')<img src="{{ $tile['image'] }}" alt="">@endif</i>
          <strong>{{ $tile['title'] }}</strong>
          <span>{{ $tile['text'] }}</span>
        </a>
      @endforeach
    </div>
    <form method="post" action="{{ url('/admin/home-options/apply') }}" style="margin-top:.6rem">
      @csrf
      <button class="btn-apply" type="submit">اعمال ۴ گزینه طراحی‌شده روی صفحه اول</button>
    </form>
  </div>

  <form method="post" action="{{ url('/admin/home-options') }}">
    @csrf
    <div class="ho-card">
      <div class="ho-sw">
        <strong>نمایش روی صفحه اول</strong>
        <label><input type="checkbox" name="corp_enabled" value="1" @checked($on)> فعال</label>
      </div>
      <div class="ho-grid" style="margin-top:.75rem">
        <label>عنوان بخش<input name="corp_title" value="{{ $f('corp_title') }}"></label>
        <label>زیرعنوان<textarea name="corp_subtitle" rows="2">{{ $f('corp_subtitle') }}</textarea></label>
      </div>
    </div>

    @foreach([1,2,3,4] as $i)
      <div class="ho-card">
        <h2>گزینه {{ $i }} — {{ $labels[$i] }}</h2>
        <div class="ho-grid">
          <div class="ho-row">
            <label>عنوان<input name="corp_{{ $i }}_title" value="{{ $f('corp_'.$i.'_title') }}"></label>
            <label>لینک<input name="corp_{{ $i }}_url" value="{{ $f('corp_'.$i.'_url') }}" dir="ltr"></label>
          </div>
          <label>متن<textarea name="corp_{{ $i }}_text" rows="2">{{ $f('corp_'.$i.'_text') }}</textarea></label>
          <label>تصویر<input name="corp_{{ $i }}_image" value="{{ $f('corp_'.$i.'_image') }}" dir="ltr"></label>
        </div>
        @if($i === 1)
          <p class="ho-hint">لینک پیشنهادی: <a href="{{ url('/admin/corp-pages/enterprise') }}">صفحه تأمین سازمانی</a></p>
        @elseif($i === 2)
          <p class="ho-hint">لینک پیشنهادی منوی تعمیرکاران: <code>/sites/repair-shop</code></p>
        @elseif($i === 3)
          <p class="ho-hint">لینک پیشنهادی: <a href="{{ url('/admin/corp-pages/cctv') }}">صفحه پروژه‌های CCTV</a></p>
        @else
          <p class="ho-hint">لینک پیشنهادی فروشگاه: <code>/sites/online-store</code></p>
        @endif
      </div>
    @endforeach

    <div class="ho-card">
      <h2>نوار سازمانی بالای دایره‌ها</h2>
      <div class="ho-grid">
        <label>عنوان<input name="corp_cta_title" value="{{ $f('corp_cta_title') }}"></label>
        <label>متن<textarea name="corp_cta_text" rows="2">{{ $f('corp_cta_text') }}</textarea></label>
        <div class="ho-row">
          <label>دکمه<input name="corp_cta_label" value="{{ $f('corp_cta_label') }}"></label>
          <label>لینک<input name="corp_cta_url" value="{{ $f('corp_cta_url') }}" dir="ltr"></label>
        </div>
      </div>
    </div>

    <div class="ho-actions">
      <button class="btn-apply" type="submit">ذخیره و اعمال روی صفحه اول</button>
      <a class="btn" href="{{ $preview }}" target="_blank" rel="noopener">باز کردن خانه</a>
    </div>
  </form>
</div>
@endsection
