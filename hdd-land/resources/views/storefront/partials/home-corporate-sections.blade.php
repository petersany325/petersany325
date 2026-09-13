@php
  try {
    $home = \App\Support\HomePageConfig::get();
    $brands = \App\Support\HomePageConfig::brands($home);
    $featured = $featured ?? null;
    if (!($featured instanceof \Illuminate\Support\Collection)) {
      $featured = collect();
    }
  } catch (\Throwable $e) {
    $home = [];
    $brands = [];
    $featured = collect();
  }
@endphp

@if($featured->isNotEmpty())
<section class="hl-section hl-featured" aria-label="محصولات ویژه">
  <div class="hl-section__head">
    <h2>محصولات منتخب</h2>
    <a href="{{ url('/products') }}">مشاهده فروشگاه</a>
  </div>
  <div class="hl-featured__grid">
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
      <a class="hl-featured__item" href="{{ $url }}">
        @if($img !== '' && ! str_contains($img, 'product-placeholder'))
          <span class="hl-featured__media"><img src="{{ $img }}" alt="{{ $name }}" width="400" height="300" loading="lazy" decoding="async" onerror="this.closest('.hl-featured__media')?.classList.add('is-empty')"></span>
        @else
          <span class="hl-featured__ph" aria-hidden="true"></span>
        @endif
        <strong>{{ $name }}</strong>
      </a>
    @endforeach
  </div>
</section>
@endif

@if(! empty($home['edu_enabled']))
<section class="hl-section hl-edu">
  <div class="hl-section__head">
    <div>
      <h2>{{ $home['edu_title'] ?? 'آموزش‌ها' }}</h2>
      <p>{{ $home['edu_subtitle'] ?? '' }}</p>
    </div>
    <a href="{{ url($home['edu_more_url'] ?? '/blog') }}">{{ $home['edu_more_label'] ?? 'همه آموزش‌ها' }}</a>
  </div>
  <div class="hl-edu__list">
    @foreach([1, 2, 3] as $i)
      @php $title = trim((string) ($home['edu_'.$i.'_title'] ?? '')); @endphp
      @continue($title === '')
      <a class="hl-edu__row" href="{{ url($home['edu_'.$i.'_url'] ?? '/blog') }}">
        <img src="{{ \App\Support\HomePageConfig::imageUrl((string) ($home['edu_'.$i.'_image'] ?? '')) }}" alt="" width="640" height="420" loading="lazy" decoding="async" onerror="this.style.opacity='0'">
        <div>
          <strong>{{ $title }}</strong>
          <p>{{ $home['edu_'.$i.'_text'] ?? '' }}</p>
          <span>ادامه مطلب</span>
        </div>
      </a>
    @endforeach
  </div>
</section>
@endif

@if(! empty($home['about_enabled']))
<section class="hl-section hl-about">
  <div class="hl-about__media">
    <img src="{{ \App\Support\HomePageConfig::imageUrl((string) ($home['about_image'] ?? '')) }}" alt="{{ $home['about_title'] ?? '' }}" width="1200" height="800" loading="lazy" decoding="async" onerror="this.parentElement.classList.add('is-empty')">
  </div>
  <div class="hl-about__copy">
    <h2>{{ $home['about_title'] ?? '' }}</h2>
    @foreach(preg_split('/\R/u', (string) ($home['about_text'] ?? '')) ?: [] as $para)
      @if(trim($para) !== '')
        <p>{{ trim($para) }}</p>
      @endif
    @endforeach
    <div class="hl-about__stats">
      @foreach([1, 2, 3] as $i)
        <div>
          <b>{{ $home['about_stat'.$i.'_title'] ?? '' }}</b>
          <span>{{ $home['about_stat'.$i.'_text'] ?? '' }}</span>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(! empty($home['corp_enabled']))
<section class="hl-section hl-corp">
  <div class="hl-section__head">
    <div>
      <h2>{{ $home['corp_title'] ?? '' }}</h2>
      <p>{{ $home['corp_subtitle'] ?? '' }}</p>
    </div>
  </div>
  <div class="hl-corp__grid">
    @foreach([1, 2, 3] as $i)
      @php $title = trim((string) ($home['corp_'.$i.'_title'] ?? '')); @endphp
      @continue($title === '')
      <a class="hl-corp__panel" href="{{ url($home['corp_'.$i.'_url'] ?? '/contact') }}">
        <img src="{{ \App\Support\HomePageConfig::imageUrl((string) ($home['corp_'.$i.'_image'] ?? '')) }}" alt="" width="1200" height="800" loading="lazy" decoding="async" onerror="this.remove()">
        <div class="hl-corp__body">
          <strong>{{ $title }}</strong>
          <p>{{ $home['corp_'.$i.'_text'] ?? '' }}</p>
        </div>
      </a>
    @endforeach
  </div>
  <div class="hl-corp__cta">
    <div>
      <h3>{{ $home['corp_cta_title'] ?? '' }}</h3>
      <p>{{ $home['corp_cta_text'] ?? '' }}</p>
    </div>
    <a class="hl-btn hl-btn--primary" href="{{ url($home['corp_cta_url'] ?? '/contact') }}">{{ $home['corp_cta_label'] ?? 'تماس با ما' }}</a>
  </div>
</section>
@endif

@if(! empty($home['brands_enabled']) && $brands !== [])
<section class="hl-brands" aria-label="برندها">
  @foreach($brands as $brand)
    <span>{{ $brand }}</span>
  @endforeach
</section>
@endif
