@foreach($widgets as $widget)
  @php
    $type = $widget['type'] ?? '';
    $s = $widget['settings'] ?? [];
    if (array_key_exists('enabled', $s) && ! $s['enabled']) { continue; }
  @endphp

  @if($type === 'online')
    <div class="online-bar">
      <div class="online-bar-inner">
        <span class="online-dot"></span>
        <strong>{{ $s['badge'] ?? 'آنلاین' }}</strong>
        <span>{{ $s['text'] ?? '' }}</span>
        <span class="muted">{{ $s['support_text'] ?? '' }}</span>
        @if(!empty($s['phone']))<a href="tel:{{ preg_replace('/\s+/','',$s['phone']) }}">{{ $s['phone'] }}</a>@endif
      </div>
    </div>

  @elseif($type === 'hero')
    <section class="hero-light">
      <div>
        @if(!empty($s['badge']))<span class="badge">{{ $s['badge'] }}</span>@endif
        <h1>{{ $s['title'] ?? '' }}</h1>
        <p>{{ $s['subtitle'] ?? '' }}</p>
        <div class="row" style="margin-top:1.25rem">
          @if(!empty($s['cta_primary_text']))<a class="btn btn-primary" href="{{ url($s['cta_primary_url'] ?? '/products') }}">{{ $s['cta_primary_text'] }}</a>@endif
          @if(!empty($s['cta_secondary_text']))<a class="btn btn-outline" href="{{ url($s['cta_secondary_url'] ?? '#') }}">{{ $s['cta_secondary_text'] }}</a>@endif
        </div>
      </div>
      <div class="hero-visual">
        <div class="panel-inner">
          <strong>{{ $s['panel_title'] ?? '' }}</strong>
          <p class="muted" style="margin:.35rem 0 0">{{ $s['panel_text'] ?? '' }}</p>
        </div>
      </div>
    </section>

  @elseif($type === 'banner_hero' || $type === 'site_banner')
    @include('theme-builder::storefront.partials.banner', ['b' => $s])

  @elseif($type === 'heading')
    @php $tag = in_array($s['size'] ?? 'h2', ['h1','h2','h3']) ? $s['size'] : 'h2'; @endphp
    <section class="section" style="padding-bottom:0;text-align:{{ $s['align'] ?? 'right' }}">
      <{{ $tag }} style="margin:0">{{ $s['text'] ?? '' }}</{{ $tag }}>
    </section>

  @elseif($type === 'text')
    <section class="section" style="padding-top:.8rem"><div class="panel">{!! nl2br(e($s['content'] ?? '')) !!}</div></section>

  @elseif($type === 'button')
    <section class="section" style="padding-top:.5rem">
      <a class="btn btn-{{ in_array($s['style'] ?? 'primary', ['primary','outline','dark']) ? $s['style'] : 'primary' }}" href="{{ url($s['url'] ?? '#') }}">{{ $s['text'] ?? 'دکمه' }}</a>
    </section>

  @elseif($type === 'image')
    <section class="section">
      @php $img = $s['url'] ?? ''; @endphp
      @if($img)
        @if(!empty($s['link']))<a href="{{ url($s['link']) }}">@endif
        <img src="{{ $img }}" alt="{{ $s['alt'] ?? '' }}" style="width:100%;border-radius:18px;border:1px solid var(--line)">
        @if(!empty($s['link']))</a>@endif
      @endif
    </section>

  @elseif($type === 'products')
    @php
      $limit = max(1, min(24, (int)($s['limit'] ?? 8)));
      $list = collect();
      $catId = (int)($s['category_id'] ?? 0);
      if ($catId > 0 && class_exists(\Plugins\Catalog\src\Models\Product::class)) {
        try {
          $list = \Plugins\Catalog\src\Models\Product::query()
            ->published()
            ->where('category_id', $catId)
            ->latest('id')
            ->limit($limit)
            ->get();
        } catch (\Throwable) {
          $list = collect();
        }
      }
      if ($list->isEmpty()) {
        $list = (($s['featured_only'] ?? '0') === '1' || ($s['featured_only'] ?? false) === true)
          ? (($featured->count() ? $featured : $latest)->take($limit))
          : $latest->take($limit);
      }
    @endphp
    <section class="section home-products-section">
      <div class="section-head section-head--sm">
        <div>
          <h2>{{ $s['title'] ?? 'محصولات' }}</h2>
          @if(!empty($s['subtitle']))<p>{{ $s['subtitle'] }}</p>@endif
        </div>
        <a href="{{ route('products.index') }}">{{ $s['link_text'] ?? 'همه محصولات' }}</a>
      </div>
      <div class="grid grid--home">
        @forelse($list as $product)
          @include('catalog::storefront.partials.product-card', ['product' => $product, 'compact' => true])
        @empty
          <div class="panel">محصولی نیست.</div>
        @endforelse
      </div>
    </section>

  @elseif($type === 'categories')
    @if($categories->count())
    <section class="section home-cats-section">
      <div class="section-head section-head--sm">
        <div>
          <h2>{{ $s['title'] ?? 'دسته‌ها' }}</h2>
          @if(!empty($s['subtitle']))<p>{{ $s['subtitle'] }}</p>@endif
        </div>
        <a href="{{ route('products.index') }}">همه محصولات</a>
      </div>
      <div class="home-cats" role="list">
        @foreach($categories as $cat)
          @php
            $kids = (isset($cat->activeChildren) && $cat->activeChildren) ? $cat->activeChildren->take(3) : collect();
            $img = method_exists($cat, 'imageUrl') ? trim((string) $cat->imageUrl()) : '';
            if ($img === '' || str_ends_with($img, '/')) { $img = ''; }
          @endphp
          <div class="home-cat" role="listitem">
            <a class="home-cat__main" href="{{ route('categories.show', $cat->slug) }}">
              <span class="home-cat__icon" aria-hidden="true">
                @if($img)
                  <img src="{{ $img }}" alt="" width="28" height="28" loading="lazy">
                @else
                  <em>{{ mb_substr($cat->name, 0, 1) }}</em>
                @endif
              </span>
              <span class="home-cat__text">
                <strong>{{ $cat->name }}</strong>
                @if($kids->count())
                  <small>{{ $kids->pluck('name')->implode(' · ') }}</small>
                @else
                  <small>مشاهده محصولات</small>
                @endif
              </span>
              <span class="home-cat__go" aria-hidden="true">‹</span>
            </a>
          </div>
        @endforeach
      </div>
    </section>
    @endif

  @elseif($type === 'features')
    <section class="section home-features-section">
      <div class="section-head section-head--sm"><div><h2>{{ $s['title'] ?? 'ویژگی‌ها' }}</h2></div></div>
      <div class="home-features">
        @foreach([[$s['item1_title']??'',$s['item1_text']??''],[$s['item2_title']??'',$s['item2_text']??''],[$s['item3_title']??'',$s['item3_text']??'']] as $item)
          @if($item[0] !== '')
            <div class="home-feature">
              <strong>{{ $item[0] }}</strong>
              <p>{{ $item[1] }}</p>
            </div>
          @endif
        @endforeach
      </div>
    </section>

  @elseif($type === 'cta')
    <section class="section">
      <div class="home-cta-panel">
        <div>
          <h2>{{ $s['title'] ?? '' }}</h2>
          <p>{{ $s['text'] ?? '' }}</p>
        </div>
        @if(!empty($s['button_text']))
          <a class="btn btn-primary btn-sm" href="{{ url($s['button_url'] ?? '/products') }}">{{ $s['button_text'] }}</a>
        @endif
      </div>
    </section>

  @elseif($type === 'faq')
    <section class="section">
      <div class="section-head"><div><h2>{{ $s['title'] ?? 'سوالات متداول' }}</h2></div></div>
      @foreach([[$s['q1']??'',$s['a1']??''],[$s['q2']??'',$s['a2']??''],[$s['q3']??'',$s['a3']??'']] as $qa)
        @if($qa[0] !== '')
          <div class="panel" style="margin-bottom:.7rem">
            <strong>{{ $qa[0] }}</strong>
            <p class="muted" style="margin:.35rem 0 0">{{ $qa[1] }}</p>
          </div>
        @endif
      @endforeach
    </section>

  @elseif($type === 'banner')
    <section class="section">
      <div class="panel" style="background:{{ $s['bg'] ?? '#1a1d23' }};color:#fff;border:0">
        <h2 style="margin:0;color:#fff">{{ $s['title'] ?? '' }}</h2>
        <p style="opacity:.85">{{ $s['text'] ?? '' }}</p>
        @if(!empty($s['button_text']))
          <a class="btn btn-primary" href="{{ url($s['button_url'] ?? '#') }}">{{ $s['button_text'] }}</a>
        @endif
      </div>
    </section>

  @elseif($type === 'spacer')
    <div style="height:{{ max(8, min(200, (int)($s['height'] ?? 32))) }}px"></div>

  @elseif($type === 'divider')
    <div class="container" style="padding:0"><hr style="border:0;border-top:1px solid var(--line);margin:1rem 0"></div>

  @elseif($type === 'html')
    <section class="section"><div class="panel">{!! \App\Support\SafeHtml::clean($s['code'] ?? '') !!}</div></section>

  @elseif($type === 'columns')
    <section class="section">
      <div class="grid" style="grid-template-columns:1fr 1fr">
        <div class="panel"><strong>{{ $s['left_title'] ?? '' }}</strong><p class="muted">{{ $s['left_text'] ?? '' }}</p></div>
        <div class="panel"><strong>{{ $s['right_title'] ?? '' }}</strong><p class="muted">{{ $s['right_text'] ?? '' }}</p></div>
      </div>
    </section>
  @endif
@endforeach
