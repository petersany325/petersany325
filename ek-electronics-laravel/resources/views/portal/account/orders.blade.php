@extends('layouts.portal')
@section('title', 'My orders')
@section('content')
@php($portal = 'account')
<div class="portal-hero"><h1>Orders</h1><p>Purchases linked to your email.</p></div>
<div class="panel">
  @forelse ($orders as $order)
    <div class="list-row">
      <div><strong>{{ $order->number }}</strong><br><small>{{ $order->created_at?->format('Y-m-d H:i') }} · {{ $order->city }}</small></div>
      <div><span class="badge-soft">{{ $order->status }}</span><br><small>R {{ number_format((float) $order->total, 2) }}</small></div>
    </div>
  @empty
    <p>No orders found.</p>
  @endforelse
  <div style="margin-top:12px">{{ $orders->links() }}</div>
</div>
@endsection
