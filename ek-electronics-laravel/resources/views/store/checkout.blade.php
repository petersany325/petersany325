@extends('layouts.store')
@section('title', 'Checkout | EK Electronics')
@section('content')
<section class="wrap section">
  <h2>Checkout</h2>
  <p class="lede">Orders are placed over WhatsApp Business so sales, accounting, and the courier desk share one thread. Card gateway (PayFast) can be added later while still posting the same invoice to WhatsApp.</p>
  <div class="account-grid">
    <form class="card service form" method="post" action="{{ route('checkout.place') }}">
      @csrf
      <label>Full name <input name="customer_name" required></label>
      <label>WhatsApp number <input name="customer_phone" placeholder="+27 …" required></label>
      <label>City / courier <input name="city" value="Midrand / nationwide courier"></label>
      <label>Company (optional) <input name="company"></label>
      <label>Email (optional) <input type="email" name="customer_email"></label>
      <p>Order total <strong>R {{ number_format($items->sum('line_total'), 2) }}</strong> incl. VAT at invoice.</p>
      <button class="btn btn-wa" type="submit" @disabled($items->isEmpty())>Place order on WhatsApp</button>
    </form>
    <aside class="card service">
      <h3>Payment options</h3>
      <ul class="muted">
        <li>EFT against WhatsApp invoice (default for B2B)</li>
        <li>Card gateway (PayFast) — recommended next integration</li>
        <li>Collection from Lone Creek, Unit 15</li>
      </ul>
      <p class="notice">All order confirmations, invoices, and tracking numbers are sent on WhatsApp from the admin console.</p>
    </aside>
  </div>
</section>
@endsection
