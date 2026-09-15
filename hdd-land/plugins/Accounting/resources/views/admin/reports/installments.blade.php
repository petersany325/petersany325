@extends('accounting::layouts.acc')
@section('title','گزارش اقساط')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>گزارش اقساط مشتریان</h1><p>درخواست‌های اقساطی و وضعیت بررسی</p></div>
  <div class="actions"><a class="btn" href="{{ route('admin.accounting.installments') }}">مدیریت اقساط</a></div></div>
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
    <label style="align-self:end"><button class="btn" type="submit">اعمال</button></label>
  </div>
</form>
<div class="panel"><div class="hd"><strong>درخواست‌ها</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>شماره</th><th>مشتری</th><th>کالا</th><th>اقساط</th><th>قسط ماهانه</th><th>وضعیت</th><th></th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->number }}</td>
      <td>{{ $r->customer_name }}<div class="s">{{ $r->user_name ?: '' }}</div></td>
      <td>{{ $r->product_title }}</td>
      <td>{{ $r->months }}</td>
      <td>{{ $m($r->monthly_amount) }}</td>
      <td><span class="badge">{{ $statuses[$r->status] ?? $r->status }}</span></td>
      <td><a href="{{ route('admin.accounting.installments.show',$r->id) }}">جزئیات</a></td>
    </tr>
  @empty
    <tr><td colspan="7">درخواستی نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
