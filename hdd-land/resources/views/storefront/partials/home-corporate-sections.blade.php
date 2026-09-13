@php
  try {
    $home = \App\Support\HomePageConfig::get();
    $trust = \App\Support\HomePageConfig::trustItems($home);
    $brands = \App\Support\HomePageConfig::brands($home);
    $featured = $featured ?? null;
    if (!($featured instanceof \Illuminate\Support\Collection)) {
      $featured = collect();
    }
  } catch (\Throwable $e) {
    $home = [];
    $trust = [];
    $brands = [];
    $featured = collect();
  }
@endphp

{{-- ۲) بند داستانی تمام‌عرض — درباره ما (یک پیام) --}}
@if(! empty($home['about_enabled']))
<section class="hl-band hl-band--about" aria-label="درباره فروشگاه">
  <div class="hl-band__media" aria-hidden="true">
    <img
      src="{{ \App\Support\HomePageConfig::imageUrl((string) ($home['about_image'] ?? '')) }}"
      alt=""
      width="1600"
      height="900"
      loading="lazy"
      decoding="async"
      onerror="this.closest('.hl-band__media')?.classList.add('is-empty')"
    >
  </div>
  <div class="hl-band__copy">
    <h2 class="hl-band__title">{{ $home['about_title'] ?? '' }}</h2>
    @php
      $aboutParas = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($home['about_text'] ?? '')) ?: [])));
      $aboutLead = $aboutParas[0] ?? '';
    @endphp
    @if($aboutLead !== '')
      <p class="hl-band__text">{{ $aboutLead }}</p>
    @endif
    <div class="hl-band__cta">
      <a class="hl-link" href="{{ url('/about') }}">بیشتر بدانید</a>
      <a class="hl-link hl-link--muted" href="{{ url('/contact') }}">تماس با ما</a>
    </div>
  </div>
</section>
@endif

{{-- ۳) بند CTA سازمانی تمام‌عرض --}}
@if(! empty($home['corp_enabled']))
<section class="hl-band hl-band--corp" aria-label="خرید سازمانی">
  <div class="hl-band__copy hl-band__copy--center">
    <p class="hl-band__eyebrow">{{ $home['corp_subtitle'] ?? '' }}</p>
    <h2 class="hl-band__title">{{ $home['corp_cta_title'] ?: ($home['corp_title'] ?? '') }}</h2>
    <p class="hl-band__text">{{ $home['corp_cta_text'] ?? '' }}</p>
    <div class="hl-band__cta">
      <a class="hl-btn hl-btn--primary" href="{{ url($home['corp_cta_url'] ?? '/contact') }}">{{ $home['corp_cta_label'] ?? 'تماس با واحد فروش' }}</a>
    </div>
  </div>
</section>

{{-- ۴) شبکه پرومو ۲ستونه (مثل promoهای اپل) --}}
@php
  $corpTiles = [];
  foreach ([1, 2, 3] as $i) {
    $title = trim((string) ($home['corp_'.$i.'_title'] ?? ''));
    if ($title === '') {
      continue;
    }
    $corpTiles[] = [
      'title' => $title,
      'text' => (string) ($home['corp_'.$i.'_text'] ?? ''),
      'image' => \App\Support\HomePageConfig::imageUrl((string) ($home['corp_'.$i.'_image'] ?? '')),
      'url' => url($home['corp_'.$i.'_url'] ?? '/contact'),
    ];
  }
@endphp
@if($corpTiles !== [])
<section class="hl-tiles" aria-label="خدمات سازمانی">
  @foreach($corpTiles as $tile)
    <a class="hl-tile" href="{{ $tile['url'] }}">
      <span class="hl-tile__media" aria-hidden="true">
        <img src="{{ $tile['image'] }}" alt="" width="900" height="700" loading="lazy" decoding="async" onerror="this.remove()">
      </span>
      <span class="hl-tile__copy">
        <strong class="hl-tile__title">{{ $tile['title'] }}</strong>
        <span class="hl-tile__text">{{ $tile['text'] }}</span>
        <span class="hl-link">جزئیات</span>
      </span>
    </a>
  @endforeach
</section>
@endif
@endif

