@php
  $tabs = \App\Support\StudioComputerMenu::officeTabs();
  $faqs = \App\Support\StudioComputerMenu::officeFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $path = [
      ['n' => '۱', 'title' => 'ثبت', 'text' => 'عنوان، شرح و اولویت (عادی، فوری، هشدار) را می‌نویسید.'],
      ['n' => '۲', 'title' => 'سپردن', 'text' => 'کار را به یک یا چند همکار یا به یک نقش می‌سپارید، ناظر می‌گذارید و مهلت را با تاریخ و ساعت تعیین می‌کنید.'],
      ['n' => '۳', 'title' => 'اعلان', 'text' => 'گیرنده و ناظر اعلان می‌گیرند. اگر مدیر روشن کرده باشد، پیامک هم فرستاده می‌شود.'],
      ['n' => '۴', 'title' => 'پاسخ و ارجاع', 'text' => 'گیرنده پاسخ می‌دهد، پیوست می‌گذارد یا کار را به نفر بعد ارجاع می‌دهد. همهٔ مسیر ثبت می‌شود.'],
      ['n' => '۵', 'title' => 'پایان و گزارش', 'text' => 'کار با نتیجه بسته می‌شود. گزارش‌ها نشان می‌دهند به‌موقع بوده یا با دیرکرد.'],
  ];
  $columns = [
      ['title' => 'نامه و دبیرخانه', 'text' => 'نامهٔ داخلی، وارده و صادره. گیرنده و رونوشت، نشان خوانده‌شده، پاسخ، و شمارهٔ دبیرخانه جدا برای هر نوع نامه.'],
      ['title' => 'کار و ارجاع', 'text' => 'کارتابل، ارجاعات دریافتی و ارسالی، مهلت و یادآوری، پیوست، و تاریخچهٔ کامل ارجاعات.'],
      ['title' => 'گزارش کار روزانه', 'text' => 'هر کارمند کار روزش را با ساعت و مدت می‌نویسد و ثبت نهایی می‌کند. مدیر روزهای بدون گزارش را هم می‌بیند.'],
  ];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>اتوماسیون اداری</span>
    </p>
    <p class="ag-kicker">اتوماسیون اداری · نسخهٔ مستقل یا ماژول کنار حسابداری</p>
    <h1>اتوماسیون اداری حساب HDD؛ نامه، دبیرخانه، کارتابل و پیگیری کارها</h1>
    <p class="ag-lead ag-lead--wide">نامهٔ داخلی، وارده و صادره با شمارهٔ دبیرخانه و امضای داخلی با پین. کار را به همکار یا به یک نقش بسپارید، مهلت بگذارید و یادآوری بگیرید. گزارش کار روزانهٔ کارکنان و گزارش عملکرد هر کارمند و هر واحد هم در همین برنامه است. اگر حسابداری حساب HDD را دارید، کار را به فاکتور و سند پیوند بدهید.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی اتوماسیون اداری<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">دبیرخانه با شماره‌گذاری · ارجاع با مهلت و یادآوری · گزارش عملکرد کارکنان</p>
    <figure class="ag-shot-ph"><span>کارتابل اتوماسیون اداری حساب HDD</span></figure>
  </div>
</section>

<section class="ag-sec" id="path">
  <div class="ag-wrap">
    <h2>مسیر یک کار</h2>
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

<section class="ag-sec" id="columns">
  <div class="ag-wrap">
    <h2>سه ستون اتوماسیون</h2>
    <div class="ag-roles">
      @foreach($columns as $col)
        <article>
          <h3>{{ $col['title'] }}</h3>
          <p>{{ $col['text'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات اتوماسیون اداری</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="off-tab" id="off-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="off-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
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

<section class="ag-sec ag-offline" id="remind">
  <div class="ag-wrap">
    <h2>هیچ کاری فراموش نمی‌شود</h2>
    <p class="ag-lead ag-lead--wide">مهلت که نزدیک شد، خود برنامه یادآوری می‌کند. برای هر کار با مهلت، گیرنده‌ها، ناظرها و سازنده سه یادآوری می‌گیرند: ۲۴ ساعت مانده، ۱ ساعت مانده و پس از گذشتن مهلت. فهرست «کارهای معوق» هر صبح نشان می‌دهد چه کاری باید پیگیری شود. «تاریخچهٔ ارجاعات» هم نشان می‌دهد کار دست چه کسی مانده است.</p>
    <div class="ag-marks">
      <div>۲۴ ساعت مانده</div>
      <div>۱ ساعت مانده</div>
      <div>مهلت گذشت</div>
    </div>
    <figure class="ag-shot-ph"><span>گزارش کارهای معوق در اتوماسیون اداری</span></figure>
  </div>
</section>

<section class="ag-sec" id="beside">
  <div class="ag-wrap">
    <h2>اگر حسابداری حساب HDD را دارید</h2>
    <p class="ag-lead ag-lead--wide">اتوماسیون اداری را روی همان برنامهٔ حسابداری فعال کنید. در هر کار می‌توانید سند حسابداری، فاکتور، دریافت و پرداخت، شخص یا کالا را پیوند بدهید. گزارش «کارها به تفکیک نوع سند پیوندی» هم نشان می‌دهد کارهای اداری بیشتر دور کدام بخش می‌چرخند. خود اتوماسیون هیچ سند یا موجودی‌ای ثبت نمی‌کند.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/computer-accounting') }}">نسخه‌های حسابداری</a>
    </div>
    <p class="ag-note">[[تأیید: بخش هوش مصنوعی در صفحهٔ اتوماسیون بیاید یا نه؟]]</p>
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
    <h2>گردش نامه و کار سازمانتان را در یک دمو ببینید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
    </div>
  </div>
</section>
