@extends('layouts.store')
@section('title', ucfirst($slug).' | EK Electronics')
@section('content')
<section class="wrap section">
  <h2>{{ ucfirst($slug) }}</h2>
  <div class="card service">
    @if ($slug === 'shipping')
      <p>Couriers leave Midrand within 24–48 hours of cleared payment. Nationwide door-to-door. Collection at Unit 15, Lone Creek Office Building D is free. Tracking links are pushed to WhatsApp automatically from the staff panel.</p>
    @elseif ($slug === 'warranty')
      <p>Refurbished drives carry a graded warranty matching the certificate on the listing (typically 90–365 days). DOA units are replaced after a health report. Data recovery is billed on success unless otherwise quoted. Full terms are confirmed on the WhatsApp invoice.</p>
    @else
      <p>Prices in South African Rand. Title passes on full payment. Confidentiality applies to all recovery and erasure jobs. Hosted for www.ekelectronics.co.za.</p>
    @endif
  </div>
</section>
@endsection
