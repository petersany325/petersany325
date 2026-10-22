@php
  $hero = $hero ?? \App\Support\StudioDesignCopy::hero();
  $services = $services ?? \App\Support\StudioDesignCopy::services();
  $reasons = $reasons ?? \App\Support\StudioDesignCopy::reasons();
  $steps = $steps ?? \App\Support\StudioDesignCopy::steps();
  $aboutBlurb = $aboutBlurb ?? \App\Support\StudioDesignCopy::aboutBlurb();
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-kicker">{{ $hero['kicker'] }}</p>
    <h1>{{ $hero['title'] }}</h1>
    <p class="ag-lead ag-lead--wide">{{ $hero['lead'] }}</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست مشاوره رایگان<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/projects') }}">مشاهده نمونه‌کارها<i>←</i></a>
    </div>
  </div>
</section>

<section class="ag-sec" id="about">
  <div class="ag-wrap">
    <p class="ag-kicker">درباره ما</p>
    <h2>فراز نت آمل، تیم طراحی و نرم‌افزار</h2>
    <p class="ag-lead ag-lead--wide">{{ $aboutBlurb }}</p>
    <a class="ag-btn ag-btn--line" href="{{ url('/about') }}">ادامه درباره ما<i>←</i></a>
  </div>
</section>

<section class="ag-sec" id="services">
  <div class="ag-wrap">
    <p class="ag-kicker">خدمات ما</p>
    <h2>از سایت شرکتی تا سامانه اختصاصی لاراول</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($services as $row)
        <a class="ag-card" href="{{ url('/design/'.$row['slug']) }}">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['short'] }}</p>
            <small>جزئیات خدمت ←</small>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="why">
  <div class="ag-wrap">
    <p class="ag-kicker">چرا فراز نت آمل؟</p>
    <h2>کدنویسی اختصاصی، نه قالب آماده</h2>
    <ul class="ag-list">
      @foreach($reasons as $row)
        <li>{{ $row }}</li>
      @endforeach
    </ul>
  </div>
</section>

<section class="ag-sec" id="steps">
  <div class="ag-wrap">
    <p class="ag-kicker">مراحل همکاری</p>
    <h2>از مشاوره تا پشتیبانی بعد از تحویل</h2>
    <ol class="ag-steps">
      @foreach($steps as $row)
        <li>
          <small>{{ $row['num'] }}</small>
          <b>{{ $row['title'] }}</b>
          <p>{{ $row['text'] }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>

<section class="ag-sec ag-sec--last" id="cta">
  <div class="ag-wrap">
    <p class="ag-kicker">دعوت به اقدام</p>
    <h2>آماده‌اید سایت کسب‌وکارتان را به سطح بعدی ببرید؟</h2>
    <p class="ag-lead ag-lead--wide">همین حالا با کارشناسان فراز نت آمل تماس بگیرید و مشاوره‌ی رایگان دریافت کنید.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">تماس با ما<i>←</i></a>
      @if(!empty($studioHome['phone']))
        <a class="ag-btn ag-btn--line" href="tel:{{ preg_replace('/\s+/', '', $studioHome['phone']) }}">{{ $studioHome['phone'] }}</a>
      @endif
    </div>
  </div>
</section>
