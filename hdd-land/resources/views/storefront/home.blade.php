@extends('layouts.storefront')
@section('title', ($page?->seo_title) ?: 'خانه')
@section('content')
@include('theme-builder::storefront.homepage', compact('featured','latest','categories'))
@endsection
