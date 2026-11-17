@php
  $tabs = \App\Support\StudioComputerMenu::distributionTabs();
  $faqs = \App\Support\StudioComputerMenu::distributionFaqs();
  $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
  foreach ($faqs as $faq) {
      $faqLd['mainEntity'][] = [
          '@type' => 'Question',
          'name' => $faq['q'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
      ];
  }
  $cycle = [
      ['n' => '۱', 'title' => 'ویزیت', 'text' => 'ویزیتور طبق مسیر و برنامهٔ ویزیت به مغازه‌ها می‌رود؛ طرح‌ها و اشانتیون روی گوشی‌اش دیده می‌شود، حتی بی‌اینترنت.'],
      ['n' => '۲', 'title' => 'سفارش', 'text' => 'سفارش با گوشی ثبت می‌شود؛ اگر اینترنت نبود، بعداً خودش می‌رسد و هر سفارش فقط یک بار ثبت می‌شود.'],
      ['n' => '۳', 'title' => 'کنترل', 'text' => 'سفارش‌های بیش از سقف اعتبار، با چک برگشتی، تخفیف بیش از مجاز، خارج از محدودهٔ مغازه یا بیش از سهمیه، برای تأیید کنار می‌روند.'],
      ['n' => '۴', 'title' => 'بارگیری', 'text' => 'سفارش‌های تأییدشده روی ماشین چیده می‌شوند، ترتیب تحویل تعیین و فاکتورها از انبار ماشین صادر می‌شوند.'],
      ['n' => '۵', 'title' => 'تحویل', 'text' => 'موزع با گوشی تحویل، برگشتی یا «تحویل نشد» را ثبت می‌کند و دفتر وضعیت را زنده می‌بیند.'],
      ['n' => '۶', 'title' => 'تسویه', 'text' => 'عصر: شمارش بار برگشتی و کسری، وجه نقد موزع و ویزیتور، بستن روز و سند پورسانت.'],
  ];
  $roles = [
      ['title' => 'مدیر پخش (نرم‌افزار کامپیوتر)', 'items' => ['داشبورد مدیر پخش', 'کارتابل سفارش ویزیتورها', 'لیست پخش و تسویهٔ ماشین', 'نقشه و GPS', 'طرح‌ها، سهمیه و پورسانت']],
      ['title' => 'ویزیتور (اپ اندروید)', 'items' => ['مسیر و مشتری‌های روز', 'ثبت سفارش، حتی بی‌اینترنت', 'دیدن طرح‌ها، اشانتیون و «چند عدد دیگر تا پلهٔ بعد»', 'ثبت بی‌خریدی', 'دریافت نقد، کارتخوان و چک', 'بستن روز']],
      ['title' => 'موزع و راننده (اپ اندروید)', 'items' => ['لیست تحویل به ترتیب', 'ثبت تحویل، برگشتی یا «تحویل نشد»', 'مکان ماشین روی نقشه']],
  ];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>پخش</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ پخش مویرگی</p>
    <h1>نرم‌افزار حسابداری پخش؛ از سفارش ویزیتور تا تسویهٔ ماشین، روی نقشه</h1>
    <p class="ag-lead ag-lead--wide">ویزیتور با گوشی سفارش می‌گیرد، حتی بی‌اینترنت. سفارش در دفتر کنترل و روی ماشین بارگیری می‌شود، موزع تحویل را با گوشی ثبت می‌کند و عصر ماشین تسویه می‌شود. طرح‌های فروش و اشانتیون، سهمیه، پورسانت و GPS، همه با حسابداری و انبار کامل.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نسخهٔ پخش<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">همهٔ امکانات</a>
    </div>
    <p class="ag-note">اپ ویزیتور و موزع · نقشه و GPS · طرح‌های فروش پلکانی</p>
    @include('studio::accounting-distribution-demos', ['n' => 1])
  </div>
</section>

<section class="ag-sec" id="cycle">
  <div class="ag-wrap">
    <h2>چرخهٔ یک روز پخش</h2>
    <ol class="ag-steps ag-cycle">
      @foreach($cycle as $step)
        <li>
          <small>{{ $step['n'] }}</small>
          <b>{{ $step['title'] }}</b>
          <p>{{ $step['text'] }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>

<section class="ag-sec" id="roles">
  <div class="ag-wrap">
    <h2>سه نقش، سه ابزار</h2>
    <div class="ag-roles">
      @foreach($roles as $role)
        <article>
          <h3>{{ $role['title'] }}</h3>
          <ul>
            @foreach($role['items'] as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </article>
      @endforeach
    </div>
    <p class="ag-note">برنامهٔ اندروید یکی است و امکاناتش با مجوز و نقش هر گوشی باز می‌شود. (حداقل اندروید ۱۲)</p>
    @include('studio::accounting-distribution-demos', ['n' => 2])
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="dist-tab" id="dist-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="dist-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          <ul class="acc-cols{{ $tab['id'] === 'visitor' ? ' acc-cols--1' : '' }}">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @if($tab['id'] === 'visitor')
            <p class="ag-lead">سفارش گوشی ویزیتور به کارتابل دفتر می‌رسد، بررسی می‌شود و بعد فاکتور یا راهی لیست پخش می‌شود. نام‌ها و مبلغ‌ها ساختگی است.</p>
            <div class="vo-flow" aria-hidden="true">
              <div class="vo-step" id="voS1"><i>۱</i><span>سفارش از گوشی ویزیتور</span></div>
              <div class="vo-link" id="voL1"></div>
              <div class="vo-step" id="voS2"><i>۲</i><span>کنترل پنج قاعده</span></div>
              <div class="vo-link" id="voL2"></div>
              <div class="vo-step" id="voS3"><i>۳</i><span>فاکتور یا لیست پخش</span></div>
            </div>
            <div class="vo-win" id="voWin">
              <div class="vo-bar">
                <div class="vo-dots" aria-hidden="true"><b></b><b></b><b></b></div>
                <div class="vo-title">کارتابل سفارش ویزیتورها</div>
                <div class="vo-count" id="voCount"></div>
              </div>
              <div class="vo-tools" aria-label="کارهای کارتابل">
                <span class="is-on">تأیید و صدور فاکتور</span>
                <span class="is-on">تأیید برای لیست پخش</span>
                <span>پیش‌فاکتور</span>
                <span>اصلاح</span>
                <span>رد</span>
              </div>
              <div class="vo-grid vo-head"><div>سفارش</div><div>ویزیتور</div><div>مشتری</div><div>مبلغ</div><div>وضعیت</div><div></div></div>
              <div class="vo-rows" id="voRows"></div>
              <div class="vo-toast" id="voToast"></div>
              <div class="vo-cursor" id="voCursor" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 2l16 10-7 1.5L9.5 21z" fill="#fff" stroke="#070b18" stroke-width="1.5"/></svg>
                <span>سرپرست</span>
              </div>
            </div>
          @elseif(($tab['alt'] ?? '') === 'لیست پخش و بارگیری ماشین')
            @include('studio::accounting-distribution-demos', ['n' => 3])
          @elseif(($tab['alt'] ?? '') === 'طرح فروش و اشانتیون پلکانی')
            @include('studio::accounting-distribution-demos', ['n' => 4])
          @elseif(($tab['alt'] ?? '') === 'داشبورد مدیر پخش')
            @include('studio::accounting-distribution-demos', ['n' => 5])
          @elseif(!empty($tab['alt']))
            <figure class="ag-shot-ph"><span>{{ $tab['alt'] }}</span></figure>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec ag-offline" id="gate">
  <div class="ag-wrap">
    <h2>هر سفارشی فاکتور نمی‌شود</h2>
    <p class="ag-lead ag-lead--wide">سفارش گوشی ویزیتور مستقیم فاکتور نمی‌شود. نرم‌افزار آن را بررسی می‌کند و اگر یکی از این‌ها باشد، برای تأیید سرپرست کنار می‌گذارد:</p>
    <div class="vo-rules" id="voRules">
      <div class="vo-card" data-rule="1"><div class="n">۱</div><div class="tx">بیش از سقف اعتبار مشتری</div><div class="go">با احتساب سفارش‌های منتظر دیگرش</div></div>
      <div class="vo-card" data-rule="2"><div class="n">۲</div><div class="tx">چک برگشتی باز</div><div class="go">← تأیید سرپرست</div></div>
      <div class="vo-card" data-rule="3"><div class="n">۳</div><div class="tx">تخفیف بیش از سقف مجاز ویزیتور</div><div class="go">← تأیید سرپرست</div></div>
      <div class="vo-card" data-rule="4"><div class="n">۴</div><div class="tx">ثبت سفارش بیرون از محدودهٔ مغازه</div><div class="go">← تأیید سرپرست</div></div>
      <div class="vo-card" data-rule="5"><div class="n">۵</div><div class="tx">بیش از سهمیهٔ ماهانهٔ ویزیتور</div><div class="go">← تأیید سرپرست</div></div>
    </div>
  </div>
</section>
<script>
(function () {
  var RULES = {1:'بیش از سقف اعتبار',2:'چک برگشتی باز',3:'تخفیف بیش از سقف ویزیتور',4:'بیرون از محدودهٔ مغازه',5:'بیش از سهمیهٔ ماهانه'};
  var ORDERS = [
    {no:2041,v:'رضا کریمی',c:'سوپرمارکت ستاره',amt:18450000,rule:0,done:'فاکتور شد'},
    {no:2042,v:'مریم احمدی',c:'فروشگاه گلستان',amt:42800000,rule:1},
    {no:2043,v:'رضا کریمی',c:'بقالی امید',amt:6300000,rule:0,done:'در لیست پخش'},
    {no:2044,v:'حسین نوری',c:'هایپر آفتاب',amt:27900000,rule:3},
    {no:2045,v:'مریم احمدی',c:'مینی‌مارکت باران',amt:9750000,rule:4}
  ];
  var APPROVE = 2042;
  var rowsEl = document.getElementById('voRows');
  var win = document.getElementById('voWin');
  var cur = document.getElementById('voCursor');
  var toast = document.getElementById('voToast');
  var countEl = document.getElementById('voCount');
  if (!rowsEl || !win) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function faN(n){return String(n).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})}
  function fa(n){return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'٬').replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})}
  function sleep(ms){return new Promise(function(r){setTimeout(r,ms)})}
  function card(k){return document.querySelector('.vo-card[data-rule="'+k+'"]')}
  function steps(a){
    ['voS1','voS2','voS3'].forEach(function(id,i){
      var e = document.getElementById(id);
      if (!e) return;
      e.classList.toggle('is-on', a===i+1);
      e.classList.toggle('is-bad', a===-2 && i===1);
    });
  }
  function run(id){var e=document.getElementById(id); if(!e) return; e.classList.remove('is-run'); void e.offsetWidth; e.classList.add('is-run')}
  function updateCount(){
    var n = rowsEl.querySelectorAll('.vo-row.is-flag').length;
    countEl.innerHTML = n ? '<em>'+fa(n)+'</em> سفارش منتظر تأیید سرپرست' : 'همهٔ سفارش‌ها بررسی شد';
  }
  function makeRow(o){
    var r = document.createElement('div');
    r.className = 'vo-row vo-grid';
    r.dataset.no = o.no;
    r.innerHTML = '<div class="num">#'+faN(o.no)+'</div><div>'+o.v+'</div><div>'+o.c+'</div>'+
      '<div class="amt">'+fa(o.amt)+' <small>ریال</small></div>'+
      '<div class="st"><span class="vo-badge is-check">در حال بررسی</span></div><div class="act"></div>';
    return r;
  }
  function resolve(o,r){
    var st = r.querySelector('.st'), act = r.querySelector('.act');
    if (o.rule) {
      r.classList.add('is-flag'); r.dataset.rule = o.rule;
      st.innerHTML = '<span class="vo-badge is-bad">نیاز به تأیید سرپرست</span><span class="vo-reason">'+RULES[o.rule]+'</span>';
      act.innerHTML = '<span class="y">تأیید</span><span>اصلاح</span>';
      var c = card(o.rule);
      if (c) { c.classList.add('is-on'); c.classList.remove('is-pulse'); void c.offsetWidth; c.classList.add('is-pulse'); }
    } else {
      r.classList.add('is-ok');
      st.innerHTML = '<span class="vo-badge is-ok">✓ '+o.done+'</span>';
    }
    updateCount();
  }
  function approveRow(r){
    if (!r) return;
    var rule = r.dataset.rule;
    r.classList.remove('is-flag'); r.classList.add('is-ok');
    delete r.dataset.rule;
    r.querySelector('.st').innerHTML = '<span class="vo-badge is-ok">✓ تأیید سرپرست · فاکتور شد</span><span class="vo-reason is-done">'+RULES[rule]+'</span>';
    r.querySelector('.act').innerHTML = '';
    var c = card(rule); if (c) c.classList.remove('is-on');
    steps(3); run('voL2'); updateCount();
    toast.textContent = 'فاکتور سفارش #'+faN(APPROVE)+' با تأیید سرپرست صادر شد';
    toast.classList.add('is-show');
  }
  function moveCursor(el){
    var w = win.getBoundingClientRect(), b = el.getBoundingClientRect();
    cur.style.transform = 'translate('+(b.left-w.left+b.width/2)+'px,'+(b.top-w.top+b.height/2)+'px)';
  }
  async function supervisor(){
    var r = rowsEl.querySelector('.vo-row[data-no="'+APPROVE+'"]'); if (!r) return;
    var btn = r.querySelector('.y');
    cur.style.transition = 'none';
    cur.style.transform = 'translate('+(win.clientWidth*0.15)+'px,'+(win.clientHeight-40)+'px)';
    void cur.offsetWidth; cur.style.transition = '';
    cur.style.opacity = 1;
    await sleep(80); moveCursor(btn); await sleep(1000);
    btn.classList.add('is-press'); await sleep(220); btn.classList.remove('is-press');
    approveRow(r);
    await sleep(600); cur.style.opacity = 0;
  }
  async function play(){
    while (true) {
      rowsEl.style.opacity = 1; rowsEl.innerHTML = ''; toast.classList.remove('is-show');
      document.querySelectorAll('.vo-card').forEach(function(c){c.classList.remove('is-on')});
      updateCount();
      for (var i = 0; i < ORDERS.length; i++) {
        var o = ORDERS[i], r = makeRow(o);
        steps(1); rowsEl.appendChild(r); await sleep(450);
        run('voL1'); steps(2); await sleep(900);
        resolve(o, r);
        if (o.rule) steps(-2); else { run('voL2'); steps(3); }
        await sleep(750);
      }
      steps(0); await sleep(700);
      await supervisor();
      await sleep(3200);
      rowsEl.style.opacity = 0; toast.classList.remove('is-show'); steps(0);
      await sleep(600);
    }
  }
  function staticView(){
    ORDERS.forEach(function(o){var r = makeRow(o); rowsEl.appendChild(r); resolve(o,r)});
    var r = rowsEl.querySelector('.vo-row[data-no="'+APPROVE+'"]');
    approveRow(r);
    cur.style.opacity = 0;
  }
  document.querySelectorAll('.vo-card').forEach(function(c){
    c.addEventListener('mouseenter', function(){
      rowsEl.querySelectorAll('.vo-row[data-rule="'+c.dataset.rule+'"]').forEach(function(r){r.classList.add('is-hl')});
    });
    c.addEventListener('mouseleave', function(){
      rowsEl.querySelectorAll('.vo-row.is-hl').forEach(function(r){r.classList.remove('is-hl')});
    });
  });
  if (reduce) { staticView(); return; }
  var started = false;
  function start(){ if (!started) { started = true; play(); } }
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function(es){ if (es[0].isIntersecting) start(); }, {threshold: 0.25}).observe(win);
  } else start();
})();
</script>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیاری که پخش را می‌فهمد</h2>
    <ul class="ag-list">
      <li>«فروش ویزیتور احمدی این ماه؟»، «کدام مسیر بی‌خریدی بیشتری دارد؟» را با نوشتن یا صدای فارسی بپرسید</li>
      <li>تحلیل فروش و مشتریان، پیش‌بینی نقدینگی، تحلیل چک‌ها</li>
      <li>همه روی کامپیوتر خودتان، بدون فرستادن اطلاعات به اینترنت</li>
    </ul>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/ai') }}">بیشتر دربارهٔ هوش مصنوعی</a>
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
    <h2>پخش شرکت خودتان را روی نقشه ببینید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
  </div>
</section>
@include('studio::accounting-distribution-demo-script')
