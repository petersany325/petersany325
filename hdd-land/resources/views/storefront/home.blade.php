@extends('layouts.storefront')
@section('title', ($page?->seo_title) ?: 'خانه')
@section('content')
{{-- اتصال قطعی بنرساز ThemeBuilder/Revolution به صفحه اول --}}
@php
  $bannerBridge = ['live' => false, 'banner' => []];
  try {
    if (class_exists(\Plugins\ThemeBuilder\src\HomepageBanner::class)) {
      \Plugins\ThemeBuilder\src\HomepageBanner::ensureViewsRegistered();
      $bannerBridge = \Plugins\ThemeBuilder\src\HomepageBanner::resolve();
    } elseif (class_exists(\Plugins\ThemeBuilder\src\ThemeConfig::class)) {
      $theme = \Plugins\ThemeBuilder\src\ThemeConfig::get();
      $b = is_array($theme['banner'] ?? null) ? $theme['banner'] : [];
      $live = method_exists(\Plugins\ThemeBuilder\src\ThemeConfig::class, 'bannerIsLive')
        ? (bool) \Plugins\ThemeBuilder\src\ThemeConfig::bannerIsLive($b)
        : false;
      $bannerBridge = ['live' => $live, 'banner' => $b];
    }
  } catch (\Throwable) {
    $bannerBridge = ['live' => false, 'banner' => []];
  }
  $revolutionLive = ! empty($bannerBridge['live']);
  $bannerRendered = false;
  $bannerView = null;
  foreach (['theme-builder::storefront.partials.banner', 'themebuilder::storefront.partials.banner'] as $candidate) {
    if (view()->exists($candidate)) {
      $bannerView = $candidate;
      break;
    }
  }
@endphp

@if($revolutionLive && $bannerView)
  <div class="hl-revolution-banner-wrap">
    @include($bannerView, ['b' => $bannerBridge['banner']])
  </div>
  @php $bannerRendered = true; @endphp
@endif

<div class="hl-home-wrap">
  @if(view()->exists('theme-builder::storefront.homepage') || view()->exists('themebuilder::storefront.homepage'))
    @include(view()->exists('theme-builder::storefront.homepage') ? 'theme-builder::storefront.homepage' : 'themebuilder::storefront.homepage', [
      'featured' => $featured ?? collect(),
      'latest' => $latest ?? collect(),
      'categories' => $categories ?? collect(),
      'revolutionAlreadyRendered' => $bannerRendered,
      'skipHero' => $bannerRendered,
    ])
  @else
    @include('storefront.partials.home-hero', [
      'skipHero' => $bannerRendered,
      'revolutionAlreadyRendered' => $bannerRendered,
    ])
    @include('storefront.partials.home-corporate-sections')
  @endif
</div>
@endsection
