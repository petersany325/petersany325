@php
  $tabs = \App\Support\StudioComputerMenu::contractingTabs();
  $faqs = \App\Support\StudioComputerMenu::contractingFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $cycle = [
      ['n' => '۱', 'title' => 'قرارداد و پروژه', 'text' => 'قرارداد با کارفرما ثبت می‌شود و پروژه روی آن تعریف می‌شود: مبلغ پیمان، برآورد کل بها، درصد کسور و بودجهٔ هر سرفصل.'],
      ['n' => '۲', 'title' => 'بها و کار در جریان', 'text' => 'هزینهٔ مصالح، دستمزد، ماشین‌آلات و سربار به پروژه می‌نشیند. مصالحی که هنوز مصرف نشده در «کار در جریان» می‌ماند.'],
      ['n' => '۳', 'title' => 'صورت وضعیت', 'text' => 'کارکرد هر دوره برای کارفرما صورت وضعیت می‌شود و کار پیمانکار جزء هم صورت وضعیت جدا دارد. کسور و ارزش افزوده خودکار حساب می‌شود.'],
      ['n' => '۴', 'title' => 'درصد پیشرفت', 'text' => 'درآمد به نسبت بهای انجام‌شده به برآورد کل شناسایی می‌شود. اگر قرارداد زیان‌ده باشد، ذخیرهٔ زیان کنار گذاشته می‌شود.'],
      ['n' => '۵', 'title' => 'سود پروژه', 'text' => 'سود و زیان هر پروژه و مقایسهٔ بها با بودجه، همیشه به‌روز است.'],
      ['n' => '۶', 'title' => 'آزادسازی کسور', 'text' => 'پس از تحویل، حسن انجام کار آزاد می‌شود. بیمه هم با شمارهٔ مفاصاحساب آزاد می‌شود.'],
  ];
  $sides = [
      ['title' => 'کارفرما (درآمد)', 'items' => ['صورت وضعیت درآمدی فقط به نام کارفرمای همان قرارداد', 'کسر حسن انجام کار و بیمه که نزد کارفرما می‌ماند', 'علی‌الحساب مالیات (اختیاری) و ارزش افزوده', 'آزادسازی سپرده به حساب کارفرما']],
      ['title' => 'پیمانکار جزء (هزینه)', 'items' => ['صورت وضعیت هزینه‌ای برای هر پیمانکار جزء', 'کسر حسن انجام کار و بیمه که شما نزد خود نگه می‌دارید', 'ارزش افزوده خرید', 'آزادسازی سپرده به حساب همان پیمانکار']],
  ];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>پیمانکاری</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ پیمانکاری</p>
    <h1>نرم‌افزار حسابداری پیمانکاری؛ صورت وضعیت، کسور و سود هر پروژه</h1>
    <p class="ag-lead ag-lead--wide">پروژه را روی قرارداد کارفرما تعریف کنید. هزینه‌ها و کار در جریان را ثبت کنید و صورت وضعیت کارفرما و پیمانکار جزء را با کسور حسن انجام کار و بیمه بزنید. درآمد به روش درصد پیشرفت شناسایی می‌شود و برای قرارداد زیان‌ده ذخیره کنار گذاشته می‌شود. حقوق و دستمزد، انبار مصالح، دارایی ثابت و سامانهٔ مؤدیان هم در همین برنامه است.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نسخهٔ پیمانکاری<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">صورت وضعیت با کسور · درآمد درصد پیشرفت · سود و زیان هر پروژه</p>
    @include('studio::accounting-contracting-demos', ['n' => 1])
  </div>
</section>

<section class="ag-sec" id="cycle">
  <div class="ag-wrap">
    <h2>چرخهٔ یک پروژه</h2>
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

<section class="ag-sec" id="sides">
  <div class="ag-wrap">
    <h2>دو طرف پیمان</h2>
    <div class="ag-roles ag-roles--2">
      @foreach($sides as $side)
        <article>
          <h3>{{ $side['title'] }}</h3>
          <ul>
            @foreach($side['items'] as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات نسخهٔ پیمانکاری</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="ct-tab" id="ct-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="ct-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          <p class="ag-lead">{{ $tab['lead'] }}</p>
          <ul class="acc-cols">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @foreach($tab['alts'] ?? [] as $alt)
            @if($alt === 'پروژه‌های پیمانکاری')
              @include('studio::accounting-contracting-demos', ['n' => 2])
            @elseif($alt === 'صورت وضعیت با کسور')
              @include('studio::accounting-contracting-demos', ['n' => 3])
            @elseif($alt === 'ماندهٔ کسور پروژه')
              @include('studio::accounting-contracting-demos', ['n' => 4])
            @elseif($alt === 'بودجهٔ پروژه در برابر هزینه')
              @include('studio::accounting-contracting-demos', ['n' => 5])
            @endif
          @endforeach
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec ag-offline" id="progress">
  <div class="ag-wrap">
    <h2>صورت وضعیت، درآمد نیست</h2>
    <p class="ag-lead ag-lead--wide">صورت وضعیت مبلغی است که از کارفرما طلب می‌کنید. درآمد واقعی به اندازهٔ کاری است که انجام داده‌اید. HDD Land درآمد را از نسبت بهای انجام‌شده به برآورد کل حساب می‌کند. اختلاف آن با صورت وضعیت را به‌صورت دارایی یا بدهی قرارداد نشان می‌دهد، و اگر قرارداد زیان‌ده شود، زیان را همان روز شناسایی می‌کند.</p>
    <ul class="ag-list">
      <li>مبلغ پیمان: ده میلیارد ریال</li>
      <li>برآورد کل بها: هشت میلیارد ریال</li>
      <li>بهای انجام‌شده تا امروز: چهار میلیارد ریال</li>
      <li>درصد پیشرفت: ۵۰٪</li>
      <li>درآمد تجمعی: پنج میلیارد ریال</li>
      <li>اگر صورت وضعیت تأییدشده کمتر از پنج میلیارد باشد، تفاوت «دارایی قرارداد» است و اگر بیشتر باشد «بدهی قرارداد».</li>
    </ul>
    @include('studio::accounting-contracting-demos', ['n' => 6])
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیار هوشمند پیمانکاری</h2>
    <p class="ag-lead ag-lead--wide">دستیار فارسی HDD Land پروژه‌ها، صورت وضعیت‌ها و کسور را می‌شناسد و به پرسش‌های شما دربارهٔ برنامه پاسخ می‌دهد. سند را هم با زبان ساده می‌نویسد، فاکتور کاغذی را اسکن می‌کند و پیش از حسابرسی ایرادها را پیدا می‌کند. همهٔ این‌ها بدون اینترنت انجام می‌شود.</p>
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
    <h2>پروژه‌های خودتان را در یک دمو ببینید؛ از قرارداد تا آزادسازی کسور</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/computer-accounting/corporate') }}">مقایسه با نسخهٔ شرکتی</a>
    </div>
  </div>
</section>
@include('studio::accounting-contracting-demo-script')
