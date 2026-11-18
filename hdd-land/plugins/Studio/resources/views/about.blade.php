@php
  $phone = '01144447220';
  $graph = [
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'Organization',
              'name' => 'شرکت فراز نت آمل',
              'alternateName' => ['سرزمین هارد', 'HDD Land'],
              'url' => 'https://hdd-land.ir',
              'telephone' => '+981144447220',
          ],
          [
              '@type' => 'LocalBusiness',
              'name' => 'شرکت فراز نت آمل',
              'alternateName' => ['سرزمین هارد', 'HDD Land'],
              'url' => 'https://hdd-land.ir/about',
              'telephone' => '+981144447220',
              'contactPoint' => [
                  ['@type' => 'ContactPoint', 'telephone' => '+989113100473', 'contactType' => 'sales', 'name' => 'مهندس رضانژاد'],
                  ['@type' => 'ContactPoint', 'telephone' => '+981144447220', 'contactType' => 'customer support'],
              ],
          ],
      ],
  ];
  $recovery = [
      'بازیابی اطلاعات هارد دیسک داخلی و اکسترنال',
      'بازیابی و تعمیر SSD، M.2 و NVMe',
      'بازیابی سرور، آرایه RAID و تجهیزات ذخیره‌سازی',
      'عیب‌یابی و تعمیر هارد در بخش الکترونیکی، مکانیکی و فریم‌ور',
      'بازیابی تجهیزات ذخیره‌سازی سازمانی و صنعتی',
      'بازیابی حافظه و دیگر رسانه‌های ذخیره‌سازی دیجیتال',
  ];
