@extends('accounting::layouts.acc')
@section('title','انبارها')
@section('content')
<div class="top"><div><h1>تعریف انبار چندگانه</h1><p>انبار اصلی و شعب برای رسید، حواله و انتقال</p></div></div>
<div class="panel"><div class="hd"><strong>انبار جدید</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.warehouses.store') }}" class="form">@csrf
  <div class="row">
    <label>کد<input name="code" required placeholder="MAIN"></label>
    <label>نام<input name="name" required placeholder="انبار مرکزی"></label>
  </div>
  <div class="row">
    <label>شهر<input name="city"></label>
    <label>آدرس<input name="address"></label>
  </div>
  <label class="check"><input type="checkbox" name="is_default" value="1"> انبار پیش‌فرض</label>
  <button class="btn" type="submit">ثبت انبار</button>
</form>
</div></div>
<div class="panel"><div class="bd" style="padding:0">
<table>
  <thead><tr><th>کد</th><th>نام</th><th>شهر</th><th>وضعیت</th></tr></thead>
  <tbody>
  @foreach($items as $w)
    <tr>
      <td>{{ $w->code }}</td>
      <td>{{ $w->name }} @if($w->is_default)<span class="badge">پیش‌فرض</span>@endif</td>
      <td>{{ $w->city ?: '—' }}</td>
      <td>{{ $w->is_active ? 'فعال' : 'غیرفعال' }}</td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
@endsection
