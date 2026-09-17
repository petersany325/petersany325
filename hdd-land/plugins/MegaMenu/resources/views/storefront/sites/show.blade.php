@extends('layouts.storefront')

@section('title', $item['title'].' | طراحی و فروش سایت')

@section('content')
@php
  $contactUrl = url('/contact');
  try {
    if (\Illuminate\Support\Facades\Route::has('contact')) {
      $contactUrl = route('contact');
    }
  } catch (\Throwable $e) {}
  $hasSections = ! empty($item['sections']) && is_array($item['sections']);
  $features = $item['features'] ?? [];
@endphp
<section class="ws-page">
  <header class="ws-hero">
    <div class="ws-wrap">
      <nav class="ws-crumbs" aria-label="مسیر">
        <a href="{{ url('/sites') }}">طراحی و فروش سایت</a>
        <span>/</span>
        <span>{{ $item['title'] }}</span>
      </nav>
      <p class="ws-kicker">محصول آماده فروش</p>
      <h1 class="ws-brand">{{ $item['title'] }}</h1>
      <p class="ws-lead">{{ $item['tagline'] }}</p>
      <div class="ws-cta-row">
        <a class="ws-btn ws-btn--accent" href="{{ $contactUrl }}?subject={{ urlencode('درخواست دمو: '.$item['title']) }}">درخواست دمو و قیمت</a>
        <a class="ws-btn ws-btn--ghost" href="#ws-features">{{ $hasSections ? 'جزئیات سیستم' : 'امکانات محصول' }}</a>
      </div>
    </div>
  </header>

  <div class="ws-wrap ws-section" id="ws-features">
    @if($hasSections)
      <h2>مدیریت تعمیرکاران — چندلایه</h2>
      @if(!empty($item['detail_intro']))
        <p class="ws-sub">{{ $item['detail_intro'] }}</p>
      @endif

      <div class="ws-layers">
        @foreach($item['sections'] as $section)
          <article class="ws-layer">
            <h3>{{ $section['title'] }}</h3>
            @if(!empty($section['lead']))
              <p class="ws-layer__lead">{{ $section['lead'] }}</p>
            @endif
            @if(!empty($section['steps']))
              <ol class="ws-steps">
                @foreach($section['steps'] as $step)
                  <li>{{ $step }}</li>
                @endforeach
              </ol>
            @endif
            @if(!empty($section['items']))
              <ul class="ws-bullets">
                @foreach($section['items'] as $row)
                  <li>{{ $row }}</li>
                @endforeach
              </ul>
            @endif
          </article>
        @endforeach
      </div>

      @if(!empty($item['detail_summary']))
        <div class="ws-summary">
          <strong>خلاصه:</strong>
          <span>{{ $item['detail_summary'] }}</span>
        </div>
      @endif
    @else
      <h2>امکانات کلیدی</h2>
      <p class="ws-sub">همه جزئیات در همین صفحه است تا منوی سایت شلوغ نشود.</p>
      <ul class="ws-features">
        @foreach($features as $f)
          <li>{{ $f }}</li>
        @endforeach
      </ul>
    @endif
  </div>

  <div class="ws-wrap ws-section">
    <h2>آموزش منوها و ویدیو</h2>
    <p class="ws-sub">در مرحله بعد، برای هر بخش منوی مدیریت این محصول صفحه آموزش فارسی + ویدیوی آپارات اضافه می‌شود.</p>
    <div class="ws-soon">
      <strong>به‌زودی:</strong>
      مرکز آموزش منو‌به‌منو، تنظیمات ویدیو آپارات و محتوای سئو برای «{{ $item['title'] }}».
    </div>
  </div>

  <div class="ws-wrap ws-section">
    <h2>سایر انواع سایت</h2>
    <div class="ws-related">
      @foreach($items as $row)
        @if($row['slug'] !== $item['slug'])
          <a href="{{ url('/sites/'.$row['slug']) }}">{{ $row['title'] }}</a>
        @endif
      @endforeach
    </div>
  </div>

  <div class="ws-wrap">
    <div class="ws-band">
      <div>
        <h2>آماده راه‌اندازی {{ $item['title'] }} هستید؟</h2>
        <p>درخواست دمو بدهید تا پکیج و زمان تحویل را اعلام کنیم.</p>
      </div>
      <a class="ws-btn ws-btn--accent" href="{{ $contactUrl }}">تماس / درخواست دمو</a>
    </div>
  </div>
</section>
@endsection
