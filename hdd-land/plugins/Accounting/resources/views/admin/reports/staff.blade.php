@extends('accounting::layouts.acc')
@section('title','گزارش کارمندان')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>گزارش کارمندان و فروشندگان</h1><p>فروش، کمیسیون و سود تقریبی هر نفر</p></div>
  <div class="actions"><a class="btn g" href="{{ route('admin.accounting.reports') }}">مرکز گزارش</a></div></div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>از تاریخ<input type="date" name="from" value="{{ $from }}"></label>
    <label>تا تاریخ<input type="date" name="to" value="{{ $to }}"></label>
  </div>
  <div class="row">
    <label>کارمند
      <select name="staff_id">
        <option value="0">همه</option>
        @foreach($staff as $s)
          <option value="{{ $s->id }}" @selected((int)$staffId===(int)$s->id)>{{ $s->name }}</option>
        @endforeach
      </select>
    </label>
    <label style="align-self:end"><button class="btn" type="submit">اعمال</button></label>
  </div>
</form>
<div class="grid">
  <div class="card"><h3>تعداد فاکتور</h3><div class="v">{{ $sum['docs'] }}</div></div>
  <div class="card"><h3>فروش</h3><div class="v">{{ $m($sum['sales']) }}</div></div>
  <div class="card"><h3>کمیسیون</h3><div class="v">{{ $m($sum['commission']) }}</div></div>
  <div class="card"><h3>سود تقریبی</h3><div class="v">{{ $m($sum['profit']) }}</div></div>
</div>
<div class="panel"><div class="hd"><strong>تفکیک پرسنل</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>کارمند</th><th>فاکتور</th><th>فروش</th><th>کمیسیون</th><th>سود تقریبی</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->staff_name }}</td>
      <td>{{ $r->docs_count }}</td>
      <td>{{ $m($r->sales_total) }}</td>
      <td>{{ $m($r->commission_total) }}</td>
      <td>{{ $m($r->profit_est) }}</td>
    </tr>
  @empty
    <tr><td colspan="5">فروشی با فروشنده ثبت‌شده نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
