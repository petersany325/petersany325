@php
  $faqs = [
      ['آیا حساب مطب به اینترنت نیاز دارد؟', 'خیر؛ برنامه کاملاً آفلاین کار می‌کند. فقط ارسال پیامک و امکان‌هایی که به اینترنت وابسته‌اند، اتصال می‌خواهند.'],
      ['روی چند سیستم نصب می‌شود؟', 'طبق لایسنس خریداری‌شده. برای چند کاربر همزمان، نسخهٔ چندکاربره یا شبکه در نظر گرفته شده است. [[جزئیات نسخه را صاحب سایت مشخص کند]]'],
      ['اطلاعات مراجعان کجا ذخیره می‌شود؟', 'روی سیستم یا سرور خود مطب. اطلاعات مراجعان برای کار برنامه به اینترنت فرستاده نمی‌شود.'],
      ['پیامک با کدام پنل کار می‌کند؟', 'با پنل نیاز پرداز و امکان اتصال به پنل‌های رایج دیگر. برای هر رویداد می‌توان پیامک را روشن یا خاموش کرد و متن را ویرایش نمود.'],
      ['شماره نوبت چطور صادر می‌شود؟', 'با ثبت رزرو، شماره نوبت به‌صورت خودکار صادر می‌شود؛ شمارش برای هر روز و هر واحد جدا است و در لیست نوبت و پیامک دیده می‌شود.'],
      ['آموزش و پشتیبانی چطور است؟', 'در هر پنجره دکمهٔ آموزش و کلید F1، راهنمای روی دکمه‌ها، تور بار اول، سناریوی کامل منشی و مدیر، و مرکز آموزش قابل جستجو داخل خود برنامه قرار دارد. پشتیبانی تلفنی و از راه دور هم ارائه می‌شود.'],
      ['نسخهٔ زیبایی یا دندانپزشکی هم دارید؟', 'بله؛ حساب مطب نسخه‌های زیبایی و دندانپزشکی هم دارد. برای مقایسه و دمو با ما تماس بگیرید.'],
      ['سیستم مورد نیاز چیست و قیمت چقدر است؟', 'ویندوز ۱۰ یا ۱۱. پایگاه‌داده همراه برنامه نصب می‌شود. [[قیمت یا «برای استعلام تماس بگیرید»]]'],
  ];
  $graph = [
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'SoftwareApplication',
              'name' => 'حساب مطب — نسخه روانشناسی',
              'alternateName' => ['HesabMatab Psychology', 'نرم‌افزار مدیریت مطب روانشناسی'],
              'applicationCategory' => 'MedicalApplication',
              'applicationSubCategory' => 'BusinessApplication',
              'operatingSystem' => 'Windows',
              'inLanguage' => 'fa',
              'url' => 'https://hdd-land.ir/software/medical-psychology',
              'description' => 'نرم‌افزار مدیریت مطب و کلینیک روانشناسی: ثبت مراجع، نوبت‌دهی، شماره نوبت، پرونده و گزارش، تسویه و نوبت جلسهٔ بعد، پیامک خودکار و سطح دسترسی؛ کاملاً آفلاین.',
              'publisher' => ['@type' => 'Organization', 'name' => 'فراز نت آمل', 'url' => 'https://hdd-land.ir/', 'telephone' => '+981144447220'],
              'offers' => ['@type' => 'Offer', 'priceCurrency' => 'IRR', 'availability' => 'https://schema.org/InStock', 'url' => 'https://hdd-land.ir/contact', 'description' => '[[قیمت نسخه یا عبارت «برای استعلام تماس بگیرید»]]'],
          ],
          [
              '@type' => 'BreadcrumbList',
              'itemListElement' => [
                  ['@type' => 'ListItem', 'position' => 1, 'name' => 'نرم‌افزارها', 'item' => 'https://hdd-land.ir/computer-accounting'],
                  ['@type' => 'ListItem', 'position' => 2, 'name' => 'نرم‌افزارهای پزشکی', 'item' => 'https://hdd-land.ir/software/medical-psychology'],
                  ['@type' => 'ListItem', 'position' => 3, 'name' => 'روانشناسی', 'item' => 'https://hdd-land.ir/software/medical-psychology'],
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
      ['reg', 'پذیرش و ثبت مراجع', 'منوی جدا برای ثبت مراجع، جستجوی سریع و یک پیامک خوش‌آمد.', [
          'منوی مستقل ثبت مراجع، جدا از نوبت‌دهی',
          'جستجو با نام، موبایل یا کد ملی',
          'با ثبت مراجع جدید، فقط یک‌بار پیامک خوش‌آمد ارسال می‌شود',
          'بلافاصله پس از ثبت می‌توان نوبت همان مراجع را گرفت',
      ]],
      ['apt', 'نوبت‌دهی جلسه', 'یک صفحهٔ ساده در چهار مرحله، از جستجوی مراجع تا تأیید و پیامک.', [
          'جستجوی مراجع، انتخاب خدمت و درمانگر، تاریخ و ساعت، پیش‌نمایش و تأیید',
          'کانال رزرو: تلفنی، حضوری یا اینترنتی',
          'زمان‌های پیشنهادی بر اساس برنامهٔ کاری درمانگر',
          'پس از تأیید، پیامک با تاریخ و ساعت دقیق جلسه ارسال می‌شود',
      ]],
      ['q', 'شماره نوبت', 'شماره خودکار روزانه، جدا برای هر واحد.', [
          'شماره نوبت خودکار از ۱ تا ۱۰۰۰',
          'شمارش برای هر روز و هر واحد جدا است و همان روز تکرار نمی‌شود',
          'شماره در لیست نوبت و در متن پیامک دیده می‌شود',
      ]],
      ['pay', 'تسویه و جلسهٔ بعد', '', [
          'با اعلام «انجام شد»، پنجرهٔ تسویه باز می‌شود',
          'ثبت جلسهٔ بعد همان‌جا، داخل پنجرهٔ تسویه',
          'پیامک تاریخ و ساعت جلسهٔ بعد پس از ثبت',
      ]],
      ['rep', 'پرونده و گزارش', '', [
          'سابقهٔ کامل مراجعات: تاریخ جلسه، خدمات، ساعت ورود و خروج، درمانگر',
          'مبلغ و وضعیت تسویه، کانال رزرو و شماره نوبت',
          'فیلتر، چاپ و خروجی Excel',
      ]],
      ['sms', 'پیامک', '', [
          'اتصال به پنل نیاز پرداز و امکان اتصال به پنل‌های رایج دیگر',
          'برای هر رویداد، روشن یا خاموش کردن پیامک و ویرایش متن',
          'پیش‌نمایش متن پیش از ارسال',
          'نشانه‌های قابل جایگزینی از جمله تاریخ، ساعت و شماره نوبت',
      ]],
      ['sec', 'کاربران و امنیت', '', [
          'سطح دسترسی جدا برای هر کاربر',
          'لایسنس امضاشده و فعال‌سازی روی همان سیستم',
          'اطلاعات روی سیستم خود مطب می‌ماند',
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
  $shots = ['نوبت‌دهی چهارمرحله‌ای','شماره نوبت و کانال رزرو','تسویه و ثبت جلسهٔ بعد','گزارش کامل مراجع','تنظیمات پیامک رویدادها','مرکز آموزش فارسی داخل برنامه'];
  $staff = auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff();
@endphp
<div class="ps">
<section class="ag-page" id="top">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">نرم‌افزارها</a>
      <span>/</span>
      <span>نرم‌افزارهای پزشکی</span>
      <span>/</span>
      <span>روانشناسی</span>
    </p>
    <p class="ag-kicker">نرم‌افزارهای پزشکی · نسخهٔ مطب روانشناسی · حساب مطب</p>
    <h1>نرم‌افزار مدیریت مطب روانشناسی؛ نوبت جلسه، پرونده، تسویه و پیامک در یک برنامه</h1>
    <p class="ag-lead ag-lead--wide">برای مطب‌ها و کلینیک‌های روانشناسی و مشاوره: از ثبت مراجع تا نوبت جلسه، انجام خدمت، تسویه و جلسهٔ بعد، با پیامک خودکار و آموزش کامل فارسی داخل برنامه؛ بدون نیاز به اینترنت.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="#demo">درخواست دموی رایگان<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">مشاهدهٔ امکانات</a>
    </div>
    <p class="ag-note">نوبت‌دهی سه‌کاناله · شماره نوبت خودکار · پیامک خودکار · آفلاین</p>
    @if($staff)
      <p class="ag-note">برای نمایندهٔ واردشده: سهم این نسخه ۱۲٪ است. این جمله در صفحهٔ عمومی دیده نمی‌شود.</p>
    @endif
    <div class="cd" id="ps1">
      <div class="cd-bar">
        <div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div>
        <div class="cd-t">حساب مطب · نسخهٔ روانشناسی</div>
        <div class="cd-r">مطب نمونه آرامش · امروز <span class="ps-sample">نمونه</span></div>
      </div>
      <div class="ps-hero">
        <div class="ps-board">
          <div class="ps-k">نوبت جاری</div>
          <div class="ps-num" id="psHeroNum">—</div>
          <div class="ps-who" id="psHeroWho">منتظر شروع…</div>
          <div class="ps-room" id="psHeroRoom"></div>
          <div class="ps-line" id="psHeroLine"></div>
        </div>
        <div class="cd-feed">
          <div class="cd-kpis">
            <div class="cd-kpi"><div class="k">جلسات امروز</div><div class="v num" id="psK1">۰</div></div>
            <div class="cd-kpi"><div class="k">در انتظار</div><div class="v num" id="psK2">۰</div></div>
            <div class="cd-kpi"><div class="k">نوبت جاری</div><div class="v num" id="psK3">۰</div></div>
            <div class="cd-kpi"><div class="k">درآمد امروز (میلیون ریال)</div><div class="v num" id="psK4">۰</div></div>
          </div>
          <div id="psFeed"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ag-sec" id="fit">
  <div class="ag-wrap">
    <h2>برای چه کسانی مناسب است</h2>
    <div class="ag-cards ag-cards--dense ps-cards-4">
      <article class="ag-card"><div><b>مطب‌های روانشناسی و مشاوره</b><p>ثبت مراجع، نوبت جلسه، تسویه و سابقهٔ مراجعات در یک برنامه.</p></div></article>
      <article class="ag-card"><div><b>کلینیک‌های چنددرمانگر</b><p>برنامهٔ کاری هر درمانگر، نوبت و گزارش جدا، و شماره نوبت روزانه برای هر واحد.</p></div></article>
      <article class="ag-card"><div><b>مراکز مشاورهٔ خانواده و زوج</b><p>همان مسیر نوبت و پرونده، با خدمات و زمان‌بندی قابل تعریف خود مرکز.</p></div></article>
      <article class="ag-card"><div><b>مطب‌هایی که منشی و چند کاربر دارند</b><p>سطح دسترسی جدا برای هر کاربر؛ اطلاعات روی سیستم خود مطب می‌ماند.</p></div></article>
    </div>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="psy-tab" id="pt-{{ $tab[0] }}" @checked($i === 0)>
        <label for="pt-{{ $tab[0] }}">{{ $tab[1] }}</label>
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
    <h2>روند کار مطب</h2>
    <p class="ag-lead ag-lead--wide">ماکت‌های زیر مسیر مشترک برنامه را نشان می‌دهند. نام‌ها، شماره‌ها و مبلغ‌ها همگی نمونه‌اند.</p>
    <div class="ps-path" id="psPath" aria-label="مسیر کار منشی">
      <div class="st"><b>۱</b>ثبت مراجع</div>
      <div class="st"><b>۲</b>نوبت جلسه</div>
      <div class="st"><b>۳</b>حضور</div>
      <div class="st"><b>۴</b>ورود به جلسه</div>
      <div class="st"><b>۵</b>انجام شد</div>
      <div class="st"><b>۶</b>تسویه و جلسهٔ بعد</div>
    </div>
    <div class="ps-grid">
      <div class="cd" id="psQ">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">نمایشگر انتظار</div><div class="cd-r">اتاق مشاوره ۱ <span class="ps-sample">نمونه</span></div></div>
        <div class="ps-board">
          <div class="ps-k">نوبت جاری</div>
          <div class="ps-num" id="psQNum">—</div>
          <div class="ps-who" id="psQWho">در انتظار جلسه</div>
          <div class="ps-room" id="psQRoom"></div>
          <div class="ps-line" id="psQLine"></div>
          <div class="ps-chips" id="psQNext"></div>
        </div>
      </div>
      <div class="cd" id="psRsv">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">رزرو جلسه در چهار مرحله</div><div class="cd-r"><span class="ps-sample">نمونه</span></div></div>
        <div class="ps-rsv">
          <div class="cd-steps" id="psRsvSteps">
            <div class="cd-stp"><b>۱</b> جستجوی مراجع</div>
            <div class="cd-stp"><b>۲</b> خدمت و درمانگر</div>
            <div class="cd-stp"><b>۳</b> تاریخ و ساعت</div>
            <div class="cd-stp"><b>۴</b> تأیید و پیامک</div>
          </div>
          <div class="ps-search"><div class="box">جستجو با نام، موبایل یا کد ملی: <b id="psRsvQ"></b></div><div class="ps-hit" id="psRsvHit">خانم سارا رضایی <em>۰۹۱۲۰۰۰۰۰۰۰ · نمونه</em></div></div>
          <div class="ps-card"><div class="ps-opts" id="psRsvOpts"></div><div class="ps-sum" id="psRsvSum"></div></div>
        </div>
      </div>
      <div class="cd" id="psSms">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">پیامک یادآوری جلسه</div><div class="cd-r">نیاز پرداز <span class="ps-sample">نمونه</span></div></div>
        <div class="ps-ph-wrap"><div class="ps-ph" aria-hidden="true"><div class="ps-notch"></div><div class="ps-screen"><div class="ps-top"><span>۱۱:۲۰</span><span>ایرانسل</span></div><div class="ps-from">مطب نمونه آرامش</div><div class="ps-sms" id="psSmsText"></div></div></div></div>
      </div>
      <div class="cd" id="psSet">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">تسویه و جلسهٔ بعد</div><div class="cd-r"><span class="ps-sample">نمونه</span></div></div>
        <div class="ps-set">
          <div class="cd-steps" id="psSetSteps">
            <div class="cd-stp"><b>۱</b> انجام شد</div>
            <div class="cd-stp"><b>۲</b> تسویه</div>
            <div class="cd-stp"><b>۳</b> جلسهٔ بعد</div>
            <div class="cd-stp"><b>۴</b> پیامک</div>
          </div>
          <div class="ps-row"><span>مراجع</span><b>خانم سارا رضایی</b></div>
          <div class="ps-row"><span>خدمت</span><b>جلسه مشاوره (نمونه)</b></div>
          <div class="ps-row"><span>درمانگر</span><b>روان‌شناس نمونه</b></div>
          <div class="ps-row"><span>مبلغ (نمونه)</span><b class="num" id="psSetAmt">—</b></div>
          <div class="ps-pay" id="psSetPay"></div>
          <div class="ps-follow" id="psSetNext">جلسهٔ بعد هنوز ثبت نشده است.</div>
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
      <li>اطلاعات مراجعان روی سیستم یا سرور خود مطب است و برای کار برنامه به اینترنت ارسال نمی‌شود.</li>
      <li>هر کاربر فقط همان بخش‌ها و اعمالی را می‌بیند که به او داده شده است.</li>
      <li>پیش از خروج می‌توان نسخهٔ پشتیبان گرفت.</li>
      <li>تغییرات مهم در سیستم ثبت می‌شود.</li>
    </ul>
  </div>
</section>

<section class="ag-sec" id="compare">
  <div class="ag-wrap">
    <h2>مقایسه با نسخه‌های دیگر و سطوح لایسنس</h2>
    <div class="ag-table-wrap">
      <table class="ag-cmp ag-cmp--two">
        <thead><tr><th>امکان</th><th>نسخهٔ روانشناسی</th><th>نسخهٔ زیبایی</th><th>نسخهٔ دندانپزشکی</th></tr></thead>
        <tbody>
          <tr><th>ثبت مراجع، نوبت‌دهی، پرونده</th><td>هست</td><td>هست</td><td>هست</td></tr>
          <tr><th>تسویه و نوبت بعدی</th><td>هست</td><td>هست</td><td>هست</td></tr>
          <tr><th>پیامک خودکار</th><td>هست</td><td>هست</td><td>هست</td></tr>
          <tr><th>شماره نوبت خودکار</th><td>هست</td><td>هست</td><td>هست</td></tr>
          <tr><th>خدمات آمادهٔ مختص همان رشته</th><td>[[طبق نسخه]]</td><td>لیزر بانوان (دکا و تیتانیوم)</td><td>[[طبق نسخه]]</td></tr>
        </tbody>
      </table>
    </div>
    <p class="ag-note">زیبایی، فیزیوتراپی و دندانپزشکی روی همین سایت هستند: <a href="{{ url('/software/medical-beauty') }}">زیبایی</a> · <a href="{{ url('/software/medical-physiotherapy') }}">فیزیوتراپی</a> · <a href="{{ url('/software/medical-dental') }}">دندانپزشکی</a> · <a href="{{ url('/ai') }}">هوش مصنوعی</a></p>
    <p class="ag-note">تقسیم دقیق امکانات بین سطوح لایسنس و قیمت را صاحب سایت مشخص می‌کند. تا آن زمان سلول‌ها جای‌نگهدار می‌مانند.</p>
    <div class="ag-table-wrap">
      <table class="ag-cmp ag-cmp--two">
        <thead><tr><th>امکان</th><th>پایه</th><th>حرفه‌ای</th><th>چندکاربره / شبکه</th></tr></thead>
        <tbody>
          <tr><th>ثبت مراجع و نوبت‌دهی</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>گزارش مراجع</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>تسویه و نوبت بعدی</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>پیامک خودکار</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
          <tr><th>شماره نوبت</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
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
    <div class="ps-gal">
      @foreach($shots as $i => $cap)
        <figure>
          <div class="ps-shot" role="img" aria-label="{{ $cap }}">تصویر {{ ['۱','۲','۳','۴','۵','۶'][$i] }}</div>
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
    <h2>نسخهٔ روانشناسی را با خدمات مطب خودتان ببینید</h2>
    <p class="ag-lead ag-lead--wide">دموی رایگان؛ خدمات مطب شما را در برنامه می‌چینیم تا پیش از خرید، کار با نوبت‌دهی، تسویه و پیامک را ببینید.</p>
    @if(session('success'))<p class="ag-note">{{ session('success') }}</p>@endif
    @if(session('error'))<p class="ag-note">{{ session('error') }}</p>@endif
    <div class="ps-demo">
      <form class="ps-form" id="psDemo" method="post" action="{{ url('/support') }}">
        @csrf
        <label>نام<input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required></label>
        <label>موبایل<input type="tel" name="phone" id="psPhone" value="{{ old('phone') }}" inputmode="tel" autocomplete="tel" required placeholder="۰۹۱۲xxxxxxx"></label>
        <label>نام مطب / کلینیک<input type="text" name="clinic" id="psClinic" value="{{ old('clinic') }}" required></label>
        <label>شهر<input type="text" name="city" id="psCity" value="{{ old('city') }}" required></label>
        <input type="hidden" name="subject" value="درخواست دمو حساب مطب روانشناسی">
        <input type="hidden" name="body" id="psBody" value="درخواست دموی نسخهٔ روانشناسی.">
        <div class="full"><button class="ag-btn ag-btn--on" type="submit">درخواست دمو<i>←</i></button></div>
      </form>
      <div class="ps-side">
        <p>تماس مستقیم</p>
        <a href="tel:01144447220">۰۱۱۴۴۴۴۷۲۲۰</a>
        <div class="ag-actions" style="margin-top:.8rem">
          <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس تلفنی</a>
          <a class="ag-btn ag-btn--line" href="{{ url('/contact') }}">صفحهٔ تماس</a>
        </div>
        <p class="ag-note">شمارهٔ فعلی خط ثابت آمل است.</p>
      </div>
    </div>
  </div>
</section>
</div>
@include('studio::software-psychology-script')
