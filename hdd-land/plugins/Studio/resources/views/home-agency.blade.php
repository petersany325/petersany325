@php
  $c = $studioHome ?? \App\Support\StudioHomeConfig::get();
  $services = \App\Support\StudioHomeConfig::services($c);
  $stats = \App\Support\StudioHomeConfig::stats($c);
  $works = \App\Support\StudioHomeConfig::works($c);
  $scene = trim((string) ($c['scene_image'] ?? ''));
  $nodes = [
    ['cls' => 'n1', 'label' => $c['node_1'] ?? ''],
    ['cls' => 'n2', 'label' => $c['node_2'] ?? ''],
    ['cls' => 'n3', 'label' => $c['node_3'] ?? ''],
    ['cls' => 'n4', 'label' => $c['node_4'] ?? ''],
  ];
  $svcIcons = [
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 8 4 12l4 4M16 8l4 4-4 4M13 5l-2 14"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 11 12 4l8 7v9H4z"/><path d="M9 20v-6h6v6"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 18V6M4 18h16"/><path d="m8 14 3-4 3 3 5-7"/></svg>',
  ];
  $statIcons = [
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6" y="7" width="12" height="13" rx="1.5"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><circle cx="16" cy="9" r="2.4"/><path d="M3.5 19c.5-3 2.8-5 5.5-5s5 2 5.5 5M14 14c2.2 0 4 1.6 4.5 4"/></svg>',
  ];
@endphp
<section class="ag-hero">
  <div class="ag-wrap ag-hero__grid">
    <div class="ag-copy">
      <h1>{!! \App\Support\StudioHomeConfig::titleHtml($c) !!}</h1>
      <p>{{ $c['hero_text'] }}</p>
      <div class="ag-actions">
        <a class="ag-btn ag-btn--on" href="{{ url($c['cta1_url'] ?: '/contact') }}">{{ $c['cta1_label'] }}<i>←</i></a>
        <a class="ag-btn ag-btn--line" href="{{ url($c['cta2_url'] ?: '/projects') }}">{{ $c['cta2_label'] }}<i>←</i></a>
      </div>
    </div>
    <div class="ag-scene" aria-hidden="true">
      @if($scene !== '')
        <img class="ag-scene__img" src="{{ \App\Support\StudioHomeConfig::imageUrl($scene) }}" alt="">
      @else
        <div class="ag-laptop">
          @if($c['laptop_image'])
            <img src="{{ \App\Support\StudioHomeConfig::imageUrl($c['laptop_image']) }}" alt="">
          @else
            <div class="ag-screen">
              <b>{{ $c['laptop_title'] }}</b>
              <i></i><i></i><i></i>
              <span></span><span></span>
            </div>
          @endif
        </div>
        <div class="ag-house">
          @if($c['house_image'])
            <img src="{{ \App\Support\StudioHomeConfig::imageUrl($c['house_image']) }}" alt="">
          @else
            <div class="ag-iso">
              <em></em><em></em>
              <i class="w1"></i><i class="w2"></i><i class="w3"></i>
            </div>
          @endif
        </div>
        @foreach($nodes as $n)
          @if($n['label'] !== '')
            <div class="ag-node ag-node--{{ $n['cls'] }}"><i></i><span>{{ $n['label'] }}</span></div>
          @endif
        @endforeach
      @endif
    </div>
  </div>
</section>

@if(!empty($c['services_enabled']) && $services)
<section class="ag-wrap ag-cards">
  @foreach($services as $i => $svc)
    <a class="ag-card" href="{{ url($svc['url']) }}">
      <i class="ag-ico">{!! $svcIcons[$i] ?? $svcIcons[0] !!}</i>
      <div>
        <b>{{ $svc['title'] }}</b>
        <p>{{ $svc['text'] }}</p>
        @if(!empty($c['svc_more_label']))
          <small>{{ $c['svc_more_label'] }} ←</small>
        @endif
      </div>
    </a>
  @endforeach
</section>
@endif

@if(!empty($c['stats_enabled']) && $stats)
<section class="ag-wrap ag-stats">
  @foreach($stats as $i => $st)
    <div>
      <i class="ag-stat-ico">{!! $statIcons[$i] ?? $statIcons[0] !!}</i>
      <strong>{{ $st['value'] }}</strong>
      <span>{{ $st['label'] }}</span>
    </div>
  @endforeach
</section>
@endif

@if(!empty($c['works_enabled']) && $works)
<section class="ag-wrap ag-works">
  <header class="ag-works__head">
    <div>
      <h2>{{ $c['works_title'] }}</h2>
      @if(!empty($c['works_subtitle']))
        <p>{{ $c['works_subtitle'] }}</p>
      @endif
    </div>
    <a href="{{ url($c['works_more_url'] ?: '/projects') }}">{{ $c['works_more_label'] }} ←</a>
  </header>
  <div class="ag-works__grid">
    @foreach($works as $w)
      <a class="ag-work" href="{{ url($w['url']) }}">
        <div class="ag-work__media">
          @if($w['image'])
            <img src="{{ \App\Support\StudioHomeConfig::imageUrl($w['image']) }}" alt="{{ $w['title'] }}">
          @endif
        </div>
        <b>{{ $w['title'] }}</b>
        <small>{{ $w['cat'] }}</small>
      </a>
    @endforeach
  </div>
</section>
@endif
