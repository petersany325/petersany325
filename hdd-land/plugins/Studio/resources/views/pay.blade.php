@extends('studio::layout')
@section('title', 'پرداخت | فراز نت آمل')
@section('content')
@php
  $offer = $offer ?? [];
  $gateways = $gateways ?? [];
  $gateway = $gateway ?? 'zarinpal';
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb"><a href="{{ url($offer['url'] ?? '/') }}">بازگشت به معرفی</a></p>
    <p class="ag-kicker">درگاه پرداخت</p>
    <h1>خرید {{ $offer['title'] ?? '' }}</h1>
    <p class="ag-lead">قیمت: <b>{{ $offer['price_label'] ?? 'قیمت اعلام می‌شود' }}</b> — سهم نماینده {{ $offer['commission'] ?? 0 }}٪ بعد از ثبت فاکتور در کارتابل می‌آید.</p>
    @if(session('error'))<div class="ag-note ag-note--err">{{ session('error') }}</div>@endif
    <form class="ag-auth-form" method="post" action="{{ url('/pay/record') }}">
      @csrf
      <input type="hidden" name="sku" value="{{ $offer['sku'] ?? '' }}">
      <label>نام خریدار<input name="buyer" value="{{ $buyer ?? '' }}" required></label>
      <label>موبایل برای پیامک فاکتور<input name="phone" dir="ltr" inputmode="tel" placeholder="0912..."></label>
      <label>درگاه
        <select name="gateway">
          <option value="zarinpal" @selected($gateway==='zarinpal')>زرین‌پال{{ empty($gateways['zarinpal']['ready']) ? ' — کلید هنوز نیامده' : '' }}</option>
          <option value="melli" @selected($gateway==='melli')>بانک ملی{{ empty($gateways['melli']['ready']) ? ' — کلید هنوز نیامده' : '' }}</option>
        </select>
      </label>
      <button class="ag-btn ag-btn--on" type="submit">ثبت فاکتور و سهم نماینده</button>
    </form>
    <p class="ag-note">اتصال واقعی زرین‌پال و بانک ملی وقتی Merchant/Terminal را بدهید فعال می‌شود. الان لینک پرداخت همین صفحه است و فروش ثبت می‌شود.</p>
  </div>
</section>
@endsection
