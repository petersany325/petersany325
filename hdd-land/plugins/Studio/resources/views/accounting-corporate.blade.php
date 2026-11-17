@php
  $tabs = \App\Support\StudioComputerMenu::corporateTabs();
  $faqs = \App\Support\StudioComputerMenu::corporateFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>شرکتی</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ شرکتی</p>
    <h1>نرم‌افزار حسابداری شرکتی؛ دفتر قانونی، حقوق، مالیات و شعب در یک برنامه</h1>
    <p class="ag-lead ag-lead--wide">برای شرکت‌هایی که حسابداری کامل می‌خواهند: سند و دفاتر قانونی، خزانه و چک، بازرگانی و انبار، حقوق و دستمزد، دارایی ثابت، سامانهٔ مؤدیان، دفتر دوم و بودجه و کنترل شعب، با دستیار هوش مصنوعی فارسی که بدون اینترنت کار می‌کند.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نسخهٔ شرکتی<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">مشاهدهٔ همهٔ امکانات</a>
    </div>
    <p class="ag-note">سامانهٔ مؤدیان · چندکاربره و شبکه‌ای · فروش و خرید ارزی</p>
    <figure class="ag-shot-ph"><span>نرم‌افزار حسابداری شرکتی حساب HDD</span></figure>
  </div>
</section>

<section class="ag-sec" id="fit">
  <div class="ag-wrap">
    <h2>برای چه شرکتی مناسب است</h2>
    <div class="ag-cards ag-cards--dense">
      <article class="ag-card"><div><b>شرکت‌های بازرگانی</b><p>خرید و فروش داخلی و خارجی، انبار، چک و تسویهٔ اشخاص.</p></div></article>
      <article class="ag-card"><div><b>شرکت‌های خدماتی و پروژه‌ای</b><p>قرارداد، صورت‌وضعیت، مدیریت کارها و سود هر نوع کار.</p></div></article>
      <article class="ag-card"><div><b>شرکت‌های چندشعبه</b><p>سرور مرکزی، نرخ و موجودی هر شعبه و گزارش از همهٔ شعب.</p></div></article>
      <article class="ag-card"><div><b>شرکت‌هایی که حسابرسی و مالیات برایشان مهم است</b><p>دفاتر قانونی، سامانهٔ مؤدیان، ارزش افزوده و حسابرسی مالیاتی.</p></div></article>
    </div>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="corp-tab" id="corp-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="corp-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          @if(($tab['lead'] ?? '') !== '')
            <p class="ag-lead">{{ $tab['lead'] }}</p>
          @endif
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

<section class="ag-sec" id="special">
  <div class="ag-wrap">
    <h2>ویژهٔ نسخهٔ شرکتی</h2>
    <div class="ag-cards">
      <article class="ag-card"><div><b>دفتر دوم و بودجه</b><p>یک دفتر موازی کنار دفتر اصلی قانونی که فقط اسناد اصلاحیه می‌گیرد؛ تراز تطبیقی دو دفتر و ردپای تغییرناپذیر دفتر دوم. تعریف بودجه برای حساب، مرکز هزینه و پروژه و گزارش بودجه در برابر عملکرد.</p></div></article>
      <article class="ag-card"><div><b>کنترل شعب</b><p>تعریف شعب، ارسال و دریافت اطلاعات با سرور مرکزی، نرخ و موقعیت کالا برای هر شعبه، انتقال کالا بین شعب و مقایسهٔ حواله و رسید. گزارش از سرور: فروش، موجودی، گردش و ماندهٔ حساب هر شعبه.</p></div></article>
      <article class="ag-card"><div><b>حسابرسی مالیاتی</b><p>پیش از رسیدگی مالیاتی، نرم‌افزار دوره را بررسی می‌کند و یافته‌ها را با توضیح نشان می‌دهد: تطبیق ارزش افزوده با دفتر، کنترل شناسهٔ خریداران و نرخ مالیات و تطبیق فروش با سامانهٔ مؤدیان.</p></div></article>
    </div>
    <figure class="ag-shot-ph"><span>دفتر دوم و بودجه</span></figure>
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیار مالی هوشمند داخل نرم‌افزار</h2>
    <p class="ag-lead">روی کامپیوتر خودتان و بدون فرستادن اطلاعات به اینترنت:</p>
    <ul class="ag-list">
      <li>حسابدار هوشمند: ثبت با زبان طبیعی</li>
      <li>مدیر مالی هوشمند و داشبورد سلامت مالی</li>
      <li>پیش‌بینی نقدینگی، تحلیل فروش، مشتریان، چک‌ها و بانک</li>
      <li>اسکن هوشمند فاکتور (OCR) و ورودی هوشمند اسناد</li>
      <li>حسابرس هوشمند</li>
      <li>گفت‌وگو با نوشتن یا صدای فارسی</li>
    </ul>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/ai') }}">بیشتر دربارهٔ هوش مصنوعی</a>
    </div>
  </div>
</section>

<section class="ag-sec" id="security">
  <div class="ag-wrap">
    <h2>امنیت و کنترل</h2>
    <ul class="ag-list">
      <li>هر کاربر فقط همان حساب‌ها، انبارها و اعمالی را می‌بیند که به او داده شده است.</li>
      <li>اسناد قطعی و دوره‌های قفل‌شده قابل تغییر نیستند؛ هر تغییر در رویدادهای ممیزی ثبت می‌شود.</li>
      <li>اطلاعات روی سرور خود شرکت می‌ماند؛ پشتیبان‌گیری با آزمایش سلامت.</li>
    </ul>
  </div>
</section>

<section class="ag-sec" id="compare">
  <div class="ag-wrap">
    <h2>مقایسه با نسخهٔ فروشگاهی</h2>
    <div class="ag-table-wrap">
      <table class="ag-cmp ag-cmp--two">
        <thead>
          <tr><th>امکان</th><th>فروشگاهی</th><th>شرکتی</th></tr>
        </thead>
        <tbody>
          <tr><th>حسابداری، خزانه، بازرگانی، انبار، مالیات</th><td>هست</td><td>هست</td></tr>
          <tr><th>فروش و خرید ارزی (با مجوز چندارزی)</th><td>هست</td><td>هست</td></tr>
          <tr><th>حقوق و دستمزد</th><td>—</td><td>هست</td></tr>
          <tr><th>قراردادها و مدیریت مشتریان</th><td>—</td><td>هست</td></tr>
          <tr><th>مدیریت وظایف و صورت‌وضعیت</th><td>—</td><td>هست</td></tr>
          <tr><th>دفتر دوم و بودجه</th><td>—</td><td>هست</td></tr>
          <tr><th>کنترل شعب</th><td>—</td><td>هست</td></tr>
          <tr><th>بایگانی پرونده‌ها</th><td>—</td><td>هست</td></tr>
        </tbody>
      </table>
    </div>
    <p class="ag-note">نیاز به همهٔ صنوف دارید؟ نسخهٔ «همهٔ صنوف» هم هست. <a href="{{ url('/contact') }}">تماس</a></p>
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
    <h2>نسخهٔ شرکتی را با اطلاعات شرکت خودتان ببینید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
  </div>
</section>
