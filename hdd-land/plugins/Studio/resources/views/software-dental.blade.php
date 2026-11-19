@php
  $faqs = [
      ['آیا به اینترنت نیاز دارد؟', 'خیر؛ برنامه کاملاً آفلاین کار می‌کند. فقط ارسال پیامک و امکان‌هایی که به اینترنت وابسته‌اند، اتصال می‌خواهند.'],
      ['روی چند سیستم نصب می‌شود؟', 'طبق لایسنس خریداری‌شده. برای چند کاربر همزمان، نسخهٔ چندکاربره یا شبکه در نظر گرفته شده است. [[جزئیات نسخه را صاحب سایت مشخص کند]]'],
      ['اطلاعات بیماران کجا ذخیره می‌شود؟', 'روی سیستم یا سرور خود مطب. اطلاعات بیماران برای کار برنامه به اینترنت فرستاده نمی‌شود.'],
      ['چارت دندانی چیست؟', 'ادنتوگرام با شماره‌گذاری FDI برای دندان‌های دائمی یا شیری؛ درمان با کلیک روی دندان یا ثبت سریع شماره دندان در پرونده ثبت می‌شود.'],
      ['شماره نوبت برای چند یونیت چطور است؟', 'برای هر یونیت جدا شمرده می‌شود و هر روز از نو، از ۱ تا ۱۰۰۰، بدون تکرار در همان روز.'],
      ['آموزش و پشتیبانی چطور است؟', 'در هر پنجره دکمهٔ آموزش و کلید F1، راهنمای روی دکمه‌ها، تور بار اول، سناریوی کامل منشی و مدیر، و مرکز آموزش قابل جستجو داخل خود برنامه قرار دارد. پشتیبانی تلفنی و از راه دور هم ارائه می‌شود.'],
      ['نسخهٔ زیبایی یا روانشناسی هم دارید؟', 'بله. هر دو روی همین سایت هستند.'],
      ['سیستم مورد نیاز چیست و قیمت چقدر است؟', 'ویندوز ۱۰ یا ۱۱. پایگاه‌داده همراه برنامه نصب می‌شود. [[قیمت یا «برای استعلام تماس بگیرید»]]'],
  ];
  $graph = [
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'SoftwareApplication',
              'name' => 'حساب مطب — نسخه دندانپزشکی',
              'alternateName' => ['HesabMatab Dental', 'نرم‌افزار مدیریت مطب دندانپزشکی'],
              'applicationCategory' => 'MedicalApplication',
              'applicationSubCategory' => 'BusinessApplication',
              'operatingSystem' => 'Windows',
              'inLanguage' => 'fa',
              'url' => 'https://hdd-land.ir/software/medical-dental',
              'description' => 'نرم‌افزار مدیریت مطب دندانپزشکی: نوبت‌دهی یونیت‌به‌یونیت، چارت دندانی FDI، کارتابل دندان‌پزشک و لابراتوار، پرونده و گزارش، تسویه و نوبت بعدی، پیامک خودکار؛ کاملاً آفلاین.',
              'publisher' => ['@type' => 'Organization', 'name' => 'فراز نت آمل', 'url' => 'https://hdd-land.ir/', 'telephone' => '+981144447220'],
              'offers' => ['@type' => 'Offer', 'priceCurrency' => 'IRR', 'availability' => 'https://schema.org/InStock', 'url' => 'https://hdd-land.ir/contact', 'description' => '[[قیمت نسخه یا عبارت «برای استعلام تماس بگیرید»]]'],
          ],
          [
              '@type' => 'BreadcrumbList',
              'itemListElement' => [
                  ['@type' => 'ListItem', 'position' => 1, 'name' => 'نرم‌افزارها', 'item' => 'https://hdd-land.ir/computer-accounting'],
                  ['@type' => 'ListItem', 'position' => 2, 'name' => 'نرم‌افزارهای پزشکی', 'item' => 'https://hdd-land.ir/software/medical-dental'],
                  ['@type' => 'ListItem', 'position' => 3, 'name' => 'دندانپزشکی', 'item' => 'https://hdd-land.ir/software/medical-dental'],
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
      ['reg', 'پذیرش و ثبت بیمار', 'منوی جدا برای ثبت بیمار و یک پیامک خوش‌آمد.', ['جستجو با نام، موبایل یا کد ملی', 'پیامک خوش‌آمد فقط یک‌بار برای بیمار جدید', 'بلافاصله پس از ثبت می‌توان نوبت گرفت']],
      ['apt', 'نوبت‌دهی روی یونیت', 'چهار مرحله تا تأیید و پیامک.', ['بیمار، خدمت و دندان‌پزشک، تاریخ و ساعت، تأیید', 'کانال رزرو: تلفنی، حضوری یا اینترنتی', 'زمان‌ها بر اساس برنامهٔ کاری پزشک', 'هر یونیت یک واحد است و شماره نوبت برای هر یونیت جدا شمرده می‌شود']],
      ['q', 'شماره نوبت و فراخوان', 'شماره روزانه و اعلام در سالن.', ['شماره خودکار ۱ تا ۱۰۰۰ در هر روز و هر یونیت، بدون تکرار همان روز', 'نمایش در لیست نوبت و در پیامک', 'فراخوان با صدای فارسی داخل برنامه و بدون اینترنت', 'نمایشگر سالن انتظار روی مانیتور دوم یا تلویزیون', 'قابل خاموش کردن یا فراخوان دستی']],
      ['chart', 'چارت دندانی FDI', 'ادنتوگرام در پروندهٔ بیمار.', ['زبانهٔ «چارت دندانی و طرح درمان (ادنتوگرام FDI)»', 'دندان‌های دائمی یا شیری', 'ثبت با کلیک روی دندان یا شماره FDI', 'سوابق درمان دندانی و لابراتوار در زبانهٔ جدا']],
      ['doc', 'کارتابل دندان‌پزشک', 'ثبت درمان و مرحلهٔ لابراتوار.', ['شماره دندان FDI و وضعیت ادنتوگرام، مثل پوسیدگی، ترمیم، عصب‌کشی، روکش، ایمپلنت یا کشیده‌شده', 'تشخیص و مرحلهٔ درمان یا لابراتوار: قالب‌گیری، ارسال، تحویل', 'رنگ دندان و نام لابراتوار', 'ارسال برای تسویه به منشی']],
      ['pay', 'تسویه و نوبت بعدی', '', ['با «انجام شد» پنجرهٔ تسویه باز می‌شود', 'ثبت نوبت بعدی همان‌جا', 'پیامک تاریخ و ساعت نوبت بعدی']],
      ['rep', 'پرونده و گزارش', '', ['تاریخ، خدمات، ساعت ورود و خروج، پزشک، مبلغ و تسویه', 'کانال رزرو و شماره نوبت', 'فیلتر، چاپ و Excel، به‌علاوه سابقهٔ درمان روی چارت']],
      ['svc', 'خدمات و تعرفه‌ها', 'درخت بخش، ماده و درمان.', ['بارگذاری چارت استاندارد خدمات دندانپزشکی، قابل ویرایش', 'واحد شمارش مثل هر دندان، هر واحد یا دو فک', 'هزینه مواد و لابراتوار و سهم دندان‌پزشک؛ قیمت خرید فقط برای مدیر یا کاربر مجاز', 'گروه‌های آماده: معاینه و رادیوگرافی؛ درمان ریشه؛ ترمیمی و زیبایی؛ پروتز و لمینت؛ ایمپلنت؛ جراحی و کشیدن؛ جرم‌گیری، لثه و بلیچینگ؛ کودکان؛ ارتودنسی']],
      ['sms', 'پیامک', '', ['پنل نیاز پرداز و پنل‌های رایج دیگر', 'روشن یا خاموش و ویرایش متن هر رویداد', 'پیش‌نمایش و نشانه‌های تاریخ، ساعت و شماره نوبت']],
      ['sec', 'امنیت و آموزش', '', ['سطح دسترسی جدا و لایسنس امضاشده روی همان سیستم', 'اطلاعات روی سیستم مطب و پیشنهاد بکاپ هنگام خروج', 'آموزش و F1، تور بار اول، سناریوی منشی و مدیر، مرکز آموزش قابل جستجو']],
  ];
  $shots = ['نوبت‌دهی روی یونیت','چارت دندانی FDI','کارتابل دندان‌پزشک و مرحلهٔ لابراتوار','تسویه و نوبت بعدی','گزارش بیمار','تنظیمات پیامک و مرکز آموزش'];
  $staff = auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff();
@endphp
<div class="dn">
<section class="ag-page" id="top">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">نرم‌افزارها</a><span>/</span><span>نرم‌افزارهای پزشکی</span><span>/</span><span>دندانپزشکی</span>
    </p>
    <p class="ag-kicker">نرم‌افزارهای پزشکی · نسخهٔ مطب دندانپزشکی · حساب مطب</p>
    <h1>نرم‌افزار مدیریت مطب دندانپزشکی؛ نوبت یونیت، چارت دندانی، تسویه و پیامک در یک برنامه</h1>
    <p class="ag-lead ag-lead--wide">برای مطب‌ها و کلینیک‌های دندانپزشکی: از ثبت بیمار تا نوبت روی یونیت، چارت FDI، ثبت درمان و لابراتوار، تسویه و نوبت بعدی، با پیامک خودکار و آموزش کامل فارسی داخل برنامه؛ بدون نیاز به اینترنت.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="#demo">درخواست دموی رایگان<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">مشاهدهٔ امکانات</a>
    </div>
    @include('studio::sale-bar', ['sku' => 'med-dental'])
    <p class="ag-note">نوبت‌دهی روی یونیت · چارت دندانی FDI · پیامک خودکار · آفلاین</p>
    @if($staff)
      <p class="ag-note">برای نمایندهٔ واردشده: سهم این نسخه ۱۲٪ است. این جمله در صفحهٔ عمومی دیده نمی‌شود.</p>
    @endif
    <div class="cd" id="dn1">
      <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">حساب مطب · نسخهٔ دندانپزشکی</div><div class="cd-r">مطب نمونه لبخند · امروز <span class="dn-sample">نمونه</span></div></div>
      <div class="dn-hero">
        <div class="dn-board"><div class="dn-k">نوبت جاری</div><div class="dn-num" id="dnHeroNum">—</div><div class="dn-who" id="dnHeroWho">منتظر شروع…</div><div class="dn-room" id="dnHeroRoom"></div><div class="dn-line" id="dnHeroLine"></div></div>
        <div class="cd-feed">
          <div class="cd-kpis">
            <div class="cd-kpi"><div class="k">نوبت‌های امروز</div><div class="v num" id="dnK1">۰</div></div>
            <div class="cd-kpi"><div class="k">در انتظار</div><div class="v num" id="dnK2">۰</div></div>
            <div class="cd-kpi"><div class="k">نوبت جاری یونیت ۱</div><div class="v num" id="dnK3">۰</div></div>
            <div class="cd-kpi"><div class="k">درآمد امروز (میلیون ریال)</div><div class="v num" id="dnK4">۰</div></div>
          </div>
          <div id="dnFeed"></div>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="ag-sec" id="fit">
  <div class="ag-wrap">
    <h2>برای چه کسانی مناسب است</h2>
    <div class="ag-cards ag-cards--dense dn-cards-4">
      <article class="ag-card"><div><b>مطب‌های عمومی و ترمیمی</b><p>معاینه، ترمیم، عصب‌کشی و جرم‌گیری با نوبت یونیت و پروندهٔ دندانی.</p></div></article>
      <article class="ag-card"><div><b>کلینیک‌های چندیونیت و چندپزشک</b><p>شماره نوبت جدا برای هر یونیت، برنامهٔ کاری هر دندان‌پزشک و کارتابل جدا.</p></div></article>
      <article class="ag-card"><div><b>مطب‌های ایمپلنت، پروتز و زیبایی دندان</b><p>ثبت درمان، مرحلهٔ لابراتوار، رنگ دندان و نام لابراتوار در کارتابل دندان‌پزشک.</p></div></article>
      <article class="ag-card"><div><b>مطب‌های اطفال و ارتودنسی</b><p>چارت دندان‌های شیری، خدمات اطفال و ارتودنسی در درخت خدمات قابل بارگذاری و ویرایش.</p></div></article>
    </div>
  </div>
</section>
<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="den-tab" id="dt-{{ $tab[0] }}" @checked($i === 0)>
        <label for="dt-{{ $tab[0] }}">{{ $tab[1] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab[1] }}</h2>
          @if($tab[2] !== '')<p class="ag-lead">{{ $tab[2] }}</p>@endif
          <ul class="acc-cols">@foreach($tab[3] as $line)<li>{{ $line }}</li>@endforeach</ul>
        </div>
      @endforeach
    </div>
  </div>
</section>
<section class="ag-sec" id="special">
  <div class="ag-wrap">
    <h2>ویژهٔ نسخهٔ دندانپزشکی</h2>
    <p class="ag-lead ag-lead--wide">ماکت‌ها نام‌ها و عددهای نمونه را نشان می‌دهند.</p>
    <div class="dn-path" id="dnPath">
      <div class="st"><b>۱</b>ثبت بیمار</div><div class="st"><b>۲</b>نوبت یونیت</div><div class="st"><b>۳</b>حضور</div><div class="st"><b>۴</b>فراخوان</div><div class="st"><b>۵</b>ثبت درمان</div><div class="st"><b>۶</b>تسویه و نوبت بعد</div>
    </div>
    <div class="dn-grid">
      <div class="cd" id="dnTeeth">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">چارت دندانی FDI</div><div class="cd-r"><span class="dn-sample">نمونه</span></div></div>
        <div class="dn-teeth"><div class="dn-arch" id="dnArchUp"></div><div class="dn-arch is-lower" id="dnArchLo"></div><div class="dn-legend"><span class="cd-chip" id="dnToothChip">وضعیت: انتخاب دندان</span><span class="cd-chip" id="dnToothFdi">FDI: —</span></div></div>
      </div>
      <div class="cd" id="dnLab">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">کارتابل و لابراتوار</div><div class="cd-r">دندان ۳۶ <span class="dn-sample">نمونه</span></div></div>
        <div class="dn-lab" id="dnLabSteps"><div class="st"><b>۱</b>قالب‌گیری</div><div class="st"><b>۲</b>ارسال به لابراتوار</div><div class="st"><b>۳</b>تحویل روکش</div></div>
      </div>
      <div class="cd" id="dnRsv">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">رزرو نوبت روی یونیت ۱</div><div class="cd-r"><span class="dn-sample">نمونه</span></div></div>
        <div class="dn-rsv">
          <div class="cd-steps" id="dnRsvSteps"><div class="cd-stp"><b>۱</b> بیمار</div><div class="cd-stp"><b>۲</b> خدمت و پزشک</div><div class="cd-stp"><b>۳</b> تاریخ و ساعت</div><div class="cd-stp"><b>۴</b> تأیید</div></div>
          <div class="dn-search"><div class="box">جستجو: <b id="dnRsvQ"></b></div><div class="dn-hit" id="dnRsvHit">خانم سارا رضایی <em>۰۹۱۲۰۰۰۰۰۰۰ · نمونه</em></div></div>
          <div class="dn-opts" id="dnRsvOpts"></div><div class="dn-sum" id="dnRsvSum"></div>
        </div>
      </div>
      <div class="cd" id="dnQ">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">نمایشگر انتظار یونیت</div><div class="cd-r">یونیت ۱ <span class="dn-sample">نمونه</span></div></div>
        <div class="dn-board"><div class="dn-k">نوبت جاری</div><div class="dn-num" id="dnQNum">—</div><div class="dn-who" id="dnQWho">در انتظار نوبت</div><div class="dn-room" id="dnQRoom"></div><div class="dn-line" id="dnQLine"></div><div class="dn-chips" id="dnQNext"></div></div>
      </div>
      <div class="cd" id="dnSms">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">پیامک نوبت</div><div class="cd-r">نیاز پرداز <span class="dn-sample">نمونه</span></div></div>
        <div class="dn-ph-wrap"><div class="dn-ph"><div class="dn-screen"><div class="dn-from">مطب نمونه لبخند</div><div class="dn-sms" id="dnSmsText"></div></div></div></div>
      </div>
      <div class="cd" id="dnSet">
        <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">تسویه و نوبت بعدی</div><div class="cd-r"><span class="dn-sample">نمونه</span></div></div>
        <div class="dn-set">
          <div class="cd-steps" id="dnSetSteps"><div class="cd-stp"><b>۱</b> انجام شد</div><div class="cd-stp"><b>۲</b> تسویه</div><div class="cd-stp"><b>۳</b> نوبت بعد</div><div class="cd-stp"><b>۴</b> پیامک</div></div>
          <div class="dn-row"><span>بیمار</span><b>خانم سارا رضایی</b></div>
          <div class="dn-row"><span>درمان</span><b>دندان ۳۶ · ترمیم کامپوزیت</b></div>
          <div class="dn-row"><span>مبلغ (نمونه)</span><b class="num" id="dnSetAmt">—</b></div>
          <div class="dn-pay" id="dnSetPay"></div>
          <div class="dn-follow" id="dnSetNext">نوبت بعدی هنوز ثبت نشده است.</div>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="ag-sec" id="security"><div class="ag-wrap"><h2>امنیت و لایسنس</h2><ul class="ag-list"><li>لایسنس امضاشده روی همان سیستم فعال می‌شود.</li><li>اطلاعات بیماران روی سیستم مطب می‌ماند و برای کار برنامه به اینترنت فرستاده نمی‌شود.</li><li>دسترسی هر کاربر جدا است. قیمت خرید و هزینهٔ لابراتوار فقط برای مدیر یا کاربر مجاز دیده می‌شود.</li><li>پیش از خروج می‌توان پشتیبان گرفت و تغییرات مهم ثبت می‌شود.</li></ul></div></section>
<section class="ag-sec" id="compare">
  <div class="ag-wrap">
    <h2>مقایسه با نسخه‌های دیگر</h2>
    <div class="ag-table-wrap"><table class="ag-cmp ag-cmp--two"><thead><tr><th>امکان</th><th>دندانپزشکی</th><th>زیبایی</th><th>روانشناسی</th></tr></thead><tbody>
      <tr><th>ثبت بیمار، نوبت، پرونده، پیامک، تسویه</th><td>هست</td><td>هست</td><td>هست</td></tr>
      <tr><th>شماره نوبت جدا برای هر واحد</th><td>یونیت</td><td>واحد/اتاق</td><td>واحد/اتاق</td></tr>
      <tr><th>چارت دندانی FDI و کارتابل لابراتوار</th><td>هست</td><td>—</td><td>—</td></tr>
      <tr><th>خدمات آمادهٔ مختص رشته</th><td>چارت استاندارد دندانپزشکی</td><td>لیزر بانوان (دکا و تیتانیوم)</td><td>[[طبق نسخه]]</td></tr>
    </tbody></table></div>
    <p class="ag-note">زیبایی، روانشناسی و فیزیوتراپی: <a href="{{ url('/software/medical-beauty') }}">زیبایی</a> · <a href="{{ url('/software/medical-psychology') }}">روانشناسی</a> · <a href="{{ url('/software/medical-physiotherapy') }}">فیزیوتراپی</a>. کد فروش پیشنهادی تا تأیید شما جای خالی است: [[SKU: med-dental]]</p>
    <div class="ag-table-wrap"><table class="ag-cmp ag-cmp--two"><thead><tr><th>امکان</th><th>پایه</th><th>حرفه‌ای</th><th>چندکاربره / شبکه</th></tr></thead><tbody>
      <tr><th>ثبت بیمار و نوبت‌دهی</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
      <tr><th>گزارش بیمار</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
      <tr><th>تسویه و نوبت بعدی</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
      <tr><th>پیامک خودکار</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
      <tr><th>شماره نوبت</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
      <tr><th>چارت دندانی FDI</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
      <tr><th>چند کاربر همزمان</th><td>—</td><td>—</td><td>هست</td></tr>
      <tr><th>قیمت</th><td>[[قیمت / تماس بگیرید]]</td><td>[[قیمت / تماس بگیرید]]</td><td>[[قیمت / تماس بگیرید]]</td></tr>
    </tbody></table></div>
  </div>
</section>
<section class="ag-sec" id="gallery"><div class="ag-wrap"><h2>تصاویر برنامه</h2><div class="dn-gal">@foreach($shots as $i => $cap)<figure><div class="dn-shot" role="img" aria-label="{{ $cap }}">تصویر {{ ['۱','۲','۳','۴','۵','۶'][$i] }}</div><figcaption>{{ $cap }}</figcaption></figure>@endforeach</div></div></section>
<section class="ag-sec" id="faq"><div class="ag-wrap"><h2>پرسش‌های رایج</h2><ul class="ag-steps" style="grid-template-columns:1fr">@foreach($faqs as $row)<li><b>{{ $row[0] }}</b><p>{{ $row[1] }}</p></li>@endforeach</ul></div></section>
<script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<section class="ag-sec ag-sec--last" id="demo">
  <div class="ag-wrap">
    <h2>نسخهٔ دندانپزشکی را با یونیت‌ها و خدمات مطب خودتان ببینید</h2>
    <p class="ag-lead ag-lead--wide">دموی رایگان؛ خدمات و یونیت‌های مطب شما را در برنامه می‌چینیم تا پیش از خرید، نوبت‌دهی، چارت دندانی، تسویه و پیامک را ببینید.</p>
    @if(session('success'))<p class="ag-note">{{ session('success') }}</p>@endif
    @if(session('error'))<p class="ag-note">{{ session('error') }}</p>@endif
    <div class="dn-demo">
      <form class="dn-form" id="dnDemo" method="post" action="{{ url('/support') }}">
        @csrf
        <label>نام<input name="name" value="{{ old('name') }}" required></label>
        <label>موبایل<input type="tel" name="phone" id="dnPhone" value="{{ old('phone') }}" required placeholder="۰۹۱۲xxxxxxx"></label>
        <label>نام مطب<input name="clinic" id="dnClinic" value="{{ old('clinic') }}" required></label>
        <label>شهر<input name="city" id="dnCity" value="{{ old('city') }}" required></label>
        <input type="hidden" name="subject" value="درخواست دمو حساب مطب دندانپزشکی">
        <input type="hidden" name="body" id="dnBody" value="درخواست دموی نسخهٔ دندانپزشکی.">
        <div class="full"><button class="ag-btn ag-btn--on" type="submit">درخواست دمو<i>←</i></button></div>
      </form>
      <div class="dn-side"><p>تماس مستقیم</p><a href="tel:01144447220">۰۱۱۴۴۴۴۷۲۲۰</a></div>
    </div>
  </div>
</section>
</div>
@include('studio::software-dental-script')
