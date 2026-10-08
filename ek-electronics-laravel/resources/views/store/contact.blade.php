@extends('layouts.store')
@section('title', 'Contact | EK Electronics')
@section('content')
<section class="wrap section">
  <h2>Contact us</h2>
  <p class="lede">Support and general enquiries. Drop a pin at Waterfall Business Park or message the desk on WhatsApp — invoices and job status leave from the same number.</p>
  <div class="grid-2">
    <div class="card service">
      <h3>Midrand office</h3>
      <p>{!! nl2br(e($settings['address'])) !!}</p>
      <p>Phone: <a href="tel:{{ preg_replace('/\s+/', '', $settings['phone']) }}">{{ $settings['phone'] }}</a><br>
      Email: <a href="mailto:{{ $settings['email'] }}">{{ $settings['email'] }}</a></p>
      <a class="btn btn-wa" href="https://wa.me/{{ $settings['whatsapp'] }}">Message on WhatsApp</a>
      <form class="form" style="margin-block-start:18px" method="post" action="{{ route('contact.whatsapp') }}">
        @csrf
        <label>Name <input name="name" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label>Message <textarea name="message" rows="4" required></textarea></label>
        <button class="btn btn-primary" type="submit">Send via WhatsApp</button>
      </form>
    </div>
    <iframe class="map" title="EK Electronics Midrand map" src="https://maps.google.com/maps?q=Lone%20Creek%20Office%20Building%20Waterfall%20Business%20Park%20Midrand&t=&z=15&ie=UTF8&iwloc=&output=embed"></iframe>
  </div>
</section>
@endsection
