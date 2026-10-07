@extends('layouts.store')
@section('title', 'Cart | EK Electronics')
@section('content')
<section class="wrap section">
  <h2>Shopping cart</h2>
  <form method="post" action="{{ route('cart.update') }}">
    @csrf
    <div class="card" style="padding:16px;overflow:auto">
      <table class="cart-table">
        <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Line</th></tr></thead>
        <tbody>
          @forelse ($items as $row)
            <tr>
              <td>{{ $row['product']->name }}</td>
              <td>R {{ number_format((float) $row['product']->price, 2) }}</td>
              <td><input class="qty" type="number" min="0" name="qty[{{ $row['product']->id }}]" value="{{ $row['qty'] }}"></td>
              <td>R {{ number_format($row['line_total'], 2) }}</td>
            </tr>
          @empty
            <tr><td colspan="4">Your cart is empty.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="toolbar-row" style="margin-block-start:16px">
      <strong>Subtotal: R {{ number_format($items->sum('line_total'), 2) }}</strong>
      <div class="cta-row">
        @if ($items->isNotEmpty())
          <button class="btn btn-outline" type="submit">Update cart</button>
          <a class="btn btn-primary" href="{{ route('checkout') }}">Checkout</a>
        @else
          <a class="btn btn-primary" href="{{ route('shop') }}">Continue shopping</a>
        @endif
      </div>
    </div>
  </form>
</section>
@endsection
