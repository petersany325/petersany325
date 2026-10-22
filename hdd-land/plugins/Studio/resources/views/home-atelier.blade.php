@php
  $receiptUrl = $receiptUrl ?? \App\Support\SiteHost::receiptUrl();
  $shopUrl = $shopUrl ?? \App\Support\SiteHost::shopUrl('/');
@endphp
<section class="fx-hero" data-fx>
  <div class="fx-stars" aria-hidden="true"></div>
  <div class="fx-aurora" aria-hidden="true"></div>
  <div class="fx-follow" aria-hidden="true"></div>
  <div class="st-wrap fx-hero__in">
    <div class="fx-copy">
      <p class="fx-kicker"><span>AI DESIGN</span> شرکت طراحی هوش مصنوعی</p>
      <h1>تخصصی طراحی سایت<br>و نرم‌افزار</h1>
      <p class="fx-lead">از ورود باید معلوم باشد اینجا فروشگاه قطعه نیست. یک شرکت طراحی سایت و نرم‌افزار هستید؛ لایهٔ هوش مصنوعی روی همان کار می‌نشیند — سایت، محصول، حسابداری و پشتیبانی.</p>
      <ul class="fx-chips" aria-label="تخصص‌ها">
        <li>طراحی سایت</li>
        <li>نرم‌افزار تحت وب</li>
        <li>لایه هوش مصنوعی</li>
      </ul>
      <div class="st-actions">
        <a class="st-cta st-cta--on" href="{{ url('/contact') }}">درخواست طراحی سایت</a>
        <a class="st-cta st-cta--line" href="{{ url('/accounting') }}">نرم‌افزار هوشمند</a>
      </div>
    </div>
    <div class="fx-core" aria-hidden="true">
      <span class="fx-ring fx-ring--a"></span>
      <span class="fx-ring fx-ring--b"></span>
      <span class="fx-ring fx-ring--c"></span>
      <span class="fx-orb"><b>AI</b></span>
      <span class="fx-spark fx-spark--1"></span>
      <span class="fx-spark fx-spark--2"></span>
      <span class="fx-spark fx-spark--3"></span>
      <svg class="fx-net" viewBox="0 0 320 320" fill="none">
        <path d="M40 170 C90 40, 230 40, 280 170" />
        <path d="M50 210 C110 280, 210 280, 270 200" />
        <path d="M30 150 H290" />
        <circle cx="86" cy="92" r="3.2"/>
        <circle cx="232" cy="88" r="2.6"/>
        <circle cx="160" cy="248" r="2.8"/>
      </svg>
    </div>
  </div>
</section>

<section class="st-wrap fx-doors">
  <p class="fx-kicker">سه تخصص</p>
  <h2>طراحی سایت، نرم‌افزار، هوش مصنوعی — یک شرکت</h2>
  <p class="acc-sub">مشتری در سه ثانیه باید بفهمد طرف یک شرکت تخصصی طراحی سایت و نرم‌افزار است، نه کاتالوگ قطعه.</p>
  <div class="fx-door-grid">
    <a class="fx-door fx-door--hot" href="{{ url('/design') }}">
      <small>۰۱ — تخصص اصلی</small>
      <b>طراحی سایت</b>
      <p>فروشگاهی، شرکتی، خدماتی. ورودی مدرن؛ قابل مدیریت از ادمین.</p>
    </a>
    <a class="fx-door" href="{{ url('/accounting') }}">
      <small>۰۲ — نرم‌افزار</small>
      <b>نرم‌افزار حسابداری هوشمند</b>
      <p>دفتر سند و فاکتور. هوش فقط پیش‌نویس می‌دهد؛ ثبت با تأیید حسابدار است.</p>
    </a>
    <a class="fx-door" href="{{ url('/ai') }}">
      <small>۰۳ — لایه AI</small>
      <b>هوشمندسازی نرم‌افزار</b>
      <p>سرفصل سند، خواندن فاکتور، توضیح گزارش، پاسخ اولیه تیکت.</p>
    </a>
  </div>
</section>

<section class="st-wrap fx-note acc-sec--last">
  <div class="st-note">
    فروشگاه قطعات جدا است: <a href="{{ $shopUrl }}">{{ $shopUrl }}</a><br>
    سامانه قبض تعمیرگاه جدا می‌ماند: <a href="{{ $receiptUrl }}" rel="noopener">{{ $receiptUrl }}</a>
  </div>
</section>

<script>
(function(){
  var root=document.querySelector('[data-fx]');
  if(!root || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var follow=root.querySelector('.fx-follow');
  if(!follow) return;
  var x=0.5,y=0.35,tx=0.5,ty=0.35;
  root.addEventListener('pointermove',function(e){
    var r=root.getBoundingClientRect();
    tx=(e.clientX-r.left)/r.width;
    ty=(e.clientY-r.top)/r.height;
  });
  function tick(){
    x+=(tx-x)*0.08; y+=(ty-y)*0.08;
    follow.style.transform='translate('+((x-0.5)*80)+'px,'+((y-0.35)*60)+'px)';
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
})();
</script>
