@extends('layouts.storefront')

@section('title', 'منوی کارتابل مشتری | سایت مدیریت تعمیرکاران')

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
        <span>منوی کارتابل مشتری</span>
      </nav>
      <p class="ws-kicker">پرتال مشتری</p>
      <h1 class="ws-brand">منوی کارتابل مشتری</h1>
      <p class="ws-lead">چهره‌ای که مشتری از سیستم می‌بیند: پیگیری، تأیید هزینه، پرداخت و پیام‌ها. روی هر عنوان بزنید تا توضیح همان بخش باز شود.</p>
    </div>
  </header>

  <div class="ws-wrap ws-section">
    <p class="ws-outline-note">
      <strong>پرتال مشتری:</strong>
      جدا از منوی کارکنان است تا خریدار بفهمد سیستم دو ورودی دارد — داخل تعمیرگاه و بیرون برای مشتری.
    </p>

    <nav class="ws-outline" aria-label="منوی کارتابل مشتری">
      @foreach($sections as $section)
        <div class="ws-outline__block">
          <div class="ws-outline__head">
            <span class="ws-outline__num">{{ $section['num'] }}.</span>
            @if(!empty($section['guide']))
              <a class="ws-outline__title is-ready" href="{{ url('/sites/repair-shop/'.$section['guide']) }}">{{ $section['title'] }}</a>
            @else
              <a class="ws-outline__title is-ready" href="{{ url('/sites/repair-shop/m/'.$section['slug']) }}">{{ $section['title'] }}</a>
            @endif
          </div>
          @if(!empty($section['short']))
            <p class="ws-outline__note-line">{{ $section['short'] }}</p>
          @endif
        </div>
      @endforeach
    </nav>

    <div class="ws-guide-nav">
      <a class="ws-btn ws-btn--line" href="{{ url('/sites/repair-shop') }}">→ بازگشت به صفحه محصول</a>
      <a class="ws-btn ws-btn--line" href="{{ url('/sites/repair-shop/staff-menu') }}">منوی کارکنان ←</a>
      <a class="ws-btn ws-btn--accent" href="{{ $contactUrl }}?subject={{ urlencode('دمو: پرتال مشتری') }}">درخواست دمو</a>
    </div>
  </div>
</section>
@endsection
