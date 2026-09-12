@extends('accounting::layouts.acc')
@section('title','گزارش انبار')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>گزارش انبارها با تفکیک</h1><p>موجودی و ارزش هر انبار</p></div>
  <div class="actions"><a class="btn g" href="{{ route('admin.accounting.warehouses') }}">تعریف انبار</a></div></div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>انبار
      <select name="warehouse_id">
        <option value="0">همه انبارها</option>
        @foreach($warehouses as $w)
          <option value="{{ $w->id }}" @selected((int)$warehouseId===(int)$w->id)>{{ $w->name }} ({{ $w->code }})</option>
        @endforeach
      </select>
    </label>
    <label style="align-self:end"><button class="btn" type="submit">اعمال</button></label>
  </div>
</form>
<div class="panel"><div class="hd"><strong>خلاصه هر انبار</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>انبار</th><th>کد</th><th>SKU</th><th>جمع تعداد</th><th>ارزش تقریبی</th></tr></thead>
  <tbody>
  @forelse($byWh as $w)
    <tr>
      <td>{{ $w->name }}</td>
      <td>{{ $w->code }}</td>
      <td>{{ $w->skus }}</td>
      <td>{{ number_format((float)$w->qty_sum, 2) }}</td>
      <td>{{ $m($w->value_sum) }}</td>
    </tr>
  @empty
    <tr><td colspan="5">موجودی ثبت نشده.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
<div class="panel"><div class="hd"><strong>جزئیات موجودی</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>انبار</th><th>کالا</th><th>SKU</th><th>تعداد</th><th>میانگین هزینه</th><th>ارزش</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->warehouse_name }}</td>
      <td>{{ $r->product_name }}</td>
      <td>{{ $r->sku ?: '—' }}</td>
      <td>{{ number_format((float)$r->qty, 3) }}</td>
      <td>{{ $m($r->avg_cost) }}</td>
      <td>{{ $m((float)$r->qty * (int)$r->avg_cost) }}</td>
    </tr>
  @empty
    <tr><td colspan="6">ردیفی نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
