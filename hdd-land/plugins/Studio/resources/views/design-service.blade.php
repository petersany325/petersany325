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
  if ($worksWord !== '' && class_exists(\App\Support\StudioHomeConfig::class)) {
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
  $contactTitle = $page['contact_title'] ?? (($page['slug'] ?? '') === 'corporate'
      ? 'آماده‌اید سایت شرکتی‌تان را بسازیم؟'
      : 'آماده‌اید «'.($page['h1'] ?? 'این خدمت').'» را شروع کنیم؟');
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
<link rel="stylesheet" href="{{ asset('css/studio-agency.css') }}?v=64">
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
        <a class="ag-btn ag-btn--on" href="#contact">درخواست مشاوره رایگان<i>←</i></a>
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
    @if(in_array(($page['device'] ?? $page['slug'] ?? ''), ['corporate', 'shop'], true))
      <div class="ds-devices" aria-hidden="true">
        <div class="ds-laptop">
          <div class="ds-screen">
            <div class="ds-nav"><i></i><i></i></div>
          @if(($page['device'] ?? '') === 'shop')
            <div class="ds-grid"><i></i><i></i><i></i><i></i></div>
          @else
            <div class="ds-hero">سایت شرکتی</div>
            <div class="ds-row"><i></i><i></i><i></i></div>
          @endif
          </div>
          <div class="ds-base"></div>
        </div>
        <div class="ds-phone"></div>
      </div>
    @endif
  </div>
</section>

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
        @if($i > 0)<em>↓</em>@endif
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
    @if(!empty($page['orders_sample']))
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
    <p class="ag-kicker">{{ ($page['slug'] ?? '') === 'shop' ? 'فروشگاه‌هایی که ساخته‌ایم' : 'سایت‌هایی که ساخته‌ایم' }}</p>
    <h2>{{ ($page['slug'] ?? '') === 'shop' ? 'نمونه‌کار فروشگاه اینترنتی' : 'نمونه‌کار' }}</h2>
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
        <p class="dg-price">شروع از <b>{{ $page['package_price'] }}</b> تومان</p>
      @else
        <p class="dg-price">قیمت دقیق بعد از مشاوره</p>
      @endif
      <a class="ag-btn ag-btn--on" href="#contact">دریافت قیمت دقیق</a>
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
      <p class="ag-kicker">مشاورهٔ رایگان</p>
      <h2>{{ $contactTitle }}</h2>
      <p class="ag-lead">فرم را بفرستید تا برای هماهنگی با شما تماس بگیریم.</p>
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
      <label>{{ $companyLabel }}<input name="company" value="{{ old('company') }}" maxlength="120"></label>
      @foreach(($page['fields'] ?? []) as $field)
        <label class="{{ count($field['options'] ?? []) > 2 ? 'dg-full' : '' }}">{{ $field['label'] }}
          <select name="{{ $field['name'] }}">
            @foreach($field['options'] as $opt)
              <option @selected(old($field['name']) === $opt)>{{ $opt }}</option>
            @endforeach
          </select>
        </label>
      @endforeach
      <label>نوع پروژه
        <select name="type" required>
          @foreach(['سایت شرکتی', 'فروشگاه اینترنتی', 'سامانهٔ اختصاصی', 'هنوز نمی‌دانم'] as $type)
            <option @selected(old('type', $leadType) === $type)>{{ $type }}</option>
          @endforeach
        </select>
      </label>
      <label class="dg-full">توضیح کوتاه (اختیاری)<textarea name="message" maxlength="2000">{{ old('message') }}</textarea></label>
      <button class="ag-btn ag-btn--on dg-full" type="submit">ارسال درخواست مشاوره</button>
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
