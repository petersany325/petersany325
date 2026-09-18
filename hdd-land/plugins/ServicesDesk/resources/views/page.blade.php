@extends('layouts.storefront')

@section('title', $copy['title'].' | سرزمین هارد')

@section('content')
<link rel="stylesheet" href="{{ asset('css/services-page.css') }}?v=1">
<article class="sv-page">
  <header class="sv-hero">
    <div class="sv-wrap">
      <nav class="sv-crumbs" aria-label="مسیر">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <span>خدمات سازمانی</span>
      </nav>
      <p class="sv-kicker">{{ $copy['kicker'] }}</p>
      <h1>{{ $copy['title'] }}</h1>
      <p class="sv-lead">{{ $copy['lead'] }}</p>
    </div>
  </header>

  <div class="sv-wrap sv-body">
    @if(trim((string) $copy['intro']) !== '')
      <p class="sv-intro">{{ $copy['intro'] }}</p>
    @endif

    <div class="sv-grid">
      @foreach($cards as $i => $card)
        <article class="sv-card">
          <em>{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</em>
          <h2>{{ $card['title'] }}</h2>
          @if($card['audience'] !== '')
            <p class="sv-who">{{ $card['audience'] }}</p>
          @endif
          <p>{{ $card['text'] }}</p>
          @if($card['cta'] !== '')
            <a href="{{ url($card['url']) }}">{{ $card['cta'] }} ←</a>
          @endif
        </article>
      @endforeach
    </div>

    @if($tools)
      <section class="sv-lab" aria-labelledby="svLab">
        <h2 id="svLab">{{ $copy['lab_title'] }}</h2>
        <ul>
          @foreach($tools as $t)<li>{{ $t }}</li>@endforeach
        </ul>
      </section>
    @endif

    <div class="sv-cta">
      <div>
        <h2>{{ $copy['cta_title'] }}</h2>
        <p>{{ $copy['cta_text'] }}</p>
      </div>
      <div class="sv-cta__btns">
        <a class="sv-btn sv-btn--on" href="{{ url($copy['cta_url']) }}">{{ $copy['cta_label'] }}</a>
        @if(trim((string) $copy['alt_label']) !== '')
          <a class="sv-btn" href="{{ url($copy['alt_url']) }}">{{ $copy['alt_label'] }}</a>
        @endif
      </div>
    </div>
  </div>
</article>
@endsection
