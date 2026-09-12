@extends('accounting::layouts.acc')
@section('title', ($type==='purchase'?'گزارش خرید':'گزارش فروش'))
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>{{ $type==='purchase' ? 'گزارش خرید' : 'گزارش فروش' }}</h1>
    <p>فیلتر تاریخ، شماره فاکتور، طرف حساب، انبار و فروشنده</p>
  </div>
  <div class="actions">
    <a class="btn g" href="{{ route('admin.accounting.reports') }}">مرکز گزارش</a>
  </div>
</div>
<form method="get" class="form panel" style="padding:1rem;margin-bottom:1rem">
  <div class="row">
    <label>نوع
      <select name="type">
        <option value="sale" @selected($type==='sale')>فروش</option>
        <option value="purchase" @selected($type==='purchase')>خرید</option>
      </select>
    </label>
    <label>شماره فاکتور<input name="doc_no" value="{{ $filters['docNo'] }}" placeholder="SF-..."></label>
  </div>
  <div class="row">
    <label>از تاریخ<input type="date" name="from" value="{{ $from }}"></label>
    <label>تا تاریخ<input type="date" name="to" value="{{ $to }}"></label>
  </div>
  <div class="row">
    <label>طرف حساب<input name="party" value="{{ $filters['party'] }}"></label>
    <label>انبار
      <select name="warehouse_id">
        <option value="0">همه</option>
        @foreach($warehouses as $w)
          <option value="{{ $w->id }}" @selected((int)$filters['warehouseId']===(int)$w->id)>{{ $w->name }}</option>
        @endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>فروشنده / کارمند
      <select name="staff_id">
        <option value="0">همه</option>
        @foreach($staff as $s)
          <option value="{{ $s->id }}" @selected((int)$filters['staffId']===(int)$s->id)>{{ $s->name }}</option>
        @endforeach
      </select>
    </label>
    <label style="align-self:end"><button class="btn" type="submit">اعمال فیلتر</button></label>
  </div>
</form>
<div class="grid">
  <div class="card"><h3>تعداد</h3><div class="v">{{ $sum['count'] }}</div></div>
  <div class="card"><h3>جمع</h3><div class="v">{{ $m($sum['total']) }}</div></div>
  <div class="card"><h3>تخفیف</h3><div class="v">{{ $m($sum['discount']) }}</div></div>
  <div class="card"><h3>کمیسیون</h3><div class="v">{{ $m($sum['commission']) }}</div></div>
</div>
<div class="panel"><div class="hd"><strong>اسناد</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>شماره</th><th>تاریخ</th><th>طرف</th><th>انبار</th><th>فروشنده</th><th>مبلغ</th><th></th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->number }}</td>
      <td>{{ $r->doc_date }}</td>
      <td>{{ $r->party_name ?: '—' }}</td>
      <td>{{ $r->warehouse_name ?: '—' }}</td>
      <td>{{ $r->staff_name ?: '—' }}</td>
      <td>{{ $m($r->total) }}</td>
      <td><a href="{{ route('admin.accounting.doc',$r->id) }}">مشاهده</a></td>
    </tr>
  @empty
    <tr><td colspan="7">موردی با این فیلتر نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
