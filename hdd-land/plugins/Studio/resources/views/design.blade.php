@php
  $hero = $hero ?? \App\Support\StudioDesignCopy::hero();
  $services = $services ?? \App\Support\StudioDesignCopy::services();
  $reasons = $reasons ?? \App\Support\StudioDesignCopy::reasons();
  $steps = $steps ?? \App\Support\StudioDesignCopy::steps();
  $faqs = $faqs ?? \App\Support\StudioDesignCopy::faqs();
  $cfg = $cfg ?? \App\Support\StudioDesignCopy::pageSettings();
  $works = $works ?? (class_exists(\App\Support\StudioHomeConfig::class) ? \App\Support\StudioHomeConfig::works() : []);
  $works = array_slice($works, 0, 6);
  $icons = [
      'corporate' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/></svg>',
      'shop' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16l-2 9H6z"/><circle cx="9" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg>',
      'laravel' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 7l-5 5 5 5M16 7l5 5-5 5"/></svg>',
      'ui-ux' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>',
      'seo' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6"/><path d="M20 20l-4-4"/></svg>',
      'support' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"/></svg>',
  ];
  $salesTel = '+98'.ltrim(preg_replace('/\D+/', '', (string) $cfg['consultant_phone']) ?? '', '0');
  $officeTel = preg_replace('/\D+/', '', (string) $cfg['office_phone']) ?? '';
  $wa = preg_replace('/\D+/', '', (string) $cfg['whatsapp']) ?? '';
  $stats = array_values(array_filter([
      ['n' => $cfg['years'] ?? '', 'l' => 'سال سابقه'],
      ['n' => $cfg['projects'] ?? '', 'l' => 'پروژهٔ تحویل‌شده'],
      ['n' => $cfg['renew'] ?? '', 'l' => 'تمدید پشتیبانی'],
  ], static fn (array $row): bool => $row['n'] !== ''));
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = ['@type' => 'Question', 'name' => $faq['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']]];
  }
  $bizLd = [
      '@context' => 'https://schema.org',
      '@type' => 'LocalBusiness',
      'name' => 'شرکت فراز نت آمل',
      'url' => 'https://hdd-land.ir/design',
      'telephone' => '+981144447220',
      'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'آمل', 'addressCountry' => 'IR'],
  ];
@endphp
<link rel="stylesheet" href="{{ asset('css/studio-agency.css') }}?v=68">
<section class="ag-page">
  <div class="ag-wrap dg-hero">
    <div>
      <p class="ag-kicker">طراحی سایت در آمل و مازندران</p>
      <h1>سایتی که کسب‌وکار شما را <em>بزرگ‌تر</em> نشان می‌دهد</h1>
      <p class="ag-lead">بیش از ۱۵ سال است سایت‌های شرکتی، فروشگاه اینترنتی و سامانه‌های اختصاصی را با لاراول می‌نویسیم؛ سریع، امن و وصل به حسابداری HDD Land.</p>
      <div class="ag-actions">
        <a class="ag-btn ag-btn--on" href="#contact">درخواست مشاوره رایگان<i>←</i></a>
        <a class="ag-btn ag-btn--line" href="{{ url('/projects') }}">دیدن نمونه‌کارها</a>
        @if($wa !== '')
          <a class="ag-btn ag-btn--line dg-wa" href="https://wa.me/{{ $wa }}" rel="noopener">پیام در واتس‌اپ</a>
        @endif
      </div>
      @if($stats !== [])
        <div class="dg-stats">
          @foreach($stats as $st)
            <div><b>{{ $st['n'] }}</b><span>{{ $st['l'] }}</span></div>
          @endforeach
        </div>
      @endif
    </div>
    <img class="ds-shot-plain" src="{{ asset($hero['image']) }}" width="{{ $hero['image_w'] }}" height="{{ $hero['image_h'] }}" alt="{{ $hero['image_alt'] }}" fetchpriority="high" decoding="async">
  </div>
</section>

