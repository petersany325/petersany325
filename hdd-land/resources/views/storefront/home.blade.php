@extends('layouts.storefront')

@section('title', (isset($page) && is_object($page) && ! empty($page->seo_title)) ? $page->seo_title : 'خانه')

@section('content')
@php
  $homeCfg = \App\Support\HomePageConfig::get();
@endphp
{{--
  ساختار فشرده صفحه اول (ارتفاع‌ها از ادمین):
  1) هیرو  2) درباره  3) سازمانی  4) کاشی‌ها  5) محصولات  6) آموزش  7) اعتماد/برند
--}}
<div class="hl-home" style="{{ \App\Support\HomePageConfig::pageStyleAttr($homeCfg) }}">
  @include('storefront.partials.home-hero')
  @include('storefront.partials.home-corporate-sections')
</div>
@endsection
