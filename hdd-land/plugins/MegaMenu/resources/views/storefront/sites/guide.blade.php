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
  $blocks = \Plugins\MegaMenu\Plugin::repairGuideBodyBlocks($body);
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

    <article class="ws-guide-body">
      @foreach($blocks as $block)
        @if(($block['type'] ?? '') === 'h2')
          <h2 class="ws-guide-h">{{ $block['text'] }}</h2>
        @elseif(($block['type'] ?? '') === 'ul')
          <ul class="ws-guide-list">
            @foreach(($block['items'] ?? []) as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        @elseif(($block['type'] ?? '') === 'ol')
          <ol class="ws-guide-steps">
            @foreach(($block['items'] ?? []) as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ol>
        @elseif(($block['type'] ?? '') === 'quote')
          <blockquote class="ws-guide-quote">{{ $block['text'] }}</blockquote>
        @elseif(($block['type'] ?? '') === 'p')
          <p>{!! nl2br(e($block['text'] ?? '')) !!}</p>
        @endif
      @endforeach
    </article>

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
