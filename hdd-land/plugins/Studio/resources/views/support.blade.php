@extends('studio::layout')
@section('title', $title ?? 'پشتیبانی HDD Land | تیکت، راهنما و پرسش‌های رایج')
@section('description', $description ?? 'پشتیبانی مشتریان HDD Land برای حسابداری، دستیار، اپ همراه، تعمیرکاران، سایت و بازیابی اطلاعات.')
@section('content')
@php
  $ticket = session('ticket');
  $editions = $editions ?? [];
  $statusLabel = ['open' => 'در انتظار', 'pending' => 'در حال بررسی', 'review' => 'در حال بررسی', 'closed' => 'بسته شد'];
@endphp
<link rel="stylesheet" href="{{ asset('css/studio-agency.css') }}?v=55">
<div class="su">
  <div class="su-wrap">
    <section class="su-hero">
      <div>
        <div class="su-kick">پشتیبانی · شرکت فراز نت آمل (سرزمین هارد)</div>
        <h1>پشتیبانی مشتریان HDD Land</h1>
        <p>اول جواب را این‌جا پیدا کنید؛ اگر نبود، تیکت بزنید. هر تیکت شماره می‌گیرد و پیامک تأیید برایتان می‌آید.</p>
        <div class="su-acts">
          <a class="su-b p" href="#suTicket">ثبت تیکت</a>
          <a class="su-b" href="{{ url('/account/tickets') }}">پیگیری تیکت‌هایم</a>
          <a class="su-b" href="tel:01144447220">تماس با دفتر</a>
        </div>
        <div class="su-search"><input id="suQ" placeholder="مثلاً: پشتیبان‌گیری، رمز، اتصال به سرور، مجوز…" aria-label="جست‌وجو در پرسش‌ها"></div>
      </div>
      <img class="su-bot" src="{{ asset('images/ai/r-thumb.webp') }}" width="120" height="120" alt="دستیار HDD Land" fetchpriority="high" decoding="async">
    </section>

    <section class="su-tight">
      <h2>دربارهٔ کدام محصول؟</h2>
      <div class="su-prods" id="suProds">
        <button type="button" class="su-pr on" data-p="acc"><span>🧮</span><b>حسابداری HDD Land</b></button>
        <button type="button" class="su-pr" data-p="ai"><span>🤖</span><b>دستیار هوشمند</b></button>
        <button type="button" class="su-pr" data-p="app"><span>📱</span><b>اپ همراه</b></button>
        <button type="button" class="su-pr" data-p="rep"><span>🔧</span><b>حسابداری تعمیرکاران</b></button>
        <button type="button" class="su-pr" data-p="web"><span>🌐</span><b>سایت</b></button>
        <button type="button" class="su-pr" data-p="rec"><span>💽</span><b>بازیابی اطلاعات</b></button>
      </div>
      <div class="su-help">
        <div>
          <div id="suFaq"></div>
          <div class="su-empty" id="suEmpty">جوابی پیدا نشد. <a href="#suTicket">تیکت بزنید ←</a></div>
        </div>
        <div class="su-tip" id="suTip">
          <h3>پیش از تیکت: اطلاعات سیستم را بفرستید</h3>
          <p>در حسابداری HDD Land از منوی سیستم «پشتیبانی» را باز کنید و «کپی اطلاعات سیستم» را بزنید. متن را در تیکت بچسبانید و تصویر پیام خطا را هم پیوست کنید.</p>
          <div class="su-win" id="suWin">
            <div class="su-bar"><i></i><i></i><i></i><span>حسابداری HDD Land — پشتیبانی</span></div>
            <div class="su-body">
              <div class="su-kv"><span>نرم‌افزار</span><span>حسابداری HDD Land · فروشگاهی</span></div>
              <div class="su-kv"><span>نسخه</span><b dir="ltr">1.32.30</b></div>
              <div class="su-kv"><span>سرور / پایگاه داده</span><b dir="ltr">SHOP-PC · Demo</b></div>
              <div class="su-kv"><span>ویندوز</span><b dir="ltr">Windows 11 · .NET 8</b></div>
              <div class="su-kv"><span>کاربر / شرکت</span><span>مدیر · فروشگاه نمونه</span></div>
              <span class="su-copy" id="suCopy">کپی اطلاعات سیستم</span>
              <span class="su-msg" id="suMsg">اطلاعات کپی شد.</span>
            </div>
          </div>
          <div class="su-note">رمز یا کلیدی در این متن نیست. مقدارها نمونه هستند.</div>
        </div>
      </div>
    </section>

    <section class="su-tight" id="suTicket">
      <h2>ثبت تیکت</h2>
      @if(session('error'))<div class="su-error">{{ session('error') }}</div>@endif
      @if(is_array($ticket))
        <div class="su-done">
          <b>تیکت شما ثبت شد</b>
          @if(($ticket['id'] ?? '') !== '')<div class="su-no" dir="ltr">{{ $ticket['id'] }}</div>@endif
          <p>پیامک تأیید به <span dir="ltr">{{ $ticket['phone'] ?? '' }}</span> فرستاده می‌شود. وضعیت را در <a href="{{ url('/account/tickets') }}">کارتابل مشتری</a> ببینید.</p>
        </div>
      @endif
      <div class="su-tk">
        <form method="post" action="{{ url('/support') }}" enctype="multipart/form-data">
          @csrf
          <label>محصول
            <select name="product" id="suProduct">
              <option value="acc" @selected(old('product', 'acc') === 'acc')>حسابداری HDD Land</option>
              <option value="ai" @selected(old('product') === 'ai')>دستیار هوشمند</option>
              <option value="app" @selected(old('product') === 'app')>اپ همراه</option>
              <option value="rep" @selected(old('product') === 'rep')>حسابداری تعمیرکاران</option>
              <option value="web" @selected(old('product') === 'web')>سایت</option>
              <option value="rec" @selected(old('product') === 'rec')>بازیابی اطلاعات</option>
            </select>
          </label>
          <label id="suEdition">نسخهٔ صنفی
            <select name="edition">
              @foreach($editions as $edition)
                <option @selected(old('edition') === $edition)>{{ $edition }}</option>
              @endforeach
            </select>
          </label>
          <div class="su-full">
            <div class="su-k">فوریت</div>
            <div class="su-urg">
              <label><input type="radio" name="urgency" value="normal" @checked(old('urgency', 'normal') !== 'stopped')> عادی</label>
              <label><input type="radio" name="urgency" value="stopped" @checked(old('urgency') === 'stopped')> کارم متوقف شده</label>
            </div>
          </div>
          <label>نام و نام خانوادگی<input name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required></label>
          <label>موبایل<input name="phone" dir="ltr" inputmode="tel" value="{{ old('phone') }}" placeholder="09xxxxxxxxx" required></label>
          <label class="su-full">شرح مشکل<textarea name="body" rows="5" required placeholder="چه کاری می‌کردید و چه پیامی آمد؟ متن «کپی اطلاعات سیستم» را هم این‌جا بچسبانید.">{{ old('body') }}</textarea></label>
          <label class="su-full">پیوست<input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf"></label>
          <p class="su-muted">عکس jpg، png، webp یا PDF، حداکثر ۵ مگابایت.</p>
          <button class="su-b p" type="submit">ثبت تیکت</button>
        </form>
        <div class="su-side">
          <div class="su-card">
            <h3>تیکت‌های من</h3>
            @auth
              @forelse(array_slice($tickets ?? [], 0, 5) as $row)
                <div class="su-row"><span dir="ltr">{{ $row['id'] }}</span> · {{ $row['subject'] }} <em>{{ $statusLabel[$row['status']] ?? 'در انتظار' }}</em></div>
              @empty
                <p class="su-muted">تیکت بازی ندارید.</p>
              @endforelse
              <a class="su-b" href="{{ url('/account/tickets') }}">کارتابل مشتری ←</a>
            @else
              <p class="su-muted">برای دیدن تیکت‌های خودتان وارد شوید.</p>
              <a class="su-b p" href="{{ url('/auth/customer') }}">ورود</a>
            @endauth
          </div>
          <div class="su-card">
            <h3>تماس</h3>
            <div class="su-row"><span>پشتیبانی و دفتر</span><a dir="ltr" href="tel:01144447220">۰۱۱ ۴۴۴۴ ۷۲۲۰</a></div>
            <div class="su-row"><span>خرید و تمدید · مهندس رضانژاد</span><a dir="ltr" href="tel:09113100473">۰۹۱۱ ۳۱۰ ۰۴۷۳</a></div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