<section class="ag-sec" id="services">
  <div class="ag-wrap">
    <p class="ag-kicker">چه کاری برایتان انجام می‌دهیم</p>
    <h2>خدمات طراحی</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($services as $row)
        <a class="ag-card" href="{{ url('/design/'.$row['slug']) }}">
          <span class="dg-ico" aria-hidden="true">{!! $icons[$row['slug']] ?? '' !!}</span>
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['short'] }}</p>
            <small>جزئیات ←</small>
          </div>
        </a>
      @endforeach
    </div>
    <div class="dg-acc">
      <div>
        <p class="ag-kicker">وصل به دفتر شما</p>
        <h2>سایت و فروشگاه مستقیم به حسابداری HDD Land وصل می‌شود</h2>
        <ul class="ag-list">
          <li>سفارش آنلاین می‌تواند فاکتور فروش شود</li>
          <li>موجودی کالا در سایت با انبار یکی می‌ماند</li>
          <li>قیمت‌ها از دفتر به‌روز می‌شوند، بدون ورود دوباره</li>
        </ul>
      </div>
      <div class="dg-flow" aria-label="مسیر داده">
        <span>سفارش در سایت</span><em>←</em><span>فاکتور در HDD Land</span><em>←</em><span>انبار و گزارش</span>
      </div>
    </div>
  </div>
</section>

@if($works !== [])
<section class="ag-sec" id="work">
  <div class="ag-wrap">
    <p class="ag-kicker">کارهایی که تحویل داده‌ایم</p>
    <h2>نمونه‌کارها</h2>
    <div class="dg-works">
      @foreach($works as $work)
        <a class="dg-work" href="{{ url($work['url'] ?: '/projects') }}">
          @if(($work['image'] ?? '') !== '')
            <img src="{{ \App\Support\StudioHomeConfig::imageUrl($work['image']) }}" alt="{{ $work['title'] }}" width="1280" height="720" loading="lazy" decoding="async">
          @endif
          <div>
            <b>{{ $work['title'] }}</b>
            @if(($work['cat'] ?? '') !== '')<span>{{ $work['cat'] }}</span>@endif
          </div>
        </a>
      @endforeach
    </div>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/projects') }}">همهٔ نمونه‌کارها<i>←</i></a>
    </div>
  </div>
</section>
@endif

<section class="ag-sec" id="why">
  <div class="ag-wrap">
    <h2>چرا فراز نت آمل؟</h2>
    <ul class="dg-why">
      @foreach($reasons as $row)
        <li>{{ $row }}</li>
      @endforeach
    </ul>
  </div>
</section>

<section class="ag-sec" id="packages">
  <div class="ag-wrap">
    <p class="ag-kicker">برای تصمیم راحت‌تر</p>
    <h2>بسته‌های طراحی</h2>
    <p class="ag-lead">قیمت نهایی بعد از جلسهٔ مشاوره و بر اساس امکانات مشخص می‌شود.</p>
    <div class="dg-pkgs">
      <article class="dg-pkg">
        <h3>پایه</h3>
        <p class="dg-price">@if(($cfg['price_base'] ?? '') !== '')شروع از <b>{{ $cfg['price_base'] }}</b> تومان@else قیمت بعد از مشاوره@endif</p>
        <ul><li>سایت معرفی</li><li>پنل مدیریت محتوا</li><li>سئوی پایه</li></ul>
        <a class="ag-btn ag-btn--line" href="#contact">مشاوره</a>
      </article>
      <article class="dg-pkg dg-pkg--on">
        <span>پرطرفدار</span>
        <h3>فروشگاهی</h3>
        <p class="dg-price">@if(($cfg['price_shop'] ?? '') !== '')شروع از <b>{{ $cfg['price_shop'] }}</b> تومان@else قیمت بعد از مشاوره@endif</p>
        <ul><li>فروشگاه با درگاه پرداخت</li><li>مدیریت سفارش و انبار</li><li>اتصال به حسابداری HDD Land</li></ul>
        <a class="ag-btn ag-btn--on" href="#contact">مشاوره</a>
      </article>
      <article class="dg-pkg">
        <h3>اختصاصی</h3>
        <p class="dg-price">بر اساس <b>نیاز پروژه</b></p>
        <ul><li>سامانهٔ سفارشی با لاراول</li><li>طراحی رابط و تجربهٔ کاربری</li><li>اتصال به سرویس‌های دیگر</li><li>قرارداد پشتیبانی</li></ul>
        <a class="ag-btn ag-btn--line" href="#contact">مشاوره</a>
      </article>
    </div>
  </div>
