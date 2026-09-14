@extends('layouts.storefront')

@section('title', (isset($page) && is_object($page) && ! empty($page->seo_title)) ? $page->seo_title : 'خانه')

@section('content')
@php
  $homeCfg = [];
  $pageStyle = '';
  try {
    $homeCfg = \App\Support\HomePageConfig::get();
    if (method_exists(\App\Support\HomePageConfig::class, 'pageStyleAttr')) {
      $pageStyle = \App\Support\HomePageConfig::pageStyleAttr($homeCfg);
    } elseif (method_exists(\App\Support\HomePageConfig::class, 'heroStyleAttr')) {
      $pageStyle = \App\Support\HomePageConfig::heroStyleAttr($homeCfg);
    }
  } catch (\Throwable $e) {
    $pageStyle = '';
  }
@endphp
{{--
  ساختار فشرده صفحه اول (ارتفاع‌ها از ادمین):
  1) هیرو  2) درباره  3) سازمانی  4) کاشی‌ها  5) محصولات  6) آموزش  7) اعتماد/برند
--}}
<div class="hl-home"@if($pageStyle !== '') style="{{ $pageStyle }}"@endif>
  @include('storefront.partials.home-hero')
  @include('storefront.partials.home-corporate-sections')
</div>
@endsection
