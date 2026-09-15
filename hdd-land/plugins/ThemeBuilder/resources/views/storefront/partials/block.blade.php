@php
  /** @var array $block */
  $type = $block['type'] ?? '';
  $s = is_array($block['settings'] ?? null) ? $block['settings'] : [];
@endphp

@if($type === 'menu' && !empty($s['items']))
  <nav class="home-top-menu style-{{ $s['style'] ?? 'pills' }}">
    @foreach($s['items'] as $item)
      @include('theme-builder::storefront.partials.menu-item', ['item' => $item])
    @endforeach
  </nav>

@elseif($type === 'education')
  <section class="section education-section">
    <div class="section-head">
      <div>
        <h2>{{ $s['title'] ?? 'آموزش' }}</h2>
        <p>{{ $s['subtitle'] ?? '' }}</p>
      </div>
    </div>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
      @foreach(($s['items'] ?? []) as $item)
        @continue(empty($item['title']))
        <article class="panel education-card">
          @if(!empty($item['image']))
            <img src="{{ $item['image'] }}" alt="" style="width:100%;height:140px;object-fit:cover;border-radius:12px;margin-bottom:.7rem">
          @endif
          <strong>{{ $item['title'] }}</strong>
          <p class="muted" style="margin:.4rem 0 .7rem">{{ $item['text'] ?? '' }}</p>
          @if(!empty($item['url']))
            <a class="btn btn-outline btn-sm" href="{{ url($item['url']) }}">مطالعه</a>
          @endif
        </article>
      @endforeach
    </div>
  </section>

@elseif($type === 'php')
  <section class="section php-block">
    @php
      $code = (string)($s['code'] ?? '');
      // Legacy "php" blocks are treated as HTML. Executing stored Blade/PHP here
      // would allow a compromised admin account to run arbitrary server-side code.
      $rendered = \App\Support\SafeHtml::clean($code);
    @endphp
    {!! $rendered !!}
  </section>

@elseif($type === 'page')
  @php
    $pageHtml = '';
    try {
      if (class_exists(\Plugins\ThemeBuilder\src\Models\BuilderPage::class)) {
        $q = \Plugins\ThemeBuilder\src\Models\BuilderPage::query()->where('status', 'publish');
        if (!empty($s['page_id'])) {
          $page = $q->where('id', (int)$s['page_id'])->first();
        } elseif (!empty($s['page_slug'])) {
          $page = $q->where('slug', (string)$s['page_slug'])->first();
        } else {
          $page = null;
        }
        if ($page) {
          $widgets = $page->content['widgets'] ?? [];
          if (is_array($widgets) && $widgets !== []) {
            $pageHtml = view('theme-builder::storefront.widgets', [
              'widgets' => $widgets,
              'featured' => $featured ?? collect(),
              'latest' => $latest ?? collect(),
              'categories' => $categories ?? collect(),
            ])->render();
          }
        }
      }
    } catch (\Throwable) {
      $pageHtml = '';
    }
  @endphp
  @if($pageHtml !== '')
    <section class="section custom-page-embed">
      @if(!empty($s['title']))<div class="section-head"><div><h2>{{ $s['title'] }}</h2></div></div>@endif
      {!! $pageHtml !!}
    </section>
  @endif

@elseif(in_array($type, ['products','html','faq','spacer','divider','hero','online','categories','cta','features','banner'], true))
  @include('theme-builder::storefront.widgets', [
    'widgets' => [['type' => $type, 'settings' => $s]],
    'featured' => $featured ?? collect(),
    'latest' => $latest ?? collect(),
    'categories' => $categories ?? collect(),
  ])
@endif
