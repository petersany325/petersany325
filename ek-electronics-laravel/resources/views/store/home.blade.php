@extends('layouts.store')
@section('title', $settings['home_meta_title'] ?? 'EK Electronics | Hard Drives, Data Recovery & Reliable Tech')
@section('content')
@php
  $img = fn (?string $path) => \App\Support\HomepageContent::imageUrl($path);
@endphp
<section>
  @if($settings['home_show_hero'] ?? true)
    <div class="hero">
      @if($img($settings['hero_image'] ?? ''))
        <img src="{{ $img($settings['hero_image'] ?? '') }}" alt="" width="1600" height="900">
      @endif
      <div class="hero-copy">
        <div class="kicker">{{ $settings['hero_kicker'] ?? ($settings['tagline'] ?? '') }}</div>
        <h1>{{ $settings['hero_headline'] ?? '' }}</h1>
        <p>{{ $settings['hero_sub'] ?? '' }}</p>
        <div class="cta-row">
          @if(!empty($settings['hero_cta_label']))
            <a class="btn btn-primary" href="{{ url($settings['hero_cta_url'] ?? '/shop') }}">{{ $settings['hero_cta_label'] }}</a>
          @endif
          @if(!empty($settings['hero_cta2_label']))
            <a class="btn btn-ghost" href="{{ url($settings['hero_cta2_url'] ?? '/services') }}">{{ $settings['hero_cta2_label'] }}</a>
          @endif
          @if($settings['hero_show_whatsapp'] ?? true)
            <a class="btn btn-wa" href="https://wa.me/{{ $settings['whatsapp'] }}?text={{ urlencode($settings['whatsapp_default_message'] ?? 'Hi EK, I need a data recovery assessment.') }}">WhatsApp a technician</a>
          @endif
        </div>
      </div>
    </div>
  @endif

  @if($settings['home_show_stats'] ?? true)
    <div class="wrap">
      <div class="stats">
        @foreach ([1, 2, 3, 4] as $n)
          @if(!empty($settings['stat_'.$n.'_value']) || !empty($settings['stat_'.$n.'_label']))
            <div class="stat"><b>{{ $settings['stat_'.$n.'_value'] ?? '' }}</b><span>{{ $settings['stat_'.$n.'_label'] ?? '' }}</span></div>
          @endif
        @endforeach
      </div>
    </div>
  @endif

  @if($settings['home_show_bestsellers'] ?? true)
    <div class="wrap section">
      <h2>{{ $settings['bestsellers_title'] ?? 'Shop bestsellers' }}</h2>
      <p class="lede">{{ $settings['bestsellers_lede'] ?? '' }}</p>
      <div class="grid-4">
        @foreach ($featured as $product)
          @include('store.partials.product-card', ['product' => $product])
        @endforeach
      </div>
    </div>
  @endif

  @if($settings['home_show_lab'] ?? true)
    <div class="wrap section">
      <div class="split">
        <div class="photo-frame">
          @if($img($settings['lab_image'] ?? ''))
            <img src="{{ $img($settings['lab_image'] ?? '') }}" alt="" width="1200" height="800">
          @endif
        </div>
        <div>
          <div class="kicker" style="color:var(--blue)">{{ $settings['lab_kicker'] ?? '' }}</div>
          <h2>{{ $settings['lab_heading'] ?? '' }}</h2>
          <p class="lede">{{ $settings['lab_body'] ?? '' }}</p>
          <div class="cta-row">
            @if(!empty($settings['lab_cta_label']))
              <a class="btn btn-primary" href="{{ url($settings['lab_cta_url'] ?? '/about') }}">{{ $settings['lab_cta_label'] }}</a>
            @endif
            @if(!empty($settings['lab_cta2_label']))
              <a class="btn btn-outline" href="{{ url($settings['lab_cta2_url'] ?? '/contact') }}">{{ $settings['lab_cta2_label'] }}</a>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif
</section>
@endsection