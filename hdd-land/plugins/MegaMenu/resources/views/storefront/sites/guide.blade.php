@extends('layouts.storefront')

@section('title', ($guide['title'] ?? 'راهنما').' | سایت مدیریت تعمیرکاران')

@section('content')
@php
  $contactUrl = url('/contact');
  try {
    if (\Illuminate\Support\Facades\Route::has('contact')) {
      $contactUrl = route('contact');
    }
  } catch (\Throwable $e) {}
  $body = trim((string) ($guide['body'] ?? ''));
  $paras = preg_split("/\n{2,}/u", $body) ?: [];
@endphp
<section class="ws-page">
  <header class="ws-hero ws-hero--guide">
    <div class="ws-wrap">
      <nav class="ws-crumbs" aria-label="مسیر">
        <a href="{{ url('/sites') }}">طراحی و فروش سایت</a>
        <span>/</span>
        <a href="{{ url('/sites/repair-shop') }}">{{ $product['title'] ?? 'سایت مدیریت تعمیرکاران' }}</a>
        <span>/</span>
        <span>{{ $guide['title'] }}</span>
      </nav>
      <p class="ws-kicker">کارتابل مدیریت</p>
      <h1 class="ws-brand">{{ $guide['title'] }}</h1>
      <p class="ws-lead">{{ $guide['short'] }}</p>
    </div>
  </header>

  <div class="ws-wrap ws-section">
    @if($embedUrl)
      <div class="ws-video">
        <iframe src="{{ $embedUrl }}" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowFullScreen title="ویدیوی {{ $guide['title'] }}" loading="lazy"></iframe>
      </div>
    @else
      <div class="ws-video ws-video--empty">
        <strong>ویدیوی آموزشی این بخش</strong>
        <span>لینک آپارات هنوز در ادمین تنظیم نشده است.</span>
      </div>
    @endif

    <div class="ws-guide-body">
      @foreach($paras as $p)
        @if(trim($p) !== '')
          <p>{{ $p }}</p>
        @endif
      @endforeach
    </div>

    <div class="ws-guide-nav">
      <a class="ws-btn ws-btn--line" href="{{ url('/sites/repair-shop') }}">→ بازگشت به صفحه محصول</a>
      <a class="ws-btn ws-btn--accent" href="{{ $contactUrl }}?subject={{ urlencode('دمو: '.$guide['title']) }}">درخواست دمو</a>
    </div>
  </div>

  <div class="ws-wrap ws-section">
    <h2>سایر کارتابل‌ها</h2>
    <div class="ws-guide-grid">
      @foreach($guides as $g)
        @if($g['slug'] !== $guide['slug'])
          <a class="ws-guide-btn" href="{{ url('/sites/repair-shop/'.$g['slug']) }}">
            <strong>{{ $g['title'] }}</strong>
            <span>{{ $g['short'] }}</span>
            <em>مشاهده ←</em>
          </a>
        @endif
      @endforeach
    </div>
  </div>
</section>
@endsection