@php
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  $faqGroups = [
      'acc' => [
          ['پشتیبان‌گیری چطور است؟', 'در منوی سیستم «پشتیبان‌گیری» را باز کنید. فقط مدیر سیستم می‌تواند پشتیبان بگیرد. نوع پشتیبان کامل یا افزایشی است و آن را روی دیسک، فلش یا شبکه ذخیره می‌کنید. رمزگذاری AES-256 پیش‌فرض روشن است و پشتیبان خودکار را می‌توان زمان‌بندی کرد.'],
          ['کلاینت به سرور وصل نمی‌شود', 'در منوی سیستم «اتصال به پایگاه داده» را باز کنید. نقش این رایانه باید کلاینت باشد و نام سرور و نام پایگاه داده درست باشند. اگر مشکل ماند، متن کپی اطلاعات سیستم را در تیکت بفرستید.'],
          ['تمدید یا ارتقای نسخه', 'در منوی سیستم «فعال‌سازی / تغییر نسخه» را باز کنید، کد درخواست بسازید و برای مشاور فروش بفرستید. اعمال مجوز و تغییر نسخه داده‌های حسابداری را تغییر نمی‌دهد.'],
          ['رمز ورودم را فراموش کرده‌ام', 'رمز کاربران را مدیر سیستم در معرفی کاربر عوض می‌کند. هر کاربر رمز خودش را از تغییر کلمه عبور عوض می‌کند.'],
          ['راهنمای هر صفحه کجاست؟', 'در خود برنامه راهنما و آموزش هست. مبحث‌ها مخصوص همان حسابداری و مجوز شماست.'],
      ],
      'ai' => [
          ['دستیار به اینترنت نیاز دارد؟', 'نه. دستیار روی کامپیوتر خودتان کار می‌کند.'],
          ['دستیار خودش چیزی ثبت می‌کند؟', 'نه. هر ثبتی فقط پیشنهاد است و با تأیید شما انجام می‌شود. سطح دسترسی هر نقش را هم مدیر تعیین می‌کند.'],
      ],
      'app' => [
          ['گوشی را چطور به سرور وصل کنم؟', 'گوشی با لینک یا QR اتصال به سرور وصل می‌شود. آن را از حسابداری HDD Land روی رایانهٔ سرور بگیرید.'],
      ],
      'rep' => [
          ['کد تأییدی که پیامک شد چیست؟', 'کد تأیید هزینهٔ تعمیر است. با دادن آن، هزینه‌ای را که تعمیرگاه اعلام کرده تأیید می‌کنید.'],
      ],
      'rec' => [
          ['دستگاه صدای غیرعادی می‌دهد', 'دستگاه را خاموش کنید و دوباره روشن نکنید. خودتان باز یا تعمیرش نکنید؛ این کار احتمال بازیابی را کم می‌کند. بعد با دفتر تماس بگیرید.'],
      ],
  ];
  foreach ($faqGroups as $rows) {
      foreach ($rows as $row) {
          $faqLd['mainEntity'][] = ['@type' => 'Question', 'name' => $row[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $row[1]]];
      }
  }
