@php
  $faqs = [
      ['آیا به اینترنت نیاز دارد؟', 'خیر؛ برنامه کاملاً آفلاین کار می‌کند. فقط ارسال پیامک و امکان‌هایی که به اینترنت وابسته‌اند، اتصال می‌خواهند.'],
      ['روی چند سیستم نصب می‌شود؟', 'طبق لایسنس خریداری‌شده. برای چند کاربر همزمان، نسخهٔ چندکاربره یا شبکه در نظر گرفته شده است. [[جزئیات نسخه را صاحب سایت مشخص کند]]'],
      ['اطلاعات بیماران کجا ذخیره می‌شود؟', 'روی سیستم یا سرور خود مطب. اطلاعات بیماران برای کار برنامه به اینترنت فرستاده نمی‌شود.'],
      ['نوبت چند واحد چطور است؟', 'برای هر واحد جداگانه صادر می‌شود و هر روز از نو شروع می‌شود. واحدهای آمادهٔ نسخه الکتروتراپی، ورزش‌درمانی، لیزر و شاک‌ویو و درمان دستی هستند و قابل ویرایش‌اند.'],
      ['شرح حال پیش‌فرض چیست؟', 'فرم «شرح حال اولیه فیزیوتراپی» ناحیهٔ درد، شدت ۰ تا ۱۰ و سابقهٔ جراحی را می‌پرسد و با فرم‌ساز قابل تغییر است.'],
      ['آموزش و پشتیبانی چطور است؟', 'در هر پنجره دکمهٔ آموزش و کلید F1، راهنمای روی دکمه‌ها، تور بار اول، سناریوی کامل منشی و مدیر، و مرکز آموزش قابل جستجو داخل خود برنامه قرار دارد. پشتیبانی تلفنی و از راه دور هم ارائه می‌شود.'],
      ['نسخهٔ زیبایی یا روانشناسی هم دارید؟', 'بله. هر دو روی همین سایت هستند.'],
      ['سیستم مورد نیاز چیست و قیمت چقدر است؟', 'ویندوز ۱۰ یا ۱۱. پایگاه‌داده همراه برنامه نصب می‌شود. [[قیمت یا «برای استعلام تماس بگیرید»]]'],
  ];
  $graph = [
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'SoftwareApplication',
              'name' => 'حساب مطب — نسخه فیزیوتراپی',
              'alternateName' => ['HesabMatab Physio', 'نرم‌افزار مدیریت کلینیک فیزیوتراپی'],
              'applicationCategory' => 'MedicalApplication',
              'operatingSystem' => 'Windows',
              'inLanguage' => 'fa',
              'url' => 'https://hdd-land.ir/software/medical-physiotherapy',
              'description' => 'نرم‌افزار مدیریت کلینیک فیزیوتراپی: نوبت‌دهی واحدبه‌واحد، صف و فراخوان هر واحد، پرونده و شرح حال، پنل درمانگر، تسویه و پیامک خودکار؛ کاملاً آفلاین.',
              'publisher' => ['@type' => 'Organization', 'name' => 'فراز نت آمل', 'url' => 'https://hdd-land.ir/', 'telephone' => '+981144447220'],
              'offers' => ['@type' => 'Offer', 'priceCurrency' => 'IRR', 'availability' => 'https://schema.org/InStock', 'url' => 'https://hdd-land.ir/contact', 'description' => '[[قیمت نسخه یا عبارت «برای استعلام تماس بگیرید»]]'],
          ],
          [
              '@type' => 'BreadcrumbList',
              'itemListElement' => [
                  ['@type' => 'ListItem', 'position' => 1, 'name' => 'نرم‌افزارها', 'item' => 'https://hdd-land.ir/computer-accounting'],
                  ['@type' => 'ListItem', 'position' => 2, 'name' => 'نرم‌افزارهای پزشکی', 'item' => 'https://hdd-land.ir/software/medical-physiotherapy'],
                  ['@type' => 'ListItem', 'position' => 3, 'name' => 'فیزیوتراپی', 'item' => 'https://hdd-land.ir/software/medical-physiotherapy'],
              ],
          ],
          [
              '@type' => 'FAQPage',
              'mainEntity' => array_map(fn ($row) => ['@type' => 'Question', 'name' => $row[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $row[1]]], $faqs),
          ],
      ],
  ];
  $tabs = [
      ['reg', 'پذیرش و ثبت بیمار', 'منوی جدا و یک پیامک خوش‌آمد.', ['جستجو با نام، موبایل یا کد ملی', 'پیامک خوش‌آمد فقط یک‌بار برای بیمار جدید', 'بلافاصله پس از ثبت می‌توان نوبت گرفت']],
      ['apt', 'نوبت‌دهی روی واحد', 'چهار مرحله تا تأیید و پیامک.', ['بیمار، خدمت و درمانگر، تاریخ و ساعت، تأیید', 'کانال رزرو: تلفنی، حضوری یا اینترنتی', 'زمان‌های آزاد بر اساس برنامهٔ کاری درمانگر', 'شماره نوبت برای هر واحد جدا صادر می‌شود']],
      ['q', 'شماره نوبت و فراخوان', 'شماره روزانه و اعلام در سالن.', ['شماره خودکار ۱ تا ۱۰۰۰ در هر روز و هر واحد، بدون تکرار همان روز', 'نمایش در لیست و پیامک', 'فراخوان با صدای فارسی داخل برنامه و بدون اینترنت', 'نمایشگر سالن انتظار روی مانیتور دوم یا تلویزیون']],
      ['unit', 'واحدها و درمانگران', 'صف جدا برای هر واحد.', ['منوی «درمانگران و واحدها»', 'عنوان پیش‌فرض «درمانگر» و عنوان قابل‌انتخاب «فیزیوتراپیست»', 'واحدهای آماده: الکتروتراپی، ورزش‌درمانی، لیزر و شاک‌ویو، دستی (منوال)', 'برنامهٔ کاری هفتگی مبنای ساعت‌های آزاد نوبت است']],
      ['doc', 'پنل درمانگر', 'ثبت جلسه و ارسال به منشی.', ['درمانگر خدمات انجام‌شده را ثبت و برای تسویه می‌فرستد', 'صف هر ۱۵ ثانیه تازه می‌شود تا چند رایانه هم‌زمان کار کنند', 'فیلتر صف بر اساس واحد یا درمانگر']],
      ['pay', 'تسویه و نوبت بعدی', '', ['پس از جلسه پنجرهٔ تسویه باز می‌شود', 'ثبت نوبت بعدی همان‌جا', 'پیامک تاریخ و ساعت نوبت بعدی']],
      ['rep', 'پرونده و شرح حال', 'فرم پیش‌فرض فیزیوتراپی.', ['ناحیهٔ درد: گردن، شانه، کمر، زانو، مچ و سایر', 'شدت درد از ۰ تا ۱۰ و سابقهٔ جراحی در ناحیه', 'علت‌های آماده مثل کمردرد، گردن‌درد، زانو، شانه، آسیب ورزشی، پس از عمل و توانبخشی عصبی', 'گزارش با فیلتر، چاپ و Excel']],
      ['svc', 'خدمات و تعرفه‌ها', 'تعرفه را مطب تعیین می‌کند.', ['عنوان، واحد شمارش از جمله جلسه، تعرفه و مدت نوبت', 'مدت پیش‌فرض: ارزیابی ۴۵ دقیقه، جلسه ۴۰ دقیقه، سایر ۲۰ دقیقه', 'نام خدمات آماده فقط برای شروع: ارزیابی اولیه، جلسه فیزیوتراپی، لیزر درمانی، شاک‌ویو، درای نیدلینگ، تراکشن', 'دستمزد درمانگر؛ قیمت خرید فقط برای مدیر یا کاربر مجاز']],
      ['sms', 'پیامک', '', ['پنل نیاز پرداز و پنل‌های رایج دیگر', 'روشن یا خاموش و ویرایش متن هر رویداد', 'پیش‌نمایش و نشانه‌های تاریخ، ساعت و شماره نوبت']],
      ['sec', 'امنیت و آموزش', '', ['سطح دسترسی جدا و لایسنس امضاشده روی همان سیستم', 'اطلاعات روی سیستم مطب و پیشنهاد بکاپ هنگام خروج', 'آموزش و F1، تور بار اول، سناریوی منشی و مدیر']],
  ];
  $shots = ['نوبت‌دهی روی واحد درمان','صف و فراخوان هر واحد','پنل درمانگر','تسویه و نوبت بعدی','گزارش بیمار و شرح حال','تنظیمات پیامک و مرکز آموزش'];
  $staff = auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff();
