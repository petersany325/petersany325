@extends('layouts.hdd-land')
@section('title', $title ?? 'صفحه')

@section('content')
<section class="section">
  <div class="section-head">
    <p class="kicker" style="color:var(--blue)"><i style="background:var(--red)"></i> {{ $eyebrow ?? 'HDD Land' }}</p>
    <h2>{{ $heading }}</h2>
    <p>{{ $lead }}</p>
  </div>
  {!! $body !!}
  <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.6rem">
    <a class="btn btn-red" href="{{ url('/contact') }}">درخواست بازیابی</a>
    <a class="btn btn-blue" href="{{ url('/products') }}">ورود به فروشگاه</a>
  </div>
</section>
@endsection
