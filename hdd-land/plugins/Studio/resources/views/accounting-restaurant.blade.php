@php
  $tabs = \App\Support\StudioComputerMenu::restaurantTabs();
  $faqs = \App\Support\StudioComputerMenu::restaurantFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $path = [
      ['n' => '۱', 'title' => 'سفارش', 'text' => 'گارسون با گوشی یا صفحهٔ لمسی سفارش میز را می‌گیرد؛ یا مشتری با اسکن QR میز سفارش می‌دهد و کارکنان تأیید می‌کنند.'],
      ['n' => '۲', 'title' => 'آشپزخانه', 'text' => 'سفارش به صف آشپزخانه و ایستگاه همان غذا می‌رود و فیش آشپزخانه چاپ می‌شود.'],
      ['n' => '۳', 'title' => 'آماده و سرو', 'text' => 'آشپز «آماده» را می‌زند؛ سفارشی که بیش از ۲۰ دقیقه مانده قرمز می‌شود.'],
      ['n' => '۴', 'title' => 'تسویه', 'text' => 'حق سرویس و پیک خودکار حساب می‌شود؛ نقد، کارتخوان یا نسیه؛ فاکتور فروش صادر می‌شود.'],
      ['n' => '۵', 'title' => 'انبار و سود', 'text' => 'مواد هر غذا طبق دستور پخت از انبار کم و به بهای تمام‌شده می‌نشیند.'],
  ];
  $kinds = [
      ['title' => 'سالن', 'text' => 'سفارش روی میز، با گارسون و تعداد نفرات؛ حق سرویس خودکار.'],
      ['title' => 'بیرون‌بر', 'text' => 'سفارش سریع پیشخوان بدون میز.'],
      ['title' => 'دلیوری', 'text' => 'نام، تلفن و نشانی مشتری، انتخاب پیک، هزینهٔ پیک و تسویه با پیک.'],
  ];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>رستوران</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ رستوران</p>
    <h1>نرم‌افزار رستوران؛ سالن، آشپزخانه و صندوق در یک برنامه</h1>
    <p class="ag-lead ag-lead--wide">نمای سالن و میز، سفارش لمسی و سفارش گارسون با گوشی، منوی QR روی میز، صف آشپزخانه با هشدار تأخیر، رزرو، دلیوری و پیک، تأیید صندوق روزانه؛ و بهای تمام‌شدهٔ هر پرس از دستور پخت، با حسابداری کامل و سامانهٔ مؤدیان.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نرم‌افزار رستوران<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">سفارش گارسون با گوشی · منوی QR میز · بهای هر پرس</p>
    @include('studio::accounting-restaurant-demos', ['n' => 1])
  </div>
</section>

<section class="ag-sec" id="path">
  <div class="ag-wrap">
    <h2>مسیر یک سفارش</h2>
    <ol class="ag-steps ag-path">
      @foreach($path as $step)
        <li>
          <small>{{ $step['n'] }}</small>
          <b>{{ $step['title'] }}</b>
          <p>{{ $step['text'] }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>

<section class="ag-sec" id="kinds">
  <div class="ag-wrap">
    <h2>سه نوع سفارش</h2>
    <div class="ag-roles">
      @foreach($kinds as $kind)
        <article>
          <h3>{{ $kind['title'] }}</h3>
          <p>{{ $kind['text'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="rest-tab" id="rest-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="rest-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          <ul class="acc-cols">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @if(($tab['alt'] ?? '') === 'نمای سالن و میزهای رستوران')
            @include('studio::accounting-restaurant-demos', ['n' => 2])
          @elseif(($tab['alt'] ?? '') === 'سفارش گارسون با گوشی')
            @include('studio::accounting-restaurant-demos', ['n' => 3])
          @elseif(($tab['alt'] ?? '') === 'منوی QR میز رستوران')
            @include('studio::accounting-restaurant-demos', ['n' => 4])
          @elseif(($tab['alt'] ?? '') === 'صف سفارش‌های آشپزخانه')
            @include('studio::accounting-restaurant-demos', ['n' => 5])
          @elseif(!empty($tab['alt']))
            <figure class="ag-shot-ph"><span>{{ $tab['alt'] }}</span></figure>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec ag-offline" id="cost">
  <div class="ag-wrap">
    <h2>بدانید هر غذا واقعاً چقدر سود می‌دهد</h2>
    <p class="ag-lead ag-lead--wide">برای هر غذا یک بار دستور پخت می‌نویسید. از آن به بعد، با تسویهٔ هر سفارش مواد همان غذا از انبار کم می‌شود و بهای تمام‌شده‌اش ثبت می‌شود. گزارش‌ها نشان می‌دهند کدام غذا پرفروش است، کدام کم‌سود، و کدام ماده رو به تمام شدن است.</p>
    <div class="ag-marks">
      <div>بهای هر پرس</div>
      <div>سود هر غذا</div>
      <div>موجودی مواد</div>
    </div>
    @include('studio::accounting-restaurant-demos', ['n' => 6])
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیاری که آشپزخانه و صندوق را می‌شناسد</h2>
    <ul class="ag-list">
      <li>«پرفروش‌ترین غذای این هفته؟»، «فروش امشب چقدر بود؟» را با نوشتن یا صدای فارسی بپرسید</li>
      <li>تحلیل سود غذاها، تحلیل فروش، داشبورد سلامت مالی</li>
      <li>اسکن هوشمند فاکتور خرید مواد (OCR)</li>
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
    <h2>سالن رستوران خودتان را در حساب HDD ببینید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
    <p class="ag-note">کافی‌شاپ دارید؟ <a href="{{ url('/computer-accounting/cafe') }}">نسخهٔ کافی‌شاپ</a></p>
  </div>
</section>
@include('studio::accounting-restaurant-demo-script')
