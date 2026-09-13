@extends('layouts.storefront')

@section('title', (isset($page) && is_object($page) && ! empty($page->seo_title)) ? $page->seo_title : 'خانه')

@section('content')
{{--
  ساختار صفحه اول (ریتم شبیه اپل):
  1) هیرو full-bleed
  2) بند داستانی درباره
  3) بند CTA سازمانی
  4) شبکه پرومو ۲ستونه
  5) قفسه محصولات
  6) کاشی‌های آموزش
  7) اعتماد + برندها
--}}
<div class="hl-home">
  @include('storefront.partials.home-hero')
  @include('storefront.partials.home-corporate-sections')
</div>
@endsection
