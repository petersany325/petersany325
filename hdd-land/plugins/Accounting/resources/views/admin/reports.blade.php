@extends('accounting::layouts.acc')
@section('title','گزارش‌ها')
@section('content')
@php $money = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>گزارش تمام قسمت‌ها</h1><p>فروش، خرید، هزینه، کمیسیون و ارزش موجودی</p></div></div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>از تاریخ<input type="date" name="from" value="{{ $from }}"></label>
    <label>تا تاریخ<input type="date" name="to" value="{{ $to }}"></label>
  </div>
  <button class="btn" type="submit">اعمال فیلتر</button>
</form>
<div class="grid">
  <div class="card"><h3>فروش</h3><div class="v">{{ $money($sales) }}</div></div>
  <div class="card"><h3>خرید</h3><div class="v">{{ $money($purchase) }}</div></div>
  <div class="card"><h3>هزینه</h3><div class="v">{{ $money($expense) }}</div></div>
  <div class="card"><h3>کمیسیون</h3><div class="v">{{ $money($commission) }}</div><div class="s">ارزش موجودی ≈ {{ $money($stockValue) }}</div></div>
</div>
<div class="panel"><div class="hd"><strong>تفکیک نوع سند</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>نوع</th><th>تعداد</th><th>جمع مبلغ</th></tr></thead>
  <tbody>
  @forelse($byType as $row)
    <tr>
      <td>{{ $types[$row->type] ?? $row->type }}</td>
      <td>{{ $row->c }}</td>
      <td>{{ $money($row->s) }}</td>
    </tr>
  @empty
    <tr><td colspan="3">در این بازه سندی نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
