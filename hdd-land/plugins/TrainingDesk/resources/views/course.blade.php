@extends('layouts.storefront')

@section('title', $course['title'].' | آکادمی سرزمین هارد')

@section('content')
<link rel="stylesheet" href="{{ asset('css/training-page.css') }}?v=1">
<article class="tr-page">
  <header class="tr-hero">
    <div class="tr-wrap">
      <nav class="tr-crumbs" aria-label="مسیر">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <a href="{{ url('/training') }}">آموزش</a>
        <span>/</span>
        <span>{{ $course['title'] }}</span>
      </nav>
      <p class="tr-kicker">{{ $course['kicker'] }}</p>
      <h1>{{ $course['title'] }}</h1>
      <p class="tr-lead">{{ $course['lead'] }}</p>
      <div class="tr-meta tr-meta--hero">
        @if($course['duration'] !== '')<span>{{ $course['duration'] }}</span>@endif
        @if($course['level'] !== '')<span>{{ $course['level'] }}</span>@endif
      </div>
    </div>
  </header>

  <div class="tr-wrap tr-body">
    @if($course['intro'] !== '')
      <p class="tr-intro">{{ $course['intro'] }}</p>
    @endif

    @if($course['audience'] !== '')
      <p class="tr-who">{{ $course['audience'] }}</p>
    @endif

    @if($course['table'])
      <section class="tr-table-wrap" aria-labelledby="trPrices">
        <h2 id="trPrices">دوره‌ها و هزینه</h2>
        <div class="tr-scroll">
          <table class="tr-table">
            <thead>
              <tr>
                <th>برند / مسیر</th>
                <th>عنوان دوره</th>
                <th>مدت</th>
                <th>سطح</th>
                <th>هزینه</th>
              </tr>
            </thead>
            <tbody>
              @foreach($course['table'] as $row)
                <tr>
                  <th scope="row">{{ $row['brand'] }}</th>
                  <td>{{ $row['course'] }}</td>
                  <td>{{ $row['duration'] }}</td>
                  <td>{{ $row['level'] }}</td>
                  <td class="tr-price">{{ $row['price'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @if(trim((string) $copy['price_note']) !== '')
          <p class="tr-note">{{ $copy['price_note'] }}</p>
        @endif
      </section>
    @endif

    @if($course['syllabus'])
      <section class="tr-syl" aria-labelledby="trSyl">
        <h2 id="trSyl">سیلابس کلاس</h2>
        <ol>
          @foreach($course['syllabus'] as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ol>
      </section>
    @endif

    <div class="tr-split">
      @if($course['includes'])
        <section>
          <h2>آنچه در دوره هست</h2>
          <ul class="tr-ticks">
            @foreach($course['includes'] as $item)<li>{{ $item }}</li>@endforeach
          </ul>
        </section>
      @endif
      @if($course['prereq'])
        <section>
          <h2>پیش‌نیاز</h2>
          <ul class="tr-ticks">
            @foreach($course['prereq'] as $item)<li>{{ $item }}</li>@endforeach
          </ul>
        </section>
      @endif
    </div>

    @if($tools)
      <section class="tr-lab" aria-labelledby="trLabCourse">
        <h2 id="trLabCourse">{{ $copy['tools_title'] }}</h2>
        <ul>
          @foreach($tools as $t)<li>{{ $t }}</li>@endforeach
        </ul>
      </section>
    @endif

    @if($course['faq'])
      <section class="tr-faq" aria-labelledby="trFaq">
        <h2 id="trFaq">پرسش‌های رایج</h2>
        @foreach($course['faq'] as $item)
          <details>
            <summary>{{ $item['q'] }}</summary>
            <p>{{ $item['a'] }}</p>
          </details>
        @endforeach
      </section>
    @endif

    @if(count($courses) > 1)
      <nav class="tr-more" aria-label="سایر دوره‌ها">
        <h2>سایر مسیرهای آکادمی</h2>
        <div class="tr-more__row">
          @foreach($courses as $other)
            @continue($other['slug'] === $course['slug'])
            <a href="{{ url($other['url']) }}">
              <small>{{ $other['kicker'] }}</small>
              <strong>{{ $other['title'] }}</strong>
            </a>
          @endforeach
        </div>
      </nav>
    @endif

    <div class="tr-cta">
      <div>
        <h2>{{ $copy['cta_title'] }}</h2>
        <p>{{ $copy['cta_text'] }}</p>
      </div>
      <div class="tr-cta__btns">
        <a class="tr-btn tr-btn--on" href="{{ url($copy['cta_url']) }}">{{ $course['cta'] ?: $copy['cta_label'] }}</a>
        <a class="tr-btn" href="{{ url('/training') }}">همه دوره‌ها</a>
      </div>
    </div>
  </div>
</article>
@endsection
