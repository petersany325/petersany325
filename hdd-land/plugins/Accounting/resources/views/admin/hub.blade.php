@extends('accounting::layouts.acc')
@section('title','میز حسابداری')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>میز حسابداری مدرن</h1>
    <p>فاکتور خرید/فروش با سریال، پیش‌فاکتور، سند دستی، بانک، انبار چندگانه، هزینه، حقوق و گزارش</p>
  </div>
  <div class="actions">
    <a class="btn" href="{{ route('admin.accounting.docs.create',['type'=>'sale']) }}">فروش + سریال</a>
    <a class="btn g" href="{{ route('admin.accounting.docs.create',['type'=>'purchase']) }}">خرید</a>
    <a class="btn w" href="{{ route('admin.accounting.docs.create',['type'=>'proforma']) }}">پیش‌فاکتور</a>
    <a class="btn g" href="{{ route('admin.accounting.docs.create',['type'=>'voucher']) }}">سند دستی</a>
  </div>
</div>
<div class="grid">
  <div class="card"><h3>فروش صادرشده</h3><div class="v">{{ $m($stats['sales_total'] ?? 0) }}</div></div>
  <div class="card"><h3>خرید صادرشده</h3><div class="v">{{ $m($stats['purchase_total'] ?? 0) }}</div></div>
  <div class="card"><h3>هزینه‌ها</h3><div class="v">{{ $m($stats['expense_total'] ?? 0) }}</div></div>
  <div class="card"><h3>پیش‌فاکتور باز</h3><div class="v">{{ (int)($stats['proforma_open'] ?? 0) }}</div>
    <div class="s">{{ (int)($stats['warehouses'] ?? 0) }} انبار · {{ (int)($stats['banks'] ?? 0) }} بانک</div></div>
</div>
<div class="chips">
  @foreach($types as $k=>$label)
    <a href="{{ route('admin.accounting.docs',['type'=>$k]) }}">{{ $label }} ({{ (int)($stats['docs'][$k] ?? 0) }})</a>
  @endforeach
</div>
<div class="panel">
  <div class="hd"><strong>آخرین اسناد</strong><a class="btn g" href="{{ route('admin.accounting.docs') }}">همه</a></div>
  <div class="bd" style="padding:0">
    <table>
      <thead><tr><th>شماره</th><th>نوع</th><th>طرف</th><th>مبلغ</th><th>وضعیت</th><th></th></tr></thead>
      <tbody>
      @forelse($recent as $d)
        <tr>
          <td>{{ $d->number }}</td>
          <td>{{ $types[$d->type] ?? $d->type }}</td>
          <td>{{ $d->party_name ?: '—' }}</td>
          <td>{{ $m($d->total) }}</td>
          <td><span class="badge {{ $d->status }}">{{ $d->status }}</span></td>
          <td><a href="{{ route('admin.accounting.doc',$d->id) }}">مشاهده</a></td>
        </tr>
      @empty
        <tr><td colspan="6">سندی نیست — از دکمه‌های بالا شروع کنید.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
