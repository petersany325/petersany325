@php
  /** @var array $b */
  if (class_exists(\Plugins\ThemeBuilder\src\ThemeConfig::class)) {
    $b = \Plugins\ThemeBuilder\src\ThemeConfig::normalizeBanner($b);
  }
  $src = class_exists(\Plugins\ThemeBuilder\src\ThemeConfig::class)
    ? \Plugins\ThemeBuilder\src\ThemeConfig::bannerUrl($b, 1)
    : ($b['image_url'] ?? '');
  $src2 = class_exists(\Plugins\ThemeBuilder\src\ThemeConfig::class)
    ? \Plugins\ThemeBuilder\src\ThemeConfig::bannerUrl($b, 2)
    : ($b['image2_url'] ?? '');
  if ($src && str_starts_with($src, '/')) { $src = url($src); }
  if ($src2 && str_starts_with($src2, '/')) { $src2 = url($src2); }

  $layers = is_array($b['layers'] ?? null) ? $b['layers'] : [];
  $designW = max(320, min(2560, (int)($b['width'] ?? 1920)));
  $h = max(180, min(900, (int)($b['height'] ?? 620)));
  $layout = $b['layout'] ?? 'full';
  $effect = $b['effect'] ?? 'none';
  $speed = $b['effect_speed'] ?? 'normal';
  $hover = $b['hover_effect'] ?? 'none';
  $align = $b['align'] ?? 'right';
  $valign = $b['valign'] ?? 'center';
  $opacity = max(0, min(90, (int)($b['overlay_opacity'] ?? 18)));
  $contentW = max(240, min(900, (int)($b['content_width'] ?? 560)));
  $radius = max(0, min(40, (int)($b['radius'] ?? 0)));
  $dark = !empty($b['dark_overlay']);
  $display = $b['text_display'] ?? 'stacked';
  $placement = $b['placement'] ?? 'homepage';
  $isCarousel = $src2 && !empty($b['slider_enabled']);
  $sliderInterval = max(3000, min(15000, (int)($b['slider_interval'] ?? 6000)));
  $imageAlt = trim((string)($b['image_alt'] ?? ''));
  $fontMap = [
    'vazirmatn' => '"Vazirmatn", Tahoma, sans-serif',
    'noto' => '"Noto Sans Arabic", "Vazirmatn", Tahoma, sans-serif',
    'rubik' => '"Rubik", "Vazirmatn", Tahoma, sans-serif',
    'tahoma' => 'Tahoma, Geneva, sans-serif',
    'arial' => 'Arial, Helvetica, sans-serif',
    'georgia' => 'Georgia, "Times New Roman", serif',
  ];
  $needFonts = [];
  foreach ($layers as $L) {
    $f = $L['font'] ?? 'vazirmatn';
    if (in_array($f, ['noto', 'rubik'], true)) {
      $needFonts[$f] = true;
    }
  }
  $classes = implode(' ', array_filter([
    'site-banner',
    'site-banner-exact',
    'layout-'.$layout,
    'effect-'.$effect,
    'speed-'.$speed,
    'hover-'.$hover,
    'align-'.$align,
    'valign-'.$valign,
    'display-'.$display,
    'place-'.$placement,
    $dark ? 'has-overlay' : 'light-overlay',
    $src2 ? 'has-second' : '',
    $isCarousel ? 'is-carousel' : '',
    !$src ? 'no-image' : '',
  ]));

  $layerStyle = function (array $L) use ($fontMap, $designW): string {
    $font = $L['font'] ?? 'vazirmatn';
    $size = max(10, min(96, (int) ($L['size'] ?? 16)));
    $weight = preg_replace('/\D/', '', (string) ($L['weight'] ?? '700')) ?: '700';
    $color = preg_match('/^#[0-9A-Fa-f]{3,8}$/', (string) ($L['color'] ?? '')) ? $L['color'] : '#1a1d23';
    $letter = (float) ($L['letter'] ?? 0);
    $fluidSize = round(($size / $designW) * 100, 6);
    $ff = $fontMap[$font] ?? $fontMap['vazirmatn'];
    $parts = [
      'font-family:'.$ff.' !important',
      'font-size:'.$size.'px !important',
      'font-size:clamp(8px,'.$fluidSize.'cqw,'.$size.'px) !important',
      'font-weight:'.$weight.' !important',
      'color:'.$color.' !important',
      'letter-spacing:'.$letter.'px !important',
      'animation-delay:'.((int) ($L['delay'] ?? 0)).'ms',
    ];
    if (! empty($L['shadow'])) {
      $parts[] = 'text-shadow:0 2px 14px rgba(0,0,0,.25) !important';
    }
    if (! empty($L['bg']) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', (string) $L['bg'])) {
      $parts[] = 'background:'.$L['bg'].' !important';
    }

    return implode(';', $parts);
  };
