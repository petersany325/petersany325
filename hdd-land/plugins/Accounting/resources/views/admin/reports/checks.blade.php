@extends('accounting::layouts.acc')
@section('title','گزارش چک‌ها')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>گزارش چک‌ها</h1><p>پرداختی، دریافتی، برگشتی، تحویل و وصول</p></div>
  <div class="actions"><a class="btn" href="{{ route('admin.accounting.checks') }}">مدیریت چک</a></div></div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>از سررسید<input type="date" name="from" value="{{ $from }}"></label>
    <label>تا سررسید<input type="date" name="to" value="{{ $to }}"></label>
  </div>
  <div class="row">
    <label>جهت
      <select name="direction">
        <option value="">همه</option>
        @foreach($directions as $k=>$lab)
          <option value="{{ $k }}" @selected($direction===$k)>{{ $lab }}</option>
        @endforeach
      </select>
    </label>
    <label>وضعیت
      <select name="status">
        <option value="">همه</option>
        @foreach($statuses as $k=>$lab)
          <option value="{{ $k }}" @selected($status===$k)>{{ $lab }}</option>
        @endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>جستجو<input name="q" value="{{ $q }}" placeholder="شماره / صیاد / طرف"></label>
    <label style="align-self:end"><button class="btn" type="submit">اعمال</button></label>
  </div>
</form>
<div class="grid">
  <div class="card"><h3>تعداد فیلتر</h3><div class="v">{{ $sum['count'] }}</div></div>
  <div class="card"><h3>مبلغ فیلتر</h3><div class="v">{{ $m($sum['amount']) }}</div></div>
</div>
<div class="panel"><div class="hd"><strong>تفکیک وضعیت</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>وضعیت</th><th>جهت</th><th>تعداد</th><th>مبلغ</th></tr></thead>
  <tbody>
  @foreach($byStatus as $b)
    <tr>
      <td>{{ $statuses[$b->status] ?? $b->status }}</td>
      <td>{{ $directions[$b->direction] ?? $b->direction }}</td>
      <td>{{ $b->cnt }}</td>
      <td>{{ $m($b->total) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
<div class="panel"><div class="hd"><strong>لیست چک</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>شماره</th><th>جهت</th><th>وضعیت</th><th>طرف</th><th>بانک</th><th>سررسید</th><th>مبلغ</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->number }}@if($r->sayad)<div class="s">صیاد {{ $r->sayad }}</div>@endif</td>
      <td>{{ $directions[$r->direction] ?? $r->direction }}</td>
      <td><span class="badge">{{ $statuses[$r->status] ?? $r->status }}</span></td>
      <td>{{ $r->party_name ?: ($r->party_user_name ?: '—') }}</td>
      <td>{{ $r->bank_name ?: ($r->linked_bank ?: '—') }}</td>
      <td>{{ $r->due_date ?: '—' }}</td>
      <td>{{ $m($r->amount) }}</td>
    </tr>
  @empty
    <tr><td colspan="7">چکی نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
