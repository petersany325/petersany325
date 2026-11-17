@php
  $tabs = \App\Support\StudioComputerMenu::distributionTabs();
  $faqs = \App\Support\StudioComputerMenu::distributionFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $cycle = [
      ['n' => '۱', 'title' => 'ویزیت', 'text' => 'ویزیتور طبق مسیر و برنامهٔ ویزیت به مغازه‌ها می‌رود؛ طرح‌ها و اشانتیون روی گوشی‌اش دیده می‌شود، حتی بی‌اینترنت.'],
      ['n' => '۲', 'title' => 'سفارش', 'text' => 'سفارش با گوشی ثبت می‌شود؛ اگر اینترنت نبود، بعداً خودش می‌رسد و هر سفارش فقط یک بار ثبت می‌شود.'],
      ['n' => '۳', 'title' => 'کنترل', 'text' => 'سفارش‌های بیش از سقف اعتبار، با چک برگشتی، تخفیف بیش از مجاز، خارج از محدودهٔ مغازه یا بیش از سهمیه، برای تأیید کنار می‌روند.'],
      ['n' => '۴', 'title' => 'بارگیری', 'text' => 'سفارش‌های تأییدشده روی ماشین چیده می‌شوند، ترتیب تحویل تعیین و فاکتورها از انبار ماشین صادر می‌شوند.'],
      ['n' => '۵', 'title' => 'تحویل', 'text' => 'موزع با گوشی تحویل، برگشتی یا «تحویل نشد» را ثبت می‌کند و دفتر وضعیت را زنده می‌بیند.'],
      ['n' => '۶', 'title' => 'تسویه', 'text' => 'عصر: شمارش بار برگشتی و کسری، وجه نقد موزع و ویزیتور، بستن روز و سند پورسانت.'],
  ];
  $roles = [
      ['title' => 'مدیر پخش (نرم‌افزار کامپیوتر)', 'items' => ['داشبورد مدیر پخش', 'کارتابل سفارش ویزیتورها', 'لیست پخش و تسویهٔ ماشین', 'نقشه و GPS', 'طرح‌ها، سهمیه و پورسانت']],
      ['title' => 'ویزیتور (اپ اندروید)', 'items' => ['مسیر و مشتری‌های روز', 'ثبت سفارش، حتی بی‌اینترنت', 'دیدن طرح‌ها، اشانتیون و «چند عدد دیگر تا پلهٔ بعد»', 'ثبت بی‌خریدی', 'دریافت نقد، کارتخوان و چک', 'بستن روز']],
      ['title' => 'موزع و راننده (اپ اندروید)', 'items' => ['لیست تحویل به ترتیب', 'ثبت تحویل، برگشتی یا «تحویل نشد»', 'مکان ماشین روی نقشه']],
  ];
  $gates = [
      'بیش از سقف اعتبار مشتری (با احتساب سفارش‌های منتظر دیگرش)',
      'چک برگشتی باز',
      'تخفیف بیش از سقف مجاز ویزیتور',
      'ثبت سفارش بیرون از محدودهٔ مغازه',
      'بیش از سهمیهٔ ماهانهٔ ویزیتور',
  ];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>پخش</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ پخش مویرگی</p>
    <h1>نرم‌افزار حسابداری پخش؛ از سفارش ویزیتور تا تسویهٔ ماشین، روی نقشه</h1>
    <p class="ag-lead ag-lead--wide">ویزیتور با گوشی سفارش می‌گیرد، حتی بی‌اینترنت. سفارش در دفتر کنترل و روی ماشین بارگیری می‌شود، موزع تحویل را با گوشی ثبت می‌کند و عصر ماشین تسویه می‌شود. طرح‌های فروش و اشانتیون، سهمیه، پورسانت و GPS، همه با حسابداری و انبار کامل.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نسخهٔ پخش<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">اپ ویزیتور و موزع · نقشه و GPS · طرح‌های فروش پلکانی</p>
    <figure class="ag-shot-ph"><span>نقشهٔ پخش و GPS در نرم‌افزار حسابداری پخش حساب HDD</span></figure>
  </div>
</section>

<section class="ag-sec" id="cycle">
  <div class="ag-wrap">
    <h2>چرخهٔ یک روز پخش</h2>
    <ol class="ag-steps ag-cycle">
      @foreach($cycle as $step)
        <li>
          <small>{{ $step['n'] }}</small>
          <b>{{ $step['title'] }}</b>
          <p>{{ $step['text'] }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>

<section class="ag-sec" id="roles">
  <div class="ag-wrap">
    <h2>سه نقش، سه ابزار</h2>
    <div class="ag-roles">
      @foreach($roles as $role)
        <article>
          <h3>{{ $role['title'] }}</h3>
          <ul>
            @foreach($role['items'] as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </article>
      @endforeach
    </div>
    <p class="ag-note">برنامهٔ اندروید یکی است و امکاناتش با مجوز و نقش هر گوشی باز می‌شود. (حداقل اندروید ۱۲)</p>
    <figure class="ag-shot-ph"><span>اپ اندروید ویزیتور حساب HDD</span></figure>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="dist-tab" id="dist-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="dist-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
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

<section class="ag-sec ag-offline" id="gate">
  <div class="ag-wrap">
    <h2>هر سفارشی فاکتور نمی‌شود</h2>
    <p class="ag-lead ag-lead--wide">سفارش گوشی ویزیتور مستقیم فاکتور نمی‌شود. نرم‌افزار آن را بررسی می‌کند و اگر یکی از این‌ها باشد، برای تأیید سرپرست کنار می‌گذارد:</p>
    <ol class="ag-steps ag-gate">
      @foreach($gates as $i => $line)
        <li><small>{{ ['۱','۲','۳','۴','۵'][$i] }}</small><p>{{ $line }}</p></li>
      @endforeach
    </ol>
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیاری که پخش را می‌فهمد</h2>
    <ul class="ag-list">
      <li>«فروش ویزیتور احمدی این ماه؟»، «کدام مسیر بی‌خریدی بیشتری دارد؟» را با نوشتن یا صدای فارسی بپرسید</li>
      <li>تحلیل فروش و مشتریان، پیش‌بینی نقدینگی، تحلیل چک‌ها</li>
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
    <h2>پخش شرکت خودتان را روی نقشه ببینید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
  </div>
</section>
