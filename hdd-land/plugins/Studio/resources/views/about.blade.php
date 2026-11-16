@php
  $aboutPage = $aboutPage ?? \App\Support\StudioDesignCopy::about();
@endphp
<section class="ag-page" id="intro">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/') }}">صفحه اصلی</a>
      <span>/</span>
      <span>درباره ما</span>
    </p>
    <p class="ag-kicker">شرکت فراز نت آمل (سرزمین هارد)</p>
    <h1>{{ $aboutPage['title'] ?? 'درباره ما' }}</h1>
    <p class="ag-lead">{{ $aboutPage['lead'] ?? '' }}</p>
    @foreach(($aboutPage['body'] ?? []) as $para)
      <p class="ag-lead ag-lead--wide">{{ $para }}</p>
    @endforeach
    <nav class="ag-jump" aria-label="بخش‌های درباره ما">
      @foreach(\App\Support\StudioDesignCopy::aboutLeaves() as $leaf)
        <a href="{{ url($leaf['url']) }}">{{ $leaf['label'] }}</a>
      @endforeach
    </nav>
    @if(!empty($aboutPage['facts']))
      <div class="ag-stats" style="margin-top:1.2rem">
        @foreach($aboutPage['facts'] as $fact)
          <div><b>{{ $fact['n'] }}</b><span>{{ $fact['l'] }}</span></div>
        @endforeach
      </div>
    @endif
  </div>
</section>

@foreach(($aboutPage['sections'] ?? []) as $section)
<section class="ag-sec" id="{{ $section['id'] }}">
  <div class="ag-wrap">
    <p class="ag-kicker">{{ $section['label'] }}</p>
    <h2>{{ $section['title'] }}</h2>
    <p class="ag-lead ag-lead--wide">{{ $section['text'] }}</p>
    @if(!empty($section['items']))
      <ul class="ag-list">
        @foreach($section['items'] as $item)
          <li>{{ $item }}</li>
        @endforeach
      </ul>
    @endif
  </div>
</section>
@endforeach

<section class="ag-sec" id="mission">
  <div class="ag-wrap">
    <p class="ag-kicker">مأموریت</p>
    <h2>مأموریت و چشم‌انداز</h2>
    <p class="ag-lead ag-lead--wide"><strong>مأموریت ما:</strong> {{ $aboutPage['mission'] ?? '' }}</p>
    <p class="ag-lead ag-lead--wide"><strong>چشم‌انداز ما:</strong> {{ $aboutPage['vision'] ?? '' }}</p>
    <p class="ag-lead ag-lead--wide">{{ $aboutPage['belief'] ?? '' }}</p>
  </div>
</section>

<section class="ag-sec ag-sec--last">
  <div class="ag-wrap">
    <p class="ag-kicker">سرزمین هارد</p>
    <h2>{{ $aboutPage['closing'] ?? '' }}</h2>
    <ul class="ag-list">
      <li>شماره ثبت شرکت: ۴۱۸۹</li>
      <li>شناسه صنفی: ۱۴۰۵۳۱۳۳۷۹</li>
      <li>سابقه طراحی سایت و نرم‌افزار: ۱۸ سال</li>
      <li>سابقه بازیابی اطلاعات و تعمیر هارد: بیش از ۲۰ سال</li>
      <li>وب‌سایت: <a href="{{ $aboutPage['site'] ?? 'https://www.hdd-land.ir' }}">{{ $aboutPage['site_label'] ?? 'www.hdd-land.ir' }}</a></li>
    </ul>
    <div class="ag-actions" style="margin-top:1rem">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">تماس با ما<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/design') }}">طراحی سایت</a>
    </div>
  </div>
</section>
