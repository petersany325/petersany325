@extends('accounting::layouts.acc')
@section('title','لیست حقوق '.$run->period)
@section('content')
@php $money = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div><h1>لیست حقوق {{ $run->period }}</h1><p>وضعیت: {{ $run->status }} · خالص کل {{ $money($run->total_net) }}</p></div>
  <a class="btn g" href="{{ route('admin.accounting.payroll') }}">بازگشت</a>
</div>
<div class="panel"><div class="bd" style="padding:0">
<table>
  <thead><tr><th>کارمند</th><th>حقوق پایه</th><th>٪ کمیسیون</th><th>مبلغ کمیسیون</th><th>کسورات</th><th>خالص</th></tr></thead>
  <tbody>
  @foreach($slips as $s)
    <tr>
      <td>{{ $s->staff_name }}</td>
      <td>{{ $money($s->base_salary) }}</td>
      <td>{{ $s->commission_rate }}</td>
      <td>{{ $money($s->commission_amount) }}</td>
      <td>{{ $money($s->deduction) }}</td>
      <td><strong>{{ $money($s->net) }}</strong></td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
@endsection
