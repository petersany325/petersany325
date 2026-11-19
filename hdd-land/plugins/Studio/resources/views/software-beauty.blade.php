@php
  $faqs = [
      ['آیا حساب مطب به اینترنت نیاز دارد؟', 'خیر؛ برنامه کاملاً آفلاین کار می‌کند. فقط ارسال پیامک و امکان‌هایی که به اینترنت وابسته‌اند، اتصال می‌خواهند.'],
      ['روی چند سیستم نصب می‌شود؟', 'طبق لایسنس خریداری‌شده. برای چند کاربر همزمان، نسخهٔ چندکاربره یا شبکه در نظر گرفته شده است. [[جزئیات نسخه را صاحب سایت مشخص کند]]'],
      ['اطلاعات بیماران کجا ذخیره می‌شود؟', 'روی سیستم یا سرور خود کلینیک. اطلاعات بیماران برای کار برنامه به اینترنت فرستاده نمی‌شود.'],
      ['پیامک با کدام پنل کار می‌کند؟', 'با پنل نیاز پرداز و امکان اتصال به پنل‌های رایج دیگر. برای هر رویداد می‌توان پیامک را روشن یا خاموش کرد و متن را ویرایش نمود.'],
      ['صدای فراخوان نوبت چطور کار می‌کند؟', 'صدای فارسی آماده داخل برنامه است و بدون اینترنت پخش می‌شود. اعلام به‌صورت «شماره …» و «نوبت خانم/آقای …» بر اساس جنسیت بیمار انجام می‌شود. فراخوان را می‌توان خاموش کرد یا دستی اجرا کرد.'],
      ['آموزش و پشتیبانی چطور است؟', 'در هر پنجره دکمهٔ آموزش و کلید F1، راهنمای روی دکمه‌ها، تور بار اول، سناریوی کامل منشی و مدیر، و مرکز آموزش قابل جستجو داخل خود برنامه قرار دارد. پشتیبانی تلفنی و از راه دور هم ارائه می‌شود.'],
      ['نسخهٔ دندانپزشکی هم دارید؟', 'بله؛ حساب مطب نسخهٔ دندانپزشکی هم دارد. برای مقایسه و دمو با ما تماس بگیرید.'],
      ['سیستم مورد نیاز چیست و قیمت چقدر است؟', 'ویندوز ۱۰ یا ۱۱. پایگاه‌داده همراه برنامه نصب می‌شود. [[قیمت یا «برای استعلام تماس بگیرید»]]'],
  ];
  $graph = [
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'SoftwareApplication',
              'name' => 'حساب مطب — نسخه پزشکی زیبایی',
              'alternateName' => ['HesabMatab Beauty', 'نرم‌افزار مدیریت کلینیک زیبایی'],
              'applicationCategory' => 'MedicalApplication',
              'applicationSubCategory' => 'BusinessApplication',
              'operatingSystem' => 'Windows',
              'inLanguage' => 'fa',
              'url' => 'https://hdd-land.ir/software/medical-beauty',
              'description' => 'نرم‌افزار مدیریت کلینیک زیبایی و لیزر: ثبت بیمار، نوبت‌دهی، شماره نوبت و فراخوان صوتی فارسی، پرونده و گزارش بیمار، تسویه و نوبت بعدی، پیامک خودکار، انبار و سطح دسترسی؛ کاملاً آفلاین.',
              'publisher' => ['@type' => 'Organization', 'name' => 'فراز نت آمل', 'url' => 'https://hdd-land.ir/', 'telephone' => '+981144447220'],
              'offers' => ['@type' => 'Offer', 'priceCurrency' => 'IRR', 'availability' => 'https://schema.org/InStock', 'url' => 'https://hdd-land.ir/contact', 'description' => '[[قیمت نسخه یا عبارت «برای استعلام تماس بگیرید»]]'],
          ],
          [
              '@type' => 'BreadcrumbList',
              'itemListElement' => [
                  ['@type' => 'ListItem', 'position' => 1, 'name' => 'نرم‌افزارها', 'item' => 'https://hdd-land.ir/computer-accounting'],
                  ['@type' => 'ListItem', 'position' => 2, 'name' => 'نرم‌افزارهای پزشکی', 'item' => 'https://hdd-land.ir/software/medical-beauty'],
                  ['@type' => 'ListItem', 'position' => 3, 'name' => 'زیبایی', 'item' => 'https://hdd-land.ir/software/medical-beauty'],
              ],
          ],
          [
              '@type' => 'FAQPage',
              'mainEntity' => array_map(fn ($row) => [
                  '@type' => 'Question',
                  'name' => $row[0],
                  'acceptedAnswer' => ['@type' => 'Answer', 'text' => $row[1]],
              ], $faqs),
          ],
      ],
  ];
  $tabs = [
      ['reg', 'پذیرش و ثبت بیمار', 'منوی جدا برای ثبت بیمار، جستجوی سریع و یک پیامک خوش‌آمد.', [
          'منوی مستقل ثبت بیمار، جدا از نوبت‌دهی',
          'جستجو با نام، موبایل یا کد ملی',
          'با ثبت بیمار جدید، فقط یک‌بار پیامک خوش‌آمد ارسال می‌شود',
          'بلافاصله پس از ثبت می‌توان نوبت همان بیمار را گرفت',
      ]],
      ['apt', 'نوبت‌دهی', 'یک صفحهٔ ساده در چهار مرحله، از جستجوی بیمار تا تأیید و پیامک.', [
          'جستجوی بیمار، انتخاب خدمت و پزشک، تاریخ و ساعت، پیش‌نمایش و تأیید',
          'کانال رزرو: تلفنی، حضوری یا اینترنتی',
          'زمان‌های پیشنهادی بر اساس برنامهٔ کاری پزشک',
          'پس از تأیید، پیامک با تاریخ و ساعت دقیق نوبت ارسال می‌شود',
      ]],
      ['q', 'شماره نوبت و فراخوان', 'شماره خودکار روزانه و اعلام فارسی آفلاین در سالن انتظار.', [
          'شماره نوبت خودکار از ۱ تا ۱۰۰۰؛ برای هر روز و هر واحد جدا، بدون تکرار در همان روز',
          'نمایش شماره در لیست نوبت و در متن پیامک',
          'فراخوان با صدای فارسی داخل برنامه و بدون اینترنت: «شماره …» و «نوبت خانم/آقای …» بر اساس جنسیت',
          'امکان خاموش کردن فراخوان یا اعلام دستی',
          'نمایشگر سالن انتظار روی مانیتور دوم یا تلویزیون',
      ]],
      ['pay', 'تسویه و نوبت بعدی', '', [
          'با اعلام «انجام شد»، پنجرهٔ تسویه باز می‌شود',
          'ثبت نوبت بعدی همان‌جا، داخل پنجرهٔ تسویه',
          'پیامک تاریخ و ساعت نوبت بعدی پس از ثبت',
      ]],
      ['rep', 'پرونده و گزارش', '', [
          'سابقهٔ کامل مراجعات: تاریخ ویزیت، خدمات، ساعت ورود و خروج، پزشک',
          'مبلغ و وضعیت تسویه، کانال رزرو و شماره نوبت',
          'فیلتر، چاپ و خروجی Excel',
      ]],
      ['svc', 'خدمات و قیمت‌ها', '', [
          'گروه‌بندی خدمات کاملاً قابل ویرایش است',
          'ساختار آماده: لیزر بانوان با زیرگروه دستگاه‌های دکا و تیتانیوم و نواحی مربوط',
          'سایر خدمات کلینیک را می‌توان با قیمت دلخواه اضافه کرد',
      ]],
      ['sms', 'پیامک', '', [
          'اتصال به پنل نیاز پرداز و امکان اتصال به پنل‌های رایج دیگر',
          'برای هر رویداد، روشن یا خاموش کردن پیامک و ویرایش متن',
          'پیش‌نمایش متن پیش از ارسال',
          'نشانه‌های قابل جایگزینی از جمله تاریخ، ساعت و شماره نوبت',
      ]],
      ['inv', 'انبار و فروش', '', [
          'فروش محصول کنار خدمات و صدور فاکتور',
          'مدیریت موجودی انبار',
          'قیمت خرید کالا فقط برای مدیر یا کاربران دارای مجوز نمایش داده می‌شود',
      ]],
      ['sec', 'کاربران و امنیت', '', [
          'سطح دسترسی جدا برای هر کاربر',
          'لایسنس امضاشده و فعال‌سازی روی همان سیستم',
          'اطلاعات روی سیستم خود کلینیک می‌ماند',
          'هنگام خروج، برنامه پیشنهاد پشتیبان‌گیری می‌دهد',
      ]],
      ['help', 'آموزش داخل برنامه', '', [
          'دکمهٔ آموزش و کلید F1 در هر پنجره',
          'راهنمای روی دکمه‌ها',
          'تور آشنایی در اجرای اول',
          'سناریوی کامل کار منشی و مدیر',
          'مرکز آموزش فارسی قابل جستجو، داخل خود برنامه',
      ]],
  ];
  $shots = [
      'نوبت‌دهی چهارمرحله‌ای',
      'شماره نوبت و کانال رزرو (تلفنی، حضوری، اینترنتی)',
      'تسویه و ثبت نوبت بعدی',
      'گزارش کامل بیمار',
      'تنظیمات پیامک رویدادها',
      'نمایشگر سالن انتظار',
      'مرکز آموزش فارسی داخل برنامه',
  ];
  $staff = auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff();
