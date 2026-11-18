@php
  $tabs = \App\Support\StudioComputerMenu::productionTabs();
  $faqs = \App\Support\StudioComputerMenu::productionFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $path = [
      ['n' => '۱', 'title' => 'خرید مواد', 'text' => 'مواد اولیه با فاکتور خرید یا قبض باسکول وارد انبار می‌شود، در صورت نیاز با سری ساخت و تاریخ انقضا.'],
      ['n' => '۲', 'title' => 'فرمول ساخت', 'text' => 'برای هر محصول مشخص می‌کنید چه موادی و به چه مقدار مصرف می‌شود و دستمزد و سربار هر واحد چقدر است.'],
      ['n' => '۳', 'title' => 'دستور تولید', 'text' => 'محصول، مقدار، تاریخ و انبار را انتخاب می‌کنید. مصرف مواد به نسبت مقدار تولید خودکار حساب می‌شود.'],
      ['n' => '۴', 'title' => 'سند خودکار', 'text' => 'مواد به «کالای در جریان ساخت» می‌رود، دستمزد و سربار جذب می‌شود و محصول با بهای تمام‌شده وارد انبار می‌شود. هر سه سند با هم ثبت می‌شوند.'],
      ['n' => '۵', 'title' => 'فروش و سود', 'text' => 'محصول با بهای واقعی‌اش فروخته می‌شود و گزارش‌ها سود هر محصول را نشان می‌دهند.'],
  ];
  $shops = [
      ['title' => 'صنایع غذایی و لبنی', 'text' => 'فرمول برای هر بسته، سری ساخت و تاریخ انقضا، هشدار نزدیک انقضا.'],
      ['title' => 'کارگاه‌های صنعتی و قطعه‌سازی', 'text' => 'محصول نیم‌ساخته در فرمول محصول نهایی، مانده‌سازی بر پایهٔ موجودی مواد.'],
      ['title' => 'مواد اولیهٔ فله و وزنی', 'text' => 'قبض باسکول با رطوبت و ناخالصی، کسری نسبت به بارنامه.'],
      ['title' => 'تولید سفارشی و پیمانکاری', 'text' => 'ارسال مواد به انبار پیمانکار و گزارش اینکه با موجودی هر انبار چند محصول ساخته می‌شود.'],
  ];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>تولیدی</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ تولیدی</p>
    <h1>نرم‌افزار حسابداری تولیدی؛ از فرمول ساخت تا بهای تمام‌شدهٔ هر واحد</h1>
    <p class="ag-lead ag-lead--wide">فرمول ساخت (BOM) هر محصول را یک بار تعریف کنید. با هر دستور تولید، مواد از انبار خارج می‌شود، دستمزد و سربار جذب می‌شود و محصول با بهای تمام‌شدهٔ واقعی وارد انبار می‌شود. سندهای حسابداری هم خودکار ثبت می‌شوند. باسکول، سری ساخت و تاریخ انقضا، حقوق و دستمزد، دارایی ثابت و سامانهٔ مؤدیان هم در همین برنامه است.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نسخهٔ تولیدی<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">فرمول ساخت (BOM) · بهای تمام‌شدهٔ خودکار · قبض باسکول</p>
    @include('studio::accounting-production-demos', ['n' => 1])
  </div>
</section>

<section class="ag-sec" id="path">
  <div class="ag-wrap">
    <h2>مسیر یک محصول</h2>
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

<section class="ag-sec" id="shops">
  <div class="ag-wrap">
    <h2>برای چه کارگاه‌ها و کارخانه‌هایی</h2>
    <div class="ag-roles ag-roles--4">
      @foreach($shops as $shop)
        <article>
          <h3>{{ $shop['title'] }}</h3>
          <p>{{ $shop['text'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات نسخهٔ تولیدی</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="prod-tab" id="prod-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="prod-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          @if(!empty($tab['lead']))
            <p class="ag-lead">{{ $tab['lead'] }}</p>
          @endif
          <ul class="acc-cols">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @foreach($tab['alts'] ?? [] as $alt)
            @if($alt === 'فرمول ساخت (BOM) محصول')
              @include('studio::accounting-production-demos', ['n' => 2])
            @elseif($alt === 'ظرفیت تولید بر اساس موجودی مواد')
              @include('studio::accounting-production-demos', ['n' => 3])
            @elseif($alt === 'سری ساخت و تاریخ انقضای کالا')
              @include('studio::accounting-production-demos', ['n' => 4])
            @elseif($alt === 'قبض باسکول و سند توزین')
              @include('studio::accounting-production-demos', ['n' => 5])
            @else
              <figure class="ag-shot-ph"><span>{{ $alt }}</span></figure>
            @endif
          @endforeach
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec ag-offline" id="cost">
  <div class="ag-wrap">
    <h2>بهای تمام‌شدهٔ واقعی هر واحد</h2>
    <p class="ag-lead ag-lead--wide">بیشتر تولیدی‌ها قیمت تمام‌شده را با حدس یا در اکسل حساب می‌کنند. در HDD Land با هر دستور تولید، بهای مواد به نرخ واقعی انبار و دستمزد و سربار طبق فرمول حساب می‌شود و محصول با همان بها وارد انبار می‌شود. اگر بهای واحد از قیمت فروش بالاتر رفت، گزارش تولید زودتر از هر کسی به شما نشان می‌دهد.</p>
    <div class="ag-note">
      <p>فرمول «ماست ۱ کیلویی» برای ۱۰۰ عدد: ۵۰ کیلو شیر و ۱۰۰ ظرف</p>
      <p>دستمزد و سربار هر واحد: ۵۰٬۰۰۰ ریال</p>
      <p>دستور تولید ۲۰۰ عدد ← مصرف ۱۰۰ کیلو شیر و ۲۰۰ ظرف، به همراه سه سند خودکار: حوالهٔ مواد، جذب دستمزد و سربار و رسید محصول</p>
    </div>
    <div class="ag-marks">
      <div>بهای مواد</div>
      <div>دستمزد و سربار</div>
      <div>بهای هر واحد</div>
    </div>
    @include('studio::accounting-production-demos', ['n' => 6])
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیار هوشمند، ویژهٔ تولید</h2>
    <p class="ag-lead ag-lead--wide">دستیار فارسی HDD Land انبار و تولید را تحلیل می‌کند، فاکتور کاغذی را اسکن و ثبت می‌کند، سند را با زبان ساده می‌نویسد، پیش از حسابرسی ایرادها را پیدا می‌کند و نقدینگی و خرید را پیش‌بینی می‌کند. همهٔ این‌ها بدون اینترنت انجام می‌شود.</p>
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
    <h2>بهای تمام‌شدهٔ محصولاتتان را در یک دمو با دادهٔ خودتان ببینید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/computer-accounting/corporate') }}">مقایسه با نسخهٔ شرکتی</a>
    </div>
  </div>
</section>
@include('studio::accounting-production-demo-script')
