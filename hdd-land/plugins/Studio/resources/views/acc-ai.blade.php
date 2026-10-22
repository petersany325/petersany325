@php
  $receiptUrl = $receiptUrl ?? \App\Support\SiteHost::receiptUrl();
  $shopUrl = $shopUrl ?? \App\Support\SiteHost::shopUrl('/');
@endphp
<section class="acc-hero">
  <div class="acc-hero__grid" aria-hidden="true"></div>
  <div class="acc-hero__glow acc-hero__glow--steel" aria-hidden="true"></div>
  <div class="acc-hero__glow acc-hero__glow--amber" aria-hidden="true"></div>
  <div class="ag-wrap st-wrap acc-hero__in">
    <div class="acc-copy">
      <p class="acc-kicker">محصول پرچم استودیو</p>
      <h1>نرم‌افزار پیشرفته حسابداری هوش مصنوعی</h1>
      <p class="acc-lead">دفتر واقعی برای فاکتور، سند، انبار و گزارش. هوش مصنوعی فقط پیش‌نویس می‌دهد؛ سند وقتی قطعی است که حسابدار تأیید کند.</p>
      <div class="ag-actions st-actions">
        <a class="ag-btn ag-btn--on st-cta st-cta--on" href="{{ url('/contact') }}">درخواست دمو حسابداری<i>←</i></a>
        <a class="ag-btn ag-btn--line st-cta st-cta--line" href="{{ url('/ai') }}">لایه AI جداگانه<i>←</i></a>
      </div>
      <p class="acc-rule">پروژه از پایه با لاراول طراحی و برنامه‌نویسی می‌شود. ادعای بستن خودکار دفاتر مالیاتی نداریم؛ هوش مصنوعی فقط پیش‌نویس می‌دهد.</p>
    </div>
    <aside class="acc-ledger" aria-label="پیش‌نمایش سند و پیشنهاد هوش مصنوعی">
      <header class="acc-ledger__bar">
        <span>سند روزانه ۱۲۴۷</span>
        <em>پیش‌نویس AI</em>
      </header>
      <table>
        <thead>
          <tr><th>حساب</th><th>بدهکار</th><th>بستانکار</th></tr>
        </thead>
        <tbody>
          <tr>
            <td>موجودی کالا — انبار اصلی</td>
            <td>۱۸٬۴۰۰٬۰۰۰</td>
            <td></td>
          </tr>
          <tr>
            <td>حساب‌های پرداختنی — تأمین‌کننده</td>
            <td></td>
            <td>۱۸٬۴۰۰٬۰۰۰</td>
          </tr>
        </tbody>
      </table>
      <div class="acc-ledger__ai">
        <small>پیشنهاد هوش مصنوعی</small>
        <p>فاکتور خرید SSD خوانده شد. سرفصل پیشنهادی: موجودی کالا / پرداختنی. مبلغ و طرف حساب را قبل از ثبت قطعی کنترل کنید.</p>
      </div>
    </aside>
  </div>
</section>

<section class="ag-wrap st-wrap acc-sec">
  <h2>هوش مصنوعی کجا حق دارد وارد شود</h2>
  <p class="acc-sub">چهار کار مشخص. بدون شعار «هوش مصنوعی همه‌کاره».</p>
  <div class="acc-jobs">
    <article>
      <small>۰۱</small>
      <b>پیشنهاد سرفصل سند</b>
      <p>از شرح فاکتور، حساب بدهکار و بستانکار را پیشنهاد می‌دهد. ثبت فقط بعد از تأیید.</p>
    </article>
    <article>
      <small>۰۲</small>
      <b>خواندن فاکتور</b>
      <p>مبلغ، طرف حساب و تاریخ را از تصویر یا PDF برمی‌دارد و پیش‌نویس سند می‌سازد.</p>
    </article>
    <article>
      <small>۰۳</small>
      <b>توضیح گزارش</b>
      <p>تراز و سود و زیان را به زبان مدیر می‌گوید؛ عدد گزارش را عوض نمی‌کند.</p>
    </article>
    <article>
      <small>۰۴</small>
      <b>پاسخ اولیه پشتیبانی</b>
      <p>پیش‌نویس جواب تیکت مشتری. ارسال با دست اپراتور است.</p>
    </article>
  </div>
</section>

<section class="ag-wrap st-wrap acc-sec" id="industries">
  <h2>حسابداری به تفکیک صنف</h2>
  <p class="acc-sub">هر نسخه منو و گردش کار همان کسب‌وکار را دارد. همه پروژه‌ها با لاراول نوشته می‌شوند.</p>
  <div class="ag-cards ag-cards--dense">
    @foreach(($industries ?? []) as $row)
      <a class="ag-card" href="{{ url('/accounting/'.$row['slug']) }}">
        <div>
          <b>{{ $row['title'] }}</b>
          <p>{{ $row['short'] }}</p>
          <small>توضیحات و منوها ←</small>
        </div>
      </a>
    @endforeach
  </div>
  @if(!empty($laravelNote))
    <p class="ag-note" style="margin-top:1rem">{{ $laravelNote }}</p>
  @endif
</section>

<section class="ag-wrap st-wrap acc-sec">
  <h2>دفتر حسابداری، کامل</h2>
  <p class="acc-sub">هوش مصنوعی روی همین دفتر سوار است. اگر سند و انبار نباشد، AI تزئین است. منوهای حسابداری وب مثل سایت قبلی این‌جا هستند.</p>
  <div class="ag-menu-grid">
    @foreach(($groups ?? []) as $group)
      <article class="ag-menu-card" id="{{ $group['slug'] }}">
        <small>حسابداری وب</small>
        <b>{{ $group['title'] }}</b>
        <ul>
          @foreach($group['items'] as $mod)
            <li><a href="{{ url('/accounting/m/'.$mod['slug']) }}">{{ $mod['title'] }}</a></li>
          @endforeach
        </ul>
      </article>
    @endforeach
  </div>
</section>

<section class="ag-wrap st-wrap acc-sec acc-sec--last">
  <div class="acc-split">
    <div>
      <h2>برای چه کسی است</h2>
      <ul class="st-list">
        <li>فروشگاه قطعه و خدمات که هم فروش دارد هم تعمیر</li>
        <li>کسب‌وکاری که سایت و دفتر را یکی می‌خواهد</li>
        <li>حسابداری که پیش‌نویس می‌خواهد، نه ربات امضاکننده</li>
      </ul>
    </div>
    <div class="st-note">
      فروشگاه قطعات جدا است: <a href="{{ $shopUrl }}">{{ $shopUrl }}</a><br>
      سامانه قبض تعمیرگاه جدا می‌ماند: <a href="{{ $receiptUrl }}" rel="noopener">{{ $receiptUrl }}</a>
    </div>
  </div>
</section>
