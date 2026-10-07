@extends('layouts.portal')
@section('title', 'Accounting')
@section('content')
<div class="portal-hero">
  <h1>Profit &amp; loss</h1>
  <p>Shop sales linked to product costs and recorded expenses.</p>
</div>
<div class="stat-row">
  <div class="stat-tile"><span>Revenue</span><strong>R {{ number_format($revenue, 2) }}</strong></div>
  <div class="stat-tile"><span>COGS</span><strong>R {{ number_format($cogs, 2) }}</strong></div>
  <div class="stat-tile"><span>Gross</span><strong>R {{ number_format($gross, 2) }}</strong></div>
  <div class="stat-tile"><span>Net</span><strong>R {{ number_format($net, 2) }}</strong></div>
</div>
<div class="panel">
  <h2>Summary</h2>
  <div class="list-row"><span>Operating expenses</span><strong>R {{ number_format($expenses, 2) }}</strong></div>
  <div class="list-row"><span>Open invoices</span><strong>R {{ number_format($openInvoices, 2) }}</strong></div>
</div>
<div class="panel">
  <h2>Recent paid shop orders</h2>
  @forelse ($recentOrders as $order)
    <div class="list-row">
      <div><strong>{{ $order->number }}</strong><br><small>{{ $order->customer_name }}</small></div>
      <small>R {{ number_format((float) $order->total, 2) }}</small>
    </div>
  @empty
    <p>No paid orders yet.</p>
  @endforelse
</div>
<div class="panel">
  <h2>Recent expenses</h2>
  @forelse ($expenseRows as $expense)
    <div class="list-row">
      <div><strong>{{ $expense->description }}</strong><br><small>{{ $expense->spent_on?->format('Y-m-d') }} · {{ $expense->category }}</small></div>
      <small>R {{ number_format((float) $expense->amount, 2) }}</small>
    </div>
  @empty
    <p>No expenses recorded.</p>
  @endforelse
</div>
@endsection
