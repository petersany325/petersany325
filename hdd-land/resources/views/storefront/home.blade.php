@extends('layouts.storefront')
@section('title', ($page?->seo_title) ?: 'خانه')
@section('content')
<div class="container hl-home-wrap">
@include('theme-builder::storefront.homepage', compact('featured','latest','categories'))
</div>
@endsection