@endphp
<section class="ag-page" id="top">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">نرم‌افزارها</a>
      <span>/</span>
      <span>نرم‌افزارهای پزشکی</span>
      <span>/</span>
      <span>زیبایی</span>
    </p>
    <p class="ag-kicker">نرم‌افزارهای پزشکی · نسخهٔ کلینیک زیبایی · حساب مطب</p>
    <h1>نرم‌افزار مدیریت کلینیک زیبایی؛ نوبت، پرونده، تسویه و پیامک در یک برنامه</h1>
    <p class="ag-lead ag-lead--wide">برای کلینیک‌های زیبایی، لیزر و مطب‌های پوست و مو: از ثبت بیمار تا نوبت، فراخوان صوتی، انجام خدمت، تسویه و نوبت بعدی، با پیامک خودکار و آموزش کامل فارسی داخل برنامه؛ بدون نیاز به اینترنت.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="#demo">درخواست دموی رایگان<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">مشاهدهٔ امکانات</a>
    </div>
    @include('studio::sale-bar', ['sku' => 'med-beauty'])
    <p class="ag-note">نوبت‌دهی سه‌کاناله · فراخوان صوتی فارسی · پیامک خودکار · آفلاین</p>
    @if($staff)
      <p class="ag-note">برای نمایندهٔ واردشده: سهم این نسخه ۱۲٪ است. این جمله در صفحهٔ عمومی دیده نمی‌شود.</p>
    @endif
    <div class="cd" id="by1">
      <div class="cd-bar">
        <div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div>
        <div class="cd-t">حساب مطب · کلینیک زیبایی</div>
        <div class="cd-r">کلینیک نمونه رز · امروز <span class="by-sample">نمونه</span></div>
      </div>
      <div class="by-hero">
        <div class="by-q">
          <div class="by-now">
            <div class="by-k">در حال فراخوان</div>
            <div class="by-num" id="byHeroNum">—</div>
            <div class="by-who" id="byHeroWho">منتظر شروع…</div>
            <div class="by-room" id="byHeroRoom"></div>
            <div class="by-voice" id="byHeroVoice"></div>
          </div>
        </div>
        <div class="cd-feed">
          <div class="cd-kpis">
            <div class="cd-kpi"><div class="k">نوبت‌های امروز</div><div class="v num" id="byK1">۰</div></div>
            <div class="cd-kpi"><div class="k">در انتظار</div><div class="v num" id="byK2">۰</div></div>
            <div class="cd-kpi"><div class="k">شماره در حال فراخوان</div><div class="v num" id="byK3">۰</div></div>
            <div class="cd-kpi"><div class="k">درآمد امروز (میلیون ریال)</div><div class="v num" id="byK4">۰</div></div>
          </div>
          <div id="byFeed"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ag-sec" id="fit">
  <div class="ag-wrap">
    <h2>برای چه کسانی مناسب است</h2>
    <div class="ag-cards ag-cards--dense by-cards-4">
      <article class="ag-card"><div><b>کلینیک‌های زیبایی و لیزر</b><p>لیزر بانوان با زیرگروه دستگاه (دکا و تیتانیوم) و نواحی درمان؛ ثبت خدمت، نوبت و پرونده در یک مسیر.</p></div></article>
      <article class="ag-card"><div><b>مطب‌های پوست، مو و تزریقات</b><p>ثبت خدمات دلخواه کلینیک (مثل فیلر، بوتاکس و جوان‌سازی)، نوبت پزشک و سابقهٔ هر مراجعه.</p></div></article>
      <article class="ag-card"><div><b>کلینیک‌های چندپزشکه</b><p>برنامهٔ کاری هر پزشک، نوبت و گزارش جدا، و شماره نوبت روزانه برای هر واحد.</p></div></article>
      <article class="ag-card"><div><b>کلینیک‌هایی که فروش محصول دارند</b><p>انبار و فروش محصول کنار خدمات؛ قیمت خرید فقط برای مدیر یا کاربر مجاز دیده می‌شود.</p></div></article>
    </div>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="beauty-tab" id="bt-{{ $tab[0] }}" @checked($i === 0)>
        <label for="bt-{{ $tab[0] }}">{{ $tab[1] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab[1] }}</h2>
          @if($tab[2] !== '')<p class="ag-lead">{{ $tab[2] }}</p>@endif
          <ul class="acc-cols">
            @foreach($tab[3] as $line)<li>{{ $line }}</li>@endforeach
          </ul>
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="special">
  <div class="ag-wrap">
    <h2>ویژهٔ نسخهٔ زیبایی</h2>
    <p class="ag-lead ag-lead--wide">ماکت‌های زیر روند واقعی برنامه را نشان می‌دهند. نام‌ها، شماره‌ها و مبلغ‌ها همگی نمونه‌اند.</p>
    <div class="by-path" id="byPath" aria-label="مسیر کار منشی">
      <div class="st"><b>۱</b>ثبت بیمار</div>
      <div class="st"><b>۲</b>نوبت</div>
      <div class="st"><b>۳</b>حضور</div>
      <div class="st"><b>۴</b>فراخوان</div>
      <div class="st"><b>۵</b>انجام شد</div>
      <div class="st"><b>۶</b>تسویه و نوبت بعدی</div>
    </div>
    <p class="ag-note">پیشنهاد صفحه: نمایشگر سالن فقط شماره، نام و اتاق را می‌گوید. نام خدمت و ناحیهٔ درمان (مثلاً صورت یا بدن) در سالن انتظار خوانده نمی‌شود و فقط روی صفحهٔ منشی می‌ماند.</p>
    <div class="by-grid">
      <div class="cd" id="byQ">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">نمایشگر سالن انتظار</div><div class="cd-r">اتاق لیزر ۱ <span class="by-sample">نمونه</span></div></div>
        <div class="by-q">
          <div class="by-now">
            <div class="by-k">در حال پذیرش</div>
            <div class="by-num" id="byQNum">—</div>
            <div class="by-who" id="byQWho">در انتظار فراخوان</div>
            <div class="by-room" id="byQRoom"></div>
            <div class="by-voice" id="byQVoice"></div>
          </div>
          <div class="by-queue" id="byQNext"></div>
        </div>
      </div>
      <div class="cd" id="byRsv">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">رزرو نوبت در چهار مرحله</div><div class="cd-r"><span class="by-sample">نمونه</span></div></div>
        <div class="by-rsv">
          <div class="cd-steps" id="byRsvSteps">
            <div class="cd-stp"><b>۱</b> جستجوی بیمار</div>
            <div class="cd-stp"><b>۲</b> خدمت و پزشک</div>
            <div class="cd-stp"><b>۳</b> تاریخ و ساعت</div>
            <div class="cd-stp"><b>۴</b> تأیید و پیامک</div>
          </div>
          <div class="by-search"><div class="box">جستجو با نام، موبایل یا کد ملی: <b id="byRsvQ"></b></div><div class="by-hit" id="byRsvHit">خانم سارا رضایی <em>۰۹۱۲۰۰۰۰۰۰۰ · نمونه</em></div></div>
          <div class="by-card"><div class="by-opts" id="byRsvOpts"></div><div class="by-sum" id="byRsvSum"></div></div>
        </div>
      </div>
      <div class="cd" id="bySms">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">پیامک تأیید نوبت</div><div class="cd-r">نیاز پرداز <span class="by-sample">نمونه</span></div></div>
        <div class="by-ph-wrap"><div class="by-ph" aria-hidden="true"><div class="by-notch"></div><div class="by-screen"><div class="by-top"><span>۱۱:۲۰</span><span>ایرانسل</span></div><div class="by-from">کلینیک نمونه رز</div><div class="by-sms" id="bySmsText"></div></div></div></div>
      </div>
      <div class="cd" id="bySet">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">تسویه و نوبت بعدی</div><div class="cd-r"><span class="by-sample">نمونه</span></div></div>
        <div class="by-set">
          <div class="cd-steps" id="bySetSteps">
            <div class="cd-stp"><b>۱</b> انجام شد</div>
            <div class="cd-stp"><b>۲</b> تسویه</div>
            <div class="cd-stp"><b>۳</b> نوبت بعدی</div>
            <div class="cd-stp"><b>۴</b> پیامک</div>
          </div>
          <div class="by-row"><span>بیمار</span><b>خانم سارا رضایی</b></div>
          <div class="by-row"><span>خدمت</span><b>لیزر بانوان · تیتانیوم · صورت</b></div>
          <div class="by-row"><span>پزشک</span><b>دکتر نمونه</b></div>
          <div class="by-row"><span>مبلغ (نمونه)</span><b class="num" id="bySetAmt">—</b></div>
          <div class="by-pay" id="bySetPay"></div>
          <div class="by-next" id="bySetNext">نوبت بعدی هنوز ثبت نشده است.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ag-sec" id="security">
  <div class="ag-wrap">
    <h2>امنیت و لایسنس</h2>
    <ul class="ag-list">
      <li>لایسنس با امضای دیجیتال صادر و روی همان سیستم فعال می‌شود.</li>
      <li>اطلاعات بیماران روی سیستم یا سرور خود کلینیک است و برای کار برنامه به اینترنت ارسال نمی‌شود.</li>
      <li>هر کاربر فقط همان بخش‌ها و اعمالی را می‌بیند که به او داده شده؛ قیمت خرید فقط در اختیار مدیر یا کاربر مجاز است.</li>
      <li>پیش از خروج می‌توان نسخهٔ پشتیبان گرفت.</li>
      <li>تغییرات مهم در سیستم ثبت می‌شود.</li>
    </ul>
  </div>
