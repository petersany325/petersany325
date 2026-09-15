@php
  $home = \App\Support\HomePageConfig::get();
  $heroImage = \App\Support\HomePageConfig::imageUrl((string) ($home['hero_image'] ?? 'images/home/hero.jpg'));
  $shopName = trim((string) \App\Models\Setting::getValue('shop_name', 'سرزمین هارد'));
@endphp

{{-- ۱) هیرو تمام‌عرض: برند + یک تیتر + یک جمله + CTA — مثل ریتم اپل --}}
@if(! empty($home['hero_enabled']))
<section class="hl-hero" aria-label="هیرو فروشگاه" style="{{ \App\Support\HomePageConfig::heroStyleAttr($home) }}">
  <div class="hl-hero__bg" aria-hidden="true">
    <img
      src="{{ $heroImage }}"
      width="1600"
      height="900"
      alt=""
      fetchpriority="high"
      decoding="async"
      onerror="this.remove()"
    >
    <div class="hl-hero__mesh"></div>
    <div class="hl-hero__scrim"></div>
  </div>

  <div class="hl-hero__inner">
    <p class="hl-hero__brand">{{ $shopName !== '' ? $shopName : 'سرزمین هارد' }}</p>
    <h1 class="hl-hero__title">{!! \App\Support\HomePageConfig::heroTitleHtml($home) !!}</h1>
    <p class="hl-hero__text">{{ $home['hero_text'] }}</p>
    <div class="hl-hero__cta">
      <a class="hl-btn hl-btn--primary" href="{{ url($home['hero_cta1_url'] ?: '/products') }}">{{ $home['hero_cta1_label'] }}</a>
      <a class="hl-btn hl-btn--ghost" href="{{ url($home['hero_cta2_url'] ?: '/contact') }}">{{ $home['hero_cta2_label'] }}</a>
    </div>
  </div>
</section>
@endif
