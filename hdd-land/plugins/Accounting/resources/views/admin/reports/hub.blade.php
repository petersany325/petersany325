@extends('accounting::layouts.acc')
@section('title','مرکز گزارش‌ها')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>مرکز گزارش‌های حسابداری</h1>
    <p>فروش، خرید، کارمند، حقوق، سند، انبار، مشتری، چک و اقساط — با فیلتر شماره فاکتور و بازه تاریخ</p>
  </div>
</div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>از تاریخ<input type="date" name="from" value="{{ $from }}"></label>
    <label>تا تاریخ<input type="date" name="to" value="{{ $to }}"></label>
  </div>
  <button class="btn" type="submit">اعمال بازه روی خلاصه</button>
</form>
<div class="grid">
  <div class="card"><h3>فروش بازه</h3><div class="v">{{ $m($sales) }}</div><div class="s">امروز {{ $m($quick['sales_today']) }} · ماه {{ $m($quick['sales_month']) }}</div></div>
  <div class="card"><h3>خرید بازه</h3><div class="v">{{ $m($purchase) }}</div><div class="s">ماه جاری {{ $m($quick['purchases_month']) }}</div></div>
  <div class="card"><h3>هزینه / کمیسیون</h3><div class="v">{{ $m($expense) }}</div><div class="s">کمیسیون {{ $m($commission) }}</div></div>
  <div class="card"><h3>سود تقریبی</h3><div class="v">{{ $m($profit) }}</div><div class="s">{{ (int)$quick['warehouses'] }} انبار · {{ (int)$quick['checks_open'] }} چک باز · {{ (int)$quick['installments_pending'] }} قسط در انتظار</div></div>
</div>
<div class="panel"><div class="hd"><strong>منوی گزارش‌های تخصصی</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>گزارش</th><th>توضیح</th><th></th></tr></thead>
  <tbody>
  @foreach($links as $link)
    <tr>
      <td><strong>{{ $link['label'] }}</strong></td>
      <td>{{ $link['desc'] }}</td>
      <td><a class="btn g" href="{{ route($link['route'], ['from'=>$from,'to'=>$to]) }}">باز کردن</a></td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
<div class="chips">
  <a href="{{ route('admin.accounting.checks') }}">مدیریت چک‌ها</a>
  <a href="{{ route('admin.accounting.installments') }}">اقساط مشتریان</a>
  <a href="{{ route('admin.accounting.warehouses') }}">انبار چندگانه</a>
  <a href="{{ route('admin.accounting.docs') }}">اسناد مالی</a>
</div>
@endsection
