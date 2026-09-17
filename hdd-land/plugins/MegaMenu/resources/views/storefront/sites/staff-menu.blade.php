@extends('layouts.storefront')

@section('title', 'منوی کارکنان | سایت مدیریت تعمیرکاران')

@section('content')
@php
  $contactUrl = url('/contact');
  try {
    if (\Illuminate\Support\Facades\Route::has('contact')) {
      $contactUrl = route('contact');
    }
  } catch (\Throwable $e) {}
@endphp
<section class="ws-page">
  <header class="ws-hero ws-hero--guide">
    <div class="ws-wrap">
      <nav class="ws-crumbs" aria-label="مسیر">
        <a href="{{ url('/sites') }}">طراحی و فروش سایت</a>
        <span>/</span>
        <a href="{{ url('/sites/repair-shop') }}">{{ $product['title'] ?? 'سایت مدیریت تعمیرکاران' }}</a>
        <span>/</span>
        <span>منوی کارکنان</span>
      </nav>
      <p class="ws-kicker">نمونه طراحی</p>
      <h1 class="ws-brand">منوی کارکنان</h1>
      <p class="ws-lead">فهرست متنی همان منوی اصلی سیستم. روی آیتم‌های آماده‌شده بزنید تا صفحه آموزش باز شود؛ بقیه فعلاً فقط نمونهٔ ساختارند.</p>
    </div>
  </header>

  <div class="ws-wrap ws-section">
    <p class="ws-outline-note">
      <strong>نمونه:</strong>
      آیتم‌های دارای آموزش کامل با رنگ تأکیدی لینک شده‌اند. بقیه ساختار منو را نشان می‌دهند و بعداً با متن + آپارات پر می‌شوند.
    </p>

    <nav class="ws-outline" aria-label="منوی کارکنان">
      @foreach($sections as $section)
        <div class="ws-outline__block">
          <div class="ws-outline__head">
            <span class="ws-outline__num">{{ $section['num'] }}.</span>
            @if(!empty($section['guide']))
              <a class="ws-outline__title is-ready" href="{{ url('/sites/repair-shop/'.$section['guide']) }}">{{ $section['title'] }}</a>
            @else
              <span class="ws-outline__title">{{ $section['title'] }}</span>
            @endif
          </div>
          @if(!empty($section['note']))
            <p class="ws-outline__note-line">{{ $section['note'] }}</p>
          @endif
          @if(!empty($section['children']))
            <ul class="ws-outline__list">
              @foreach($section['children'] as $child)
                <li>
                  @if(!empty($child['guide']))
                    <a class="is-ready" href="{{ url('/sites/repair-shop/'.$child['guide']) }}">{{ $child['title'] }}</a>
                  @else
                    <span>{{ $child['title'] }}</span>
                  @endif
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      @endforeach
    </nav>

    <div class="ws-guide-nav">
      <a class="ws-btn ws-btn--line" href="{{ url('/sites/repair-shop') }}">→ بازگشت به صفحه محصول</a>
      <a class="ws-btn ws-btn--accent" href="{{ $contactUrl }}?subject={{ urlencode('دمو: منوی کارکنان') }}">درخواست دمو</a>
    </div>
  </div>
</section>
@endsection
