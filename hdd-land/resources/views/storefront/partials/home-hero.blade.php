@php
  $home = \App\Support\HomePageConfig::get();
  $trust = \App\Support\HomePageConfig::trustItems($home);
  $heroImage = \App\Support\HomePageConfig::imageUrl((string) ($home['hero_image'] ?? 'images/home/hero.jpg'));
  $mergeImage = \App\Support\HomePageConfig::imageUrl((string) ($home['hero_merge_image'] ?? ''));
  $layout = (string) ($home['hero_layout'] ?? 'split-rtl');

  // مسیر واقعی صفحه اول از این partial است — بنر Revolution را همین‌جا اولویت بده.
  $themeClass = \Plugins\ThemeBuilder\src\ThemeConfig::class;
  $revolutionBanner = [];
  $useRevolution = false;
  if (class_exists($themeClass) && method_exists($themeClass, 'get')) {
    try {
      $theme = $themeClass::get();
      $revolutionBanner = is_array($theme['banner'] ?? null) ? $theme['banner'] : [];
      if (method_exists($themeClass, 'bannerIsLive')) {
        $useRevolution = (bool) $themeClass::bannerIsLive($revolutionBanner);
      } elseif (! empty($revolutionBanner['enabled'])) {
        $img = method_exists($themeClass, 'bannerUrl') ? (string) $themeClass::bannerUrl($revolutionBanner, 1) : '';
        $layers = is_array($revolutionBanner['layers'] ?? null) ? $revolutionBanner['layers'] : [];
        $hasLayer = false;
        foreach ($layers as $layer) {
          if (is_array($layer) && ! empty($layer['enabled']) && empty($layer['deleted']) && trim((string) ($layer['content'] ?? '')) !== '') {
            $hasLayer = true;
            break;
          }
        }
        $useRevolution = $img !== '' || $hasLayer;
      }
    } catch (\Throwable) {
      $useRevolution = false;
    }
  }

  // اگر homepage از قبل Revolution را رندر کرده، دوباره نکش.
  $alreadyRendered = ! empty($revolutionAlreadyRendered);
  $skipHero = ! empty($skipHero) || $useRevolution;
@endphp

@if($useRevolution && ! $alreadyRendered)
  @include('theme-builder::storefront.partials.banner', ['b' => $revolutionBanner])
@elseif(! $skipHero && ! empty($home['hero_enabled']))
{{-- مارکاپ هم‌تراز با صفحه زنده hdd-land.ir برای fallback --}}
<section class="hl-hero" aria-label="بنر فروشگاه و تأمین سازمانی" @if(method_exists(\App\Support\HomePageConfig::class, 'heroStyleAttr')) style="{{ \App\Support\HomePageConfig::heroStyleAttr($home) }}" @endif>
  <div class="hl-hero__copy">
    <div class="hl-kicker"><i></i> {{ $home['hero_kicker'] }}</div>
    <h1>{!! \App\Support\HomePageConfig::heroTitleHtml($home) !!}</h1>
    <p class="hl-hero__lead">{{ $home['hero_text'] }}</p>
    <div class="hl-hero__actions">
      <a class="btn-hl btn-hl-primary" href="{{ url($home['hero_cta1_url'] ?: '/products') }}">{{ $home['hero_cta1_label'] }}</a>
      <a class="btn-hl btn-hl-ghost" href="{{ url($home['hero_cta2_url'] ?: '/contact') }}">{{ $home['hero_cta2_label'] }}</a>
    </div>
  </div>
  <div class="hl-hero__media">
    <img src="{{ $heroImage }}" width="1350" height="900" alt="{{ $home['hero_title'] }}" fetchpriority="high" decoding="async">
    @if(!empty($home['hero_merge_enabled']) && $mergeImage !== '')
      <img class="hl-hero__merge" src="{{ $mergeImage }}" alt="" loading="lazy" decoding="async">
    @endif
  </div>
</section>
@endif

@if(!empty($home['trust_enabled']) && $trust !== [])
<section class="hl-trust" aria-label="اعتماد">
  @foreach($trust as $item)
    <div class="hl-trust-item">
      <span class="hl-trust-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v6c0 5 3.4 8.4 7 9 3.6-.6 7-4 7-9V6l-7-3z"/><path d="m9 12 2 2 4-4"/></svg>
      </span>
      <strong>{{ $item['title'] }}</strong>
      <span>{{ $item['text'] }}</span>
    </div>
  @endforeach
</section>
@endif
