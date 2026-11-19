@php
  $page = $servicePage ?? null;
  if ($page === null && !empty($service['slug'] ?? null)) {
      $page = \App\Support\StudioDesignCopy::servicePage($service['slug']);
  }
  $page = $page ?? [];
  $cfg = $cfg ?? \App\Support\StudioDesignCopy::pageSettings();
  $others = array_values(array_filter(
      \App\Support\StudioDesignCopy::services(),
      static fn (array $row): bool => ($row['slug'] ?? '') !== ($page['slug'] ?? '')
  ));
  $works = [];
  $worksWord = (string) ($page['works_word'] ?? '');
  if (!empty($page['showcase']) && is_array($page['showcase'])) {
      $works = array_values($page['showcase']);
  } elseif ($worksWord !== '' && class_exists(\App\Support\StudioHomeConfig::class)) {
      foreach (\App\Support\StudioHomeConfig::works() as $work) {
          $blob = ($work['title'] ?? '').' '.($work['cat'] ?? '');
          $image = (string) ($work['image'] ?? '');
          if (!str_contains($blob, $worksWord) || (($page['works_exclude'] ?? '') !== '' && str_contains($image, $page['works_exclude']))) {
              continue;
          }
          $works[] = $work;
      }
  }
  $salesTel = '+98'.ltrim(preg_replace('/\D+/', '', (string) $cfg['consultant_phone']) ?? '', '0');
  $officeTel = preg_replace('/\D+/', '', (string) $cfg['office_phone']) ?? '';
  $wa = preg_replace('/\D+/', '', (string) ($page['whatsapp'] ?? $cfg['whatsapp'])) ?? '';
  $faqs = $page['faqs'] ?? [];
  $leadType = match ($page['slug'] ?? '') {
      'corporate' => 'سایت شرکتی',
      'shop' => 'فروشگاه اینترنتی',
      'laravel' => 'سامانهٔ اختصاصی',
      default => 'هنوز نمی‌دانم',
  };
  $typeOptions = $page['type_options'] ?? ['سایت شرکتی', 'فروشگاه اینترنتی', 'سامانهٔ اختصاصی', 'طراحی UI/UX', 'سئو و سرعت', 'پشتیبانی', 'هنوز نمی‌دانم'];
  $typeDefault = $page['type_default'] ?? $leadType;
  $typeLabel = $page['type_label'] ?? 'نوع پروژه';
  $contactKicker = $page['contact_kicker'] ?? 'مشاورهٔ رایگان';
  $contactLead = $page['contact_lead'] ?? 'فرم را بفرستید تا برای هماهنگی با شما تماس بگیریم.';
  $submitLabel = $page['submit_label'] ?? 'ارسال درخواست مشاوره';
  $contactTitle = $page['contact_title'] ?? 'برای شروع، مشاورهٔ رایگان بگیرید';
  $companyLabel = $page['company_label'] ?? 'نام شرکت';
  $faqKicker = $page['faq_kicker'] ?? 'پرسش‌های متداول';
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = ['@type' => 'Question', 'name' => $faq['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']]];
  }
  $url = 'https://hdd-land.ir/design/'.($page['slug'] ?? '');
  $graph = [
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'Service',
              'name' => $page['h1'] ?? '',
              'url' => $url,
              'provider' => ['@type' => 'LocalBusiness', 'name' => 'شرکت فراز نت آمل', 'telephone' => '+981144447220'],
              'areaServed' => ['آمل', 'مازندران'],
          ],
          [
              '@type' => 'BreadcrumbList',
              'itemListElement' => [
                  ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => 'https://hdd-land.ir/'],
                  ['@type' => 'ListItem', 'position' => 2, 'name' => 'طراحی سایت', 'item' => 'https://hdd-land.ir/design'],
                  ['@type' => 'ListItem', 'position' => 3, 'name' => $page['h1'] ?? '', 'item' => $url],
              ],
          ],
      ],
  ];
