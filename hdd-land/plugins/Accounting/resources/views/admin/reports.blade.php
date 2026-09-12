@extends('accounting::layouts.acc')
@section('title','گزارش‌ها')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>گزارش دقیق تمام منوها</h1><p>فروش، خرید، هزینه، کمیسیون، انبار، حقوق و سود تقریبی</p></div></div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>از تاریخ<input type="date" name="from" value="{{ $from }}"></label>
    <label>تا تاریخ<input type="date" name="to" value="{{ $to }}"></label>
  </div>
  <button class="btn" type="submit">اعمال فیلتر</button>
</form>
<div class="grid">
  <div class="card"><h3>فروش</h3><div class="v">{{ $m($sales) }}</div></div>
  <div class="card"><h3>خرید</h3><div class="v">{{ $m($purchase) }}</div></div>
  <div class="card"><h3>هزینه</h3><div class="v">{{ $m($expense) }}</div></div>
  <div class="card"><h3>سود تقریبی</h3><div class="v">{{ $m($profit) }}</div><div class="s">کمیسیون {{ $m($commission) }} · موجودی ≈ {{ $m($stockValue) }}</div></div>
</div>

<div class="panel"><div class="hd"><strong>گزارش منو به منو</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>بخش</th><th>تعداد</th><th>مبلغ / ارزش</th><th></th></tr></thead>
  <tbody>
  @foreach($menuReport as $row)
    <tr>
      <td>{{ $row['label'] }}</td>
      <td>{{ number_format($row['count']) }}</td>
      <td>{{ $m($row['total']) }}</td>
      <td>
        @if(in_array($row['key'], ['sale','purchase','proforma','voucher','expense'], true))
          <a href="{{ route('admin.accounting.docs',['type'=>$row['key']]) }}">اسناد</a>
        @elseif($row['key']==='stock' || $row['key']==='warehouses')
          <a href="{{ route('admin.accounting.stock') }}">انبار</a>
        @elseif($row['key']==='banks')
          <a href="{{ route('admin.accounting.banks') }}">بانک</a>
        @elseif($row['key']==='payroll')
          <a href="{{ route('admin.accounting.payroll') }}">حقوق</a>
        @elseif($row['key']==='commission')
          <a href="{{ route('admin.accounting.commissions') }}">کمیسیون</a>
        @endif
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>

<div class="panel"><div class="hd"><strong>تفکیک نوع سند</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>نوع</th><th>تعداد</th><th>جمع مبلغ</th></tr></thead>
  <tbody>
  @forelse($byType as $row)
    <tr>
      <td>{{ $types[$row->type] ?? $row->type }}</td>
      <td>{{ $row->c }}</td>
      <td>{{ $m($row->s) }}</td>
    </tr>
  @empty
    <tr><td colspan="3">در این بازه سندی نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>

<div class="panel"><div class="hd"><strong>روند روزانه</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>تاریخ</th><th>فروش</th><th>خرید</th><th>هزینه</th></tr></thead>
  <tbody>
  @forelse($daily as $d)
    <tr>
      <td>{{ $d->doc_date }}</td>
      <td>{{ $m($d->sales) }}</td>
      <td>{{ $m($d->purchase) }}</td>
      <td>{{ $m($d->expense) }}</td>
    </tr>
  @empty
    <tr><td colspan="4">داده‌ای نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