@endphp
<div class="py">
<section class="ag-page" id="top">
  <div class="ag-wrap">
    <p class="ag-crumb"><a href="{{ url('/computer-accounting') }}">نرم‌افزارها</a><span>/</span><span>نرم‌افزارهای پزشکی</span><span>/</span><span>فیزیوتراپی</span></p>
    <p class="ag-kicker">نرم‌افزارهای پزشکی · نسخهٔ مطب فیزیوتراپی · حساب مطب</p>
    <h1>نرم‌افزار مدیریت کلینیک فیزیوتراپی؛ نوبت واحد، پرونده، تسویه و پیامک در یک برنامه</h1>
    <p class="ag-lead ag-lead--wide">برای کلینیک‌ها و مراکز فیزیوتراپی: از ثبت بیمار تا نوبت روی واحد درمان، صف و فراخوان هر واحد، پرونده و شرح حال، ثبت جلسه در پنل درمانگر، تسویه و نوبت بعدی، با پیامک خودکار و آموزش کامل فارسی داخل برنامه؛ بدون نیاز به اینترنت.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="#demo">درخواست دموی رایگان<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">مشاهدهٔ امکانات</a>
    </div>
    @include('studio::sale-bar', ['sku' => 'med-physio'])
    <p class="ag-note">نوبت‌دهی روی واحد · صف هر واحد · پیامک خودکار · آفلاین</p>
    <div class="cd" id="py1">
      <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">حساب مطب · نسخهٔ فیزیوتراپی</div><div class="cd-r">کلینیک نمونه حرکت · امروز <span class="py-sample">نمونه</span></div></div>
      <div class="py-hero">
        <div class="py-board"><div class="py-k">نوبت جاری</div><div class="py-num" id="pyHeroNum">—</div><div class="py-who" id="pyHeroWho">منتظر شروع…</div><div class="py-room" id="pyHeroRoom"></div><div class="py-line" id="pyHeroLine"></div></div>
        <div class="cd-feed">
          <div class="cd-kpis">
            <div class="cd-kpi"><div class="k">نوبت‌های امروز</div><div class="v num" id="pyK1">۰</div></div>
            <div class="cd-kpi"><div class="k">در انتظار</div><div class="v num" id="pyK2">۰</div></div>
            <div class="cd-kpi"><div class="k">نوبت جاری واحد الکتروتراپی</div><div class="v num" id="pyK3">۰</div></div>
            <div class="cd-kpi"><div class="k">واحدهای فعال</div><div class="v num" id="pyK4">۰</div></div>
          </div>
          <div id="pyFeed"></div>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="ag-sec" id="fit"><div class="ag-wrap"><h2>برای چه کسانی مناسب است</h2><div class="ag-cards ag-cards--dense py-cards-4">
  <article class="ag-card"><div><b>کلینیک‌های فیزیوتراپی عمومی</b><p>ارزیابی اولیه و جلسهٔ درمانی با نوبت روی واحد، پرونده و شرح حال درد.</p></div></article>
  <article class="ag-card"><div><b>مراکز چندواحدی</b><p>واحدهایی مثل الکتروتراپی، ورزش‌درمانی، لیزر و شاک‌ویو، و درمان دستی؛ شماره نوبت جدا برای هر واحد.</p></div></article>
  <article class="ag-card"><div><b>توانبخشی پس از جراحی یا آسیب</b><p>علت مراجعه و شرح حال ناحیهٔ درد و سابقهٔ جراحی در پرونده ثبت می‌شود؛ نوبت بعدی همان‌جا گرفته می‌شود.</p></div></article>
  <article class="ag-card"><div><b>کلینیک‌های آسیب ورزشی و چنددرمانگر</b><p>برنامهٔ کاری هر درمانگر، صف و کارتابل جدا، و خدمات قابل تعریف مثل لیزر، شاک‌ویو یا درای نیدلینگ.</p></div></article>
