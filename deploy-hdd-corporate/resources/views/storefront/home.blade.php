@extends('layouts.hdd-land')
@section('title', ($page?->seo_title) ?: \App\Models\Setting::getValue('shop_name', 'HDD Land'))

@section('content')
@php
  if (! class_exists(\App\Support\CorporateHomeConfig::class) && is_file(app_path('Support/CorporateHomeConfig.php'))) {
      require_once app_path('Support/CorporateHomeConfig.php');
  }
  $h = class_exists(\App\Support\CorporateHomeConfig::class)
      ? \App\Support\CorporateHomeConfig::get()
      : [];
  $href = fn ($u) => class_exists(\App\Support\CorporateHomeConfig::class)
      ? \App\Support\CorporateHomeConfig::href((string) $u)
      : url($u);
  $subPaths = class_exists(\App\Support\CorporateHomeConfig::class)
      ? \App\Support\CorporateHomeConfig::subPaths($h['sub_paths'] ?? '')
      : [];
  $devices = class_exists(\App\Support\CorporateHomeConfig::class)
      ? \App\Support\CorporateHomeConfig::devices($h['devices'] ?? '')
      : [];
@endphp
<section class="banner">
  <div class="banner-grid">
    <div>
      <div class="kicker"><i></i> {{ $h['banner_kicker'] ?? 'سایت شرکتی + فروشگاهی' }}</div>
      <h1>{!! $h['banner_title'] ?? 'بازیابی تخصصی، <em>خرید ابزار</em> در یک خانه' !!}</h1>
      <p class="lead">{{ $h['banner_lead'] ?? '' }}</p>
      <div class="banner-actions">
        <a class="btn btn-red" href="{{ $href($h['banner_cta_red_url'] ?? '/contact') }}">{{ $h['banner_cta_red_label'] ?? 'درخواست بازیابی' }}</a>
        <a class="btn btn-blue" href="{{ $href($h['banner_cta_blue_url'] ?? '/products') }}">{{ $h['banner_cta_blue_label'] ?? 'ورود به فروشگاه' }}</a>
      </div>
      <div class="dual-gates">
        <div class="gate"><b>{{ $h['banner_gate1_title'] ?? 'مسیر شرکتی' }}</b>{{ $h['banner_gate1_text'] ?? '' }}</div>
        <div class="gate"><b>{{ $h['banner_gate2_title'] ?? 'مسیر فروشگاهی' }}</b>{{ $h['banner_gate2_text'] ?? '' }}</div>
      </div>
    </div>

    <div class="orbit" aria-hidden="true">
      <div class="ring r1"></div>
      <div class="ring r2"></div>
      <div class="ring r3"></div>
      <div class="orbit-core"><div class="disk"><b>{{ $h['banner_orbit_title'] ?? 'DUAL' }}</b>{{ $h['banner_orbit_subtitle'] ?? 'SERVICE + SHOP' }}</div></div>
      <span class="sat s1">HDD</span>
      <span class="sat s2">SSD</span>
      <span class="sat s3">RAID</span>
      <span class="sat s4">خدمت</span>
      <span class="sat s5">فروش</span>
    </div>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <h2>{{ $h['paths_heading'] ?? 'دو مسیر اصلی صفحه اول' }}</h2>
    <p>{{ $h['paths_lead'] ?? '' }}</p>
  </div>

  <div class="dual-paths">
    <article class="dual corp">
      <div class="code">{{ $h['corp_code'] ?? 'CORPORATE' }}</div>
      <h3>{{ $h['corp_title'] ?? 'خدمات شرکتی' }}</h3>
      <p>{{ $h['corp_text'] ?? '' }}</p>
      <div class="actions">
        <a class="btn btn-red" href="{{ $href($h['corp_cta1_url'] ?? '/contact') }}">{{ $h['corp_cta1_label'] ?? 'ثبت درخواست' }}</a>
        <a class="btn btn-ghost" href="{{ $href($h['corp_cta2_url'] ?? '/services') }}">{{ $h['corp_cta2_label'] ?? 'مشاهده خدمات' }}</a>
      </div>
    </article>
    <article class="dual shop">
      <div class="code">{{ $h['shop_code'] ?? 'SHOP' }}</div>
      <h3>{{ $h['shop_title'] ?? 'فروشگاه نرم‌افزار' }}</h3>
      <p>{{ $h['shop_text'] ?? '' }}</p>
      <div class="actions">
        <a class="btn btn-blue" href="{{ $href($h['shop_cta1_url'] ?? '/products') }}">{{ $h['shop_cta1_label'] ?? 'باز کردن فروشگاه' }}</a>
        <a class="btn btn-red" href="{{ $href($h['shop_cta2_url'] ?? '/products') }}" style="background:#fff;color:var(--red);border:1.5px solid rgba(225,29,46,.35);box-shadow:none">{{ $h['shop_cta2_label'] ?? 'محصولات' }}</a>
      </div>
    </article>
  </div>

  <div class="sub-paths">
    @forelse($subPaths as $sp)
      <article class="sub">
        <h4>{{ $sp['title'] }}</h4>
        <p>{{ $sp['text'] }}</p>
        <a href="{{ $href($sp['url']) }}">{{ $sp['label'] }}</a>
      </article>
    @empty
      <article class="sub"><h4>گارانتی هارد</h4><p>قبول گارانتی شرکت‌ها با مدارک و پیگیری.</p><a href="{{ url('/warranty') }}">ثبت گارانتی ←</a></article>
    @endforelse
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="section-head">
    <h2>{{ $h['devices_heading'] ?? 'پوشش دستگاه‌ها' }}</h2>
    <p>{{ $h['devices_lead'] ?? '' }}</p>
  </div>
  <div class="devices">
    @forelse($devices as $dv)
      <div class="device"><b>{{ $dv['title'] }}</b><span>{{ $dv['subtitle'] }}</span></div>
    @empty
      <div class="device"><b>هارد دیسک</b><span>منطقی / فیزیکی</span></div>
    @endforelse
  </div>
</section>

<section class="cta-band">
  <div>
    <h2>{{ $h['cta_heading'] ?? 'کدام مسیر را لازم دارید؟' }}</h2>
    <p>{{ $h['cta_lead'] ?? '' }}</p>
  </div>
  <div class="actions">
    <a class="btn btn-red" href="{{ $href($h['cta_red_url'] ?? '/contact') }}">{{ $h['cta_red_label'] ?? 'درخواست بازیابی' }}</a>
    <a class="btn btn-blue" href="{{ $href($h['cta_blue_url'] ?? '/products') }}">{{ $h['cta_blue_label'] ?? 'ورود به فروشگاه' }}</a>
  </div>
</section>
@endsection
