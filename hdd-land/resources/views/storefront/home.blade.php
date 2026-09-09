@extends('layouts.storefront')
@section('title', ($page?->seo_title) ?: 'خانه')
@section('content')
<div class="container hl-home-wrap">
@php
  $bannerBridge = class_exists(\Plugins\ThemeBuilder\src\HomepageBanner::class)
    ? \Plugins\ThemeBuilder\src\HomepageBanner::resolve()
    : ['live' => false, 'banner' => []];
@endphp
@if(view()->exists('theme-builder::storefront.homepage'))
  @include('theme-builder::storefront.homepage', compact('featured','latest','categories'))
@else
  {{-- Fallback if ThemeBuilder views are not registered --}}
  @if(!empty($bannerBridge['live']))
    @include('theme-builder::storefront.partials.banner', ['b' => $bannerBridge['banner']])
  @endif
  @include('storefront.partials.home-hero', [
    'skipHero' => !empty($bannerBridge['live']),
    'revolutionAlreadyRendered' => !empty($bannerBridge['live']),
  ])
  @include('storefront.partials.home-corporate-sections')
@endif
</div>
@endsection