</div></div></section>
<section class="ag-sec" id="features"><div class="ag-wrap"><h2>امکانات</h2><div class="acc-tabs">
  @foreach($tabs as $i => $tab)
    <input type="radio" name="py-tab" id="ft-{{ $tab[0] }}" @checked($i === 0)>
    <label for="ft-{{ $tab[0] }}">{{ $tab[1] }}</label>
    <div class="acc-tabs__panel"><h2>{{ $tab[1] }}</h2>@if($tab[2] !== '')<p class="ag-lead">{{ $tab[2] }}</p>@endif<ul class="acc-cols">@foreach($tab[3] as $line)<li>{{ $line }}</li>@endforeach</ul></div>
  @endforeach
</div></div></section>
<section class="ag-sec" id="special"><div class="ag-wrap">
  <h2>ویژهٔ نسخهٔ فیزیوتراپی</h2>
  <p class="ag-lead ag-lead--wide">ماکت‌ها نام‌ها و عددهای نمونه را نشان می‌دهند.</p>
  <div class="py-path" id="pyPath"><div class="st"><b>۱</b>ثبت بیمار</div><div class="st"><b>۲</b>نوبت واحد</div><div class="st"><b>۳</b>حضور</div><div class="st"><b>۴</b>فراخوان</div><div class="st"><b>۵</b>ثبت جلسه</div><div class="st"><b>۶</b>تسویه و نوبت بعد</div></div>
  <div class="py-grid">
    <div class="cd" id="pyUnits"><div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">تابلوی چهار واحد</div><div class="cd-r"><span class="py-sample">نمونه</span></div></div><div class="py-units" id="pyUnitBoard"></div></div>
    <div class="cd" id="pyIntake"><div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">شرح حال اولیه</div><div class="cd-r"><span class="py-sample">نمونه</span></div></div>
      <div class="py-in">
        <div class="row" id="pyArea"><span>ناحیهٔ درد</span><b id="pyAreaV">—</b></div>
        <div class="row" id="pyPain"><span>شدت درد ۰ تا ۱۰</span><div class="py-dots" id="pyDots"></div></div>
        <div class="row" id="pySurg"><span>سابقهٔ جراحی</span><b id="pySurgV">—</b></div>
      </div>
    </div>
    <div class="cd" id="pyRsv"><div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">رزرو روی واحد الکتروتراپی</div><div class="cd-r"><span class="py-sample">نمونه</span></div></div>
      <div class="py-rsv"><div class="cd-steps" id="pyRsvSteps"><div class="cd-stp"><b>۱</b> بیمار</div><div class="cd-stp"><b>۲</b> خدمت</div><div class="cd-stp"><b>۳</b> ساعت</div><div class="cd-stp"><b>۴</b> تأیید</div></div>
      <div class="py-search"><div class="box">جستجو: <b id="pyRsvQ"></b></div><div class="py-hit" id="pyRsvHit">خانم سارا رضایی <em>۰۹۱۲۰۰۰۰۰۰۰ · نمونه</em></div></div>
      <div class="py-opts" id="pyRsvOpts"></div><div class="py-sum" id="pyRsvSum"></div></div>
    </div>
    <div class="cd" id="pyQ"><div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">نمایشگر انتظار واحد</div><div class="cd-r">الکتروتراپی <span class="py-sample">نمونه</span></div></div>
      <div class="py-board"><div class="py-k">نوبت جاری</div><div class="py-num" id="pyQNum">—</div><div class="py-who" id="pyQWho">در انتظار</div><div class="py-room" id="pyQRoom"></div><div class="py-line" id="pyQLine"></div><div class="py-chips" id="pyQNext"></div></div>
    </div>
    <div class="cd" id="pySms"><div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">پیامک نوبت</div><div class="cd-r"><span class="py-sample">نمونه</span></div></div><div class="py-ph"><div class="py-screen"><div class="py-from">کلینیک نمونه حرکت</div><div class="py-sms" id="pySmsText"></div></div></div></div>
    <div class="cd" id="pySet"><div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">تسویه و نوبت بعدی</div><div class="cd-r"><span class="py-sample">نمونه</span></div></div>
      <div class="py-set"><div class="cd-steps" id="pySetSteps"><div class="cd-stp"><b>۱</b> جلسه</div><div class="cd-stp"><b>۲</b> تسویه</div><div class="cd-stp"><b>۳</b> نوبت بعد</div><div class="cd-stp"><b>۴</b> پیامک</div></div>
      <div class="py-row"><span>بیمار</span><b>خانم سارا رضایی</b></div>
      <div class="py-row"><span>خدمت</span><b>جلسه فیزیوتراپی (نمونه)</b></div>
      <div class="py-row"><span>مبلغ (نمونه)</span><b class="num" id="pySetAmt">—</b></div>
      <div class="py-pay" id="pySetPay"></div>
      <div class="py-follow" id="pySetNext">نوبت بعدی هنوز ثبت نشده است.</div></div>
    </div>
  </div>
