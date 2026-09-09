@php
  /** @var \Plugins\MegaMenu\src\Models\MegaMenuItem $child */
  $nested = ! empty($nested);
  $showIcons = $showIcons ?? true;
  $skipHeading = ! empty($skipHeading);
  $kids = $child->relationLoaded('activeChildren')
    ? $child->activeChildren
    : $child->activeChildren()->with(['category', 'activeChildren.category', 'activeChildren.activeChildren'])->get();
  $hasKids = $kids->count() > 0;
  $type = (string) ($child->type ?: 'link');
  $href = $child->href();
  $openNew = ! empty($child->open_in_new);
  $isGroup = in_array($type, ['column', 'heading', 'tab'], true)
    || ($hasKids && ! $nested && in_array($type, ['link', 'category'], true));
@endphp

@if($isGroup)
  <div class="mega-col {{ $child->css_class }} {{ $hasKids ? 'has-kids' : '' }}" style="{{ $child->inlineStyle() }}">
    @if(! $skipHeading && ($type === 'heading' || $type === 'column' || $type === 'tab' || $hasKids))
      @if($href && $href !== '#' && $href !== url('#'))
        <a class="mega-heading" href="{{ $href }}" @if($openNew) target="_blank" rel="noopener" @endif>
          @if($showIcons && $child->icon_image_url)
            <img class="mega-ico-img" src="{{ $child->icon_image_url }}" alt="">
          @elseif($showIcons && $child->icon)
            <span class="mega-ico">{{ $child->icon }}</span>
          @endif
          <span>{{ $child->tab_label ?: $child->title }}</span>
          @if($child->badge)<span class="mega-badge">{{ $child->badge }}</span>@endif
        </a>
      @else
        <div class="mega-heading">
          @if($showIcons && $child->icon_image_url)
            <img class="mega-ico-img" src="{{ $child->icon_image_url }}" alt="">
          @elseif($showIcons && $child->icon)
            <span class="mega-ico">{{ $child->icon }}</span>
          @endif
          <span>{{ $child->tab_label ?: $child->title }}</span>
          @if($child->badge)<span class="mega-badge">{{ $child->badge }}</span>@endif
        </div>
      @endif
      @if($child->description)
        <p class="mega-desc">{{ $child->description }}</p>
      @endif
    @endif

    @if($hasKids)
      <div class="mega-sub-list {{ $nested || $skipHeading ? 'mega-sub-list--nested' : '' }}">
        @foreach($kids as $grand)
          @include('mega-menu::storefront.partials.item-block', ['child' => $grand, 'nested' => true, 'showIcons' => $showIcons])
        @endforeach
      </div>
    @endif
  </div>
@elseif($type === 'promo')
  <a class="mega-promo {{ $child->css_class }}" href="{{ $href }}" @if($openNew) target="_blank" rel="noopener" @endif style="{{ $child->inlineStyle() }}">
    @if($child->image_url)
      <img src="{{ $child->image_url }}" alt="{{ $child->title }}" loading="lazy">
    @endif
    <div class="mega-promo-body">
      <strong>
        @if($showIcons && $child->icon)<span>{{ $child->icon }}</span>@endif
        {{ $child->title }}
        @if($child->badge)<span class="mega-badge">{{ $child->badge }}</span>@endif
      </strong>
      @if($child->description)<p>{{ $child->description }}</p>@endif
    </div>
  </a>
@elseif($type === 'html')
  <div class="mega-html {{ $child->css_class }}" style="{{ $child->inlineStyle() }}">
    {!! \App\Support\SafeHtml::clean((string) $child->html) !!}
  </div>
@elseif($type === 'search')
  <form class="mega-search mega-search-inline {{ $child->css_class }}" action="{{ url('/products') }}" method="get" role="search">
    <input type="search" name="q" placeholder="{{ $child->search_placeholder ?: 'جستجو...' }}" autocomplete="off">
    <button type="submit">جستجو</button>
  </form>
@else
  {{-- link / category / plain nested item --}}
  <div class="mega-link-wrap {{ $hasKids ? 'has-kids' : '' }}">
    <a class="{{ $nested ? 'mega-sub' : 'mega-link' }} {{ $child->css_class }} {{ $child->image_url ? 'has-thumb' : '' }}"
       href="{{ $href }}"
       @if($openNew) target="_blank" rel="noopener" @endif
       style="{{ $child->inlineStyle() }}">
      @if($child->image_url)
        <img class="mega-thumb" src="{{ $child->image_url }}" alt="" loading="lazy">
      @elseif($showIcons && $child->icon_image_url)
        <img class="mega-ico-img" src="{{ $child->icon_image_url }}" alt="">
      @elseif($showIcons && $child->icon)
        <span class="mega-ico">{{ $child->icon }}</span>
      @endif
      <span class="mega-link-text">
        <span class="mega-link-title">
          {{ $child->title }}
          @if($child->badge)<span class="mega-badge">{{ $child->badge }}</span>@endif
        </span>
        @if($child->description)
          <small class="mega-desc">{{ $child->description }}</small>
        @endif
      </span>
    </a>
    @if($hasKids)
      <div class="mega-sub-list mega-sub-list--nested">
        @foreach($kids as $grand)
          @include('mega-menu::storefront.partials.item-block', ['child' => $grand, 'nested' => true, 'showIcons' => $showIcons])
        @endforeach
      </div>
    @endif
  </div>
@endif
