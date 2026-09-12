@extends('accounting::layouts.acc', ['portal' => 'staff'])
@section('title', 'اسناد')
@section('content')
@php $money = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="acc-top"><div><h1>اسناد مالی</h1></div></div>
<div class="acc-links">
  <a href="{{ route('staff.accounting.docs') }}">همه</a>
  @foreach($types as $k=>$v)
    <a href="{{ route('staff.accounting.docs', ['type'=>$k]) }}">{{ $v }}</a>
  @endforeach
</div>
<div class="acc-panel"><div class="bd" style="padding:0">
<table class="acc-table">
  <thead><tr><th>شماره</th><th>نوع</th><th>طرف</th><th>مبلغ</th><th></th></tr></thead>
  <tbody>
  @foreach($docs as $d)
    <tr>
      <td>{{ $d->number }}</td>
      <td>{{ $types[$d->type] ?? $d->type }}</td>
      <td>{{ $d->party_name ?: '—' }}</td>
      <td>{{ $money($d->total) }}</td>
      <td><a href="{{ route('staff.accounting.doc', $d->id) }}">جزئیات</a></td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
@endsection
