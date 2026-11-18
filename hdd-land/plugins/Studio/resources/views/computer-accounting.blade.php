@php
  $cards = \App\Support\StudioComputerMenu::cards();
  $shared = \App\Support\StudioComputerMenu::shared();
  $assistant = \App\Support\StudioComputerMenu::assistant();
  $network = \App\Support\StudioComputerMenu::network();
  $faqs = \App\Support\StudioComputerMenu::faqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
@endphp
<section class="ag-page" id="top">
  <div class="ag-wrap">
    <p class="ag-kicker">حسابداری کامپیوتری</p>
    <h1>حسابداری ویژهٔ صنف شما، با دستیار هوشمند فارسی</h1>
    <p class="ag-lead ag-lead--wide">فاکتور، انبار، خزانه، حقوق، مالیات و سامانهٔ مؤدیان در یک نرم‌افزار؛ هر صنف منو و منطق حسابداری خودش را دارد. دستیار هوش مصنوعی داخل خود نرم‌افزار و بدون اینترنت کار می‌کند.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو و مشاوره<i>←</i></a>
      <span class="ag-btn ag-btn--line">تماس در واتس‌اپ · [[لینک واتس‌اپ]]</span>
    </div>
    <p class="ag-note">نصب روی کامپیوتر و شبکهٔ خودتان · کار هم‌زمان چند کاربر · پشتیبانی [[مدت/نوع پشتیبانی]]</p>
    @include('studio::accounting-hub-demos', ['n' => 1])
  </div>
</section>

<section class="ag-sec" id="industries">
  <div class="ag-wrap">
    <p class="ag-kicker">صنف‌ها</p>
    <h2>صنف خود را انتخاب کنید</h2>
    <p class="ag-lead">هر نسخه فقط منوهایی را دارد که آن صنف لازم دارد؛ نه کمتر، نه شلوغ‌تر.</p>
    <div class="ag-cards ag-cards--dense">
      @foreach($cards as $row)
        <a class="ag-card" href="{{ url('/computer-accounting/'.$row['slug']) }}">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </a>
      @endforeach
    </div>
    <p class="ag-note">تعمیرگاه دارید؟ حسابداری تعمیرکاران تحت وب است. <a href="{{ url('/accounting') }}">حسابداری وب</a></p>
  </div>
</section>

<section class="ag-sec" id="shared">
  <div class="ag-wrap">
    <p class="ag-kicker">مشترک</p>
    <h2>آنچه در همهٔ نسخه‌ها هست</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($shared as $row)
        <article class="ag-card">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <p class="ag-kicker">هوش مصنوعی</p>
    <h2>دستیار هوشمندی که حسابداری بلد است</h2>
    <p class="ag-lead ag-lead--wide">دستیار داخل خود نرم‌افزار و روی کامپیوتر شما کار می‌کند؛ اطلاعات مالی‌تان به اینترنت فرستاده نمی‌شود.</p>
    <div class="ag-cards ag-cards--dense">
      @foreach($assistant as $row)
        <article class="ag-card">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </article>
      @endforeach
    </div>
    @include('studio::accounting-hub-demos', ['n' => 2])
  </div>
</section>

<section class="ag-sec" id="network">
  <div class="ag-wrap">
    <p class="ag-kicker">شبکه</p>
    <h2>یک نرم‌افزار برای کل مجموعه</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($network as $row)
        <article class="ag-card">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="migrate">
  <div class="ag-wrap">
    <p class="ag-kicker">انتقال</p>
    <h2>اطلاعات قبلی‌تان را می‌آوریم</h2>
    <p class="ag-lead ag-lead--wide">انتقال اطلاعات از نرم‌افزارهای هلو، آرتا و پیو؛ و ورود اطلاعات کالا و اشخاص از Excel. [[اگر نرم‌افزارهای دیگری را هم با خدمات انتقال پشتیبانی می‌کنید، این‌جا نام ببرید]]</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/computer-accounting/data-migration') }}">مراحل مهاجرت داده</a>
    </div>
  </div>
</section>

<section class="ag-sec" id="trust">
  <div class="ag-wrap">
    <p class="ag-kicker">اعتماد</p>
    <h2>چرا حسابداری HDD Land</h2>
    <div class="ag-stats">
      <div><b>۱۸ سال</b><span>طراحی نرم‌افزار</span></div>
      <div><b>۴۱۸۹</b><span>شمارهٔ ثبت شرکت</span></div>
      <div><b>[[تعداد مشتری]]</b><span>مشتری</span></div>
      <div><b>[[مدت پشتیبانی]]</b><span>پشتیبانی</span></div>
    </div>
    <p class="ag-note">شرکت فراز نت آمل (سرزمین هارد) · شناسهٔ صنفی ۱۴۰۵۳۱۳۳۷۹</p>
  </div>
</section>

<section class="ag-sec" id="faq">
  <div class="ag-wrap">
    <p class="ag-kicker">پرسش‌ها</p>
    <h2>پرسش‌های رایج</h2>
    <ul class="ag-steps" style="grid-template-columns:1fr">
      @foreach($faqs as $row)
        <li>
          <b>{{ $row['q'] }}</b>
          <p>{{ $row['a'] }}</p>
        </li>
      @endforeach
    </ul>
  </div>
</section>
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@include('studio::accounting-hub-demo-script')

<section class="ag-sec ag-sec--last" id="demo">
  <div class="ag-wrap">
    <p class="ag-kicker">دمو</p>
    <h2>آمادهٔ دیدن نرم‌افزار هستید؟</h2>
    <p class="ag-lead">یک دموی کوتاه با صنف خودتان نشان‌تان می‌دهیم.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
  </div>
</section>