@endphp
<section class="{{ $classes }}" data-placement="{{ $placement }}" data-placement-label="{{ $b['placement_label'] ?? '' }}"
  style="--banner-w:{{ $designW }};--banner-design-h:{{ $h }};--banner-ratio:{{ $designW }} / {{ $h }};--banner-py:{{ round(1000/$designW,6) }}cqw;--banner-px:{{ round(1600/$designW,6) }}cqw;--banner-radius-fluid:{{ round(1200/$designW,6) }}cqw;--banner-opacity:{{ $opacity / 100 }};--banner-content-w:{{ $contentW }}px;--banner-radius:{{ $radius }}px;">
  @if(!empty($b['placement_label']))
    <span class="banner-place-tag" aria-hidden="true">{{ $b['placement_label'] }}</span>
  @endif
  <div class="banner-media">
    @if($src)
      <img class="banner-img banner-img-1 img-anim-{{ $effect }} speed-{{ $speed }}"
           src="{{ $src }}" width="{{ $designW }}" height="{{ $h }}" alt="{{ $imageAlt }}"
           @if(in_array($placement,['homepage','under_header'],true)) fetchpriority="high" loading="eager" @else loading="lazy" @endif decoding="async">
    @else
      <div class="banner-img banner-img-1 img-anim-{{ $effect }} speed-{{ $speed }}"></div>
    @endif
    @if($src2)
      <img class="banner-img banner-img-2" src="{{ $src2 }}" width="{{ $designW }}" height="{{ $h }}" alt="" loading="lazy" decoding="async">
    @endif
  </div>
  @if(!empty($b['link']))
    <a class="site-banner-link" href="{{ url($b['link']) }}" @if(!empty($b['open_new'])) target="_blank" rel="noopener" @endif aria-label="بنر"></a>
  @endif
  <div class="site-banner-content free-layout">
    @foreach($layers as $L)
      @continue(empty($L['enabled']))
      @php
        $type = $L['type'] ?? 'text';
        $content = trim((string)($L['content'] ?? ''));
        if ($content === '') continue;
        $anim = $L['animation'] ?? 'none';
        $asp = $L['anim_speed'] ?? 'normal';
        $id = $L['id'] ?? '';
        $cls = 'banner-layer free-layer anim-item text-anim-'.$anim.' anim-speed-'.$asp;
        $fallbackPos = ['brand'=>[8,10],'badge'=>[8,23],'title'=>[8,35],'text'=>[8,52],'cta1'=>[8,70],'cta2'=>[24,70]];
        [$fallbackX,$fallbackY] = $fallbackPos[$id] ?? [10,20];
        $fallbackWidth = in_array($id, ['title','text'], true) ? ($id === 'title' ? 58 : 52) : 0;
        $width = max(0,min(90,(float)($L['width'] ?? $fallbackWidth)));
        $x = max(0,min($width > 0 ? 100 - $width : 95,(float)($L['x'] ?? $fallbackX)));
        $y = max(0,min(92,(float)($L['y'] ?? $fallbackY)));
        $style = $layerStyle($L).';left:'.$x.'%;top:'.$y.'%;'.($width > 0 ? 'width:'.$width.'%;max-width:'.$width.'%;white-space:normal' : 'width:max-content');
        $url = trim((string)($L['url'] ?? ''));
      @endphp
      @if($type === 'button')
        @php
          $isGhost = ($id === 'cta2') || (($L['style'] ?? '') === 'ghost');
          $buttonStyle = $style;
          if (!$isGhost && !empty($L['bg'])) $buttonStyle .= ';background:'.$L['bg'].'!important;border-color:'.$L['bg'].'!important';
        @endphp
        <a class="btn {{ $isGhost ? 'btn-outline banner-btn-ghost' : 'btn-primary' }} {{ $cls }}" href="{{ url($url !== '' ? $url : '#') }}" style="{{ $buttonStyle }}">{{ $content }}</a>
      @elseif($type === 'badge')
        @if($url !== '')
          <a href="{{ url($url) }}" class="banner-badge {{ $cls }}" style="{{ $style }};text-decoration:none">{{ $content }}</a>
        @else
          <span class="banner-badge {{ $cls }}" style="{{ $style }}">{{ $content }}</span>
        @endif
      @elseif($id === 'title')
        @if($url !== '')
          <a href="{{ url($url) }}" class="banner-title {{ $cls }}" style="{{ $style }};text-decoration:none;display:block">{{ $content }}</a>
        @else
          <h2 class="banner-title {{ $cls }}" style="{{ $style }}">{{ $content }}</h2>
        @endif
      @elseif($id === 'brand')
        @if($url !== '')
          <a href="{{ url($url) }}" class="banner-brand {{ $cls }}" style="{{ $style }};text-decoration:none;display:block">{{ $content }}</a>
        @else
          <div class="banner-brand {{ $cls }}" style="{{ $style }}">{{ $content }}</div>
        @endif
      @else
        @if($url !== '')
          <a href="{{ url($url) }}" class="banner-text {{ $cls }}" style="{{ $style }};text-decoration:none;display:block">{{ $content }}</a>
        @else
          <p class="banner-text {{ $cls }}" style="{{ $style }}">{{ $content }}</p>
        @endif
      @endif
    @endforeach
  </div>
  @if($isCarousel && !empty($b['slider_navigation']))
    <div class="banner-slider-controls" aria-label="کنترل اسلایدر">
      <button type="button" class="banner-prev" aria-label="اسلاید قبلی">→</button>
      <span class="banner-dots"><button type="button" class="active" aria-label="اسلاید اول"></button><button type="button" aria-label="اسلاید دوم"></button></span>
      <button type="button" class="banner-next" aria-label="اسلاید بعدی">←</button>
    </div>
  @endif
