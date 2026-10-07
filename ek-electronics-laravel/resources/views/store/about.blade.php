@extends('layouts.store')
@section('title', 'About | EK Electronics')
@section('content')
<section class="wrap section">
  <div class="split">
    <div>
      <div class="kicker" style="color:var(--blue)">Who we are</div>
      <h2>EK Electronics</h2>
      <p>A South African technology solutions company specialising in Hard Drive Refurbishment, Data Recovery, Secure Data Erasure, and Computer Component Sales.</p>
      <p class="lede">We extend the life of technology — helping individuals and businesses save money, protect their data, and reduce electronic waste through innovative, reliable solutions.</p>
    </div>
    <div class="photo-frame"><img src="{{ asset('assets/img/team.jpg') }}" alt="Team" width="1200" height="800"></div>
  </div>
  <div class="grid-2" style="margin-block-start:28px">
    <article class="card service"><h3>Mission</h3><p>To provide trusted, affordable, and high-quality tech repair and recovery services, built on Integrity, Innovation, and Impact.</p></article>
    <article class="card service"><h3>Vision</h3><p>To become a leading data recovery and hardware refurbishment centre in South Africa, known for technical excellence, ethical standards, and a customer-first approach.</p></article>
  </div>
</section>
@endsection
