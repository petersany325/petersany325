@extends('accounting::layouts.acc', ['portal' => 'staff'])
@section('title', 'گزارش')
@section('content')
@php $money = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="acc-top"><div><h1>گزارش ماه جاری</h1><p>{{ $from }} تا {{ $to }}</p></div></div>
<div class="acc-grid">
  <div class="acc-card"><h3>فروش</h3><div class="val">{{ $money($sales) }}</div></div>
  <div class="acc-card"><h3>خرید</h3><div class="val">{{ $money($purchase) }}</div></div>
  <div class="acc-card"><h3>هزینه</h3><div class="val">{{ $money($expense) }}</div></div>
</div>
@endsection
