@extends('layouts.storefront')
@section('title','ورود کارمندان')
@section('content')
<div class="auth-box">
  <h1>لینک نامعتبر</h1>
  <p class="sub">برای ورود کارمندان باید از <b>لینک اختصاصی امن</b> که ادمین در اختیار شما گذاشته استفاده کنید. آدرس عمومی ورود کارمندان غیرفعال است.</p>
  <div class="links">
    <a href="{{ url('/login') }}">ورود مشتریان</a>
    <a href="{{ url('/') }}">فروشگاه</a>
  </div>
</div>
@endsection