</section>

<section class="ag-sec" id="steps">
  <div class="ag-wrap">
    <p class="ag-kicker">از اولین تماس تا بعد از تحویل</p>
    <h2>مراحل همکاری</h2>
    <ol class="ag-steps">
      @foreach($steps as $row)
        <li>
          <small>{{ $row['num'] }}</small>
          <b>{{ $row['title'] }}</b>
          <p>{{ $row['text'] }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>

<section class="ag-sec" id="faq">
  <div class="ag-wrap">
    <p class="ag-kicker">قبل از تماس بخوانید</p>
    <h2>پرسش‌های متداول</h2>
    <div class="dg-faq">
      @foreach($faqs as $row)
        <details>
          <summary>{{ $row['q'] }}</summary>
          <p>{{ $row['a'] }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec ag-sec--last" id="contact">
  <div class="ag-wrap dg-contact">
    <div>
      <p class="ag-kicker">مشاورهٔ رایگان</p>
      <h2>آماده‌اید سایت کسب‌وکارتان را بسازیم؟</h2>
      <p class="ag-lead">فرم را بفرستید تا برای هماهنگی دمو تماس بگیریم.</p>
      <div class="dg-consult">
        <div>
          <small>مشاور فروش</small>
          <b>{{ $cfg['consultant_name'] }}</b>
          <a dir="ltr" href="tel:{{ $salesTel }}">{{ $cfg['consultant_phone'] }}</a>
        </div>
      </div>
      <p class="dg-office">تلفن شرکت <a dir="ltr" href="tel:{{ $officeTel }}">{{ $cfg['office_phone'] }}</a></p>
      @if($wa !== '')
        <a class="ag-btn ag-btn--line dg-wa" href="https://wa.me/{{ $wa }}" rel="noopener">پیام در واتس‌اپ به مشاور فروش</a>
      @endif
    </div>
    <form method="post" action="{{ url('/design/lead') }}">
      @csrf
      <div class="dg-hp" aria-hidden="true"><input name="dg_hp" tabindex="-1" autocomplete="off" value=""></div>
      <label>نام و نام خانوادگی<input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
      <label>شمارهٔ موبایل<input name="phone" value="{{ old('phone') }}" required inputmode="tel" dir="ltr" autocomplete="tel" placeholder="09xxxxxxxxx"></label>
      <label class="dg-full">نوع پروژه
        <select name="type" required>
          @foreach(['سایت شرکتی', 'فروشگاه اینترنتی', 'سامانهٔ اختصاصی', 'هنوز نمی‌دانم'] as $type)
            <option @selected(old('type') === $type)>{{ $type }}</option>
          @endforeach
        </select>
      </label>
      <label class="dg-full">توضیح کوتاه (اختیاری)<textarea name="message" maxlength="2000">{{ old('message') }}</textarea></label>
      <button class="ag-btn ag-btn--on dg-full" type="submit">ارسال درخواست مشاوره</button>
      @if(session('design_lead'))
        <p class="dg-ok">درخواست ثبت شد. برای هماهنگی با شما تماس می‌گیریم.</p>
      @endif
      @if($errors->any())
        <p class="dg-err">{{ $errors->first() }}</p>
      @endif
    </form>
  </div>
</section>
<script type="application/ld+json">{!! json_encode($bizLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
