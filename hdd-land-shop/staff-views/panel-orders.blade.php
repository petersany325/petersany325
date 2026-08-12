@extends('layouts.staff')
@section('title','سفارش‌ها')
@section('content')
<h1>سفارش‌ها</h1>
<p class="muted">برای جزئیات کامل می‌توانید از لینک ادمین استفاده کنید اگر دسترسی دارید.</p>
<div class="panel" style="overflow:auto">
  <table class="table">
    <thead><tr><th>شماره</th><th>مشتری</th><th>مبلغ</th><th>پرداخت</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
    <tbody>
    @forelse($orders as $o)
      <tr>
        <td>{{ $o->order_number ?? ('#'.$o->id) }}</td>
        <td>{{ $o->customer_name }}</td>
        <td>{{ number_format((int)$o->total) }}</td>
        <td>{{ $o->payment_status }}</td>
        <td>{{ $o->status }}</td>
        <td>{{ substr((string)$o->created_at,0,16) }}</td>
      </tr>
    @empty
      <tr><td colspan="6" class="muted" style="text-align:center;padding:1rem">سفارشی نیست.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
