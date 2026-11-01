@php
  $block = $block ?? [];
  $media = $media ?? static function (string $src): string {
      if (class_exists(\Plugins\ThemeBuilder\src\PageBuilder::class)) {
          return \Plugins\ThemeBuilder\src\PageBuilder::mediaUrl($src);
      }
      $src = trim($src);
      if ($src === '' || preg_match('#^https?://#i', $src) || str_starts_with($src, '/')) {
          return $src;
      }

      return asset($src);
  };
  $pair = ($block['layout'] ?? '') === 'pair';
  $items = is_array($block['items'] ?? null) ? $block['items'] : [];
  $first = $items[0] ?? [];
@endphp
<section class="ag-sec pb-gallery{{ $pair ? ' pb-pair' : '' }}" id="{{ $block['id'] ?? 'software-shots' }}">
  @if($pair)
    <div class="ag-wrap pb-pair__grid">
      <div>
        @if(!empty($block['kicker']))
          <p class="ag-kicker">{{ $block['kicker'] }}</p>
        @endif
        @if(!empty($block['title']))
          <h2>{{ $block['title'] }}</h2>
        @endif
        @if(!empty($block['text']))
          <p class="ag-lead ag-lead--wide">{{ $block['text'] }}</p>
        @endif
      </div>
      @if(!empty($first['image']))
        <figure class="pb-shot">
          <div class="pb-shot__bar" aria-hidden="true"><i></i><i></i><i></i></div>
          <img src="{{ $media($first['image']) }}" alt="{{ $block['title'] ?? '' }}" width="1412" height="1132" loading="lazy">
        </figure>
      @endif
    </div>
  @else
    <div class="ag-wrap">
      @if(!empty($block['kicker']))
        <p class="ag-kicker">{{ $block['kicker'] }}</p>
      @endif
      @if(!empty($block['title']))
        <h2>{{ $block['title'] }}</h2>
      @endif
      @if(!empty($block['text']))
        <p class="ag-lead ag-lead--wide">{{ $block['text'] }}</p>
      @endif
      <div class="pb-shots">
        @foreach($items as $row)
          @continue(empty($row['image']))
          <figure class="pb-shot{{ !empty($row['wide']) ? ' pb-shot--wide' : '' }}">
            <div class="pb-shot__bar" aria-hidden="true"><i></i><i></i><i></i></div>
            <img src="{{ $media($row['image']) }}" alt="{{ $row['title'] ?? '' }}" width="1412" height="1132" loading="lazy">
            @if(!empty($row['title']) || !empty($row['text']))
              <figcaption>
                @if(!empty($row['title']))<b>{{ $row['title'] }}</b>@endif
                @if(!empty($row['text']))<span>{{ $row['text'] }}</span>@endif
              </figcaption>
            @endif
          </figure>
        @endforeach
      </div>
    </div>
  @endif
</section>
<style>
.pb-pair__grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(240px,1.05fr);gap:1.3rem;align-items:center}
.pb-shots{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1.1rem}
.pb-shot{margin:0;background:#101628;border:1px solid rgba(255,255,255,.08);border-radius:18px;overflow:hidden;box-shadow:0 18px 40px rgba(0,0,0,.28)}
.pb-shot--wide{grid-column:1 / -1}
.pb-shot__bar{display:flex;gap:6px;align-items:center;height:28px;padding:0 12px;background:#1a2348}
.pb-shot__bar i{width:8px;height:8px;border-radius:50%;background:#4b5568}
.pb-shot__bar i:first-child{background:#f59e0b}
.pb-shot img{width:100%;height:auto;display:block;background:#fff;object-fit:contain}
.pb-shot figcaption{padding:.75rem 1rem 1rem}
.pb-shot figcaption b{display:block;color:#fff;font-size:.95rem}
.pb-shot figcaption span{display:block;margin-top:.25rem;color:#8e97b8;font-size:.82rem;line-height:1.6}
@media(max-width:800px){.pb-pair__grid,.pb-shots{grid-template-columns:1fr}}
</style>
