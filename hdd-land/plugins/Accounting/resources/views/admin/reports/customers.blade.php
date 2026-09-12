@extends('accounting::layouts.acc')
@section('title','گزارش مشتریان')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>گزارش مشتریان</h1><p>جمع خرید و تعداد فاکتور هر مشتری</p></div>
  <div class="actions"><a class="btn g" href="{{ route('admin.accounting.reports') }}">مرکز گزارش</a></div></div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>از تاریخ<input type="date" name="from" value="{{ $from }}"></label>
    <label>تا تاریخ<input type="date" name="to" value="{{ $to }}"></label>
  </div>
  <div class="row">
    <label>جستجوی مشتری<input name="party" value="{{ $party }}" placeholder="نام یا شناسه"></label>
    <label style="align-self:end"><button class="btn" type="submit">اعمال</button></label>
  </div>
</form>
<div class="grid">
  <div class="card"><h3>فاکتور</h3><div class="v">{{ $sum['docs'] }}</div></div>
  <div class="card"><h3>فروش</h3><div class="v">{{ $m($sum['sales']) }}</div></div>
  <div class="card"><h3>تخفیف</h3><div class="v">{{ $m($sum['discount']) }}</div></div>
</div>
<div class="panel"><div class="hd"><strong>مشتریان</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>مشتری</th><th>شناسه</th><th>فاکتور</th><th>خرید</th><th>تخفیف</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->party_label }}</td>
      <td>{{ $r->party_user_id ?: '—' }}</td>
      <td>{{ $r->docs_count }}</td>
      <td>{{ $m($r->sales_total) }}</td>
      <td>{{ $m($r->discount_total) }}</td>
    </tr>
  @empty
    <tr><td colspan="5">مشتری‌ای نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
