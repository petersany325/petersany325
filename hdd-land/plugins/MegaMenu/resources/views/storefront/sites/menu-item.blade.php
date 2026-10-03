@extends('layouts.storefront')

@section('title', ($page['title'] ?? 'آموزش منو').' | سایت مدیریت تعمیرکاران')

@section('content')
@php
  $contactUrl = url('/contact');
  try {
    if (\Illuminate\Support\Facades\Route::has('contact')) {
      $contactUrl = route('contact');
    }
  } catch (\Throwable $e) {}
  $menuLabel = ($page['menu'] ?? '') === 'customer' ? 'منوی کارتابل مشتری' : 'منوی کارکنان';
@endphp
<section class="ws-page">
  <header class="ws-hero ws-hero--guide">
    <div class="ws-wrap">
      <nav class="ws-crumbs" aria-label="مسیر">
        <a href="{{ url('/sites') }}">طراحی و فروش سایت</a>
        <span>/</span>
        <a href="{{ url('/sites/repair-shop') }}">{{ $product['title'] ?? 'سایت مدیریت تعمیرکاران' }}</a>
        <span>/</span>
        <a href="{{ $menuBack }}">{{ $menuLabel }}</a>
        @if(!empty($page['parent']['title']))
          <span>/</span>
          <a href="{{ url('/sites/repair-shop/m/'.$page['parent']['slug']) }}">{{ $page['parent']['title'] }}</a>
        @endif
        <span>/</span>
        <span>{{ $page['title'] }}</span>
      </nav>
      <p class="ws-kicker">{{ $menuLabel }}</p>
      <h1 class="ws-brand">{{ $page['title'] }}</h1>
      <p class="ws-lead">{{ $page['short'] }}</p>
    </div>
  </header>

  <div class="ws-wrap ws-section">
    @if($embedUrl)
      <div class="ws-video">
        <iframe src="{{ $embedUrl }}" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowFullScreen title="ویدیوی {{ $page['title'] }}" loading="lazy"></iframe>
      </div>
    @else
      <div class="ws-video ws-video--empty">
        <strong>ویدیوی آموزشی این بخش</strong>
        <span>لینک آپارات هنوز تنظیم نشده است.</span>
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

    @if(!empty($page['children']))
      <div class="ws-menu-children">
        <h2 class="ws-guide-h">باز کردن زیرمنوها</h2>
        <ul class="ws-outline__list">
          @foreach($page['children'] as $child)
            <li>
              @if(!empty($child['guide']))
                <a class="is-ready" href="{{ url('/sites/repair-shop/'.$child['guide']) }}">{{ $child['title'] }}</a>
              @else
                <a class="is-ready" href="{{ url('/sites/repair-shop/m/'.$child['slug']) }}">{{ $child['title'] }}</a>
              @endif
            </li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="ws-guide-nav">
      <a class="ws-btn ws-btn--line" href="{{ $menuBack }}">{{ $menuBackLabel }}</a>
      <a class="ws-btn ws-btn--accent" href="{{ $contactUrl }}?subject={{ urlencode('دمو: '.$page['title']) }}">درخواست دمو</a>
    </div>
  </div>
</section>
@endsection