@endphp
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var FAQ = @json($faqGroups);
  var cur = 'acc';
  var prods = document.querySelectorAll('.su-pr');
  var q = document.getElementById('suQ');
  var faq = document.getElementById('suFaq');
  var empty = document.getElementById('suEmpty');
  var tip = document.getElementById('suTip');
  var product = document.getElementById('suProduct');
  var edition = document.getElementById('suEdition');
  function esc(s) { return String(s).replace(/[&<>]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;'}[c]; }); }
  function render() {
    var query = (q && q.value || '').trim();
    var list = [];
    if (query) {
      Object.keys(FAQ).forEach(function (k) { FAQ[k].forEach(function (x) { if ((x[0] + x[1]).indexOf(query) >= 0) list.push(x); }); });
    } else if (FAQ[cur]) list = FAQ[cur];
    faq.innerHTML = list.map(function (x, i) { return '<details class="su-faq"' + (i === 0 && !query ? ' open' : '') + '><summary>' + esc(x[0]) + '</summary><p>' + esc(x[1]) + '</p></details>'; }).join('');
    empty.hidden = list.length > 0;
    tip.hidden = !(cur === 'acc' || query);
  }
  function pick(p) {
    cur = p;
    prods.forEach(function (x) { x.classList.toggle('on', x.getAttribute('data-p') === p); });
    if (product) product.value = p;
    if (edition) edition.hidden = p !== 'acc';
    if (q) q.value = '';
    render();
  }
  prods.forEach(function (x) { x.addEventListener('click', function () { pick(x.getAttribute('data-p')); }); });
  if (product) product.addEventListener('change', function () { pick(product.value); });
  if (q) q.addEventListener('input', function () {
    prods.forEach(function (x) { x.classList.toggle('on', !q.value && x.getAttribute('data-p') === cur); });
    render();
  });
  pick(product ? product.value : 'acc');
  var win = document.getElementById('suWin');
  var msg = document.getElementById('suMsg');
  var copy = document.getElementById('suCopy');
  function showCopied() { if (msg) msg.classList.add('on'); }
  if (reduce) { showCopied(); return; }
  function loop() {
    if (!win || win.hidden) return;
    if (msg) msg.classList.remove('on');
    if (copy) copy.classList.remove('press');
    setTimeout(function () { if (copy) copy.classList.add('press'); }, 1200);
    setTimeout(function () { if (copy) copy.classList.remove('press'); showCopied(); }, 1500);
    setTimeout(loop, 4300);
  }
  if (win && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) { if (es[0].isIntersecting) { io.disconnect(); loop(); } }, { threshold: 0.4 });
    io.observe(win);
  } else loop();
})();
</script>
@endsection
