@extends('studio::layout')
@section('title', 'پرداخت سبد | فراز نت آمل')
@section('content')
@php $gateways = $gateways ?? []; @endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb"><a href="{{ url('/computer-accounting/cart') }}">بازگشت به سبد</a></p>
    <p class="ag-kicker">درگاه پرداخت</p>
    <h1>پرداخت سبد</h1>
    <ul class="ag-steps" style="grid-template-columns:1fr">
      @foreach($items as $row)
        <li><b>{{ $row['title'] }}</b><p>{{ $row['price_label'] }}</p></li>
      @endforeach
    </ul>
    <p class="ag-lead">جمع: <b>{{ $totalLabel }}</b></p>
    @if(session('error'))<p class="ag-note">{{ session('error') }}</p>@endif
    <form class="ag-auth-form" method="post" action="{{ url('/pay/cart') }}">
      @csrf
      <label>نام خریدار<input name="buyer" value="{{ old('buyer', auth()->user()->name ?? '') }}" required></label>
      <label>موبایل<input name="phone" dir="ltr" inputmode="tel" placeholder="0912..."></label>
      <label>درگاه
        <select name="gateway">
          <option value="zarinpal">زرین‌پال{{ empty($gateways['zarinpal']['ready']) ? ' — کلید هنوز نیامده' : '' }}</option>
          <option value="melli">بانک ملی{{ empty($gateways['melli']['ready']) ? ' — کلید هنوز نیامده' : '' }}</option>
        </select>
      </label>
      <button class="ag-btn ag-btn--on" type="submit">ثبت در کارتابل و رفتن به پرداخت</button>
    </form>
    <p class="ag-note">بعد از ثبت، همین سبد در کارتابل فاکتورها می‌ماند. اتصال زندهٔ زرین‌پال و بانک ملی وقتی کلید درگاه را بدهید فعال می‌شود.</p>
  </div>
</section>
@endsection
