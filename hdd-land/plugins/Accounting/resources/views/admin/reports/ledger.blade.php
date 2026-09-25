@extends('accounting::layouts.acc')
@section('title', $title)
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>{{ $title }}</h1>
    <p>از روی دفتر کل دوطرفه — نه جمع خام فاکتورها</p>
  </div>
</div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    @if($kind !== 'balance')
      <label>از تاریخ<input type="date" name="from" value="{{ $from }}"></label>
    @endif
    <label>تا تاریخ<input type="date" name="to" value="{{ $to }}"></label>
  </div>
  <button class="btn" type="submit">اعمال</button>
</form>
<div class="panel">
  <div class="bd" style="padding:0">
    <table>
      <thead><tr><th>کد</th><th>حساب</th><th>بدهکار</th><th>بستانکار</th><th>مانده</th></tr></thead>
      <tbody>
      @forelse($rows as $r)
        @php $net = (int)$r->debit - (int)$r->credit; @endphp
        <tr>
          <td style="direction:ltr;text-align:left">{{ $r->code }}</td>
          <td>{{ $r->name }}</td>
          <td>{{ $m($r->debit) }}</td>
          <td>{{ $m($r->credit) }}</td>
          <td>{{ $m($net) }}</td>
        </tr>
      @empty
        <tr><td colspan="5">گردشی در این بازه نیست — سند صادرشده همگام می‌شود.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@if($kind==='trial')
  <div class="grid">
    <div class="card"><h3>جمع بدهکار</h3><div class="v">{{ $m($debit) }}</div></div>
    <div class="card"><h3>جمع بستانکار</h3><div class="v">{{ $m($credit) }}</div></div>
    <div class="card"><h3>تراز</h3><div class="v">{{ $debit===$credit ? 'متوازن' : $m($debit-$credit) }}</div></div>
  </div>
@elseif($kind==='income')
  <div class="grid">
    <div class="card"><h3>درآمد</h3><div class="v">{{ $m($credit) }}</div></div>
    <div class="card"><h3>بها + هزینه</h3><div class="v">{{ $m($debit) }}</div></div>
    <div class="card"><h3>سود (زیان)</h3><div class="v">{{ $m($profit ?? 0) }}</div></div>
  </div>
@endif
<div class="chips">
  <a href="{{ url('/admin/accounting/reports/trial') }}">تراز آزمایشی</a>
  <a href="{{ url('/admin/accounting/reports/income') }}">سود و زیان</a>
  <a href="{{ url('/admin/accounting/reports/balance') }}">ترازنامه</a>
  <a href="{{ url('/admin/accounting/chart') }}">کدینگ</a>
</div>
@endsection
