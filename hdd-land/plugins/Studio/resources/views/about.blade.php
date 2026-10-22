@php
  $aboutPage = $aboutPage ?? \App\Support\StudioDesignCopy::about();
  $reasons = $reasons ?? \App\Support\StudioDesignCopy::reasons();
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-kicker">درباره ما</p>
    <h1>{{ $aboutPage['title'] }}</h1>
    <p class="ag-lead ag-lead--wide">{{ $aboutPage['lead'] }}</p>
    @foreach($aboutPage['body'] as $para)
      <p class="ag-lead ag-lead--wide">{{ $para }}</p>
    @endforeach
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست مشاوره رایگان<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/design') }}">خدمات طراحی سایت<i>←</i></a>
    </div>
    <h2 class="ag-sec-title">چرا فراز نت آمل؟</h2>
    <ul class="ag-list">
      @foreach($reasons as $row)
        <li>{{ $row }}</li>
      @endforeach
    </ul>
  </div>
</section>