@endphp
<link rel="stylesheet" href="{{ asset('css/studio-agency.css') }}?v=57">
<div class="ab">
  <div class="ab-wrap">
    <section class="ab-hero">
      <div>
        <div class="ab-kick">شرکت فراز نت آمل · سرزمین هارد (HDD Land)</div>
        <h1>از ۲۰ سال نجات اطلاعات<br>تا <em>حسابداری هوشمند فارسی</em></h1>
        <p class="ab-lead">کارمان را با بازیابی اطلاعات و تعمیر هارد شروع کردیم. امروز سایت و نرم‌افزار می‌سازیم: حسابداری با منطق هر صنف و دستیاری که روی کامپیوتر خودتان، بدون اینترنت، جواب می‌دهد.</p>
        <div class="ab-btns">
          <a class="ab-btn p" href="{{ url('/contact') }}">تماس با ما</a>
          <a class="ab-btn s" href="#abPath">مسیر ما ↓</a>
        </div>
      </div>
      <div class="ab-stage" id="abStage">
        <div class="ab-halo" aria-hidden="true"></div>
        <div class="ab-ring" aria-hidden="true"></div>
        <img class="ab-robot" src="{{ asset('images/ai/r-hello.webp') }}" width="230" height="230" alt="دستیار HDD Land" fetchpriority="high" decoding="async">
        <div class="ab-chip c1"><b class="num">+۲۰ سال</b>بازیابی اطلاعات</div>
        <div class="ab-chip c2"><b class="num">۱۸ سال</b>سایت و نرم‌افزار</div>
        <div class="ab-chip c3"><b>آفلاین</b>دستیار روی سیستم شما</div>
      </div>
    </section>

    <section id="abPath">
      <div class="ab-kick">مسیر ما</div>
      <h2>چطور به اینجا رسیدیم</h2>
      <div class="ab-tl" id="abTl">
        <div class="ab-fill" id="abFill"></div>
        <div class="ab-st"><div class="ab-dot">💽</div><div><span class="ab-when">بیش از ۲۰ سال پیش</span><h3>آزمایشگاه بازیابی اطلاعات</h3><p>تعمیر هارد در بخش الکترونیکی، مکانیکی و فریم‌ور؛ بعد SSD، NVMe و RAID.</p></div></div>
        <div class="ab-st"><div class="ab-dot">🌐</div><div><span class="ab-when">۱۸ سال پیش</span><h3>طراحی سایت و نرم‌افزار</h3><p>سایت شرکتی، فروشگاه اینترنتی و سامانهٔ اختصاصی تحت وب.</p></div></div>
        <div class="ab-st"><div class="ab-dot">📊</div><div><span class="ab-when">سپس</span><h3>حسابداری HDD Land</h3><p>نسخهٔ ویژهٔ هر صنف: شرکتی، فروشگاهی، رستوران، پخش، تولیدی و تعمیرکاران.</p></div></div>
        <div class="ab-st"><div class="ab-dot">🔧</div><div><span class="ab-when">سپس</span><h3>حسابداری وب و تعمیرگاه</h3><p>پذیرش، ارجاع به تعمیرکار و کارتابل مشتری، تحت وب.</p></div></div>
        <div class="ab-st"><div class="ab-dot">🤖</div><div><span class="ab-when">امروز</span><h3>دستیار هوشمند و اپ همراه</h3><p>دستیار فارسی آفلاین داخل حسابداری و اپ اندروید برای ویزیتور و مدیر.</p></div></div>
      </div>
    </section>

    <section>
      <div class="ab-kick">کار ما</div>
      <h2>سه کار، یک تیم</h2>
      <div class="ab-cols">
        <a class="ab-col ab-fade" href="{{ url('/contact') }}"><span class="ab-ic">💽</span><h3>بازیابی اطلاعات</h3>
          <ul>
            @foreach($recovery as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
          <span class="ab-go">درخواست بررسی ←</span></a>
        <a class="ab-col ab-fade" href="{{ url('/computer-accounting') }}"><span class="ab-ic">🧮</span><h3>نرم‌افزار و سایت</h3>
          <ul><li>حسابداری ویژهٔ هر صنف</li><li>حسابداری وب و تعمیرگاه</li><li>سایت، فروشگاه و سامانهٔ اختصاصی</li></ul>
          <div class="ab-pr"><span>حسابداری HDD Land</span><span>حسابداری تعمیرکاران</span><span>اپ همراه</span></div>
          <span class="ab-go">نرم‌افزارها ←</span></a>
        <a class="ab-col ab-fade" href="{{ url('/ai') }}"><span class="ab-ic">🤖</span><h3>هوش مصنوعی</h3>
          <ul><li>دستیار فارسی با صدا، داخل حسابداری</li><li>روی کامپیوتر خودتان، بدون اینترنت</li><li>هیچ ثبتی بدون تأیید شما انجام نمی‌شود</li></ul>
          <div class="ab-pr"><span>دستیار هوشمند فارسی</span></div>
          <span class="ab-go">آشنایی با دستیار ←</span></a>
      </div>
    </section>

    <section>
      <div class="ab-kick">آزمایشگاه</div>
      <h2>جایی که اطلاعات برمی‌گردد</h2>
      <p class="ab-muted">تجهیزات تخصصی، ابزار عیب‌یابی و نرم‌افزار حرفه‌ای، کنار تجربهٔ عملی.</p>
      <div class="ab-lab">
        <div class="ab-rec">
          <div class="ab-top"><b>بازیابی هارد ۲ ترابایتی</b><span class="ab-muted">نمایش نمونه</span></div>
          <div class="ab-sect" id="abSect"></div>
          <div class="ab-bar"><b id="abRbar"></b></div>
          <div class="ab-top ab-top--stat"><span>خوانده شد: <b class="num" id="abRp">۰</b>٪</span><span class="ab-muted" id="abRs">در حال خواندن…</span></div>
          <div class="ab-legend"><span class="lg1">سالم</span><span class="lg2">با تلاش دوباره</span><span class="lg3">آسیب‌دیده</span></div>
        </div>
      </div>
    </section>

    <section>
      <div class="ab-kick">چرا ما</div>
      <h2>چیزهایی که ما را جدا می‌کند</h2>
      <div class="ab-why">
        <div class="ab-fade"><span class="ab-e">🔒</span><b>اطلاعات پیش خودتان</b><p>دستیار و پردازش اسناد روی کامپیوتر خودتان انجام می‌شود، بدون فرستادن به اینترنت.</p></div>
        <div class="ab-fade"><span class="ab-e">🏪</span><b>منطق واقعی هر صنف</b><p>رستوران، پخش و تولیدی هر کدام منو و گزارش خودشان را دارند، نه یک قالب برای همه.</p></div>
        <div class="ab-fade"><span class="ab-e">🛠️</span><b>سخت‌افزار و نرم‌افزار با هم</b><p>هم هارد را تعمیر می‌کنیم و هم نرم‌افزاری را که رویش کار می‌کنید می‌سازیم.</p></div>
        <div class="ab-fade"><span class="ab-e">🤝</span><b>سازنده خودش پشتیبان است</b><p>نرم‌افزار را خودمان نوشته‌ایم و پشتیبانی هم با همان تیم است.</p></div>
      </div>
    </section>

    <section class="ab-trust-sec">
      <div class="ab-trust">
        <span>شمارهٔ ثبت <b class="num">۴۱۸۹</b></span>
        <span>شناسهٔ صنفی <b class="num">۱۴۰۵۳۱۳۳۷۹</b></span>
      </div>
    </section>

    <section class="ab-contact-sec">
      <div class="ab-kick">ارتباط با ما</div>
      <h2>تلفنی با دفتر یا مشاور فروش</h2>
      <div class="ab-contact">
        <div class="ab-card">
          <div class="ab-row"><span class="ab-k">مشاور فروش</span><span>مهندس رضانژاد · <a class="num" dir="ltr" href="tel:09113100473">۰۹۱۱ ۳۱۰ ۰۴۷۳</a></span></div>
          <div class="ab-row"><span class="ab-k">دفتر</span><a class="num" dir="ltr" href="tel:{{ $phone }}">۰۱۱ ۴۴۴۴ ۷۲۲۰</a></div>
        </div>
      </div>
    </section>
  </div>
</div>
<script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@include('studio::about-script')
