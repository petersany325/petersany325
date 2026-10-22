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
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست مشاوره رایگان<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/design') }}">→ همه خدمات طراحی</a>
    </div>
  </div>
</section>
