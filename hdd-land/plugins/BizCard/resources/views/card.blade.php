<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $s['title'] ?? $s['brand'] ?? 'سرزمین هارد' }} — کارت ویزیت</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/estedad-font@v7.0.0/dist/Estedad-Variable.css">
  <link rel="stylesheet" href="{{ asset('css/biz-card.css') }}?v=3">
  <style>:root{--bc-brand:{{ $s['accent'] ?? '#e23d12' }}}</style>
</head>
<body class="bc-body">
@php
  $hero = \Plugins\BizCard\src\CardConfig::assetUrl((string) ($s['hero'] ?? '/images/card/hero.jpg'));
  $logo = \Plugins\BizCard\src\CardConfig::assetUrl((string) ($s['logo'] ?? '/images/card/icon.png'));
  $phone = (string) ($s['phone'] ?? '01144447220');
@endphp
<div class="bc-shell">
  <section class="bc-hero">
    @if($hero !== '')<img class="bg" src="{{ $hero }}" alt="">@endif
    <div class="scrim"></div>
    <div class="in">
      <img class="bc-logo" src="{{ $logo }}" alt="{{ $s['brand'] ?? '' }}">
      <p class="bc-kicker">{{ $s['kicker'] ?? '' }}</p>
      <h1>{{ $s['title'] ?? $s['brand'] ?? 'سرزمین هارد' }}</h1>
      <p class="bc-sub">{{ $s['subtitle'] ?? '' }}</p>
    </div>
  </section>

  <div class="bc-actions">
    @if(!empty($s['show_save']))
      <a class="bc-btn bc-btn-p" href="{{ url('/card/vcard') }}" download="{{ $s['vcard_filename'] ?? 'sarzamin-hard.vcf' }}">ذخیره در گوشی</a>
    @endif
    @if(!empty($s['show_call']))
      <a class="bc-btn bc-btn-g" href="{{ \Plugins\BizCard\src\CardConfig::telHref($phone) }}">تماس {{ \Plugins\BizCard\src\CardConfig::prettyPhone($phone) }}</a>
    @endif
  </div>

  @if(!empty($s['show_social']) && !empty($contactLinks))
    <p class="bc-sec">{{ $s['contact_section'] ?? 'ارتباط مستقیم' }}</p>
    <div class="bc-list">
      @foreach($contactLinks as $item)
        <a class="bc-item" href="{{ $item['url'] }}" @if(str_starts_with($item['url'], 'http')) target="_blank" rel="noopener noreferrer" @endif>
          <span><strong>{{ $item['label'] }}</strong>@if($item['hint'] !== '')<small>{{ $item['hint'] }}</small>@endif</span>
          <em>{{ $item['action'] }}</em>
        </a>
      @endforeach
    </div>
  @endif

  @if(!empty($s['show_links']) && !empty($siteLinks))
    <p class="bc-sec">{{ $s['links_section'] ?? 'منوها و گزینه‌های سایت' }}</p>
    <div class="bc-list">
      @foreach($siteLinks as $item)
        <a class="bc-item" href="{{ str_starts_with($item['url'], 'http') || str_starts_with($item['url'], 'tel:') ? $item['url'] : url($item['url']) }}" @if(str_starts_with($item['url'], 'http')) target="_blank" rel="noopener noreferrer" @endif>
          <span><strong>{{ $item['label'] }}</strong>@if($item['hint'] !== '')<small>{{ $item['hint'] }}</small>@endif</span>
          <em>{{ $item['action'] }}</em>
        </a>
      @endforeach
    </div>
  @endif

  @if(!empty($s['show_club']))
    <section class="bc-club" id="club">
      <h2>{{ $s['club_title'] ?? 'باشگاه مشتری سرزمین هارد' }}</h2>
      <p>{{ $s['club_text'] ?? '' }}</p>
      @if(session('club_ok'))
        <div class="bc-alert bc-alert-ok">{{ session('club_ok') }}</div>
      @endif
      @if(session('club_error'))
        <div class="bc-alert bc-alert-err">{{ session('club_error') }}</div>
      @endif
      <form method="post" action="{{ url('/card/club') }}">
        @csrf
        <label>نام
          <input name="name" value="{{ old('name') }}" autocomplete="name" placeholder="نام شما">
        </label>
        <label>موبایل
          <input name="phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" placeholder="09xxxxxxxxx" dir="ltr">
        </label>
        <button class="bc-btn bc-btn-p" type="submit" style="width:100%">{{ $s['club_button'] ?? 'درخواست عضویت باشگاه مشتری' }}</button>
      </form>
    </section>
  @endif

  <p class="bc-foot">{{ $s['footer_note'] ?? '' }} · <a href="{{ url('/') }}">hdd-land.ir</a></p>
</div>
</body>
</html>
