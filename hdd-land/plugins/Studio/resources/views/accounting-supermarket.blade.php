@php
  $tabs = \App\Support\StudioComputerMenu::supermarketTabs();
  $faqs = \App\Support\StudioComputerMenu::supermarketFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $day = [
      ['n' => '۱', 'title' => 'باز کردن شیفت', 'text' => 'صندوق‌دار موجودی اول کشو را می‌نویسد و شیفت باز می‌شود.'],
      ['n' => '۲', 'title' => 'فروش', 'text' => 'کالا با بارکدخوان یا بارکد ترازو اسکن می‌شود؛ پرداخت نقد، کارت یا کارت هدیه و باقی پول روی صفحه.'],
      ['n' => '۳', 'title' => 'مشتری بعدی', 'text' => 'سبد مشتری‌ای که چیزی جا گذاشته معلق می‌شود و صف معطل نمی‌ماند.'],
      ['n' => '۴', 'title' => 'بستن شیفت و گزارش Z', 'text' => 'شمارش کشو، و گزارش فروش، برگشت، مالیات، نقد و کارت با کسری یا اضافهٔ صندوق.'],
  ];
  $hardware = ['بارکدخوان', 'ترازوی بارکددار', 'چاپگر فیش ۸ سانتی', 'کشوی پول', 'مانیتور دوم مشتری', 'نمایشگر پایه‌دار VFD', 'کارتخوان'];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>سوپرمارکت</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ سوپرمارکت</p>
    <h1>نرم‌افزار حسابداری سوپرمارکت؛ صندوق سریع، حتی بدون شبکه</h1>
    <p class="ag-lead ag-lead--wide">فروش با بارکد و ترازو، نمایشگر مشتری و کشوی پول، باشگاه مشتریان و امتیاز، شیفت و گزارش Z صندوق‌دار، موجودی لحظه‌ای و حسابداری کامل؛ اگر شبکه قطع شود، صندوق فروش را ادامه می‌دهد.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی صندوق سوپرمارکت<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">صندوق بدون اتصال · بارکد ترازو · باشگاه مشتریان</p>
    <figure class="ag-shot-ph"><span>صندوق فروش سوپرمارکت در نرم‌افزار حساب HDD</span></figure>
  </div>
</section>

<section class="ag-sec" id="day">
  <div class="ag-wrap">
    <h2>یک روز پشت صندوق، با حساب HDD</h2>
    <ol class="ag-steps ag-day">
      @foreach($day as $step)
        <li>
          <small>{{ $step['n'] }}</small>
          <b>{{ $step['title'] }}</b>
          <p>{{ $step['text'] }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>

<section class="ag-sec ag-offline" id="offline">
  <div class="ag-wrap">
    <h2>شبکه قطع شد؟ صف شما نمی‌ایستد</h2>
    <p class="ag-lead ag-lead--wide">اگر شبکه یا کامپیوتر سرور قطع شود، صندوق از نسخهٔ محلی خودش فروش را ادامه می‌دهد. قیمت، تخفیف، مالیات و بارکد ترازو مثل همیشه حساب می‌شوند و پرداخت نقد و کارت پذیرفته می‌شود. وقتی ارتباط برگشت، رسیدها با همان تاریخ و ساعت خودکار ثبت می‌شوند و شمارهٔ فاکتور واقعی می‌گیرند.</p>
    <ul class="ag-list">
      <li>هر نیم دقیقه خودش اتصال را امتحان می‌کند</li>
      <li>رسیدی که سرور نپذیرد برای بررسی کنار گذاشته می‌شود، گم نمی‌شود</li>
      <li>تا همهٔ رسیدهای شیفت ثبت نشده، شیفت بسته نمی‌شود</li>
    </ul>
    <figure class="ag-shot-ph"><span>فروش سوپرمارکت بدون اتصال به شبکه</span></figure>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="market-tab" id="market-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="market-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          <ul class="acc-cols">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @if(!empty($tab['alt']))
            <figure class="ag-shot-ph"><span>{{ $tab['alt'] }}</span></figure>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="hardware">
  <div class="ag-wrap">
    <h2>سخت‌افزارهای پشتیبانی‌شده</h2>
    <div class="ag-hw">
      @foreach($hardware as $item)
        <div><b>{{ $item }}</b></div>
      @endforeach
    </div>
    <p class="ag-note">[[فهرست مدل‌ها یا برندهایی که آزموده‌اید، اگر می‌خواهید اعلام کنید]]</p>
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیاری که فروشگاه شما را می‌شناسد</h2>
    <ul class="ag-list">
      <li>بپرسید «امروز کدام کالا بیشتر فروش رفت؟» یا «سود این هفته چقدر بود؟»؛ با نوشتن یا صدای فارسی</li>
      <li>تحلیل فروش و مشتریان، داشبورد سلامت مالی</li>
      <li>اسکن هوشمند فاکتور خرید (OCR)</li>
      <li>همه روی کامپیوتر خودتان، بدون فرستادن اطلاعات به اینترنت</li>
    </ul>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/ai') }}">بیشتر دربارهٔ هوش مصنوعی</a>
    </div>
  </div>
</section>

<section class="ag-sec" id="faq">
  <div class="ag-wrap">
    <h2>پرسش‌های رایج</h2>
    <ul class="ag-steps" style="grid-template-columns:1fr">
      @foreach($faqs as $row)
        <li><b>{{ $row['q'] }}</b><p>{{ $row['a'] }}</p></li>
      @endforeach
    </ul>
  </div>
</section>
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

<section class="ag-sec ag-sec--last">
  <div class="ag-wrap">
    <h2>صندوق را با کالاهای فروشگاه خودتان امتحان کنید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
  </div>
</section>
