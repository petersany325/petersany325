@php
  $tabs = [
      ['id' => 'intake', 'title' => 'پذیرش', 'lead' => 'میز ورود دستگاه به تعمیرگاه. قبض همان لحظه ساخته می‌شود و برچسب چاپ می‌شود.', 'items' => ['پذیرش تکی یا گروهی دستگاه', 'جستجوی قبض با سریال، موبایل یا شماره', 'لیست و فیلتر پذیرش‌ها', 'تحویل گروهی چند قبض آماده', 'ورود قطعه از راه دور، با عکس و باربری', 'سطل زباله برای بازیابی قبض حذف‌شده']],
      ['id' => 'handoff', 'title' => 'ارجاع', 'lead' => 'دستگاه از پذیرش به میز تعمیرکار می‌رود و محلش روی قبض می‌ماند.', 'items' => ['اعلان «ارجاع دستگاه برای دریافت»', 'تأیید دریافت روی کارتابل تعمیرکار', 'بازگشت به پذیرش فقط بعد از گزارش کار', 'گزارش محل فعلی دستگاه و تاریخچه ارجاع']],
      ['id' => 'cost', 'title' => 'تأیید هزینه', 'lead' => 'اگر هزینه نیاز به اجازهٔ مشتری دارد، لینک تأیید در کارتابل می‌ماند.', 'items' => ['صف درخواست‌های در انتظار تأیید', 'تعیین خدماتی که تأیید مشتری لازم دارند', 'تاریخچهٔ تأیید یا رد', 'پیامک کد تأیید برای مشتری']],
      ['id' => 'customer', 'title' => 'کارتابل مشتری', 'lead' => 'مشتری وضعیت تعمیر را از پرتال می‌بیند؛ نیرو و مشتری قاطی نمی‌شوند.', 'items' => ['پیگیری وضعیت قبض', 'دیدن مبلغ و تأیید یا رد هزینه', 'پرداخت آنلاین یا ثبت فیش', 'گارانتی، پیام و پروفایل مشتری']],
      ['id' => 'stock', 'title' => 'انبار', 'lead' => 'قطعهٔ مصرفی روی همان قبض از انبار کم می‌شود.', 'items' => ['میز انبار و انبارهای چندگانه', 'رسید ورود و حواله خروج', 'کارتکس و گردش کالا', 'ارزش موجودی و تعریف کالای جدید']],
      ['id' => 'sms', 'title' => 'پیامک', 'lead' => 'با عوض شدن وضعیت قبض، قالب پیامک همان وضعیت برای مشتری می‌رود.', 'items' => ['قالب پیامک هر وضعیت', 'گزارش پیامک‌های ارسال‌شدهٔ قبض', 'متن خوش‌آمد ورود نیرو']],
      ['id' => 'staff', 'title' => 'کارمندان', 'lead' => 'هر نیرو فقط بخش‌هایی را می‌بیند که به او داده شده.', 'items' => ['تعریف نیرو، وظیفه و دسترسی', 'کارتابل کارآموز و بازهٔ دوره', 'تخصص و درصد کمیسیون تعمیرکار']],
      ['id' => 'books', 'title' => 'حسابداری', 'lead' => 'قطعه و اجرت قبض به دفتر همین تعمیرگاه می‌رسد. این دفتر، حسابداری کامپیوتری صنوف نیست.', 'items' => ['میز حسابداری تعمیرگاه', 'اسناد روزنامه و سند دستی', 'سرفصل، دفتر معین و تراز آزمایشی', 'لیست بدهکاران']],
      ['id' => 'reports', 'title' => 'گزارش‌ها', 'lead' => 'عملکرد کارگاه، صندوق و محل دستگاه از روی قبض‌های واقعی.', 'items' => ['عملکرد تعمیرکاران', 'کالای خرج‌شده و عملیات کارگاه', 'صندوق و دریافت‌ها', 'تأیید فیش بانکی و پیام مشتری']],
      ['id' => 'backup', 'title' => 'بکاپ', 'lead' => 'نگهداری سامانه و پشتیبان، جدا از رایانهٔ هر تعمیرکار.', 'items' => ['نگهداری و پشتیبان‌گیری', 'تنظیمات سیستم و دفتر روز', 'پروفایل کاربر واردشده']],
  ];
  $faqs = [
      ['q' => 'این صفحه با حسابداری کامپیوتری تعمیرکاران یکی است؟', 'a' => 'نه. این سامانهٔ وب تعمیرگاه است: پذیرش، ارجاع، تأیید هزینه و کارتابل مشتری در مرورگر. حسابداری تعمیرکاران برنامهٔ ویندوز روی SQL Server 2022 داخل کارگاه است و صفحهٔ جدا دارد.'],
      ['q' => 'مشتری وضعیت دستگاه را از کجا می‌بیند؟', 'a' => 'از کارتابل مشتری: پیگیری قبض، تأیید یا رد هزینه، پرداخت، گارانتی و پیام. منوی نیرو جداست.'],
      ['q' => 'پیامک وضعیت به اینترنت وصل است؟', 'a' => 'ارسال پیامک به درگاه پیامک وصل است. بقیهٔ کار کارتابل روی همین سامانه می‌ماند.'],
      ['q' => 'قیمت این سامانه چقدر است؟', 'a' => 'قیمتی اینجا اعلام نشده. برای دیدن دمو با دفتر تماس بگیرید.'],
  ];
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/accounting') }}">نرم‌افزار وب</a>
      <span>/</span>
      <span>تعمیرگاه</span>
    </p>
    <p class="ag-kicker">سامانهٔ وب تعمیرگاه</p>
    <h1>مدیریت تعمیرگاه روی وب؛ از پذیرش دستگاه تا کارتابل مشتری</h1>
    <p class="ag-lead ag-lead--wide">پذیرش و قبض، ارجاع به تعمیرکار، تأیید هزینه، انبار قطعه، پیامک وضعیت و کارتابل مشتری در یک سامانهٔ وب. نیرو کار خودش را می‌بیند و مشتری فقط وضعیت دستگاه خودش را.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱ ۴۴۴۴ ۷۲۲۰</a>
      <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop/staff-menu') }}">منوی کارکنان</a>
      <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop/customer-menu') }}">کارتابل مشتری</a>
    </div>
    <p class="ag-note">نمونهٔ زیر ساختگی است. حسابداری کامپیوتری تعمیرکاران صفحهٔ جدا دارد: <a href="{{ url('/computer-accounting/technicians') }}">حسابداری تعمیرکاران</a>.</p>
    <div class="cd" id="rsDesk">
      <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">میز کار تعمیرگاه · نمونه</div><div class="cd-r">تعمیرگاه نمونه</div></div>
      <div class="cd-hero">
        <div class="cd-mods" id="rsMods"></div>
        <div class="cd-feed">
          <div class="cd-kpis">
            <div class="cd-kpi"><div class="k">قبض باز</div><div class="v num" id="rsK1">۰</div></div>
            <div class="cd-kpi"><div class="k">در دست تعمیر</div><div class="v num" id="rsK2">۰</div></div>
            <div class="cd-kpi"><div class="k">آمادهٔ تحویل</div><div class="v num" id="rsK3">۰</div></div>
          </div>
          <div id="rsFeed"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات سامانه</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="rs-tab" id="rs-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="rs-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          <p class="ag-lead">{{ $tab['lead'] }}</p>
          <ul class="acc-cols">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @if($tab['id'] === 'intake')
            <div class="cd" id="rsIn">
              <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">ساخت قبض · نمونه</div><div class="cd-r">پذیرش</div></div>
              <div class="cd-body">
                <div class="cd-steps" id="rsInSt">
                  <div class="cd-stp"><b>۱</b>مشتری</div>
                  <div class="cd-stp"><b>۲</b>دستگاه</div>
                  <div class="cd-stp"><b>۳</b>ایراد</div>
                  <div class="cd-stp"><b>۴</b>برچسب قبض</div>
                </div>
                <table>
                  <tbody>
                    <tr><th>مشتری</th><td id="rsIn1">—</td></tr>
                    <tr><th>دستگاه</th><td id="rsIn2">—</td></tr>
                    <tr><th>ایراد</th><td id="rsIn3">—</td></tr>
                    <tr><th>شماره قبض</th><td class="num" id="rsIn4">—</td></tr>
                  </tbody>
                </table>
                <div class="cd-toast" id="rsInT"></div>
              </div>
            </div>
          @elseif($tab['id'] === 'customer')
            <div class="cd" id="rsCu">
              <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">کارتابل مشتری · نمونه</div><div class="cd-r">قبض ۱۰۸۴</div></div>
              <div class="cd-body">
                <div class="cd-steps" id="rsCuSt">
                  <div class="cd-stp"><b>۱</b>پیگیری</div>
                  <div class="cd-stp"><b>۲</b>تأیید هزینه</div>
                  <div class="cd-stp"><b>۳</b>پرداخت</div>
                </div>
                <p id="rsCuSay">مشتری با موبایل وارد می‌شود و فقط قبض‌های خودش را می‌بیند.</p>
                <span class="cd-chip" id="rsCuChip">در انتظار</span>
                <div class="ag-actions" style="margin-top:.8rem">
                  <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop/customer-menu') }}">همهٔ منوی کارتابل مشتری</a>
                </div>
              </div>
            </div>
          @elseif($tab['id'] === 'cost')
            <div class="cd" id="rsAp">
              <div class="cd-bar"><div class="cd-dots" aria-hidden="true"><b></b><b></b><b></b></div><div class="cd-t">صف تأیید هزینه · نمونه</div><div class="cd-r">کارتابل نیرو</div></div>
              <div class="cd-body">
                <div class="cd-cnts">
                  <div class="cd-cnt"><div class="k">در انتظار</div><div class="v num" id="rsA0">۰</div></div>
                  <div class="cd-cnt"><div class="k">تأیید شد</div><div class="v num is-green" id="rsA1">۰</div></div>
                  <div class="cd-cnt"><div class="k">رد شد</div><div class="v num is-red" id="rsA2">۰</div></div>
                </div>
                <div class="cd-q" id="rsAQ"></div>
              </div>
            </div>
          @endif
        </div>
      @endforeach
    </div>
    <div class="ag-actions" style="margin-top:1rem">
      <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop/staff-menu') }}">همهٔ منوی کارکنان</a>
    </div>
  </div>
