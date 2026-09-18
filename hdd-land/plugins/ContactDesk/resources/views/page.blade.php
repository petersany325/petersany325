@extends('layouts.storefront')

@section('title', $copy['title'].' | سرزمین هارد')

@section('content')
@php
  $C = \Plugins\ContactDesk\src\Support\ContactCopy::class;
  $ticketUrl = url($copy['ticket_url'] ?: '/account/tickets');
@endphp
<link rel="stylesheet" href="{{ asset('css/contact-page.css') }}?v=1">
<article class="ct-page">
  <header class="ct-hero">
    <div class="ct-wrap">
      <nav class="ct-crumbs" aria-label="مسیر">
        <a href="{{ url('/') }}">خانه</a><span>/</span><span>تماس</span>
      </nav>
      <p class="ct-kicker">{{ $copy['kicker'] }}</p>
      <h1>{{ $copy['title'] }}</h1>
      <p class="ct-lead">{{ $copy['lead'] }}</p>
      <a class="ct-phone" href="{{ $C::telHref($copy['phone']) }}">
        <small>{{ $copy['phone_label'] }}</small>
        <strong dir="ltr">{{ $copy['phone_display'] ?: $copy['phone'] }}</strong>
      </a>
    </div>
  </header>

  <div class="ct-wrap ct-body">
    <a class="ct-ticket" href="{{ $ticketUrl }}">
      <span>تیکت</span>
      <strong>{{ $copy['ticket_title'] }}</strong>
      <p>{{ $copy['ticket_text'] }}</p>
      <em>{{ $copy['ticket_cta'] }} ←</em>
    </a>

    <div class="ct-grid">
      <a class="ct-card" href="{{ $C::waHref($copy['wa1']) }}" target="_blank" rel="noopener">
        <small>{{ $copy['wa1_label'] }}</small>
        <strong dir="ltr">{{ $copy['wa1_display'] ?: $copy['wa1'] }}</strong>
        <em>گفتگو در واتساپ</em>
      </a>
      <a class="ct-card" href="{{ $C::waHref($copy['wa2']) }}" target="_blank" rel="noopener">
        <small>{{ $copy['wa2_label'] }}</small>
        <strong dir="ltr">{{ $copy['wa2_display'] ?: $copy['wa2'] }}</strong>
        <em>گفتگو در واتساپ</em>
      </a>
      <a class="ct-card" href="{{ $C::tgHref($copy['tg']) }}" target="_blank" rel="noopener">
        <small>{{ $copy['tg_label'] }}</small>
        <strong dir="ltr">&#64;{{ ltrim($copy['tg'], '@') }}</strong>
        <em>باز کردن تلگرام</em>
      </a>
      <a class="ct-card" href="{{ $C::igHref($copy['ig']) }}" target="_blank" rel="noopener">
        <small>{{ $copy['ig_label'] }}</small>
        <strong dir="ltr">{{ $copy['ig'] }}</strong>
        <em>مشاهده اینستاگرام</em>
      </a>
    </div>

    <section class="ct-office">
      <div>
        <small>{{ $copy['office_label'] }}</small>
        <p>{{ $copy['office'] }}</p>
        @if(trim((string) $copy['hours']) !== '')
          <p class="ct-hours">{{ $copy['hours_label'] }}: {{ $copy['hours'] }}</p>
        @endif
      </div>
      @if(trim((string) $copy['map_url']) !== '')
        <a class="ct-map" href="{{ $copy['map_url'] }}" target="_blank" rel="noopener">{{ $copy['map_label'] }}</a>
      @endif
    </section>
  </div>
</article>
@endsection