@endphp
<link rel="stylesheet" href="{{ asset('css/studio-agency.css') }}?v=67">
<section class="ag-page">
  <div class="ag-wrap dg-hero">
    <div>
      <p class="ag-crumb">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <a href="{{ url('/design') }}">طراحی سایت</a>
        <span>/</span>
        <span>{{ $page['h1'] ?? '' }}</span>
      </p>
      <h1>{{ $page['h1'] ?? '' }}</h1>
      <p class="ag-lead">{{ $page['lead'] ?? '' }}</p>
      <div class="ag-actions">
        <a class="ag-btn ag-btn--on" href="#contact">{{ $page['hero_cta'] ?? 'درخواست مشاوره رایگان' }}<i>←</i></a>
        @if($wa !== '')
          <a class="ag-btn ag-btn--line dg-wa" href="https://wa.me/{{ $wa }}" rel="noopener">پیام در واتس‌اپ</a>
        @endif
      </div>
      @if(!empty($page['facts']))
        <div class="ds-facts">
          @foreach($page['facts'] as $fact)
            <div><span>{{ $fact['l'] }}</span><b>{{ $fact['n'] }}</b></div>
          @endforeach
        </div>
      @endif
    </div>
    @if(!empty($page['hero_plain']) && !empty($page['hero_image']))
      <img class="ds-shot-plain" src="{{ asset($page['hero_image']) }}" width="{{ $page['hero_w'] ?? 1600 }}" height="{{ $page['hero_h'] ?? 1000 }}" alt="{{ $page['hero_alt'] ?? '' }}" fetchpriority="high" decoding="async">
    @elseif(in_array(($page['device'] ?? $page['slug'] ?? ''), ['corporate', 'shop', 'laravel'], true))
      <div class="ds-devices{{ !empty($page['hero_image']) ? ' ds-devices--shot' : '' }}">
        <div class="ds-laptop">
          <div class="ds-screen">
          @if(!empty($page['hero_image']))
            <img src="{{ asset($page['hero_image']) }}" width="1280" height="720" alt="{{ $page['hero_alt'] ?? '' }}" fetchpriority="high" decoding="async">
          @else
            <div class="ds-nav"><i></i><i></i></div>
            @if(($page['device'] ?? '') === 'shop')
              <div class="ds-grid"><i></i><i></i><i></i><i></i></div>
            @else
              <div class="ds-hero">سایت شرکتی</div>
              <div class="ds-row"><i></i><i></i><i></i></div>
            @endif
          @endif
          </div>
          <div class="ds-base"></div>
        </div>
        <div class="ds-phone" aria-hidden="true">
          @if(!empty($page['hero_image']))
            <img src="{{ asset($page['hero_image']) }}" width="1280" height="720" alt="">
          @endif
        </div>
      </div>
    @endif
  </div>
</section>

@if(!empty($page['before_after']['before']['image']) && !empty($page['before_after']['after']['image']))
<section class="ag-sec">
  <div class="ag-wrap">
    <p class="ag-kicker">{{ $page['before_after']['kicker'] ?? 'تفاوت را ببینید' }}</p>
    <h2>{{ $page['before_after']['title'] ?? 'قبل و بعد' }}</h2>
    <div class="ds-ba">
      @foreach(['before' => 'قبل', 'after' => 'بعد'] as $key => $label)
        @php $shot = $page['before_after'][$key]; @endphp
        <figure>
          <img src="{{ asset($shot['image']) }}" width="{{ $shot['w'] ?? 1200 }}" height="{{ $shot['h'] ?? 750 }}" alt="{{ $shot['alt'] ?? $label }}" loading="lazy" decoding="async">
          <figcaption><b>{{ $label }}</b>@if(($shot['metric'] ?? '') !== '')<span>{{ $shot['metric'] }}</span>@endif</figcaption>
        </figure>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(!empty($page['journey']['steps']))
<section class="ag-sec">
  <div class="ag-wrap">
    <p class="ag-kicker">{{ $page['journey']['kicker'] }}</p>
    <h2>{{ $page['journey']['title'] }}</h2>
    <ol class="ds-journey">
      @foreach($page['journey']['steps'] as $step)
        <li><b>{{ $step['title'] }}</b><p>{{ $step['text'] }}</p></li>
      @endforeach
    </ol>
  </div>
</section>
@endif

@if(!empty($page['band']['items']))
<section class="ag-sec">
  <div class="ag-wrap ds-band">
    <div>
      <p class="ag-kicker">{{ $page['band']['kicker'] }}</p>
      <h2>{{ $page['band']['title'] }}</h2>
      <ul class="ag-list">
        @foreach($page['band']['items'] as $line)
          <li>{{ $line }}</li>
        @endforeach
      </ul>
    </div>
    <div class="ds-pipe">
      @foreach($page['band']['flow'] as $i => $node)
        @if($i > 0 && ($page['band']['arrow'] ?? '↓') !== '')<em>{{ $page['band']['arrow'] }}</em>@endif
        <div><b>{{ $node[0] }}</b><span>{{ $node[1] }}</span></div>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(!empty($page['pages']))
