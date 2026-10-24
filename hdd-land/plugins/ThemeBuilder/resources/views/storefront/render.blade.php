@php
  $page = $page ?? ($builderPage ?? []);
  $blocks = is_array($page['blocks'] ?? null) ? $page['blocks'] : [];
  $media = static function (string $src): string {
      if (class_exists(\Plugins\ThemeBuilder\src\PageBuilder::class)) {
          return \Plugins\ThemeBuilder\src\PageBuilder::mediaUrl($src);
      }
      return $src;
  };
@endphp
@foreach($blocks as $block)
  @php $type = $block['type'] ?? ''; @endphp

  @if($type === 'hero')
    <section class="ag-page pb-hero">
      <div class="ag-wrap{{ !empty($block['image']) ? ' pb-hero__grid' : '' }}">
        <div>
          @if(!empty($block['crumb']))
            <p class="ag-crumb">{!! $block['crumb'] !!}</p>
          @endif
          @if(!empty($block['kicker']))
            <p class="ag-kicker">{{ $block['kicker'] }}</p>
          @endif
          <h1>{{ $block['title'] ?? '' }}</h1>
          @if(!empty($block['text']))
            <p class="ag-lead ag-lead--wide">{{ $block['text'] }}</p>
          @endif
          <div class="ag-actions">
            @if(!empty($block['cta1_label']))
              <a class="ag-btn ag-btn--on" href="{{ url($block['cta1_url'] ?? '/contact') }}">{{ $block['cta1_label'] }}<i>←</i></a>
            @endif
            @if(!empty($block['cta2_label']))
              <a class="ag-btn ag-btn--line" href="{{ url($block['cta2_url'] ?? '/') }}">{{ $block['cta2_label'] }}</a>
            @endif
          </div>
        </div>
        @if(!empty($block['image']))
          <figure class="pb-hero__media">
            <img src="{{ $media($block['image']) }}" alt="{{ $block['title'] ?? '' }}">
          </figure>
        @endif
      </div>
    </section>

  @elseif($type === 'richtext')
    <section class="ag-sec">
      <div class="ag-wrap pb-rich">
        @if(!empty($block['title']))
          <p class="ag-kicker">متن</p>
          <h2>{{ $block['title'] }}</h2>
        @endif
        <div class="ag-lead ag-lead--wide pb-html">{!! $block['html'] ?? '' !!}</div>
      </div>
    </section>

  @elseif($type === 'image')
    <section class="ag-sec">
      <div class="ag-wrap">
        @if(!empty($block['title']))
          <h2>{{ $block['title'] }}</h2>
        @endif
        <figure class="pb-figure">
          <img src="{{ $media($block['image'] ?? '') }}" alt="{{ $block['alt'] ?? ($block['title'] ?? '') }}">
          @if(!empty($block['caption']))
            <figcaption>{{ $block['caption'] }}</figcaption>
          @endif
        </figure>
      </div>
    </section>

  @elseif($type === 'cards')
    <section class="ag-sec">
      <div class="ag-wrap">
        @if(!empty($block['title']))
          <p class="ag-kicker">بخش‌ها</p>
          <h2>{{ $block['title'] }}</h2>
        @endif
        <div class="ag-cards ag-cards--dense">
          @foreach(($block['items'] ?? []) as $item)
            @continue(empty($item['title']))
            @php $href = $item['url'] ?? ''; @endphp
            @if($href)
              <a class="ag-card" href="{{ url($href) }}">
                @if(!empty($item['image']))
                  <img class="pb-card-img" src="{{ $media($item['image']) }}" alt="">
                @endif
                <div>
                  <b>{{ $item['title'] }}</b>
                  <p>{{ $item['text'] ?? '' }}</p>
                </div>
              </a>
            @else
              <article class="ag-card">
                @if(!empty($item['image']))
                  <img class="pb-card-img" src="{{ $media($item['image']) }}" alt="">
                @endif
                <div>
                  <b>{{ $item['title'] }}</b>
                  <p>{{ $item['text'] ?? '' }}</p>
                </div>
              </article>
            @endif
          @endforeach
        </div>
      </div>
    </section>

  @elseif($type === 'list')
    <section class="ag-sec">
      <div class="ag-wrap">
        @if(!empty($block['title']))
          <h2>{{ $block['title'] }}</h2>
        @endif
        <ul class="ag-list">
          @foreach(($block['items'] ?? []) as $row)
            <li>{{ is_array($row) ? ($row['text'] ?? $row['title'] ?? '') : $row }}</li>
          @endforeach
        </ul>
      </div>
    </section>

  @elseif($type === 'steps')
    <section class="ag-sec">
      <div class="ag-wrap">
        @if(!empty($block['title']))
          <p class="ag-kicker">مراحل</p>
          <h2>{{ $block['title'] }}</h2>
        @endif
        <ol class="ag-steps">
          @foreach(($block['items'] ?? []) as $i => $row)
            <li>
              <small>{{ $row['num'] ?? ($i + 1) }}</small>
              <b>{{ $row['title'] ?? '' }}</b>
              <p>{{ $row['text'] ?? '' }}</p>
            </li>
          @endforeach
        </ol>
      </div>
    </section>

  @elseif($type === 'stats')
    <section class="ag-sec">
      <div class="ag-wrap">
        @if(!empty($block['title']))
          <h2>{{ $block['title'] }}</h2>
        @endif
        <div class="ag-stats">
          @foreach(($block['items'] ?? []) as $row)
            <div>
              <strong>{{ $row['value'] ?? '' }}</strong>
              <span>{{ $row['label'] ?? '' }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </section>

  @elseif($type === 'works')
    <section class="ag-sec">
      <div class="ag-wrap">
        @if(!empty($block['title']))
          <h2>{{ $block['title'] }}</h2>
        @endif
        <div class="ag-works">
          <div class="ag-works__grid">
            @foreach(($block['items'] ?? []) as $row)
              <a class="ag-work" href="{{ url($row['url'] ?? '/projects') }}">
                <div class="ag-work__media">
                  @if(!empty($row['image']))
                    <img src="{{ $media($row['image']) }}" alt="{{ $row['title'] ?? '' }}">
                  @endif
                </div>
                <small>{{ $row['cat'] ?? '' }}</small>
                <b>{{ $row['title'] ?? '' }}</b>
              </a>
            @endforeach
          </div>
        </div>
      </div>
    </section>

  @elseif($type === 'cta')
    <section class="ag-sec ag-sec--last">
      <div class="ag-wrap">
        <p class="ag-kicker">دعوت به اقدام</p>
        <h2>{{ $block['title'] ?? '' }}</h2>
        <p class="ag-lead ag-lead--wide">{{ $block['text'] ?? '' }}</p>
        <div class="ag-actions">
          @if(!empty($block['cta1_label']))
            <a class="ag-btn ag-btn--on" href="{{ url($block['cta1_url'] ?? '/contact') }}">{{ $block['cta1_label'] }}<i>←</i></a>
          @endif
        </div>
      </div>
    </section>
  @endif
@endforeach
<style>
.pb-hero__grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(220px,.9fr);gap:1.4rem;align-items:center}
.pb-hero__media img,.pb-figure img,.pb-card-img,.ag-work img{width:100%;border-radius:18px;display:block;object-fit:cover}
.pb-hero__media img{min-height:220px;max-height:360px}
.pb-figure figcaption{margin-top:.5rem;color:#8e97b8;font-size:.82rem}
.pb-html p{margin:0 0 1rem}
.pb-card-img{height:140px;margin-bottom:.7rem}
@media(max-width:800px){.pb-hero__grid{grid-template-columns:1fr}}
</style>
