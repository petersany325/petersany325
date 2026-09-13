@extends('layouts.storefront')

@section('title', (isset($page) && is_object($page) && ! empty($page->seo_title)) ? $page->seo_title : 'خانه')

@section('content')
{{-- صفحه اول مدرن — Revolution / بنرساز لایه‌ای خاموش است --}}
<div class="hl-home">
  @include('storefront.partials.home-hero')
  @include('storefront.partials.home-corporate-sections')
</div>
@endsection