</section>

<section class="ag-sec" id="compare">
  <div class="ag-wrap">
    <h2>مقایسه با نسخهٔ دندانپزشکی و سطوح لایسنس</h2>
    <div class="ag-table-wrap">
      <table class="ag-cmp ag-cmp--two">
        <thead><tr><th>امکان</th><th>نسخهٔ زیبایی</th><th>نسخهٔ دندانپزشکی</th></tr></thead>
        <tbody>
          <tr><th>ثبت بیمار، نوبت‌دهی، پرونده</th><td>هست</td><td>هست</td></tr>
          <tr><th>تسویه و نوبت بعدی</th><td>هست</td><td>هست</td></tr>
          <tr><th>پیامک خودکار</th><td>هست</td><td>هست</td></tr>
          <tr><th>شماره نوبت و فراخوان صوتی فارسی</th><td>هست</td><td>[[طبق نسخه]]</td></tr>
          <tr><th>نمایشگر سالن انتظار</th><td>هست</td><td>[[طبق نسخه]]</td></tr>
          <tr><th>خدمات آمادهٔ لیزر بانوان (دکا و تیتانیوم)</th><td>هست</td><td>—</td></tr>
          <tr><th>انبار و فروش محصول</th><td>هست</td><td>[[طبق نسخه]]</td></tr>
        </tbody>
      </table>
    </div>
    <p class="ag-note">فیزیوتراپی، روانشناسی و دندانپزشکی در حساب مطب همین سایت هستند: <a href="{{ url('/software/medical-physiotherapy') }}">فیزیوتراپی</a> · <a href="{{ url('/software/medical-psychology') }}">روانشناسی</a> · <a href="{{ url('/software/medical-dental') }}">دندانپزشکی</a> · <a href="{{ url('/ai') }}">هوش مصنوعی</a></p>
    <p class="ag-note">تقسیم دقیق امکانات بین سطوح لایسنس و قیمت را صاحب سایت مشخص می‌کند. تا آن زمان سلول‌ها جای‌نگهدار می‌مانند.</p>
    <div class="ag-table-wrap">
      <table class="ag-cmp ag-cmp--two">
        <thead><tr><th>امکان</th><th>پایه</th><th>حرفه‌ای</th><th>چندکاربره / شبکه</th></tr></thead>
        <tbody>
          <tr><th>ثبت بیمار و نوبت‌دهی</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>گزارش بیمار</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>تسویه و نوبت بعدی</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>پیامک خودکار</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>شماره نوبت و فراخوان صوتی</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>نمایشگر سالن انتظار</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>انبار و فروش محصول</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>چند کاربر همزمان</th><td>—</td><td>—</td><td>هست</td></tr>
          <tr><th>قیمت</th><td>[[قیمت / تماس بگیرید]]</td><td>[[قیمت / تماس بگیرید]]</td><td>[[قیمت / تماس بگیرید]]</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="ag-sec" id="gallery">
  <div class="ag-wrap">
    <h2>تصاویر برنامه</h2>
    <p class="ag-lead">تا رسیدن اسکرین‌شات‌های واقعی، هر کادر فقط کپشن است.</p>
    <div class="by-gal">
      @foreach($shots as $i => $cap)
        <figure>
          <div class="by-shot" role="img" aria-label="{{ $cap }}">تصویر {{ ['۱','۲','۳','۴','۵','۶','۷'][$i] }}</div>
          <figcaption>{{ $cap }}</figcaption>
        </figure>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="faq">
  <div class="ag-wrap">
    <h2>پرسش‌های رایج</h2>
    <ul class="ag-steps" style="grid-template-columns:1fr">
      @foreach($faqs as $row)
        <li><b>{{ $row[0] }}</b><p>{{ $row[1] }}</p></li>
      @endforeach
    </ul>
  </div>
