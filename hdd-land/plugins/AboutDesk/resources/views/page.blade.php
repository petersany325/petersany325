@extends('layouts.storefront')

@section('title', 'درباره ما | سرزمین هارد — HDD Land')

@section('content')
<link rel="stylesheet" href="{{ asset('css/about-page.css') }}?v=1">
<article class="ab-page">
  <header class="ab-hero">
    <div class="ab-orbit" aria-hidden="true">
      <i class="ab-ring ab-ring--a"></i>
      <i class="ab-ring ab-ring--b"></i>
      <i class="ab-ring ab-ring--c"></i>
      <i class="ab-hub"></i>
    </div>
    <div class="ab-wrap ab-hero__inner">
      <nav class="ab-crumbs" aria-label="مسیر">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <span>درباره ما</span>
      </nav>
      <p class="ab-kicker">{{ $copy['kicker'] }}</p>
      <h1 class="ab-3d">
        <span class="ab-3d__depth" aria-hidden="true">{{ $copy['title_3d'] }}</span>
        <span class="ab-3d__face">{{ $copy['title_3d'] }}</span>
      </h1>
      <p class="ab-brandline">
        <b>{{ $copy['brand'] }}</b>
        <span>{{ $copy['legal'] }}</span>
      </p>
      <div class="ab-sites">
        <a href="{{ $copy['site_1_url'] }}" target="_blank" rel="noopener">{{ $copy['site_1_label'] }}</a>
        <a href="{{ $copy['site_2_url'] }}" target="_blank" rel="noopener">{{ $copy['site_2_label'] }}</a>
      </div>
    </div>
  </header>

  <div class="ab-wrap ab-body">
    <aside class="ab-seal" aria-label="نمایندگی">
      <strong>نماینده رسمی SeDiv</strong>
      <span>فروش و پشتیبانی جهانی · تنها نماینده برتر</span>
    </aside>

    <div class="ab-prose">
      @foreach($paragraphs as $p)
        <p>{{ $p }}</p>
      @endforeach
    </div>

    <section class="ab-lab" aria-labelledby="abLabTitle">
      <h2 id="abLabTitle">{{ $copy['lab_title'] }}</h2>
      <ul>
        @foreach($tools as $tool)
          <li>{{ $tool }}</li>
        @endforeach
      </ul>
    </section>

    <div class="ab-cta">
      <a class="ab-btn ab-btn--on" href="{{ url('/contact') }}">تماس با سرزمین هارد</a>
      <a class="ab-btn" href="{{ url('/serial-check') }}">استعلام گارانتی</a>
      <a class="ab-btn" href="{{ url('/warranty-register') }}">ثبت گارانتی سازمانی</a>
    </div>
  </div>
</article>
@endsection
