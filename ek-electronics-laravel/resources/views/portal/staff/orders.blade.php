@extends('layouts.portal')
@section('title', 'Staff orders')
@section('content')
<div class="portal-hero"><h1>Shop orders</h1><p>Linked to the storefront checkout and WhatsApp.</p></div>
<div class="panel">
  @forelse ($orders as $order)
    <div class="list-row">
      <div><strong>{{ $order->number }}</strong><br><small>{{ $order->customer_name }} · {{ $order->customer_phone }}</small></div>
      <div><span class="badge-soft">{{ $order->status }}</span><br><small>R {{ number_format((float) $order->total, 2) }}</small></div>
    </div>
  @empty
    <p>No orders.</p>
  @endforelse
  <div style="margin-top:12px">{{ $orders->links() }}</div>
</div>
@endsection
