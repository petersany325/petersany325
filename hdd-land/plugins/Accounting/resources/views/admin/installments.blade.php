@extends('accounting::layouts.acc')
@section('title','اقساط مشتریان')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>اقساط مشتریان</h1>
    <p>{{ $pending }} درخواست در انتظار بررسی</p>
  </div>
  <div class="actions">
    <a class="btn g" href="{{ route('admin.accounting.reports.installments') }}">گزارش اقساط</a>
  </div>
</div>

<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>وضعیت
      <select name="status">
        <option value="">همه</option>
        @foreach($statuses as $k=>$lab)
          <option value="{{ $k }}" @selected($status===$k)>{{ $lab }}</option>
        @endforeach
      </select>
    </label>
    <label>جستجو<input name="q" value="{{ $q }}" placeholder="شماره / مشتری / کالا"></label>
  </div>
  <button class="btn" type="submit">فیلتر</button>
</form>

<div class="panel"><div class="hd"><strong>ثبت دستی درخواست</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.installments.store') }}" class="form">@csrf
  <div class="row">
    <label>نام مشتری<input name="customer_name" required></label>
    <label>موبایل<input name="customer_mobile"></label>
  </div>
  <div class="row">
    <label>کد ملی<input name="customer_national_id"></label>
    <label>شناسه کاربر<input name="user_id" type="number"></label>
  </div>
  <div class="row">
    <label>عنوان کالا<input name="product_title" required></label>
    <label>شناسه کالا<input name="product_id" type="number"></label>
  </div>
  <div class="row">
    <label>قیمت<input name="product_price" required></label>
    <label>پیش‌پرداخت<input name="down_payment" value="0"></label>
  </div>
  <div class="row">
    <label>تعداد اقساط<input name="months" type="number" min="1" max="36" value="3"></label>
    <label>یادداشت مشتری<input name="customer_note"></label>
  </div>
  <button class="btn" type="submit">ثبت</button>
</form>
</div></div>

<div class="panel"><div class="hd"><strong>لیست درخواست‌ها</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>شماره</th><th>مشتری</th><th>کالا</th><th>اقساط</th><th>ماهانه</th><th>وضعیت</th><th></th></tr></thead>
  <tbody>
  @forelse($items as $r)
    <tr>
      <td>{{ $r->number }}</td>
      <td>{{ $r->customer_name }}<div class="s">{{ $r->customer_mobile ?: ($r->user_name ?: '') }}</div></td>
      <td>{{ $r->product_title }}</td>
      <td>{{ $r->months }}</td>
      <td>{{ $m($r->monthly_amount) }}</td>
      <td><span class="badge">{{ $statuses[$r->status] ?? $r->status }}</span></td>
      <td><a href="{{ route('admin.accounting.installments.show', $r->id) }}">جزئیات</a></td>
    </tr>
  @empty
    <tr><td colspan="7">درخواستی نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
