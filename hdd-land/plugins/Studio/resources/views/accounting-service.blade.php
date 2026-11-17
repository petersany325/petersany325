@php
  $tabs = \App\Support\StudioComputerMenu::serviceTabs();
  $faqs = \App\Support\StudioComputerMenu::serviceFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $trades = ['مشاوره', 'آموزش', 'پشتیبانی', 'اجرای پروژه', 'نظارت', 'طراحی و مهندسی'];
  $path = [
      ['n' => '۱', 'title' => 'قرارداد', 'text' => 'قرارداد با مشتری با مبلغ، تاریخ شروع و پایان، پیش‌دریافت و جدول اقساط ثبت و فعال می‌شود.'],
      ['n' => '۲', 'title' => 'انجام کار', 'text' => 'کار به یک همکار ارجاع می‌شود و با تیکت پیگیری می‌شود. کارکنان گزارش کار روزانه می‌نویسند.'],
      ['n' => '۳', 'title' => 'فاکتور خدمات', 'text' => 'خدمت با مقدار (مثلاً ساعت) و نرخ فاکتور می‌شود، با ارزش افزوده و اضافات و کسورات.'],
      ['n' => '۴', 'title' => 'بهای مستقیم', 'text' => 'قطعهٔ مصرفی، دستمزد تکنسین یا هزینهٔ دیگر به همان ردیف خدمت وصل می‌شود.'],
      ['n' => '۵', 'title' => 'وصول و سود', 'text' => 'دریافت به فاکتور تخصیص می‌یابد. گزارش‌ها مطالبات معوق و سود هر خدمت و هر نوع کار را نشان می‌دهند.'],
  ];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>خدماتی</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ خدماتی</p>
    <h1>نرم‌افزار حسابداری خدماتی؛ قرارداد، فاکتور خدمات و سود هر نوع کار</h1>
    <p class="ag-lead ag-lead--wide">قرارداد با مشتری و اقساطش را ثبت کنید و هر خدمت را با نرخ ساعتی یا مقطوع فاکتور کنید. بهای مستقیم هر خدمت را هم ثبت کنید تا بدانید کدام کار واقعاً سود می‌دهد. وصول مطالبات، چک و بانک، تیکت و گزارش کار روزانهٔ کارکنان، دارایی ثابت و سامانهٔ مؤدیان هم در همین برنامه است.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نسخهٔ خدماتی<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">قرارداد و اقساط · سود هر نوع کار · پیگیری وصول</p>
    <figure class="ag-shot-ph"><span>سودآوری به تفکیک نوع کار در نرم‌افزار حسابداری خدماتی حساب HDD</span></figure>
  </div>
</section>

<section class="ag-sec" id="who">
  <div class="ag-wrap">
    <h2>برای چه کسب‌وکارهایی</h2>
    <div class="ag-trades">
      @foreach($trades as $name)
        <div><b>{{ $name }}</b></div>
      @endforeach
    </div>
    <p class="ag-note">نوع کار خودتان را هم با زمان و نرخ استاندارد تعریف کنید.</p>
  </div>
</section>

<section class="ag-sec" id="path">
  <div class="ag-wrap">
    <h2>مسیر یک خدمت</h2>
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

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات نسخهٔ خدماتی</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="svc-tab" id="svc-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="svc-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          <p class="ag-lead">{{ $tab['lead'] }}</p>
          <ul class="acc-cols">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @foreach($tab['alts'] ?? [] as $alt)
            <figure class="ag-shot-ph"><span>{{ $alt }}</span></figure>
          @endforeach
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec ag-offline" id="profit">
  <div class="ag-wrap">
    <h2>سود واقعی هر خدمت</h2>
    <p class="ag-lead ag-lead--wide">همهٔ خدمت‌ها به یک اندازه سود نمی‌دهند. در یک شرکت خدماتی، هزینهٔ اصلی وقت آدم‌هاست. حساب HDD برای هر نوع کار زمان و نرخ استاندارد دستمزد را نگه می‌دارد و با هر فاکتور خدمات، درآمد آن نوع کار را در برابر بهای استاندارد دستمزدش می‌گذارد. قطعه یا دستمزد واقعی هر کار را هم می‌شود مستقیم به ردیف خدمت در فاکتور وصل کرد تا سود ناخالص همان خدمت دقیق باشد.</p>
    <div class="ag-note">
      <p>خدمت «پشتیبانی ماهانه»: ۱۰ ساعت × ۵٬۰۰۰٬۰۰۰ ریال = ۵۰٬۰۰۰٬۰۰۰ ریال درآمد</p>
      <p>نوع کار «پشتیبانی» با زمان و نرخ استاندارد ← بهای استاندارد دستمزد و حاشیه در گزارش «سودآوری به تفکیک نوع کار»</p>
    </div>
    <figure class="ag-shot-ph"><span>سود ناخالص خدمات در حسابداری خدماتی</span></figure>
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیار هوشمند برای شرکت خدماتی</h2>
    <p class="ag-lead ag-lead--wide">دستیار فارسی حساب HDD فاکتورهایی را که بیش از ۹۰ روز وصول نشده‌اند پیدا می‌کند و هشدار می‌دهد. فروش و مشتریان را هم تحلیل می‌کند، وصول و نقدینگی را پیش‌بینی می‌کند، سند را با زبان ساده می‌نویسد و فاکتور کاغذی را اسکن می‌کند. همهٔ این‌ها بدون اینترنت انجام می‌شود.</p>
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
    <h2>خدمات شرکت خودتان را در یک دمو ببینید؛ از قرارداد تا سود هر نوع کار</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
    </div>
    <p class="ag-note">انبار و حقوق و دستمزد هم لازم دارید؟ <a href="{{ url('/computer-accounting/corporate') }}">نسخهٔ شرکتی</a></p>
  </div>
</section>
