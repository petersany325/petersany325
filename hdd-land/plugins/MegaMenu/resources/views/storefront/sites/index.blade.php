@extends('layouts.storefront')

@section('title', 'طراحی و فروش سایت')

@section('content')
<section class="ws-page">
  <header class="ws-hero ws-hero--hub">
    <div class="ws-wrap">
      <p class="ws-kicker">طراحی و فروش سایت</p>
      <h1 class="ws-brand">سایت آماده برای کسب‌وکار شما</h1>
      <p class="ws-lead">از منوی «طراحی و فروش سایت» نوع سایت را انتخاب کنید یا از فهرست زیر وارد صفحه تخصصی همان محصول شوید. جزئیات، امکانات و آموزش داخل همان صفحه است — نه داخل مگامنو.</p>
    </div>
  </header>

  <div class="ws-wrap ws-hub-list">
    @foreach($items as $row)
      <a class="ws-hub-item" href="{{ url('/sites/'.$row['slug']) }}">
        <span class="ws-hub-item__title">{{ $row['title'] }}</span>
        <span class="ws-hub-item__short">{{ $row['short'] }}</span>
        <span class="ws-hub-item__go">مشاهده صفحه فروش ←</span>
      </a>
    @endforeach
  </div>
</section>
@endsection
