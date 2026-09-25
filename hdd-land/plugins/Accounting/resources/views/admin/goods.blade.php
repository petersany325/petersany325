@extends('accounting::layouts.acc')
@section('title','تعریف کالا')
@section('content')
@php $m = fn($n) => number_format((int)$n); @endphp
<div class="top">
  <div>
    <h1>تعریف کالا و سریال</h1>
    <p>کد، بارکد، واحد، نرخ فروش، بهای تمام‌شده، مالیات و موجودی سایت — همان کاتالوگ فروشگاه</p>
  </div>
  <a class="btn g" href="{{ url('/admin/products') }}">محصولات فروشگاه</a>
</div>
<div class="panel"><div class="hd"><strong>کالای جدید</strong></div><div class="bd">
<form method="post" action="{{ url('/admin/accounting/goods') }}" class="form">@csrf
  <div class="row">
    <label>نام کالا<input name="name" required></label>
    <label>کد / SKU<input name="sku"></label>
  </div>
  <div class="row">
    <label>بارکد<input name="barcode"></label>
    <label>واحد<input name="unit" value="عدد"></label>
  </div>
  <div class="row">
    <label>نرخ فروش<input name="price" value="0"></label>
    <label>بهای تمام‌شده<input name="cost_price" value="0"></label>
  </div>
  <div class="row">
    <label>مالیات ٪<input name="vat_rate" type="number" step="0.01" value="0"></label>
    <label>حداقل سفارش<input name="min_qty" value="0"></label>
  </div>
  <div class="row">
    <label>موجودی سایت<input name="stock" value="0"></label>
    <label class="check" style="align-self:end"><input type="checkbox" name="is_active" value="1" checked> فعال</label>
  </div>
  <button class="btn" type="submit">ثبت کالا در فروشگاه</button>
</form>
</div></div>
<div class="panel"><div class="hd"><strong>کالاهای فروشگاه</strong><a href="{{ url('/admin/accounting/reports/shop-stock') }}">گزارش تطبیق موجودی</a></div>
<div class="bd" style="padding:0;overflow:auto">
<table>
  <thead><tr><th>کد</th><th>نام</th><th>واحد</th><th>نرخ</th><th>بها</th><th>مالیات٪</th><th>موجودی سایت</th></tr></thead>
  <tbody>
  @forelse($products as $p)
    <tr>
      <td>{{ $p->sku ?? '—' }}</td>
      <td>{{ $p->name }}</td>
      <td>{{ $p->unit ?? 'عدد' }}</td>
      <td>{{ $m($p->price ?? 0) }}</td>
      <td>{{ $m($p->cost_price ?? 0) }}</td>
      <td>{{ $p->vat_rate ?? 0 }}</td>
      <td>{{ $p->stock ?? '—' }}</td>
    </tr>
  @empty
    <tr><td colspan="7">کالایی نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
