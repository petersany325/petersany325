@extends('layouts.portal')
@section('title', 'Staff desk')
@section('content')
<div class="portal-hero">
  <h1>Staff desk</h1>
  <p>Mobile operations for orders, tickets, and shop-linked accounting.</p>
</div>
<div class="stat-row">
  <div class="stat-tile"><span>New orders</span><strong>{{ $ordersOpen }}</strong></div>
  <div class="stat-tile"><span>Open tickets</span><strong>{{ $ticketsOpen }}</strong></div>
  <div class="stat-tile"><span>Open invoices</span><strong>{{ $invoicesOpen }}</strong></div>
  <div class="stat-tile"><span>Low stock</span><strong>{{ $lowStock->count() }}</strong></div>
</div>
<div class="portal-actions">
  <a class="btn-portal primary" href="{{ route('staff.orders') }}">Orders</a>
  <a class="btn-portal" href="{{ route('staff.tickets') }}">Tickets</a>
  <a class="btn-portal" href="{{ route('staff.accounting') }}">P&amp;L</a>
  @if(auth()->user()->isAdmin())
    <a class="btn-portal" href="{{ url('/admin') }}">Full admin</a>
  @endif
</div>
<div class="panel">
  <h2>Recovery jobs</h2>
  @forelse ($jobs as $job)
    <div class="list-row">
      <div><strong>{{ $job->number }}</strong><br><small>{{ $job->client_name }} · {{ $job->media }}</small></div>
      <span class="badge-soft">{{ $job->stage }}</span>
    </div>
  @empty
    <p>No recovery jobs.</p>
  @endforelse
</div>
<div class="panel">
  <h2>Low stock</h2>
  @forelse ($lowStock as $product)
    <div class="list-row">
      <div><strong>{{ $product->name }}</strong><br><small>{{ $product->sku }}</small></div>
      <span class="badge-soft">{{ $product->stock }} left</span>
    </div>
  @empty
    <p>Stock levels look healthy.</p>
  @endforelse
</div>
@endsection