</section>
<script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

<section class="ag-sec ag-sec--last" id="demo">
  <div class="ag-wrap">
    <h2>نسخهٔ کلینیک زیبایی را با خدمات کلینیک خودتان ببینید</h2>
    <p class="ag-lead ag-lead--wide">دموی رایگان؛ خدمات کلینیک شما را در برنامه می‌چینیم تا پیش از خرید، کار با نوبت‌دهی، فراخوان، تسویه و پیامک را ببینید.</p>
    @if(session('success'))<p class="ag-note">{{ session('success') }}</p>@endif
    @if(session('error'))<p class="ag-note">{{ session('error') }}</p>@endif
    <div class="by-demo">
      <form class="by-form" id="byDemo" method="post" action="{{ url('/support') }}">
        @csrf
        <label>نام<input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required></label>
        <label>موبایل<input type="tel" name="phone" id="byPhone" value="{{ old('phone') }}" inputmode="tel" autocomplete="tel" required placeholder="۰۹۱۲xxxxxxx"></label>
        <label>نام کلینیک<input type="text" name="clinic" id="byClinic" value="{{ old('clinic') }}" required></label>
        <label>شهر<input type="text" name="city" id="byCity" value="{{ old('city') }}" required></label>
        <input type="hidden" name="subject" value="درخواست دمو حساب مطب زیبایی">
        <input type="hidden" name="body" id="byBody" value="درخواست دموی نسخهٔ کلینیک زیبایی.">
        <div class="full"><button class="ag-btn ag-btn--on" type="submit">درخواست دمو<i>←</i></button></div>
      </form>
      <div class="by-side">
        <p>تماس مستقیم</p>
        <a href="tel:01144447220">۰۱۱۴۴۴۴۷۲۲۰</a>
        <div class="ag-actions" style="margin-top:.8rem">
          <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس تلفنی</a>
          <a class="ag-btn ag-btn--line" href="{{ url('/contact') }}">صفحهٔ تماس</a>
        </div>
        <p class="ag-note">شمارهٔ فعلی خط ثابت آمل است؛ دکمهٔ واتساپ و ایتا بعد از اعلام شمارهٔ موبایل اضافه می‌شود.</p>
      </div>
    </div>
  </div>
</section>
@include('studio::software-beauty-script')
