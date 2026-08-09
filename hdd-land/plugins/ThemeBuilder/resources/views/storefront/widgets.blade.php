@foreach($widgets as $widget)
  @php
    $type = $widget['type'] ?? '';
    $s = $widget['settings'] ?? [];
    if (array_key_exists('enabled', $s) && ! $s['enabled']) { continue; }
  @endphp

  @if($type === 'online')
    <div class="online-bar">
      <div class="online-bar-inner">
        <span class="online-status">
          <span class="online-dot" aria-hidden="true"></span>
          <strong>{{ $s['badge'] ?? 'آنلاین' }}</strong>
        </span>
        <span class="online-copy">{{ $s['text'] ?? '' }}</span>
        @if(!empty($s['support_text']))
          <span class="online-support muted">{{ $s['support_text'] }}</span>
        @endif
        @if(!empty($s['phone']))
          <a class="online-phone" href="tel:{{ preg_replace('/\s+/','',$s['phone']) }}">{{ $s['phone'] }}</a>
        @endif
        <a class="online-track" href="{{ url($s['track_url'] ?? '/orders/track') }}">{{ $s['track_text'] ?? 'پیگیری سفارش' }}</a>
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
      // صفحه اول: فقط کالای موجود با قیمت واقعی؛ در صورت کمبود از آخرین‌ها پر می‌شود
      $pick = function ($items) {
        return collect($items)->filter(function ($p) {
          return $p && method_exists($p, 'inStock') && $p->inStock() && (int) ($p->price ?? 0) > 0;
        });
      };
      $list = $pick($list)->values();
      if ($list->count() < $limit) {
        $seen = $list->pluck('id')->filter()->all();
        $fill = $pick($latest ?? collect())
          ->reject(fn ($p) => in_array($p->id, $seen, true))
          ->take($limit - $list->count());
        $list = $list->concat($fill)->values();
      }
      $list = $list->take($limit)->values();
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
            $key = mb_strtolower(($cat->slug ?? '').' '.($cat->name ?? ''));
            $iconKind = 'chip';
            if (str_contains($key, 'nvme') || str_contains($key, 'm.2') || str_contains($key, 'ام ۲')) { $iconKind = 'nvme'; }
            elseif (str_contains($key, 'ssd') || str_contains($key, 'اس اس دی')) { $iconKind = 'ssd'; }
            elseif (str_contains($key, 'ram') || str_contains($key, 'رم') || str_contains($key, 'حافظه')) { $iconKind = 'ram'; }
            elseif (str_contains($key, 'hdd') || str_contains($key, 'هارد') || str_contains($key, 'hard')) { $iconKind = 'hdd'; }
          @endphp
          <div class="home-cat" role="listitem">
            <a class="home-cat__main" href="{{ route('categories.show', $cat->slug) }}">
              <span class="home-cat__icon home-cat__icon--{{ $iconKind }}" aria-hidden="true">
                @if($img)
                  <img src="{{ $img }}" alt="" width="28" height="28" loading="lazy">
                @elseif($iconKind === 'ssd')
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h2M11 10h2M15 10h2M7 14h10"/></svg>
                @elseif($iconKind === 'nvme')
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="8" width="20" height="8" rx="1.5"/><circle cx="6" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="18" cy="12" r="1.2" fill="currentColor" stroke="none"/><path d="M9 12h6"/></svg>
                @elseif($iconKind === 'ram')
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="10" rx="1.5"/><path d="M6 7V5M10 7V5M14 7V5M18 7V5M7 17v2M11 17v2M15 17v2"/></svg>
                @elseif($iconKind === 'hdd')
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 14h18"/><circle cx="7.5" cy="17" r="1" fill="currentColor" stroke="none"/><circle cx="11" cy="17" r="1" fill="currentColor" stroke="none"/></svg>
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
