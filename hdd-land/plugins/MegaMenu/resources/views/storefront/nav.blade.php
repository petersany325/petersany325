@php
  try {
    $menuTree = \Plugins\MegaMenu\src\Models\MegaMenuItem::tree();
    $mm = \Plugins\MegaMenu\Plugin::settings();
  } catch (\Throwable $e) {
    $menuTree = collect();
    $mm = ['nav_align'=>'right','nav_style'=>'pills','dropdown_size'=>'auto','show_icons'=>true,'accent'=>'#e23d12','open_mode'=>'hover','gap_brand'=>18];
  }
  $fontFamilies = [];
  foreach ($menuTree as $mi) {
    $f = trim((string) ($mi->font_family ?? ''));
    if ($f !== '' && ! str_contains($f, ',') && ! in_array($f, ['Tahoma', 'Arial'], true)) {
      $fontFamilies[$f] = true;
    }
  }
  $googleFontsUrl = '';
  if ($fontFamilies) {
    $parts = [];
    foreach (array_keys($fontFamilies) as $fn) {
      $parts[] = 'family='.rawurlencode($fn).':wght@400;600;700;800';
    }
    $googleFontsUrl = 'https://fonts.googleapis.com/css2?'.implode('&', $parts).'&display=swap';
  }
  $navAlign = $mm['nav_align'] ?? 'right';
  $navStyle = $mm['nav_style'] ?? 'underline';
  $ddSize = $mm['dropdown_size'] ?? 'auto';
  $showIcons = !empty($mm['show_icons']);
  $accent = $mm['accent'] ?? '#e23d12';
  $defaultOpen = $mm['open_mode'] ?? 'hover';
  $gapBrand = (int) ($mm['gap_brand'] ?? 18);
  $panelFx = $mm['panel_fx'] ?? 'soft';
  $panelBg = $mm['panel_bg'] ?? 'white';
  $panelLayout = $mm['panel_layout'] ?? 'columns';
  $panelCols = max(2, min(6, (int) ($mm['panel_cols'] ?? 4)));
  $navItemGap = max(0, min(32, (int) ($mm['nav_item_gap'] ?? 4)));
  $panelColGap = max(4, min(48, (int) ($mm['panel_col_gap'] ?? 16)));
  $panelRowGap = max(4, min(48, (int) ($mm['panel_row_gap'] ?? 12)));
  $panelPadding = max(8, min(48, (int) ($mm['panel_padding'] ?? 16)));