<section class="ag-sec">
  <div class="ag-wrap">
    <p class="ag-kicker">ساختار سایت شما</p>
    <h2>چه صفحه‌هایی می‌سازیم</h2>
    <p class="ag-lead">این‌ها صفحه‌های سایت خود شماست.</p>
    <div class="ds-4">
      @foreach($page['pages'] as $card)
        <article class="ag-card"><div><b>{{ $card['title'] }}</b><p>{{ $card['text'] }}</p></div></article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(!empty($page['features']))
<section class="ag-sec">
  <div class="ag-wrap">
    <p class="ag-kicker">{{ $page['features_kicker'] ?? 'امکانات' }}</p>
    <h2>{{ $page['features_title'] ?? 'امکانات این خدمت' }}</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($page['features'] as $card)
        <article class="ag-card"><div><b>{{ $card['title'] }}</b><p>{{ $card['text'] }}</p></div></article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(!empty($page['checks']))
<section class="ag-sec">
  <div class="ag-wrap ds-split">
    <div>
      <p class="ag-kicker">{{ $page['checks_kicker'] ?? 'بعد از تحویل' }}</p>
      <h2>{{ $page['checks_title'] ?? 'سایت دست خودتان است' }}</h2>
      <ul class="ag-list">
        @foreach($page['checks'] as $line)
          <li>{{ $line }}</li>
        @endforeach
      </ul>
    </div>
    @if(!empty($page['kit']))
      <div class="ds-kit" aria-label="نمونهٔ دیزاین سیستم">
        <span>رنگ‌ها</span>
        <div class="ds-sw"><i style="background:#7c5cff"></i><i style="background:#b9a8ff"></i><i style="background:#1c1838"></i><i style="background:#2ecc8f"></i><i style="background:#efecfa"></i></div>
        <span>تایپوگرافی</span>
        <div class="ds-typ"><b>تیتر صفحه</b><p>متن اصلی برای خواندن راحت</p><small>توضیح و برچسب</small></div>
        <span>دکمه‌ها</span>
        <div class="ds-btns"><i>اصلی</i><i class="line">ثانویه</i><i class="ok">موفق</i></div>
      </div>
    @elseif(!empty($page['panel_image']))
      <img class="ds-panel-img" src="{{ asset($page['panel_image']) }}" width="{{ $page['panel_w'] ?? 1280 }}" height="{{ $page['panel_h'] ?? 800 }}" alt="{{ $page['panel_alt'] ?? '' }}" loading="lazy" decoding="async">
    @elseif(!empty($page['orders_sample']))
      <div class="ds-panel" aria-label="نمونهٔ فهرست سفارش‌ها">
        <div class="ds-top">پنل فروشگاه · نمونه</div>
        <div class="ds-table">
          <table>
            <thead><tr><th>سفارش</th><th>مشتری</th><th>مبلغ (تومان)</th><th>وضعیت</th></tr></thead>
            <tbody>
              @foreach($page['orders_sample'] as $row)
                <tr><td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td><td>{{ $row[3] }}</td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @else
    <div class="ds-panel" aria-label="نمای ساده از پنل مدیریت">
      <div class="ds-top">پنل مدیریت · ویرایش صفحهٔ «درباره ما»</div>
      <div class="ds-body">
        <div class="ds-side"><span>خدمات</span><b>درباره ما</b><span>نمونه‌کار</span><span>اخبار</span><span>تماس</span></div>
        <div class="ds-main">
          <div>عنوان<i></i></div>
          <div>متن صفحه<i class="tall"></i></div>
          <span>ذخیره</span>
        </div>
      </div>
    </div>
    @endif
  </div>
</section>
@endif

@if($works !== [])
<section class="ag-sec">
  <div class="ag-wrap">
    <p class="ag-kicker">{{ $page['works_kicker'] ?? 'نمونه‌کار' }}</p>
    <h2>{{ $page['works_title'] ?? 'پروژه‌های مرتبط' }}</h2>
    @if(($page['works_lead'] ?? '') !== '')
      <p class="ag-lead">{{ $page['works_lead'] }}</p>
    @endif
    <div class="dg-works">
      @foreach($works as $work)
        <a class="dg-work" href="{{ url($work['url'] ?: '/projects') }}">
          @if(($work['image'] ?? '') !== '')
            <img src="{{ \App\Support\StudioHomeConfig::imageUrl($work['image']) }}" alt="{{ $work['title'] }}" width="1280" height="720" loading="lazy" decoding="async">
          @endif
          <div><b>{{ $work['title'] }}</b>@if(($work['cat'] ?? '') !== '')<span>{{ $work['cat'] }}</span>@endif</div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(!empty($page['package']))
