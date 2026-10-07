@extends('layouts.store')
@section('title', 'Services | EK Electronics')
@section('content')
<section class="wrap section">
  <h2>Our services</h2>
  <p class="lede">Precision work for people and businesses who need storage that is honest about its health — and data that is either recovered or gone for good.</p>
  <div class="grid-3">
    <article class="service card">
      <img src="{{ asset('assets/img/hdd.jpg') }}" alt="Refurbished hard drives" style="border-radius:12px;aspect-ratio:16/9;object-fit:cover;inline-size:100%">
      <h3>Hard drive refurbishment</h3>
      <p class="muted">Multi-stage diagnostics: health testing, surface scanning, sector repair, and performance verification. Units are graded and certified for resellers, workshops, and budget-conscious buyers.</p>
      <p><strong>Save money without compromising on performance.</strong></p>
    </article>
    <article class="service card">
      <img src="{{ asset('assets/img/lab.jpg') }}" alt="Data recovery bench" style="border-radius:12px;aspect-ratio:16/9;object-fit:cover;inline-size:100%">
      <h3>Data recovery</h3>
      <p class="muted">Lost files, failed, formatted, or non-detecting media. We recover from HDD, SSD, flash, externals, and RAID with urgency and confidentiality.</p>
      <a class="btn btn-wa" href="https://wa.me/{{ $settings['whatsapp'] }}?text={{ urlencode('Request a data recovery assessment.') }}">Request an assessment</a>
    </article>
    <article class="service card">
      <img src="{{ asset('assets/img/security.jpg') }}" alt="Secure data practices" style="border-radius:12px;aspect-ratio:16/9;object-fit:cover;inline-size:100%">
      <h3>Secure data erasure</h3>
      <p class="muted">Certified wiping for disposal or resale using industry-approved methods so data cannot be recovered.</p>
      <p><strong>Protect your privacy. Stay compliant.</strong></p>
    </article>
  </div>
  <h3 style="margin-block-start:36px">Add-on services</h3>
  <div class="grid-4">
    <div class="card service">Drive diagnostics &amp; health reports</div>
    <div class="card service">Custom bulk wiping &amp; asset disposal</div>
    <div class="card service">Corporate hardware buy-backs</div>
    <div class="card service">On-site data recovery consultations</div>
  </div>
</section>
@endsection
