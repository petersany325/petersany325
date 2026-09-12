@extends('accounting::layouts.acc')
@section('title','گزارش اسناد')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>گزارش سند حسابداری</h1><p>سند دستی و هزینه با فیلتر شماره</p></div>
  <div class="actions"><a class="btn g" href="{{ route('admin.accounting.reports') }}">مرکز گزارش</a></div></div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>از تاریخ<input type="date" name="from" value="{{ $from }}"></label>
    <label>تا تاریخ<input type="date" name="to" value="{{ $to }}"></label>
  </div>
  <div class="row">
    <label>نوع
      <select name="type">
        <option value="" @selected($type==='')>سند + هزینه</option>
        <option value="voucher" @selected($type==='voucher')>سند دستی</option>
        <option value="expense" @selected($type==='expense')>هزینه</option>
      </select>
    </label>
    <label>شماره سند<input name="doc_no" value="{{ $docNo }}"></label>
  </div>
  <button class="btn" type="submit">اعمال</button>
</form>
<div class="grid">
  <div class="card"><h3>تعداد</h3><div class="v">{{ $sum['count'] }}</div></div>
  <div class="card"><h3>جمع</h3><div class="v">{{ $m($sum['total']) }}</div></div>
</div>
<div class="panel"><div class="hd"><strong>اسناد</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>شماره</th><th>نوع</th><th>تاریخ</th><th>توضیح</th><th>ثبت‌کننده</th><th>مبلغ</th><th></th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->number }}</td>
      <td>{{ $types[$r->type] ?? $r->type }}</td>
      <td>{{ $r->doc_date }}</td>
      <td>{{ \Illuminate\Support\Str::limit($r->notes ?? $r->party_name, 40) }}</td>
      <td>{{ $r->staff_name ?: '—' }}</td>
      <td>{{ $m($r->total) }}</td>
      <td><a href="{{ route('admin.accounting.doc',$r->id) }}">مشاهده</a></td>
    </tr>
  @empty
    <tr><td colspan="7">موردی نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