</section>

<section class="ag-sec" id="compare">
  <div class="ag-wrap">
    <h2>فرق با دفتر و با حسابداری کامپیوتر</h2>
    <div class="ag-table-wrap">
      <table class="ag-cmp ag-cmp--two">
        <thead>
          <tr><th>موضوع</th><th>دفتر و تلفن</th><th>حسابداری کامپیوتر تعمیرکاران</th><th>این سامانهٔ وب</th></tr>
        </thead>
        <tbody>
          <tr><th>محل کار</th><td>کاغذ و تماس</td><td>برنامهٔ ویندوز داخل کارگاه</td><td>مرورگر، برای نیرو و مشتری</td></tr>
          <tr><th>پیگیری دستگاه</th><td>گشتن کارگاه</td><td>قبض و کارتابل روی همان رایانه</td><td>ارجاع و محل دستگاه در کارتابل وب</td></tr>
          <tr><th>کارتابل مشتری</th><td>تماس مشتری</td><td>ندارد؛ برنامه داخل کارگاه است</td><td>پیگیری، تأیید هزینه و پرداخت</td></tr>
          <tr><th>حسابداری</th><td>دفتر جدا</td><td>دفتر کامل صنوف روی SQL Server 2022</td><td>دفتر همین تعمیرگاه، وصل به قبض</td></tr>
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
    <h2>دمو را با کارگاه خودتان ببینید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop/staff-menu') }}">منوی کارکنان</a>
      <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop/customer-menu') }}">کارتابل مشتری</a>
    </div>
  </div>
</section>
@include('studio::repair-shop-script')
