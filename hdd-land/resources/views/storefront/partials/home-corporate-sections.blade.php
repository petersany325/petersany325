@php
  $home = \App\Support\HomePageConfig::get();
  $brands = \App\Support\HomePageConfig::brands($home);
@endphp

@if(!empty($home['edu_enabled']))
<section class="section hl-edu-section">
  <div class="hl-head">
    <div>
      <h2>{{ $home['edu_title'] }}</h2>
      <p>{{ $home['edu_subtitle'] }}</p>
    </div>
  </div>
  <div class="hl-edu-grid">
    @foreach([1,2,3] as $i)
      @php $title = trim((string) ($home['edu_'.$i.'_title'] ?? '')); @endphp
      @continue($title === '')
      <article class="hl-edu-card">
        <img src="{{ \App\Support\HomePageConfig::imageUrl((string) ($home['edu_'.$i.'_image'] ?? '')) }}" alt="" width="960" height="640" loading="lazy">
        <div class="body">
          <strong>{{ $title }}</strong>
          <p>{{ $home['edu_'.$i.'_text'] ?? '' }}</p>
          <a href="{{ url($home['edu_'.$i.'_url'] ?: '/blog') }}">مشاهده آموزش</a>
        </div>
      </article>
    @endforeach
  </div>
  <div class="hl-edu-more">
    <a class="btn-hl btn-hl-ghost" href="{{ url($home['edu_more_url'] ?: '/blog') }}">{{ $home['edu_more_label'] ?: 'مشاهده همه آموزش‌ها' }}</a>
  </div>
</section>
@endif

@if(!empty($home['about_enabled']))
<section class="section hl-about-section">
  <div class="hl-about">
    <img src="{{ \App\Support\HomePageConfig::imageUrl((string) ($home['about_image'] ?? '')) }}" alt="{{ $home['about_title'] }}" width="1200" height="800" loading="lazy">
    <div>
      <div class="hl-head" style="margin-bottom:.6rem">
        <div><h2>{{ $home['about_title'] }}</h2></div>
      </div>
      @foreach(preg_split('/\R/u', (string) ($home['about_text'] ?? '')) ?: [] as $para)
        @if(trim($para) !== '')
          <p class="muted">{{ trim($para) }}</p>
        @endif
      @endforeach
      <div class="hl-stats">
        @foreach([1,2,3] as $i)
          <div class="hl-stat"><b>{{ $home['about_stat'.$i.'_title'] ?? '' }}</b><span>{{ $home['about_stat'.$i.'_text'] ?? '' }}</span></div>
        @endforeach
      </div>
    </div>
  </div>
</section>
@endif

@if(!empty($home['corp_enabled']))
<section class="section hl-corp-section">
  <div class="hl-head">
    <div>
      <h2>{{ $home['corp_title'] }}</h2>
      <p>{{ $home['corp_subtitle'] }}</p>
    </div>
  </div>
  <div class="hl-corp-grid">
    @foreach([1,2,3] as $i)
      @php $title = trim((string) ($home['corp_'.$i.'_title'] ?? '')); @endphp
      @continue($title === '')
      <article class="hl-corp-card">
        <img src="{{ \App\Support\HomePageConfig::imageUrl((string) ($home['corp_'.$i.'_image'] ?? '')) }}" alt="" width="1200" height="800" loading="lazy">
        <div class="body">
          <strong>{{ $title }}</strong>
          <p>{{ $home['corp_'.$i.'_text'] ?? '' }}</p>
          <a href="{{ url($home['corp_'.$i.'_url'] ?: '/contact') }}">مشاهده بیشتر</a>
        </div>
      </article>
    @endforeach
  </div>
  <div class="hl-org-cta">
    <div>
      <h3>{{ $home['corp_cta_title'] }}</h3>
      <p>{{ $home['corp_cta_text'] }}</p>
    </div>
    <a class="btn-hl btn-hl-primary" href="{{ url($home['corp_cta_url'] ?: '/contact') }}">{{ $home['corp_cta_label'] }}</a>
  </div>
</section>
@endif

@if(!empty($home['brands_enabled']) && $brands !== [])
<div class="section" style="padding-top:.2rem">
  <div class="hl-brands" aria-label="برندها">
    @foreach($brands as $brand)
      <span>{{ $brand }}</span>
    @endforeach
  </div>
</div>
@endif
