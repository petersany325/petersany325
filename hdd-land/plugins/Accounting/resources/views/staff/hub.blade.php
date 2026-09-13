@extends('accounting::layouts.acc', ['portal' => 'staff'])
@section('title', 'حسابداری کارمند')
@section('content')
@php $money = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="acc-top"><div><h1>میز حسابداری کارمند</h1><p>مشاهده اسناد، انبار و گزارش دوره</p></div></div>
<div class="acc-grid">
  <div class="acc-card"><h3>فروش</h3><div class="val">{{ $money($stats['sales_total'] ?? 0) }}</div></div>
  <div class="acc-card"><h3>خرید</h3><div class="val">{{ $money($stats['purchase_total'] ?? 0) }}</div></div>
  <div class="acc-card"><h3>هزینه</h3><div class="val">{{ $money($stats['expense_total'] ?? 0) }}</div></div>
  <div class="acc-card"><h3>پیش‌فاکتور باز</h3><div class="val">{{ (int)($stats['proforma_open'] ?? 0) }}</div></div>
</div>
<div class="acc-links">
  <a href="{{ route('staff.accounting.docs', ['type'=>'sale']) }}">فروش</a>
  <a href="{{ route('staff.accounting.docs', ['type'=>'purchase']) }}">خرید</a>
  <a href="{{ route('staff.accounting.stock') }}">انبار</a>
  <a href="{{ route('staff.accounting.reports') }}">گزارش</a>
</div>
<div class="acc-panel"><div class="hd"><strong>آخرین اسناد</strong></div><div class="bd" style="padding:0">
<table class="acc-table">
  <thead><tr><th>شماره</th><th>نوع</th><th>مبلغ</th><th>وضعیت</th><th></th></tr></thead>
  <tbody>
  @foreach($recent as $d)
    <tr>
      <td>{{ $d->number }}</td>
      <td>{{ $types[$d->type] ?? $d->type }}</td>
      <td>{{ $money($d->total) }}</td>
      <td><span class="acc-badge {{ $d->status }}">{{ $d->status }}</span></td>
      <td><a href="{{ route('staff.accounting.doc', $d->id) }}">باز</a></td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
@endsection
