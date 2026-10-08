@extends('layouts.portal')

@section('title', 'My account')
@section('body_class', 'portal-account')

@section('content')
@php($portal = 'account')
<div class="portal-hero">
  <h1>Hello, {{ auth()->user()->name }}</h1>
  <p>Your orders, invoices, and support tickets in one place.</p>
</div>
<div class="stat-row">
  <div class="stat-tile"><span>Orders</span><strong>{{ $orders->count() }}</strong></div>
  <div class="stat-tile"><span>Tickets</span><strong>{{ $tickets->count() }}</strong></div>
  <div class="stat-tile"><span>Invoices</span><strong>{{ $invoices->count() }}</strong></div>
  <div class="stat-tile"><span>Cart</span><strong>{{ collect(session('cart', []))->sum() }}</strong></div>
</div>
<div class="portal-actions">
  <a class="btn-portal primary" href="{{ route('account.tickets.create') }}">New ticket</a>
  <a class="btn-portal" href="{{ route('shop') }}">Continue shopping</a>
  <a class="btn-portal" href="{{ route('account.orders') }}">All orders</a>
</div>
<div class="panel">
  <h2>Recent orders</h2>
  @forelse ($orders as $order)
    <div class="list-row">
      <div><strong>{{ $order->number }}</strong><br><small>{{ $order->created_at?->format('Y-m-d') }}</small></div>
      <div><span class="badge-soft">{{ $order->status }}</span><br><small>R {{ number_format((float) $order->total, 2) }}</small></div>
    </div>
  @empty
    <p class="blurb">No orders yet linked to {{ auth()->user()->email }}.</p>
  @endforelse
</div>
<div class="panel">
  <h2>Support tickets</h2>
  @forelse ($tickets as $ticket)
    <a class="list-row" href="{{ route('account.tickets.show', $ticket) }}">
      <div><strong>{{ $ticket->number }}</strong><br><small>{{ $ticket->subject }}</small></div>
      <span class="badge-soft">{{ $ticket->status }}</span>
    </a>
  @empty
    <p class="blurb">No tickets yet.</p>
  @endforelse
</div>
@endsection
