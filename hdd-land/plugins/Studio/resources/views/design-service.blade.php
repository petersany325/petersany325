@php $service = $service ?? []; @endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/design') }}">طراحی سایت</a>
      <span>/</span>
      <span>{{ $service['title'] ?? '' }}</span>
    </p>
    <p class="ag-kicker">خدمات فراز نت آمل</p>
    <h1>{{ $service['title'] ?? '' }}</h1>
    <p class="ag-lead ag-lead--wide">{{ $service['text'] ?? ($service['short'] ?? '') }}</p>
    @php
      $sell = class_exists(\App\Support\StudioOffers::class) ? \App\Support\StudioOffers::find('design-'.($service['slug'] ?? '')) : null;
    @endphp
    <p>قیمت: <b>{{ $sell['price_label'] ?? 'قیمت اعلام می‌شود' }}</b></p>
    <div class="ag-actions">
      @if($sell)
        <a class="ag-btn ag-btn--on" href="{{ url($sell['sell_url']) }}">فروش این سایت<i>←</i></a>
      @endif
      <a class="ag-btn ag-btn--line" href="{{ url('/contact') }}">درخواست مشاوره رایگان<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/design') }}">→ همه خدمات طراحی</a>
    </div>
  </div>
</section>