@endphp
@if($menuTree->count())
<nav class="nav mega-nav mega-nav-pro nav-align-{{ $navAlign }} style-{{ $navStyle }} dd-{{ $ddSize }} panel-fx-{{ $panelFx }} panel-bg-{{ $panelBg }} layout-{{ $panelLayout }}"
     data-mega-nav dir="rtl"
     style="--mega-accent:{{ $accent }};--mega-gap-brand:{{ $gapBrand }}px;--mega-nav-gap:{{ $navItemGap }}px;--mega-cols:{{ $panelCols }};--mega-col-gap:{{ $panelColGap }}px;--mega-row-gap:{{ $panelRowGap }}px;--mega-panel-pad:{{ $panelPadding }}px">
  @foreach($menuTree as $item)
    @php
      $hasPanel = $item->is_mega || $item->activeChildren->count() || ($item->form_type ?? 'none') !== 'none' || $item->show_search;
      $style = $item->inlineStyle();
      $openMode = $item->open_mode ?: $defaultOpen;
      $titleKey = mb_strtolower($item->title ?? '');
      $shopish = str_contains($titleKey, 'محصول') || str_contains($titleKey, 'فروشگاه');
      $forceMega = (bool) $item->is_mega || ($shopish && $item->activeChildren->count() > 0);
      $panelClass = $item->panelClasses();
      if ($forceMega) {
        if (in_array($panelLayout, ['cascade', 'list'], true)) {
          $panelClass = trim(str_replace(['is-mega-panel', 'w-wide', 'w-full', 'w-normal'], '', $panelClass).' is-mega-panel w-normal');
        } else {
          $panelClass = trim(str_replace('is-dropdown', 'is-mega-panel w-wide', $panelClass).' is-mega-panel w-wide');
        }
      }
    @endphp
    <div class="mega-item {{ $hasPanel ? 'has-mega' : '' }} {{ ($item->is_mega || $forceMega) ? 'is-mega-root' : '' }} {{ $item->css_class }}"
         data-mega-item
         data-open-mode="{{ $openMode }}"
         style="{{ $style }}">
      <a class="mega-trigger" href="{{ $item->href() }}"
         @if($item->open_in_new) target="_blank" rel="noopener" @endif
         @if($hasPanel) aria-haspopup="true" aria-expanded="false" data-mega-trigger @endif>
        @if($showIcons)
          @if($item->icon_image_url)
            <img class="mega-ico-img" src="{{ $item->icon_image_url }}" alt="" loading="lazy">
          @elseif($item->icon)
            <span class="mega-ico" aria-hidden="true">{{ $item->icon }}</span>
          @endif
        @endif
        <span>{{ $item->title }}</span>
        @if($item->badge)<span class="mega-badge">{{ $item->badge }}</span>@endif
        @if($hasPanel)<span class="mega-caret" aria-hidden="true">▾</span>@endif
      </a>

      @if($hasPanel)
        <div class="{{ $panelClass }}"
             data-mega-panel
             style="{{ $style }}@if($item->bg_image_url);--mega-bg:url('{{ $item->bg_image_url }}')@endif">

          @if($item->is_mega && $item->bg_image_url)
            <div class="mega-panel-bg" style="background-image:url('{{ $item->bg_image_url }}')"></div>
          @endif

          @if($item->is_mega && $item->show_search)
            <form class="mega-search" action="{{ url('/products') }}" method="get" role="search">
              <input type="search" name="q" placeholder="{{ $item->search_placeholder ?: 'جستجو در فروشگاه...' }}" autocomplete="off">
              <button type="submit">جستجو</button>
            </form>
          @endif

          @if($item->is_mega && $item->is_tabbed && $item->activeChildren->count())
            <div class="mega-tabs" data-mega-tabs>
              <div class="mega-tab-list" role="tablist">
                @foreach($item->activeChildren as $ti => $tabChild)
                  <button type="button" class="mega-tab-btn {{ $ti===0?'active':'' }}"
                          role="tab" data-tab="{{ $ti }}"
                          aria-selected="{{ $ti===0 ? 'true':'false' }}">
                    @if($showIcons && $tabChild->icon_image_url)
                      <img class="mega-ico-img" src="{{ $tabChild->icon_image_url }}" alt="">
                    @elseif($showIcons && $tabChild->icon)
                      <span>{{ $tabChild->icon }}</span>
                    @endif
                    {{ $tabChild->tab_label ?: $tabChild->title }}
                  </button>
                @endforeach
              </div>
              <div class="mega-tab-panels">
                @foreach($item->activeChildren as $ti => $tabChild)
                  <div class="mega-tab-panel {{ $ti===0?'active':'' }}" data-tab-panel="{{ $ti }}" role="tabpanel">
                    @include('mega-menu::storefront.partials.item-block', ['child' => $tabChild, 'nested' => true, 'showIcons' => $showIcons])
                  </div>
                @endforeach
              </div>
            </div>
          @else
            <div class="{{ $forceMega ? 'mega-shell-pro' : '' }}">
              <div class="mega-grid">
                @foreach($item->activeChildren as $child)
                  @include('mega-menu::storefront.partials.item-block', ['child' => $child, 'nested' => false, 'showIcons' => $showIcons])
                @endforeach
              </div>
              @if($forceMega && !empty($mm['org_promo_enabled']))
                @php
                  $orgPromoImg = trim((string) ($mm['org_promo_image'] ?? ''));
                  if ($orgPromoImg === '') { $orgPromoImg = asset('images/home/mega-promo.jpg'); }
                  elseif (! str_starts_with($orgPromoImg, 'http') && ! str_starts_with($orgPromoImg, '//')) {
                    $orgPromoImg = asset(ltrim($orgPromoImg, '/'));
                  }
                  $orgPromoHref = trim((string) ($mm['org_promo_url'] ?? '/products')) ?: '/products';
                  if (! str_starts_with($orgPromoHref, 'http') && ! str_starts_with($orgPromoHref, '//') && ! str_starts_with($orgPromoHref, '#')) {
                    $orgPromoHref = url($orgPromoHref);
                  }
                @endphp
                <a class="mega-promo mega-promo--designed" href="{{ $orgPromoHref }}">
                  <img src="{{ $orgPromoImg }}" alt="" width="720" height="480" loading="lazy">
                  <div class="mega-promo-body">
                    <strong>{{ $mm['org_promo_title'] ?? 'پیشنهاد سازمانی' }}</strong>
                    @if(!empty($mm['org_promo_desc']))
                      <p>{{ $mm['org_promo_desc'] }}</p>
                    @endif
                    <span>{{ $mm['org_promo_button'] ?? 'مشاهده' }}</span>
                  </div>
                </a>
              @endif
            </div>
          @endif

          @if($item->is_mega && ($item->form_type ?? 'none') !== 'none')
            <div class="mega-form-box">
              @if($item->form_type === 'search')
                <form action="{{ url('/products') }}" method="get" class="mega-inline-form">
                  <strong>جستجوی سریع</strong>
                  <input type="search" name="q" placeholder="{{ $item->search_placeholder ?: 'نام محصول...' }}">
                  <button class="btn btn-primary btn-sm" type="submit">برو</button>
                </form>
              @elseif($item->form_type === 'newsletter')
                <form action="{{ url('/contact') }}" method="get" class="mega-inline-form">
                  <strong>خبرنامه</strong>
                  <input type="email" name="email" placeholder="ایمیل شما">
                  <button class="btn btn-primary btn-sm" type="submit">عضویت</button>
                </form>
              @elseif($item->form_type === 'login')
                <form action="{{ route('login') }}" method="get" class="mega-inline-form">
                  <strong>ورود سریع</strong>
                  <a class="btn btn-primary btn-sm" href="{{ route('login') }}">ورود</a>
                  <a class="btn btn-outline btn-sm" href="{{ route('register') }}">ثبت‌نام</a>
                </form>
              @elseif($item->form_type === 'custom' && $item->form_html)
                <div class="mega-html">{!! \App\Support\SafeHtml::clean($item->form_html) !!}</div>
              @endif
            </div>
          @endif
        </div>
      @endif
    </div>
  @endforeach

  <span class="mega-utils">
    <a class="mega-util" href="{{ route('cart.index') }}">سبد ({{ $cartCount }})</a>
    @auth
      @if(auth()->user()->isAdmin())
        <a class="mega-util" href="{{ route('admin.dashboard') }}">پنل</a>
      @else
        <a class="mega-util" href="{{ route('account.index') }}">حساب من</a>
      @endif
      <form action="{{ route('logout') }}" method="post" style="display:inline">@csrf<button class="btn btn-outline btn-sm" type="submit">خروج</button></form>
    @else
      <a class="mega-util" href="{{ route('login') }}">ورود</a>
      <a class="btn btn-primary btn-sm" href="{{ route('register') }}">ثبت‌نام</a>
    @endauth
  </span>
</nav>
@else
<nav class="nav" dir="rtl">
  <a href="{{ route('home') }}">خانه</a>
  <a href="{{ route('products.index') }}">محصولات</a>
  <a href="{{ route('orders.track') }}">پیگیری سفارش</a>
  <a href="{{ route('about') }}">درباره ما</a>
  <a href="{{ route('contact') }}">تماس</a>
  <a href="{{ route('cart.index') }}">سبد ({{ $cartCount }})</a>
  @auth
    @if(auth()->user()->isAdmin())
      <a href="{{ route('admin.dashboard') }}">پنل</a>
    @else
      <a href="{{ route('account.index') }}">حساب من</a>
    @endif
    <form action="{{ route('logout') }}" method="post" style="display:inline">@csrf<button class="btn btn-outline btn-sm" type="submit">خروج</button></form>
  @else
    <a href="{{ route('login') }}">ورود</a>
    <a class="btn btn-primary btn-sm" href="{{ route('register') }}">ثبت‌نام</a>
  @endauth
</nav>
@endif
