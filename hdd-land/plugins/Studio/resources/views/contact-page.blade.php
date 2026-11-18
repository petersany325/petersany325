@php
  $faqs = [
      ['q' => 'دموی حسابداری HDD Land را می‌شود دید؟', 'a' => 'بله. با مشاور فروش، مهندس رضانژاد، تماس بگیرید تا نسخهٔ صنف شما را نشانتان بدهد.'],
      ['q' => 'دستیار هوشمند به اینترنت نیاز دارد؟', 'a' => 'نه. دستیار روی کامپیوتر خودتان کار می‌کند و هیچ ثبتی را بدون تأیید شما انجام نمی‌دهد.'],
  ];
  $topics = [
      ['t' => 'بازیابی اطلاعات', 'e' => '💽', 's' => 'هارد، SSD، فلش، RAID'],
      ['t' => 'حسابداری و نرم‌افزار', 'e' => '🧮', 's' => 'دمو، خرید، نسخهٔ صنفی'],
      ['t' => 'طراحی سایت', 'e' => '🌐', 's' => 'شرکتی، فروشگاهی، اختصاصی'],
      ['t' => 'هوش مصنوعی', 'e' => '🤖', 's' => 'دستیار فارسی آفلاین'],
      ['t' => 'پشتیبانی مشتریان', 'e' => '🛟', 's' => 'برای مشتریان فعلی'],
  ];
  $graph = [
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'LocalBusiness',
              'name' => 'شرکت فراز نت آمل',
              'alternateName' => ['سرزمین هارد', 'HDD Land'],
              'url' => 'https://hdd-land.ir/contact',
              'telephone' => '+981144447220',
              'contactPoint' => [
                  ['@type' => 'ContactPoint', 'telephone' => '+989113100473', 'contactType' => 'sales', 'name' => 'مهندس رضانژاد'],
                  ['@type' => 'ContactPoint', 'telephone' => '+981144447220', 'contactType' => 'customer support'],
              ],
          ],
          [
              '@type' => 'FAQPage',
              'mainEntity' => array_map(fn ($row) => [
                  '@type' => 'Question',
                  'name' => $row['q'],
                  'acceptedAnswer' => ['@type' => 'Answer', 'text' => $row['a']],
              ], $faqs),
          ],
      ],
  ];
@endphp
<link rel="stylesheet" href="{{ asset('css/studio-agency.css') }}?v=57">
<div class="ct">
  <div class="ct-wrap">
    <section class="ct-hero">
      <div>
        <div class="ct-kick">تماس با ما · شرکت فراز نت آمل (سرزمین هارد)</div>
        <h1>چطور کمکتان کنیم؟</h1>
        <p>بازیابی اطلاعات، حسابداری HDD Land، طراحی سایت یا دستیار هوشمند؛ موضوع را انتخاب کنید یا مستقیم با مشاور فروش حرف بزنید.</p>
      </div>
      <div class="ct-bot">
        <img src="{{ asset('images/ai/r-hello.webp') }}" width="92" height="92" alt="دستیار HDD Land" fetchpriority="high" decoding="async">
        <div class="ct-bubble" id="ctBubble">سلام! چه کمکی از ما برمی‌آید؟</div>
      </div>
    </section>

    <section class="ct-tight">
      <div class="ct-quick">
        <div class="ct-qc sales">
          <span class="ct-lbl">مشاور فروش</span>
          <span class="ct-who">مهندس رضانژاد</span>
          <a class="ct-ph" dir="ltr" href="tel:09113100473">۰۹۱۱ ۳۱۰ ۰۴۷۳</a>
          <span class="ct-muted">خرید نرم‌افزار، دموی حسابداری، طراحی سایت و قیمت</span>
          <div class="ct-acts"><a class="ct-b p" href="tel:09113100473">تماس</a></div>
        </div>
        <div class="ct-qc">
          <span class="ct-lbl">دفتر و آزمایشگاه</span>
          <a class="ct-ph" dir="ltr" href="tel:01144447220">۰۱۱ ۴۴۴۴ ۷۲۲۰</a>
          <span class="ct-muted">بازیابی اطلاعات، پشتیبانی و پیگیری</span>
          <div class="ct-acts"><a class="ct-b" href="tel:01144447220">تماس با دفتر</a></div>
        </div>
      </div>
    </section>

    <section class="ct-tight" id="ctTopics">
      <h2>موضوع را انتخاب کنید</h2>
      <div class="ct-topics">
        @foreach($topics as $topic)
          <button type="button" class="ct-tp" data-t="{{ $topic['t'] }}"><span class="ct-e">{{ $topic['e'] }}</span><b>{{ $topic['t'] }}</b><small>{{ $topic['s'] }}</small></button>
        @endforeach
      </div>
      <div class="ct-warn" id="ctWarn">اگر دستگاه صدای غیرعادی می‌دهد، آن را خاموش کنید و دوباره روشن نکنید. خودتان باز یا تعمیرش نکنید؛ این کار احتمال بازیابی را کم می‌کند.</div>
      <div class="ct-sup" id="ctSup">مشتری فعلی هستید؟ سریع‌ترین راه، <a href="{{ url('/account/tickets') }}">ثبت تیکت در کارتابل مشتری</a> است. برای بقیهٔ موضوع‌ها با مشاور فروش تماس بگیرید.</div>
    </section>

    <section class="ct-tight">
      <h2>پرسش‌های رایج</h2>
      @foreach($faqs as $faq)
        <details class="ct-faq"><summary>{{ $faq['q'] }}</summary><p>{{ $faq['a'] }}</p></details>
      @endforeach
    </section>
  </div>
</div>
<script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@include('studio::contact-script')
