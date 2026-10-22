@php
  $item = $item ?? [];
  $hubUrl = $hubUrl ?? '/';
  $hubLabel = $hubLabel ?? 'بازگشت';
  $parent = $item['parent'] ?? null;
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url($hubUrl) }}">{{ $hubLabel }}</a>
      @if($parent)
        <span>/</span>
        <a href="{{ url($hubUrl.'#'.($parent['slug'] ?? '')) }}">{{ $parent['title'] ?? '' }}</a>
      @endif
    </p>
    <p class="ag-kicker">{{ $hubLabel }}</p>
    <h1>{{ $item['title'] ?? '' }}</h1>
    <p class="ag-lead">{{ $item['short'] ?? '' }}</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو و استقرار<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url($hubUrl) }}">→ {{ $hubLabel }}</a>
    </div>
    <div class="ag-note" style="margin-top:1.4rem">
      این صفحه معرفی همان منوی نرم‌افزار است. داده قبض تعمیرگاه روی سامانه جدا می‌ماند و از اینجا پاک یا قاطی نمی‌شود.
    </div>
  </div>
</section>
