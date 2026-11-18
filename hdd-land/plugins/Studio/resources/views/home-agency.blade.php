@php
  $c = $studioHome ?? \App\Support\StudioHomeConfig::get();
  $services = \App\Support\StudioHomeConfig::services($c);
  $stats = \App\Support\StudioHomeConfig::stats($c);
  $works = \App\Support\StudioHomeConfig::works($c);
  $orbit = [
    ['cls' => 's1', 'mark' => '🌐', 'title' => 'طراحی سایت', 'text' => 'واکنش‌گرا و بهینه برای گوگل', 'url' => $services[0]['url'] ?? '/design'],
    ['cls' => 's2', 'mark' => '🧮', 'title' => 'ساخت نرم‌افزار', 'text' => 'حسابداری صنوف، انبار، تعمیرگاه', 'url' => $services[1]['url'] ?? '/computer-accounting'],
    ['cls' => 's3', 'mark' => '✨', 'title' => 'هوش مصنوعی', 'text' => 'دستیار فارسی بدون اینترنت', 'url' => $services[2]['url'] ?? '/ai'],
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
<section class="ag-wrap ag-hero hh" id="hhHero" aria-label="معرفی شرکت">
  <div class="hh-wrap">
    <div class="hh-copy">
      <div class="hh-pill"><i></i>شرکت فراز نت آمل · سرزمین هارد</div>
      <h1>{!! \App\Support\StudioHomeConfig::titleHtml($c) !!}</h1>
      <p class="hh-lead">{{ $c['hero_text'] }}</p>
      <div class="hh-btns">
        <a class="ag-btn ag-btn--on" href="{{ url($c['cta1_url'] ?: '/contact') }}">{{ $c['cta1_label'] }}<i>←</i></a>
        <a class="ag-btn ag-btn--line" href="{{ url($c['cta2_url'] ?: '/projects') }}">{{ $c['cta2_label'] }}<i>←</i></a>
      </div>
    </div>
    <div class="hh-orbit">
      <div class="hh-halo" aria-hidden="true"></div>
      <div class="hh-ring" aria-hidden="true"><i></i></div>
      <div class="hh-robot">
        <img src="{{ asset('images/hero-robot.webp') }}" width="250" height="250" alt="دستیار هوش مصنوعی HDD Land" fetchpriority="high" decoding="async">
      </div>
      @foreach($orbit as $i => $card)
        <a class="hh-svc {{ $card['cls'] }}{{ $i === 0 ? ' on' : '' }}" href="{{ url($card['url']) }}"><b>{{ $card['mark'] }} {{ $card['title'] }}</b><small>{{ $card['text'] }}</small></a>
      @endforeach
      <div class="hh-say" id="hhSay" aria-live="off">سایتی که در موبایل هم سریع باز شود.</div>
    </div>
  </div>
</section>
<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var hero = document.getElementById('hhHero');
  var say = document.getElementById('hhSay');
  if (!hero || !say) return;
  var svc = hero.querySelectorAll('.hh-svc');
  var SAY = ['سایتی که در موبایل هم سریع باز شود.','حسابداری با منطق واقعی صنف شما.','می‌پرسید، روی کامپیوتر خودتان جواب می‌دهم.'];
  if (reduce) return;
  function sleep(ms){ return new Promise(function (r) { setTimeout(r, ms); }); }
  var paused = false;
  svc.forEach(function (s, idx) {
    s.addEventListener('mouseenter', function () {
      paused = true;
      svc.forEach(function (x, j) { x.classList.toggle('on', j === idx); });
      say.textContent = SAY[idx] || '';
    });
    s.addEventListener('mouseleave', function () { paused = false; });
  });
  function play() {
    (async function loop() {
      for (;;) {
        for (var i = 0; i < SAY.length; i++) {
          while (paused) await sleep(200);
          svc.forEach(function (x, j) { x.classList.toggle('on', j === i); });
          say.textContent = '';
          for (var k = 1; k <= SAY[i].length && !paused; k++) {
            say.textContent = SAY[i].slice(0, k);
            await sleep(28);
          }
          await sleep(2200);
          while (paused) await sleep(200);
        }
        if (reduce) return;
      }
    })();
  }
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      if (es[0].isIntersecting) { io.disconnect(); play(); }
    }, { threshold: 0.3 });
    io.observe(hero);
  } else {
    play();
  }
})();
</script>

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
