@extends('layouts.store')
@section('title', 'EK Electronics | Hard Drives, Data Recovery & Reliable Tech')
@section('content')
<section>
  <div class="hero">
    <img src="{{ asset('assets/img/hero.jpg') }}" alt="Enterprise server racks" width="1600" height="900">
    <div class="hero-copy">
      <div class="kicker">{{ $settings['tagline'] ?? 'Innovation. Integrity. Impact.' }}</div>
      <h1>We’re experts in Hard Drives, Data Recovery, and Reliable Tech Solutions.</h1>
      <p>Buy certified refurbished storage and components online. Recover lost data. Wipe drives to a compliant standard. EK Electronics serves individuals, IT companies, and resellers across South Africa.</p>
      <div class="cta-row">
        <a class="btn btn-primary" href="{{ route('shop') }}">Shop the catalogue</a>
        <a class="btn btn-ghost" href="{{ route('services') }}">Book data recovery</a>
        <a class="btn btn-wa" href="https://wa.me/{{ $settings['whatsapp'] }}?text={{ urlencode('Hi EK, I need a data recovery assessment.') }}">WhatsApp a technician</a>
      </div>
    </div>
  </div>
  <div class="wrap">
    <div class="stats">
      <div class="stat"><b>885+</b><span>SKU in the live product feed</span></div>
      <div class="stat"><b>Grade A / A+</b><span>Tested, certified refurbished drives</span></div>
      <div class="stat"><b>24–48h</b><span>Courier dispatch from Midrand</span></div>
      <div class="stat"><b>WhatsApp-first</b><span>Orders, invoices &amp; recovery updates</span></div>
    </div>
  </div>
  <div class="wrap section">
    <h2>Shop bestsellers</h2>
    <p class="lede">Direct cart checkout in ZAR. Pay on invoice or confirm stock on WhatsApp — both paths land in the same order desk.</p>
    <div class="grid-4">
      @foreach ($featured as $product)
        @include('store.partials.product-card', ['product' => $product])
      @endforeach
    </div>
  </div>
  <div class="wrap section">
    <div class="split">
      <div class="photo-frame"><img src="{{ asset('assets/img/lab.jpg') }}" alt="Technician diagnostics" width="1200" height="800"></div>
      <div>
        <div class="kicker" style="color:var(--blue)">Lab in Midrand</div>
        <h2>Recover the unrecoverable. Supply drives that last.</h2>
        <p class="lede">Every refurbished unit is health-tested, surface-scanned, and graded. Data recovery cases are handled confidentially with professional tools for HDD, SSD, flash, and RAID.</p>
        <div class="cta-row">
          <a class="btn btn-primary" href="{{ route('about') }}">Our story</a>
          <a class="btn btn-outline" href="{{ route('contact') }}">Visit the office</a>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
