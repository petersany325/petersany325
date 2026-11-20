@php
  $cards = \App\Support\StudioComputerMenu::cards();
  $shared = \App\Support\StudioComputerMenu::shared();
  $assistant = \App\Support\StudioComputerMenu::assistant();
  $network = \App\Support\StudioComputerMenu::network();
  $faqs = \App\Support\StudioComputerMenu::faqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
@endphp
<section class="ag-page" id="top">
  <div class="ag-wrap">
    <p class="ag-kicker">حسابداری کامپیوتری</p>
    <h1>حسابداری ویژهٔ صنف شما، با دستیار هوشمند فارسی</h1>
    <p class="ag-lead ag-lead--wide">فاکتور، انبار، خزانه، حقوق، مالیات و سامانهٔ مؤدیان در یک نرم‌افزار؛ هر صنف منو و منطق حسابداری خودش را دارد. دستیار هوش مصنوعی داخل خود نرم‌افزار و بدون اینترنت کار می‌کند.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو و مشاوره<i>←</i></a>
      <span class="ag-btn ag-btn--line">تماس در واتس‌اپ · [[لینک واتس‌اپ]]</span>
    </div>
    <p class="ag-note">نصب روی کامپیوتر و شبکهٔ خودتان · کار هم‌زمان چند کاربر · پشتیبانی [[مدت/نوع پشتیبانی]]</p>
    @include('studio::accounting-hub-demos', ['n' => 1])
  </div>
</section>

<section class="ag-sec" id="industries">
  <div class="ag-wrap">
    <p class="ag-kicker">صنف‌ها</p>
    <h2>صنف خود را انتخاب کنید</h2>
    <p class="ag-lead">هر نسخه فقط منوهایی را دارد که آن صنف لازم دارد؛ نه کمتر، نه شلوغ‌تر.</p>
    <div class="ag-cards ag-cards--dense">
      @foreach($cards as $row)
        <a class="ag-card" href="{{ url('/computer-accounting/'.$row['slug']) }}">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </a>
      @endforeach
    </div>
    <p class="ag-note">تعمیرگاه دارید؟ <a href="{{ url('/computer-accounting/technicians') }}">حسابداری تعمیرکاران</a> نسخهٔ کامپیوتری است. قبض آنلاین و کارتابل مشتری در <a href="{{ url('/accounting') }}">حسابداری وب</a> می‌ماند.</p>
  </div>
</section>

