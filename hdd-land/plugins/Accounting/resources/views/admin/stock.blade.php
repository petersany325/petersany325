@extends('accounting::layouts.acc')
@section('title','انبار')
@section('content')
@php $money = fn($n) => number_format((int)$n); @endphp
<div class="top">
  <div><h1>حواله، رسید و موجودی</h1><p>حرکت کالا بین انبارها با سریال</p></div>
  <a class="btn" href="{{ route('admin.accounting.warehouses') }}">مدیریت انبارها</a>
</div>
<div class="panel"><div class="hd"><strong>ثبت حرکت</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.stock.store') }}" class="form">@csrf
  <div class="row">
    <label>نوع
      <select name="type">
        <option value="stock_in">رسید انبار</option>
        <option value="stock_out">حواله انبار</option>
        <option value="transfer">انتقال بین انبار</option>
      </select>
    </label>
    <label>تاریخ<input type="date" name="doc_date" value="{{ now()->toDateString() }}"></label>
  </div>
  <div class="row">
    <label>انبار مبدأ
      <select name="warehouse_id" required>
        @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
      </select>
    </label>
    <label>انبار مقصد (انتقال)
      <select name="warehouse_to_id">
        <option value="">—</option>
        @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>نام کالا<input name="title" required></label>
    <label>تعداد<input name="qty" value="1" required></label>
  </div>
  <div class="row">
    <label>بهای واحد<input name="unit_cost" value="0"></label>
    <label>طرف / مرجع<input name="party_name"></label>
  </div>
  <label>سریال‌ها<textarea name="serials" rows="2" placeholder="SN1, SN2"></textarea></label>
  <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
  <label class="check"><input type="checkbox" name="issue_now" value="1" checked> اعمال فوری روی موجودی</label>
  <button class="btn" type="submit">ثبت حرکت</button>
</form>
</div></div>
<div class="panel"><div class="hd"><strong>موجودی انبارها</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>انبار</th><th>محصول</th><th>تعداد</th><th>میانگین بها</th></tr></thead>
  <tbody>
  @forelse($balances as $b)
    <tr>
      <td>{{ $b->warehouse_name }} ({{ $b->warehouse_code }})</td>
      <td>#{{ $b->product_id }}</td>
      <td>{{ $b->qty }}</td>
      <td>{{ $money($b->avg_cost) }}</td>
    </tr>
  @empty
    <tr><td colspan="4">موجودی ثبت نشده.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
<div class="panel"><div class="hd"><strong>آخرین حرکات</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>شماره</th><th>نوع</th><th>تاریخ</th><th>وضعیت</th><th></th></tr></thead>
  <tbody>
  @foreach($moves as $m)
    <tr>
      <td>{{ $m->number }}</td>
      <td>{{ $m->type }}</td>
      <td>{{ $m->doc_date }}</td>
      <td><span class="badge {{ $m->status }}">{{ $m->status }}</span></td>
      <td><a href="{{ route('admin.accounting.doc', $m->id) }}">باز</a></td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
@endsection
