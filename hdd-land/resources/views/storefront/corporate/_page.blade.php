@extends('layouts.storefront')
@section('title', $title ?? 'صفحه')

@section('content')
<section class="section">
  <div class="hl-head">
    <div>
      <h2>{{ $heading }}</h2>
      <p>{{ $lead }}</p>
    </div>
  </div>
  {!! $body !!}
  <div class="hl-hero__actions" style="margin-top:1.25rem">
    <a class="btn-hl btn-hl-primary" href="{{ url('/contact') }}">تماس با واحد فروش</a>
    <a class="btn-hl btn-hl-ghost" href="{{ url('/products') }}">ورود به فروشگاه</a>
  </div>
</section>
@endsection
