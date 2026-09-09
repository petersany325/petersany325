@extends('layouts.storefront')
@section('title', ($page?->seo_title) ?: 'خانه')
@section('content')
{{-- اتصال قطعی بنرساز ThemeBuilder/Revolution به صفحه اول --}}
@php
  $bannerBridge = ['live' => false, 'banner' => []];
  try {
    if (class_exists(\Plugins\ThemeBuilder\src\HomepageBanner::class)) {
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
@endphp

@if($revolutionLive && view()->exists('theme-builder::storefront.partials.banner'))
  <div class="hl-revolution-banner-wrap">
    @include('theme-builder::storefront.partials.banner', ['b' => $bannerBridge['banner']])
  </div>
  @php $bannerRendered = true; @endphp
@endif

<div class="hl-home-wrap">
  @if(view()->exists('theme-builder::storefront.homepage'))
    @include('theme-builder::storefront.homepage', [
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
