@php
  $tabs = \App\Support\StudioComputerMenu::shopTabs();
  $faqs = \App\Support\StudioComputerMenu::shopFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $trades = ['لوازم خانگی', 'موبایل و لوازم جانبی', 'پوشاک و کفش', 'لوازم یدکی', 'ابزار و یراق', 'مصالح ساختمانی', 'لوازم‌التحریر و کتاب', 'عمده‌فروشی و بنکداری'];
  $flow = [
      ['n' => '۱', 'title' => 'خرید', 'text' => 'فاکتور خرید یا سفارش خرید؛ کالا با بهای واقعی (میانگین یا FIFO) وارد انبار می‌شود.'],
      ['n' => '۲', 'title' => 'فروش', 'text' => 'فاکتور فروش با بارکد، تخفیف و مالیات؛ نقد، کارتخوان، چک یا نسیه.'],
      ['n' => '۳', 'title' => 'دریافت', 'text' => 'چک و اقساط با سررسید و یادآور؛ هر دریافت به فاکتور خودش وصل می‌شود.'],
      ['n' => '۴', 'title' => 'سود', 'text' => 'سود هر فاکتور، هر کالا، هر مشتری و هر روز؛ بدون محاسبهٔ دستی.'],
  ];
  $compare = [
      ['فاکتور، انبار، چک، اقساط، مؤدیان', '✓', '✓', '✓'],
      ['تخفیف‌های پلکانی و مشترکین', '✓', '✓', '✓'],
      ['کارتخوان متصل (PC-POS)', '✓', '✓', '✓'],
      ['صندوق فروش سریع (POS) و شیفت', '—', '✓', '—'],
      ['صندوق بدون اتصال، نمایشگر مشتری، کشوی پول', '—', '✓', '—'],
      ['باشگاه مشتریان با امتیاز', '—', '✓', '—'],
      ['حقوق و دستمزد', '—', '✓', '✓'],
      ['قراردادها، دفتر دوم و بودجه، کنترل شعب', '—', '—', '✓'],
  ];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>فروشگاهی</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ فروشگاهی</p>
    <h1>نرم‌افزار حسابداری فروشگاهی؛ فاکتور سریع، انبار دقیق، سود واقعی هر کالا</h1>
    <p class="ag-lead ag-lead--wide">فاکتور فروش و خرید با بارکد، انبار و موجودی، چک و اقساط، کارتخوان متصل به فاکتور، تخفیف‌های پلکانی و مشترکین، پیامک و سامانهٔ مؤدیان؛ و در پایان روز بدانید هر کالا چقدر سود داده است.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نسخهٔ فروشگاهی<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">سود هر کالا و فاکتور · چک و اقساط · سامانهٔ مؤدیان</p>
    @include('studio::accounting-shop-demos', ['n' => 1])
  </div>
</section>

<section class="ag-sec" id="trades">
  <div class="ag-wrap">
    <h2>مناسب کدام فروشگاه است</h2>
    <div class="ag-trades">
      @foreach($trades as $name)
        <div><b>{{ $name }}</b></div>
      @endforeach
    </div>
    <p class="ag-note">فروشگاه شما صندوق سریع و بارکد ترازو می‌خواهد؟ <a href="{{ url('/computer-accounting/supermarket') }}">نسخهٔ سوپرمارکت</a></p>
  </div>
</section>

<section class="ag-sec" id="flow">
  <div class="ag-wrap">
    <h2>از فاکتور تا سود واقعی</h2>
    <ol class="ag-steps ag-flow">
      @foreach($flow as $step)
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
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="shop-tab" id="shop-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="shop-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          <ul class="acc-cols">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @if(($tab['alt'] ?? '') === 'تسویهٔ فاکتور با نقد، کارت و چک')
            @include('studio::accounting-shop-demos', ['n' => 2])
          @elseif(($tab['alt'] ?? '') === 'گزارش موجودی کالا')
            @include('studio::accounting-shop-demos', ['n' => 3])
          @elseif(($tab['alt'] ?? '') === 'مدیریت چک دریافتی')
            @include('studio::accounting-shop-demos', ['n' => 4])
          @elseif(($tab['alt'] ?? '') === 'گزارش سود هر کالا')
            @include('studio::accounting-shop-demos', ['n' => 5])
          @elseif(!empty($tab['alt']))
            <figure class="ag-shot-ph"><span>{{ $tab['alt'] }}</span></figure>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیاری که حساب فروشگاه را می‌داند</h2>
    <ul class="ag-list">
      <li>«سود این ماه چقدر بود؟»، «کدام کالا فروش نمی‌رود؟»، «مانده آقای رضایی؟» را با نوشتن یا صدای فارسی بپرسید</li>
      <li>تحلیل فروش و مشتریان، پیش‌بینی نقدینگی، داشبورد سلامت مالی</li>
      <li>اسکن هوشمند فاکتور خرید (OCR)</li>
      <li>همه روی کامپیوتر خودتان، بدون فرستادن اطلاعات به اینترنت</li>
    </ul>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/ai') }}">بیشتر دربارهٔ هوش مصنوعی</a>
    </div>
  </div>
</section>

<section class="ag-sec" id="compare">
  <div class="ag-wrap">
    <h2>مقایسه با سوپرمارکت و شرکتی</h2>
    <div class="ag-table-wrap">
      <table class="ag-cmp ag-cmp--two">
        <thead>
          <tr><th>امکان</th><th>فروشگاهی</th><th>سوپرمارکت</th><th>شرکتی</th></tr>
        </thead>
        <tbody>
          @foreach($compare as $row)
            <tr>
              <th>{{ $row[0] }}</th>
              <td>{{ $row[1] }}</td>
              <td>{{ $row[2] }}</td>
              <td>{{ $row[3] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
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
    <h2>فروشگاه خودتان را در حساب HDD ببینید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
  </div>
</section>
@include('studio::accounting-shop-demo-script')
