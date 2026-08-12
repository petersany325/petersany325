@extends('layouts.hdd-land')
@section('title', ($page?->seo_title) ?: \App\Models\Setting::getValue('shop_name', 'HDD Land'))

@section('content')
<section class="banner">
  <div class="banner-grid">
    <div>
      <div class="kicker"><i></i> سایت شرکتی + فروشگاهی</div>
      <h1>بازیابی تخصصی، <em>خرید ابزار</em> در یک خانه</h1>
      <p class="lead">
        صفحه اول دو دروازه دارد: خدمات شرکتی برای دستگاه آسیب‌دیده، و فروشگاه برای نرم‌افزار و آموزش.
      </p>
      <div class="banner-actions">
        <a class="btn btn-red" href="{{ url('/contact') }}">درخواست بازیابی</a>
        <a class="btn btn-blue" href="{{ url('/products') }}">ورود به فروشگاه</a>
      </div>
      <div class="dual-gates">
        <div class="gate"><b>مسیر شرکتی</b>بازیابی · تعمیر · گارانتی</div>
        <div class="gate"><b>مسیر فروشگاهی</b>نرم‌افزار · آموزش · لایسنس</div>
      </div>
    </div>

    <div class="orbit" aria-hidden="true">
      <div class="ring r1"></div>
      <div class="ring r2"></div>
      <div class="ring r3"></div>
      <div class="orbit-core"><div class="disk"><b>DUAL</b>SERVICE + SHOP</div></div>
      <span class="sat s1">HDD</span>
      <span class="sat s2">SSD</span>
      <span class="sat s3">RAID</span>
      <span class="sat s4">خدمت</span>
      <span class="sat s5">فروش</span>
    </div>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <h2>دو مسیر اصلی صفحه اول</h2>
    <p>خدمات و فروشگاه هم‌سطح‌اند؛ جزئیات هرکدام داخل بخش خودش باز می‌شود.</p>
  </div>

  <div class="dual-paths">
    <article class="dual corp">
      <div class="code">CORPORATE</div>
      <h3>خدمات شرکتی</h3>
      <p>بازیابی اطلاعات، تعمیر استوریج و قبول گارانتی هارد — با فرم درخواست جدا.</p>
      <div class="actions">
        <a class="btn btn-red" href="{{ url('/contact') }}">ثبت درخواست</a>
        <a class="btn btn-ghost" href="{{ url('/services') }}">مشاهده خدمات</a>
      </div>
    </article>
    <article class="dual shop">
      <div class="code">SHOP</div>
      <h3>فروشگاه نرم‌افزار</h3>
      <p>خرید نرم‌افزار بازیابی/تعمیر، پکیج آموزش و لایسنس — با سبد خرید جدا.</p>
      <div class="actions">
        <a class="btn btn-blue" href="{{ url('/products') }}">باز کردن فروشگاه</a>
        <a class="btn btn-red" href="{{ url('/products') }}" style="background:#fff;color:var(--red);border:1.5px solid rgba(225,29,46,.35);box-shadow:none">محصولات</a>
      </div>
    </article>
  </div>

  <div class="sub-paths">
    <article class="sub">
      <h4>گارانتی هارد</h4>
      <p>قبول گارانتی شرکت‌ها با مدارک و پیگیری.</p>
      <a href="{{ url('/warranty') }}">ثبت گارانتی ←</a>
    </article>
    <article class="sub">
      <h4>آموزش تخصصی</h4>
      <p>دوره تعمیرات هارد و بازیابی اطلاعات.</p>
      <a href="{{ url('/training') }}">مشاهده دوره‌ها ←</a>
    </article>
    <article class="sub">
      <h4>بلاگ آموزشی</h4>
      <p>مقالات و راهنماهای تخصصی برای انتشار محتوا.</p>
      <a href="{{ url('/blog') }}">ورود به بلاگ ←</a>
    </article>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="section-head">
    <h2>پوشش دستگاه‌ها</h2>
    <p>تمرکز شرکتی صفحه اول برای اعتمادسازی.</p>
  </div>
  <div class="devices">
    <div class="device"><b>هارد دیسک</b><span>منطقی / فیزیکی</span></div>
    <div class="device"><b>SSD / NVMe</b><span>عدم شناسایی</span></div>
    <div class="device"><b>فلش</b><span>USB / کارت</span></div>
    <div class="device"><b>سرور</b><span>RAID / NAS</span></div>
    <div class="device"><b>DVR</b><span>دوربین</span></div>
    <div class="device"><b>موبایل</b><span>داده گوشی</span></div>
    <div class="device"><b>تعمیر</b><span>استوریج</span></div>
    <div class="device"><b>گارانتی</b><span>برندها</span></div>
  </div>
</section>

<section class="cta-band">
  <div>
    <h2>کدام مسیر را لازم دارید؟</h2>
    <p>خدمت حضوری یا خرید نرم‌افزار — هر دو از صفحه اول شروع می‌شود.</p>
  </div>
  <div class="actions">
    <a class="btn btn-red" href="{{ url('/contact') }}">درخواست بازیابی</a>
    <a class="btn btn-blue" href="{{ url('/products') }}">ورود به فروشگاه</a>
  </div>
</section>
@endsection
