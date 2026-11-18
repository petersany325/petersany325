@php
  $tabs = \App\Support\StudioComputerMenu::cafeTabs();
  $faqs = \App\Support\StudioComputerMenu::cafeFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $latte = [
      ['n' => '۱', 'title' => 'سفارش', 'text' => '«لاته (متوسط)» با شات اضافه و شیر جو دوسر روی صفحهٔ لمسی؛ یا مشتری از QR میز سفارش می‌دهد و صندوق‌دار تأیید می‌کند.'],
      ['n' => '۲', 'title' => 'بار', 'text' => '«ارسال به بار» فیش را چاپ می‌کند و قلم در صف باریستا می‌نشیند؛ بیش از ۲۰ دقیقه قرمز می‌شود.'],
      ['n' => '۳', 'title' => 'تسویه', 'text' => 'نقد، کارتخوان یا نسیه؛ تخفیف قلم و تخفیف سفارش جدا؛ فاکتور فروش ثبت می‌شود.'],
      ['n' => '۴', 'title' => 'انبار و بها', 'text' => 'قهوه و شیر همان اندازه طبق دستور مصرف از انبار کافه کم و بهای فنجان ثبت می‌شود.'],
  ];
  $switches = [
      ['title' => 'سرویس میز', 'on' => 'نوع «سالن»، نمای میزها با رنگ وضعیت، QR هر میز، حق سرویس', 'off' => 'فقط پیشخوان و بیرون‌بر'],
      ['title' => 'دلیوری', 'on' => 'نوع «دلیوری»، نشانی مشتری، پیک و کرایهٔ پیک', 'off' => 'بدون پیک'],
      ['title' => 'آشپزخانهٔ غذا', 'on' => 'صبحانه و غذای گرم به صف آشپزخانه', 'off' => 'همه‌چیز از ایستگاه بار'],
  ];
  $cols = ['نوع', 'قلم', 'تعداد', 'فروش خالص', 'بهای مواد', 'درصد بها'];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>کافی‌شاپ</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ کافی‌شاپ</p>
    <h1>نرم‌افزار کافی‌شاپ؛ سفارش سریع پیشخوان، بار و حساب هر فنجان</h1>
    <p class="ag-lead ag-lead--wide">سفارش لمسی پشت پیشخوان، با اندازه‌های کوچک، متوسط و بزرگ و افزودنی‌هایی مثل شات اضافه، شیر جو دوسر یا بدون شکر. صف بار با هشدار تأخیر، منوی QR، بستن صندوق هر شیفت و کسر خودکار قهوه و شیر از انبار، با حسابداری کامل و سامانهٔ مؤدیان.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نرم‌افزار کافی‌شاپ<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">اندازه و افزودنی · ایستگاه بار · درصد بهای هر نوشیدنی</p>
    @include('studio::accounting-cafe-demos', ['n' => 1])
  </div>
</section>

<section class="ag-sec" id="latte">
  <div class="ag-wrap">
    <h2>یک لاته، از سفارش تا انبار</h2>
    <ol class="ag-steps ag-flow">
      @foreach($latte as $step)
        <li>
          <small>{{ $step['n'] }}</small>
          <b>{{ $step['title'] }}</b>
          <p>{{ $step['text'] }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>

<section class="ag-sec" id="shape">
  <div class="ag-wrap">
    <h2>کافهٔ شما، به شکل خودتان</h2>
    <div class="ag-roles">
      @foreach($switches as $card)
        <article>
          <h3>{{ $card['title'] }}</h3>
          <span class="ag-switch ag-switch--on" aria-hidden="true"><i></i>روشن</span>
          <p><b>روشن:</b> {{ $card['on'] }}</p>
          <p><b>خاموش:</b> {{ $card['off'] }}</p>
        </article>
      @endforeach
    </div>
    <p class="ag-note">همه از تنظیمات کافی‌شاپ و از تاریخی که خودتان تعیین می‌کنید.</p>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات نرم‌افزار کافی‌شاپ</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="cafe-tab" id="cafe-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="cafe-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          <p class="ag-lead">{{ $tab['lead'] }}</p>
          <ul class="acc-cols">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @php $alt = $tab['alts'][0] ?? ''; @endphp
          @if($alt === 'صف بار و نوشیدنی')
            @include('studio::accounting-cafe-demos', ['n' => 2])
          @elseif($alt === 'منوی کافه با اندازه‌ها')
            @include('studio::accounting-cafe-demos', ['n' => 3])
          @elseif($alt === 'منوی QR کافی‌شاپ')
            @include('studio::accounting-cafe-demos', ['n' => 4])
          @elseif($alt === 'بستن صندوق شیفت کافه')
            @include('studio::accounting-cafe-demos', ['n' => 5])
          @elseif($alt !== '')
            <figure class="ag-shot-ph"><span>{{ $alt }}</span></figure>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec ag-offline" id="cup">
  <div class="ag-wrap">
    <h2>درصد بهای هر فنجان</h2>
    <p class="ag-lead ag-lead--wide">بدانید هر فنجان چند درصد از قیمتش خرج مواد است. چون هر اندازه دستور مصرف خودش را دارد، با هر فروش بهای قهوه و شیر همان فنجان ثبت می‌شود. گزارش «درصد بهای نوشیدنی و غذا» برای هر قلم تعداد، فروش خالص، بهای مواد و درصد بها را نشان می‌دهد، و نوشیدنی را از غذا و دسر جدا می‌کند. اگر درصد بهای یک ماه از حدی که تعیین کرده‌اید بالاتر برود، دستیار هوشمند هشدار می‌دهد که نرخ منو یا دستور مصرف را بازبینی کنید.</p>
    <div class="ag-hw">
      @foreach($cols as $col)
        <div><b>{{ $col }}</b></div>
      @endforeach
    </div>
    @include('studio::accounting-cafe-demos', ['n' => 6])
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیار هوشمند کافه</h2>
    <p class="ag-lead ag-lead--wide">دستیار فارسی HDD Land فروش و مواد کافه را تحلیل می‌کند، درصد بهای بالا را هشدار می‌دهد، فاکتور خرید کاغذی را اسکن و ثبت می‌کند و به پرسش‌های شما دربارهٔ برنامه پاسخ می‌دهد. همهٔ این‌ها بدون اینترنت انجام می‌شود.</p>
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
    <h2>کافهٔ خودتان را در یک دمو ببینید؛ از اولین لاته تا بستن صندوق</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
    </div>
    <p class="ag-note">رستوران دارید؟ <a href="{{ url('/computer-accounting/restaurant') }}">نسخهٔ رستوران</a></p>
  </div>
</section>
@include('studio::accounting-cafe-demo-script')
