@extends('layouts.storefront')

@section('title', $copy['hub_title'].' | سرزمین هارد')

@section('content')
<link rel="stylesheet" href="{{ asset('css/training-page.css') }}?v=1">
<article class="tr-page">
  <header class="tr-hero">
    <div class="tr-wrap">
      <nav class="tr-crumbs" aria-label="مسیر">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <span>آموزش</span>
      </nav>
      <p class="tr-kicker">{{ $copy['hub_kicker'] }}</p>
      <h1>{{ $copy['hub_title'] }}</h1>
      <p class="tr-lead">{{ $copy['hub_lead'] }}</p>
      <ul class="tr-stats">
        @foreach(['hub_stat_1','hub_stat_2','hub_stat_3','hub_stat_4'] as $k)
          @if(trim((string) $copy[$k]) !== '')
            <li>{{ $copy[$k] }}</li>
          @endif
        @endforeach
      </ul>
    </div>
  </header>

  <div class="tr-wrap tr-body">
    @if(trim((string) $copy['hub_intro']) !== '')
      <p class="tr-intro">{{ $copy['hub_intro'] }}</p>
    @endif

    <div class="tr-grid">
      @foreach($courses as $i => $course)
        <article class="tr-card">
          <em>{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</em>
          <small>{{ $course['kicker'] }}</small>
          <h2>{{ $course['title'] }}</h2>
          <p>{{ $course['card'] }}</p>
          <div class="tr-meta">
            @if($course['duration'] !== '')<span>{{ $course['duration'] }}</span>@endif
            @if($course['level'] !== '')<span>{{ $course['level'] }}</span>@endif
          </div>
          <a href="{{ url($course['url']) }}">سیلابس، برندها و هزینه ←</a>
        </article>
      @endforeach
    </div>

    @if($tools)
      <section class="tr-lab" aria-labelledby="trLab">
        <h2 id="trLab">{{ $copy['tools_title'] }}</h2>
        <ul>
          @foreach($tools as $t)<li>{{ $t }}</li>@endforeach
        </ul>
      </section>
    @endif

    @if(trim((string) $copy['price_note']) !== '')
      <p class="tr-note">{{ $copy['price_note'] }}</p>
    @endif

    <div class="tr-cta">
      <div>
        <h2>{{ $copy['cta_title'] }}</h2>
        <p>{{ $copy['cta_text'] }}</p>
      </div>
      <div class="tr-cta__btns">
        <a class="tr-btn tr-btn--on" href="{{ url($copy['cta_url']) }}">{{ $copy['cta_label'] }}</a>
        @if(trim((string) $copy['alt_label']) !== '')
          <a class="tr-btn" href="{{ url($copy['alt_url']) }}">{{ $copy['alt_label'] }}</a>
        @endif
      </div>
    </div>
  </div>
</article>
@endsection