</div></section>
<section class="ag-sec" id="security"><div class="ag-wrap"><h2>امنیت و لایسنس</h2><ul class="ag-list"><li>لایسنس امضاشده روی همان سیستم فعال می‌شود.</li><li>اطلاعات بیماران روی سیستم مطب می‌ماند و برای کار برنامه به اینترنت فرستاده نمی‌شود.</li><li>دسترسی هر کاربر جدا است. قیمت خرید فقط برای مدیر یا کاربر مجاز دیده می‌شود.</li><li>پیش از خروج می‌توان پشتیبان گرفت و تغییرات مهم ثبت می‌شود.</li></ul></div></section>
<section class="ag-sec" id="compare"><div class="ag-wrap"><h2>مقایسه با نسخه‌های دیگر</h2>
  <div class="ag-table-wrap"><table class="ag-cmp ag-cmp--two"><thead><tr><th>امکان</th><th>فیزیوتراپی</th><th>زیبایی</th><th>روانشناسی</th></tr></thead><tbody>
    <tr><th>ثبت بیمار، نوبت، پرونده، پیامک، تسویه</th><td>هست</td><td>هست</td><td>هست</td></tr>
    <tr><th>شماره نوبت جدا برای هر واحد</th><td>واحد درمان</td><td>واحد/اتاق</td><td>واحد/اتاق</td></tr>
    <tr><th>واحدها و خدمات آمادهٔ نسخه</th><td>چهار واحد درمانی و خدمات بذر فیزیوتراپی</td><td>اتاق تزریق و لیزر</td><td>اتاق مشاوره و روان‌سنجی</td></tr>
    <tr><th>شرح حال مختص رشته</th><td>ناحیه و شدت درد، سابقه جراحی</td><td>نوع پوست و تزریق قبلی</td><td>خواب، اضطراب، خلق و هدف جلسه</td></tr>
  </tbody></table></div>
  <p class="ag-note">زیبایی، روانشناسی و دندانپزشکی: <a href="{{ url('/software/medical-beauty') }}">زیبایی</a> · <a href="{{ url('/software/medical-psychology') }}">روانشناسی</a> · <a href="{{ url('/software/medical-dental') }}">دندانپزشکی</a>. کد فروش پیشنهادی تا تأیید شما جای خالی است: [[SKU: med-physio]]</p>
  <div class="ag-table-wrap"><table class="ag-cmp ag-cmp--two"><thead><tr><th>امکان</th><th>پایه</th><th>حرفه‌ای</th><th>چندکاربره / شبکه</th></tr></thead><tbody>
    <tr><th>ثبت بیمار و نوبت‌دهی</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
    <tr><th>گزارش بیمار</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
    <tr><th>تسویه و نوبت بعدی</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
    <tr><th>پیامک خودکار</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
    <tr><th>شماره نوبت هر واحد</th><td>[[ ]]</td><td>[[ ]]</td><td>[[ ]]</td></tr>
    <tr><th>چند کاربر همزمان</th><td>—</td><td>—</td><td>هست</td></tr>
    <tr><th>قیمت</th><td>[[قیمت / تماس بگیرید]]</td><td>[[قیمت / تماس بگیرید]]</td><td>[[قیمت / تماس بگیرید]]</td></tr>
  </tbody></table></div>
