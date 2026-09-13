@extends('accounting::layouts.acc')
@section('title','گزارش حقوق')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>گزارش حقوق و مزایا</h1><p>فیش‌های حقوقی در بازه انتخابی</p></div>
  <div class="actions"><a class="btn g" href="{{ route('admin.accounting.payroll') }}">لیست حقوق</a></div></div>
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
  <div class="card"><h3>پایه</h3><div class="v">{{ $m($sum['base']) }}</div></div>
  <div class="card"><h3>کمیسیون</h3><div class="v">{{ $m($sum['commission']) }}</div></div>
  <div class="card"><h3>کسورات</h3><div class="v">{{ $m($sum['deduction']) }}</div></div>
  <div class="card"><h3>خالص</h3><div class="v">{{ $m($sum['net']) }}</div></div>
</div>
<div class="panel"><div class="hd"><strong>فیش‌ها</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>دوره</th><th>کارمند</th><th>پایه</th><th>کمیسیون</th><th>کسورات</th><th>خالص</th><th>وضعیت ران</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->period }}</td>
      <td>{{ $r->staff_name }}</td>
      <td>{{ $m($r->base_salary) }}</td>
      <td>{{ $m($r->commission_amount) }}</td>
      <td>{{ $m($r->deduction) }}</td>
      <td><strong>{{ $m($r->net) }}</strong></td>
      <td><span class="badge">{{ $r->run_status }}</span></td>
    </tr>
  @empty
    <tr><td colspan="7">فیشی در این بازه نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