<section class="ag-sec" id="prices">
  <div class="ag-wrap">
    <p class="ag-kicker">قیمت</p>
    <h2>قیمت نرم‌افزار کامپیوتر</h2>
    <p class="ag-lead">مبالغ به تومان است. همهٔ نسخه‌ها با هم ۲۰۰ میلیون تومان است.</p>
    @php
      $editions = \App\Support\StudioPriceList::editions();
      $bundle = \App\Support\StudioPriceList::bundle();
      $addons = \App\Support\StudioPriceList::addons();
      $cartCount = count(\App\Support\StudioCart::skus());
    @endphp
    <style>
      .ag-price-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem}
      .ag-price{width:100%;border-collapse:collapse;background:#101628;border:1px solid rgba(255,255,255,.08);border-radius:16px;overflow:hidden}
      .ag-price th,.ag-price td{padding:.72rem .9rem;border-bottom:1px solid rgba(255,255,255,.06);text-align:right;font-size:.92rem}
      .ag-price thead th{color:#9aa3c7;font-weight:600;font-size:.74rem;background:#0c1224}
      .ag-price tbody th{color:#eef2ff;font-weight:600}
      .ag-price tbody th a{color:#eef2ff;text-decoration:none}
      .ag-price tbody th a:hover{color:#c4b5fd}
      .ag-price td{color:#fff;font-weight:700;white-space:nowrap}
      .ag-price tr.is-all th,.ag-price tr.is-all td{background:rgba(124,92,255,.16);color:#fff}
      .ag-price-block h3{margin:0 0 .55rem;font-size:1rem}
      .ag-price .ag-cart{border:0;border-radius:999px;background:#7c5cff;color:#fff;font:inherit;font-size:.74rem;font-weight:600;padding:.32rem .7rem;cursor:pointer}
      .ag-price .ag-cart.is-in{background:transparent;color:#c4b5fd;border:1px solid rgba(124,92,255,.5);text-decoration:none;display:inline-block}
      .ag-cart-bar{display:flex;justify-content:space-between;gap:.7rem;align-items:center;margin-top:.9rem;padding:.7rem .9rem;border-radius:14px;background:#101628;border:1px solid rgba(124,92,255,.35)}
      @media(max-width:800px){.ag-price-grid{grid-template-columns:1fr}.ag-price th,.ag-price td{padding:.62rem .7rem;font-size:.84rem}.ag-cart-bar{flex-direction:column;align-items:stretch}}
    </style>
    <div class="ag-price-grid">
      <div class="ag-price-block">
      <h3>قیمت نرم‌افزار کامپیوتر</h3>
      <table class="ag-price">
        <thead><tr><th>نسخه</th><th>قیمت</th><th></th></tr></thead>
        <tbody>
          @foreach(array_merge($editions, [$bundle]) as $row)
            <tr class="{{ $row['kind'] === 'bundle' ? 'is-all' : '' }}">
              <th>@if($row['kind'] === 'edition')<a href="{{ url($row['url']) }}">{{ $row['title'] }}</a>@else{{ $row['title'] }}@endif</th>
              <td>{{ \App\Support\StudioPriceList::label($row['million']) }}</td>
              <td>@include('studio::price-cart-button', ['sku' => $row['sku']])</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      </div>
      <div class="ag-price-block">
      <h3>قیمت افزونه‌ها</h3>
      <table class="ag-price">
        <thead><tr><th>افزونه</th><th>قیمت</th><th></th></tr></thead>
        <tbody>
          @foreach($addons as $row)
            <tr>
              <th>{{ $row['title'] }}</th>
              <td>{{ \App\Support\StudioPriceList::label($row['million']) }}</td>
              <td>@include('studio::price-cart-button', ['sku' => $row['sku']])</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      </div>
    </div>
    <div class="ag-cart-bar">
      <span>سبد شما: {{ strtr((string) $cartCount, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']) }} مورد</span>
      <a class="ag-btn ag-btn--on" href="{{ url('/computer-accounting/cart') }}">کارتابل سبد و پرداخت<i>←</i></a>
    </div>
    <p class="ag-note">مشاورهٔ فروش: <a href="tel:09113100473">۰۹۱۱۳۱۰۰۴۷۳</a></p>
  </div>
</section>

<section class="ag-sec" id="shared">
  <div class="ag-wrap">
    <p class="ag-kicker">مشترک</p>
    <h2>آنچه در همهٔ نسخه‌ها هست</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($shared as $row)
        <article class="ag-card">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <p class="ag-kicker">هوش مصنوعی</p>
    <h2>دستیار هوشمندی که حسابداری بلد است</h2>
    <p class="ag-lead ag-lead--wide">دستیار داخل خود نرم‌افزار و روی کامپیوتر شما کار می‌کند؛ اطلاعات مالی‌تان به اینترنت فرستاده نمی‌شود.</p>
    <div class="ag-cards ag-cards--dense">
      @foreach($assistant as $row)
        <article class="ag-card">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </article>
      @endforeach
    </div>
    @include('studio::accounting-hub-demos', ['n' => 2])
  </div>
</section>

<section class="ag-sec" id="network">
  <div class="ag-wrap">
    <p class="ag-kicker">شبکه</p>
    <h2>یک نرم‌افزار برای کل مجموعه</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($network as $row)
        <article class="ag-card">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="migrate">
  <div class="ag-wrap">
    <p class="ag-kicker">انتقال</p>
    <h2>اطلاعات قبلی‌تان را می‌آوریم</h2>
    <p class="ag-lead ag-lead--wide">انتقال اطلاعات از نرم‌افزارهای هلو، آرتا و پیو؛ و ورود اطلاعات کالا و اشخاص از Excel. [[اگر نرم‌افزارهای دیگری را هم با خدمات انتقال پشتیبانی می‌کنید، این‌جا نام ببرید]]</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/computer-accounting/data-migration') }}">مراحل مهاجرت داده</a>
    </div>
  </div>
</section>

<section class="ag-sec" id="trust">
  <div class="ag-wrap">
    <p class="ag-kicker">اعتماد</p>
    <h2>چرا حسابداری HDD Land</h2>
    <div class="ag-stats">
      <div><b>۱۸ سال</b><span>طراحی نرم‌افزار</span></div>
      <div><b>۴۱۸۹</b><span>شمارهٔ ثبت شرکت</span></div>
      <div><b>[[تعداد مشتری]]</b><span>مشتری</span></div>
      <div><b>[[مدت پشتیبانی]]</b><span>پشتیبانی</span></div>
    </div>
    <p class="ag-note">شرکت فراز نت آمل (سرزمین هارد) · شناسهٔ صنفی ۱۴۰۵۳۱۳۳۷۹</p>
  </div>
</section>

<section class="ag-sec" id="faq">
  <div class="ag-wrap">
    <p class="ag-kicker">پرسش‌ها</p>
    <h2>پرسش‌های رایج</h2>
    <ul class="ag-steps" style="grid-template-columns:1fr">
      @foreach($faqs as $row)
        <li>
          <b>{{ $row['q'] }}</b>
          <p>{{ $row['a'] }}</p>
        </li>
      @endforeach
    </ul>
  </div>
</section>
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@include('studio::accounting-hub-demo-script')

<section class="ag-sec ag-sec--last" id="demo">
  <div class="ag-wrap">
    <p class="ag-kicker">دمو</p>
    <h2>آمادهٔ دیدن نرم‌افزار هستید؟</h2>
    <p class="ag-lead">یک دموی کوتاه با صنف خودتان نشان‌تان می‌دهیم.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
  </div>
</section>
