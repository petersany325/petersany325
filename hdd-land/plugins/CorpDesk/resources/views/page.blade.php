@extends('layouts.storefront')

@section('title', ($copy['title'] ?? 'خدمات سازمانی').' | سرزمین هارد')

@section('content')
<link rel="stylesheet" href="{{ asset('css/corp-landing.css') }}?v=1">
<article class="cl-page">
  <header class="cl-hero">
    @if(!empty($copy['hero_image']))
      <img src="{{ \App\Support\HomePageConfig::imageUrl((string) $copy['hero_image']) }}" alt="" width="1600" height="900">
    @endif
    <div class="cl-wrap">
      <nav class="cl-crumbs" aria-label="مسیر">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <span>{{ $page === 'cctv' ? 'پروژه‌های نظارتی' : 'تأمین سازمانی' }}</span>
      </nav>
      <p class="cl-kicker">{{ $copy['kicker'] }}</p>
      <h1>{{ $copy['title'] }}</h1>
      <p class="cl-lead">{{ $copy['lead'] }}</p>
      <div class="cl-hero-cta">
        @if(!empty($copy['cta1_label']))
          <a class="cl-btn cl-btn--on" href="{{ url($copy['cta1_url'] ?: '/contact') }}">{{ $copy['cta1_label'] }}</a>
        @endif
        @if(!empty($copy['cta2_label']))
          <a class="cl-btn cl-btn--ghost" href="{{ url($copy['cta2_url'] ?: '/products') }}">{{ $copy['cta2_label'] }}</a>
        @endif
      </div>
    </div>
  </header>

  <div class="cl-wrap cl-body">
    <section class="cl-intro">
      <h2>{{ $copy['intro_title'] }}</h2>
      @foreach($intro as $para)
        <p>{{ $para }}</p>
      @endforeach
    </section>

    @if($features)
    <section class="cl-sec">
      <h2>{{ $copy['features_title'] }}</h2>
      <div class="cl-grid">
        @foreach($features as $item)
          <article class="cl-card">
            <h3>{{ $item['title'] }}</h3>
            <p>{{ $item['text'] }}</p>
          </article>
        @endforeach
      </div>
    </section>
    @endif

    @if($stats)
    <section class="cl-stats" aria-label="شاخص‌ها">
      @foreach($stats as $item)
        <div class="cl-stat"><b>{{ $item['title'] }}</b><span>{{ $item['text'] }}</span></div>
      @endforeach
    </section>
    @endif

    @if($steps)
    <section class="cl-sec">
      <h2>{{ $copy['steps_title'] }}</h2>
      <div class="cl-grid">
        @foreach($steps as $item)
          <article class="cl-step">
            <h3>{{ $item['title'] }}</h3>
            <p>{{ $item['text'] }}</p>
          </article>
        @endforeach
      </div>
    </section>
    @endif

    @if($cases)
    <section class="cl-sec">
      <h2>{{ $copy['cases_title'] }}</h2>
      <div class="cl-grid">
        @foreach($cases as $item)
          <article class="cl-case">
            <h3>{{ $item['title'] }}</h3>
            <p>{{ $item['text'] }}</p>
          </article>
        @endforeach
      </div>
    </section>
    @endif

    @if($brands)
    <section class="cl-sec">
      <h2>{{ $copy['brands_title'] }}</h2>
      <div class="cl-brands">
        @foreach($brands as $b)<span>{{ $b }}</span>@endforeach
      </div>
    </section>
    @endif

    @if($faq)
    <section class="cl-sec">
      <h2>{{ $copy['faq_title'] }}</h2>
      <div class="cl-faq">
        @foreach($faq as $item)
          <details>
            <summary>{{ $item['title'] }}</summary>
            <p>{{ $item['text'] }}</p>
          </details>
        @endforeach
      </div>
    </section>
    @endif

    <div class="cl-cta">
      <div>
        <h2>{{ $copy['cta_title'] }}</h2>
        <p>{{ $copy['cta_text'] }}</p>
      </div>
      <div class="cl-hero-cta">
        <a class="cl-btn cl-btn--on" href="{{ url($copy['cta_url'] ?: '/contact') }}">{{ $copy['cta_label'] }}</a>
        @if(!empty($copy['alt_label']))
          <a class="cl-btn cl-btn--ghost" href="{{ url($copy['alt_url'] ?: '/') }}">{{ $copy['alt_label'] }}</a>
        @endif
      </div>
    </div>
  </div>
</article>
@endsection