{{-- ۵) محصولات منتخب — شبکه تمیز بدون کارت سنگین --}}
@if($featured->isNotEmpty())
<section class="hl-shelf" aria-label="محصولات ویژه">
  <div class="hl-shelf__head">
    <h2>محصولات منتخب</h2>
    <a class="hl-link" href="{{ url('/products') }}">مشاهده فروشگاه</a>
  </div>
  <div class="hl-shelf__grid">
    @foreach($featured->take(8) as $product)
      @php
        try {
          $name = $product->name ?? $product->title ?? 'محصول';
          $url = url('/products/'.($product->slug ?? $product->id ?? ''));
          $img = '';
          if (is_object($product) && method_exists($product, 'imageUrl')) {
              $img = (string) $product->imageUrl();
          } else {
            $raw = (string) ($product->image_url ?? $product->thumb_url ?? $product->image ?? '');
            $raw = ltrim(str_replace('\\', '/', $raw), '/');
            if ($raw !== '') {
              if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                $img = $raw;
              } elseif (str_starts_with($raw, 'uploads/')) {
                $img = asset($raw);
              } elseif (str_starts_with($raw, 'media/')) {
                  $img = asset('uploads/'.$raw);
              } else {
                $img = asset($raw);
              }
            }
          }
        } catch (\Throwable $e) {
          $name = 'محصول';
          $url = url('/products');
          $img = '';
        }
      @endphp
      <a class="hl-shelf__item" href="{{ $url }}">
        @if($img !== '' && ! str_contains($img, 'product-placeholder'))
          <span class="hl-shelf__media"><img src="{{ $img }}" alt="{{ $name }}" width="400" height="300" loading="lazy" decoding="async" onerror="this.closest('.hl-shelf__media')?.classList.add('is-empty')"></span>
        @else
          <span class="hl-shelf__ph" aria-hidden="true"></span>
        @endif
        <strong>{{ $name }}</strong>
      </a>
    @endforeach
  </div>
</section>
@endif

{{-- ۶) آموزش‌ها به‌صورت کاشی‌های داستانی --}}
@if(! empty($home['edu_enabled']))
@php
  $eduTiles = [];
  foreach ([1, 2, 3] as $i) {
    $title = trim((string) ($home['edu_'.$i.'_title'] ?? ''));
    if ($title === '') {
      continue;
    }
    $eduTiles[] = [
      'title' => $title,
      'text' => (string) ($home['edu_'.$i.'_text'] ?? ''),
      'image' => \App\Support\HomePageConfig::imageUrl((string) ($home['edu_'.$i.'_image'] ?? '')),
      'url' => url($home['edu_'.$i.'_url'] ?? '/blog'),
    ];
  }
@endphp
@if($eduTiles !== [])
<section class="hl-tiles hl-tiles--edu" aria-label="آموزش‌ها">
  <div class="hl-shelf__head hl-shelf__head--bleed">
    <div>
      <h2>{{ $home['edu_title'] ?? 'آموزش‌ها' }}</h2>
      <p>{{ $home['edu_subtitle'] ?? '' }}</p>
    </div>
    <a class="hl-link" href="{{ url($home['edu_more_url'] ?? '/blog') }}">{{ $home['edu_more_label'] ?? 'همه آموزش‌ها' }}</a>
  </div>
  @foreach($eduTiles as $tile)
    <a class="hl-tile hl-tile--light" href="{{ $tile['url'] }}">
      <span class="hl-tile__media" aria-hidden="true">
        <img src="{{ $tile['image'] }}" alt="" width="900" height="700" loading="lazy" decoding="async" onerror="this.remove()">
      </span>
      <span class="hl-tile__copy">
        <strong class="hl-tile__title">{{ $tile['title'] }}</strong>
        <span class="hl-tile__text">{{ $tile['text'] }}</span>
        <span class="hl-link">ادامه مطلب</span>
      </span>
    </a>
  @endforeach
</section>
@endif
@endif

{{-- ۷) اعتماد — نوار آرام بعد از محتوای اصلی (خارج از ویوپورت اول) --}}
@if(! empty($home['trust_enabled']) && $trust !== [])
<section class="hl-trust" aria-label="اعتماد">
  @foreach($trust as $item)
    <div class="hl-trust__item">
      <strong>{{ $item['title'] }}</strong>
      <span>{{ $item['text'] }}</span>
    </div>
  @endforeach
</section>
@endif

{{-- ۸) برندها --}}
@if(! empty($home['brands_enabled']) && $brands !== [])
<section class="hl-brands" aria-label="برندها">
  @foreach($brands as $brand)
    <span>{{ $brand }}</span>
  @endforeach
</section>
@endif