</div></section>
<section class="ag-sec" id="gallery"><div class="ag-wrap"><h2>تصاویر برنامه</h2><div class="py-gal">@foreach($shots as $i => $cap)<figure><div class="py-shot" role="img" aria-label="{{ $cap }}">تصویر {{ ['۱','۲','۳','۴','۵','۶'][$i] }}</div><figcaption>{{ $cap }}</figcaption></figure>@endforeach</div></div></section>
<section class="ag-sec" id="faq"><div class="ag-wrap"><h2>پرسش‌های رایج</h2><ul class="ag-steps" style="grid-template-columns:1fr">@foreach($faqs as $row)<li><b>{{ $row[0] }}</b><p>{{ $row[1] }}</p></li>@endforeach</ul></div></section>
<script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<section class="ag-sec ag-sec--last" id="demo"><div class="ag-wrap">
  <h2>نسخهٔ فیزیوتراپی را با واحدها و خدمات کلینیک خودتان ببینید</h2>
  <p class="ag-lead ag-lead--wide">دموی رایگان؛ واحدها و خدمات کلینیک شما را در برنامه می‌چینیم تا پیش از خرید، نوبت‌دهی، صف هر واحد، تسویه و پیامک را ببینید.</p>
  @if(session('success'))<p class="ag-note">{{ session('success') }}</p>@endif
  @if(session('error'))<p class="ag-note">{{ session('error') }}</p>@endif
  <div class="py-demo">
    <form class="py-form" id="pyDemo" method="post" action="{{ url('/support') }}">
      @csrf
      <label>نام<input name="name" value="{{ old('name') }}" required></label>
      <label>موبایل<input type="tel" name="phone" id="pyPhone" value="{{ old('phone') }}" required placeholder="۰۹۱۲xxxxxxx"></label>
      <label>نام مطب / کلینیک<input name="clinic" id="pyClinic" value="{{ old('clinic') }}" required></label>
      <label>شهر<input name="city" id="pyCity" value="{{ old('city') }}" required></label>
      <input type="hidden" name="subject" value="درخواست دمو حساب مطب فیزیوتراپی">
      <input type="hidden" name="body" id="pyBody" value="درخواست دموی نسخهٔ فیزیوتراپی.">
      <div class="full"><button class="ag-btn ag-btn--on" type="submit">درخواست دمو<i>←</i></button></div>
    </form>
    <div class="py-side"><p>تماس مستقیم</p><a href="tel:01144447220">۰۱۱۴۴۴۴۷۲۲۰</a></div>
  </div>
</div></section>
</div>
@include('studio::software-physio-script')
