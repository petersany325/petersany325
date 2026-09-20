@extends('accounting::layouts.acc')
@section('title','میز حسابداری')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>میز حسابداری مدرن</h1>
    <p>یک حقیقت فروش: سفارش فروشگاه، فاکتور، اقساط و موجودی کالا روی یک سند</p>
  </div>
  <div class="actions">
    <a class="btn" href="{{ route('admin.accounting.docs.create',['type'=>'sale']) }}">فروش + سریال</a>
    <a class="btn g" href="{{ route('admin.accounting.docs.create',['type'=>'purchase']) }}">خرید</a>
    <a class="btn w" href="{{ route('admin.accounting.checks') }}">چک‌ها</a>
    <a class="btn o" href="{{ route('admin.accounting.installments') }}">اقساط</a>
    <form method="post" action="{{ route('admin.accounting.sync-shop') }}">@csrf
      <button class="btn g" type="submit">همگام‌سازی سفارش‌های فروشگاه</button>
    </form>
  </div>
</div>
<div class="grid">
  <div class="card"><h3>فروش صادرشده</h3><div class="v">{{ $m($stats['sales_total'] ?? 0) }}</div></div>
  <div class="card"><h3>خرید صادرشده</h3><div class="v">{{ $m($stats['purchase_total'] ?? 0) }}</div></div>
  <div class="card"><h3>هزینه‌ها</h3><div class="v">{{ $m($stats['expense_total'] ?? 0) }}</div></div>
  <div class="card"><h3>پیش‌فاکتور باز</h3><div class="v">{{ (int)($stats['proforma_open'] ?? 0) }}</div>
    <div class="s">{{ (int)($stats['warehouses'] ?? 0) }} انبار · {{ (int)($stats['banks'] ?? 0) }} بانک</div></div>
</div>

<div class="panel">
  <div class="hd"><strong>منوهای جدید حسابداری</strong><a class="btn g" href="{{ route('admin.accounting.reports') }}">مرکز گزارش‌ها</a></div>
  <div class="bd">
    <div class="chips" style="margin:0">
      <a href="{{ route('admin.accounting.checks') }}">چک‌ها — پرداختی / دریافتی / برگشتی / تحویل</a>
      <a href="{{ route('admin.accounting.installments') }}">اقساط مشتریان — تأیید و جدول اقساط</a>
      <a href="{{ route('admin.accounting.warehouses') }}">انبار چندگانه</a>
      <a href="{{ route('admin.accounting.reports.sales') }}">گزارش فروش/خرید (فیلتر شماره فاکتور)</a>
      <a href="{{ route('admin.accounting.reports.staff') }}">گزارش کارمندان و سود</a>
      <a href="{{ route('admin.accounting.reports.payroll') }}">گزارش حقوق و مزایا</a>
      <a href="{{ route('admin.accounting.reports.vouchers') }}">گزارش سند حسابداری</a>
      <a href="{{ route('admin.accounting.reports.warehouse') }}">گزارش انبار با تفکیک</a>
      <a href="{{ route('admin.accounting.reports.customers') }}">گزارش مشتریان</a>
      <a href="{{ route('admin.accounting.reports.checks') }}">گزارش چک‌ها</a>
      <a href="{{ route('admin.accounting.reports.installments') }}">گزارش اقساط</a>
    </div>
  </div>
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