<section class="ag-sec">
  <div class="ag-wrap ds-pkg">
    <div>
      <p class="ag-kicker">{{ $page['package_kicker'] ?? 'بسته' }}</p>
      <h2>{{ $page['package_title'] ?? 'هر آنچه برای شروع لازم است' }}</h2>
      @if(($page['package_price'] ?? '') !== '')
        @if(($page['price_prefix'] ?? '') !== '')
          <p class="dg-price">{{ $page['price_prefix'] }} <b>{{ $page['package_price'] }}</b> تومان</p>
        @else
          <p class="dg-price">شروع از <b>{{ $page['package_price'] }}</b> تومان</p>
        @endif
      @else
        <p class="dg-price">{{ $page['price_note'] ?? 'قیمت دقیق بعد از مشاوره' }}</p>
      @endif
      @if(($page['package_lead'] ?? '') !== '')
        <p class="ag-lead">{{ $page['package_lead'] }}</p>
      @endif
      <a class="ag-btn ag-btn--on" href="#contact">{{ $page['package_button'] ?? 'دریافت قیمت دقیق' }}</a>
    </div>
    <ul>
      @foreach($page['package'] as $line)
        <li>{{ $line }}</li>
      @endforeach
    </ul>
  </div>
</section>
@endif

@if($faqs !== [])
<section class="ag-sec" id="faq">
  <div class="ag-wrap">
    <p class="ag-kicker">{{ $faqKicker }}</p>
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
@endif

<section class="ag-sec" id="contact">
  <div class="ag-wrap dg-contact">
    <div>
      <p class="ag-kicker">{{ $contactKicker }}</p>
      <h2>{{ $contactTitle }}</h2>
      <p class="ag-lead">{{ $contactLead }}</p>
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
      <input type="hidden" name="back" value="{{ url('/design/'.($page['slug'] ?? '')) }}">
      <div class="dg-hp" aria-hidden="true"><input name="dg_hp" tabindex="-1" autocomplete="off" value=""></div>
      <label>نام و نام خانوادگی<input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
      <label>شمارهٔ موبایل<input name="phone" value="{{ old('phone') }}" required inputmode="tel" dir="ltr" autocomplete="tel"></label>
      @if(empty($page['hide_company']))
        <label>{{ $companyLabel }}<input name="company" value="{{ old('company') }}" maxlength="120"></label>
      @endif
      @if(!empty($page['type_before']))
        <label>{{ $typeLabel }}
          <select name="type" required>
            @foreach($typeOptions as $type)
              <option value="{{ $type }}" @selected(old('type', $typeDefault) === $type)>{{ $type }}</option>
            @endforeach
          </select>
        </label>
      @endif
      @foreach(($page['fields'] ?? []) as $field)
        <label class="{{ !empty($field['full']) || count($field['options'] ?? []) > 2 ? 'dg-full' : '' }}">{{ $field['label'] }}
          @if(($field['type'] ?? 'select') === 'select')
            <select name="{{ $field['name'] }}">
              <option value="" @selected(old($field['name'], '') === '')>انتخاب کنید</option>
              @foreach(($field['options'] ?? []) as $opt)
                <option value="{{ $opt }}" @selected(old($field['name']) === $opt)>{{ $opt }}</option>
              @endforeach
            </select>
          @else
            <input name="{{ $field['name'] }}" value="{{ old($field['name']) }}" @if(($field['type'] ?? '') === 'url') type="url" inputmode="url" dir="ltr" placeholder="https://" @endif maxlength="300">
          @endif
        </label>
      @endforeach
      @if(empty($page['type_before']))
        <label>{{ $typeLabel }}
          <select name="type" required>
            @foreach($typeOptions as $type)
              <option value="{{ $type }}" @selected(old('type', $typeDefault) === $type)>{{ $type }}</option>
            @endforeach
          </select>
        </label>
      @endif
      <label class="dg-full">توضیح کوتاه (اختیاری)<textarea name="message" maxlength="2000">{{ old('message') }}</textarea></label>
      <button class="ag-btn ag-btn--on dg-full" type="submit">{{ $submitLabel }}</button>
      @if(session('design_lead'))<p class="dg-ok">درخواست ثبت شد. برای هماهنگی با شما تماس می‌گیریم.</p>@endif
      @if($errors->any())<p class="dg-err">{{ $errors->first() }}</p>@endif
    </form>
  </div>
</section>

@if($others !== [])
<section class="ag-sec ag-sec--last">
  <div class="ag-wrap">
    <h2>خدمات دیگر طراحی</h2>
    <div class="ds-others">
      @foreach($others as $row)
        <a href="{{ url('/design/'.$row['slug']) }}">{{ $row['title'] }}</a>
      @endforeach
    </div>
  </div>
</section>
@endif
<script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@if($faqLd['mainEntity'] !== [])
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