</section>
<style>
.site-banner.site-banner-exact{container-type:inline-size;height:auto!important;min-height:0!important;aspect-ratio:var(--banner-ratio)!important;max-height:none!important}.site-banner.site-banner-exact .banner-img{object-fit:cover;object-position:center}.site-banner.site-banner-exact .free-layer.btn{padding:clamp(3px,var(--banner-py),10px) clamp(6px,var(--banner-px),16px)!important;border-radius:clamp(6px,var(--banner-radius-fluid),12px)!important}.site-banner.site-banner-exact .banner-badge{padding:clamp(2px,var(--banner-py),5px) clamp(5px,var(--banner-radius-fluid),12px)!important}
.site-banner .site-banner-content.free-layout{position:absolute!important;inset:0!important;width:100%!important;max-width:none!important;margin:0!important;padding:0!important;background:transparent!important;box-shadow:none!important;border:0!important;display:block!important;pointer-events:none}.site-banner .free-layer{position:absolute!important;z-index:6;pointer-events:auto;margin:0!important;white-space:normal;overflow-wrap:anywhere;line-height:1.45}.site-banner .free-layer.btn{display:inline-flex!important}.site-banner .free-layer.banner-badge{display:inline-flex}@media(max-width:760px){.site-banner .free-layer{max-width:84%!important}.site-banner .free-layer.btn{padding:.5rem .75rem!important}}
</style>
@if($isCarousel)
<style>
.site-banner.is-carousel .banner-img{will-change:opacity,transform;transition:opacity .75s cubic-bezier(.22,.61,.36,1),transform 6s ease;backface-visibility:hidden}.site-banner.is-carousel .banner-img-1{opacity:1}.site-banner.is-carousel .banner-img-2{opacity:0}.site-banner.is-carousel.slide-2 .banner-img-1{opacity:0}.site-banner.is-carousel.slide-2 .banner-img-2{opacity:1;transform:scale(1.035)}.banner-slider-controls{position:absolute;z-index:8;inset-inline:0;bottom:16px;display:flex;align-items:center;justify-content:center;gap:.65rem;pointer-events:none}.banner-slider-controls>button,.banner-dots button{pointer-events:auto;border:1px solid rgba(255,255,255,.45);background:rgba(15,23,42,.52);color:#fff;backdrop-filter:blur(8px);cursor:pointer}.banner-slider-controls>button{width:36px;height:36px;border-radius:12px;font-size:1rem}.banner-dots{display:flex;gap:.35rem}.banner-dots button{width:9px;height:9px;padding:0;border-radius:99px;transition:width .25s}.banner-dots button.active{width:24px;background:#fff}@media(prefers-reduced-motion:reduce){.site-banner.is-carousel .banner-img{transition:none!important;animation:none!important;transform:none!important}}@media(max-width:640px){.banner-slider-controls{bottom:10px}.banner-slider-controls>button{width:32px;height:32px}}
</style>
<script>
(()=>{const root=document.currentScript.previousElementSibling?.previousElementSibling||document.querySelector('.site-banner.is-carousel:last-of-type');if(!root||root.dataset.sliderReady)return;root.dataset.sliderReady='1';const dots=[...root.querySelectorAll('.banner-dots button')];let index=0,timer=null;const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;const show=i=>{index=(i+2)%2;root.classList.toggle('slide-2',index===1);dots.forEach((d,n)=>d.classList.toggle('active',n===index))};const stop=()=>{if(timer){clearInterval(timer);timer=null}};const start=()=>{stop();if({{ !empty($b['slider_autoplay']) ? 'true' : 'false' }}&&!reduced)timer=setInterval(()=>show(index+1),{{ $sliderInterval }})};root.querySelector('.banner-prev')?.addEventListener('click',()=>{show(index-1);start()});root.querySelector('.banner-next')?.addEventListener('click',()=>{show(index+1);start()});dots.forEach((d,n)=>d.addEventListener('click',()=>{show(n);start()}));@if(!empty($b['slider_pause_hover'])) root.addEventListener('mouseenter',stop);root.addEventListener('mouseleave',start); @endif document.addEventListener('visibilitychange',()=>document.hidden?stop():start());start()})();
</script>
@endif
