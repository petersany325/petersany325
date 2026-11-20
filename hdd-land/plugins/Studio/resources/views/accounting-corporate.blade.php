@php
  $tabs = \App\Support\StudioComputerMenu::corporateTabs();
  $faqs = \App\Support\StudioComputerMenu::corporateFaqs();
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
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>شرکتی</span>
    </p>
    <p class="ag-kicker">حسابداری کامپیوتری · نسخهٔ شرکتی</p>
    <h1>نرم‌افزار حسابداری شرکتی؛ دفتر قانونی، حقوق، مالیات و بودجه در یک برنامه</h1>
    <p class="ag-lead ag-lead--wide">برای شرکت‌هایی که حسابداری کامل می‌خواهند: سند و دفاتر قانونی، خزانه و چک، بازرگانی و انبار، حقوق و دستمزد، دارایی ثابت، سامانهٔ مؤدیان و دفتر دوم و بودجه، با دستیار هوش مصنوعی فارسی که بدون اینترنت کار می‌کند.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دموی نسخهٔ شرکتی<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="#features">مشاهدهٔ همهٔ امکانات</a>
    </div>
    @include('studio::sale-bar', ['sku' => 'acc-corporate'])
    <p class="ag-note">سامانهٔ مؤدیان · چندکاربره و شبکه‌ای · فروش و خرید ارزی</p>
    @include('studio::accounting-corporate-demos', ['n' => 1])
  </div>
</section>

<section class="ag-sec" id="fit">
  <div class="ag-wrap">
    <h2>برای چه شرکتی مناسب است</h2>
    <div class="ag-cards ag-cards--dense">
      <article class="ag-card"><div><b>شرکت‌های بازرگانی</b><p>خرید و فروش داخلی و خارجی، انبار، چک و تسویهٔ اشخاص.</p></div></article>
      <article class="ag-card"><div><b>شرکت‌های خدماتی و پروژه‌ای</b><p>قرارداد، صورت‌وضعیت، مدیریت کارها و سود هر نوع کار.</p></div></article>
      <article class="ag-card"><div><b>شرکت‌هایی که حسابرسی و مالیات برایشان مهم است</b><p>دفاتر قانونی، سامانهٔ مؤدیان، ارزش افزوده و حسابرسی مالیاتی.</p></div></article>
    </div>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <h2>امکانات</h2>
    <div class="acc-tabs">
      @foreach($tabs as $i => $tab)
        <input type="radio" name="corp-tab" id="corp-{{ $tab['id'] }}" @checked($i === 0)>
        <label for="corp-{{ $tab['id'] }}">{{ $tab['title'] }}</label>
        <div class="acc-tabs__panel">
          <h2>{{ $tab['title'] }}</h2>
          @if(($tab['lead'] ?? '') !== '')
            <p class="ag-lead">{{ $tab['lead'] }}</p>
          @endif
          <ul class="acc-cols">
            @foreach($tab['items'] as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
          @if(($tab['alt'] ?? '') === 'ترازنامه در نرم‌افزار حسابداری شرکتی')
            @include('studio::accounting-corporate-demos', ['n' => 2])
          @elseif(($tab['alt'] ?? '') === 'حقوق و دستمزد در حسابداری HDD Land')
            @include('studio::accounting-corporate-demos', ['n' => 3])
          @elseif(($tab['alt'] ?? '') === 'ارسال به سامانهٔ مؤدیان')
            @include('studio::accounting-corporate-demos', ['n' => 4])
          @elseif(!empty($tab['alt']))
            <figure class="ag-shot-ph"><span>{{ $tab['alt'] }}</span></figure>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="special">
  <div class="ag-wrap">
    <h2>ویژهٔ نسخهٔ شرکتی</h2>
    <div class="ag-cards">
      <article class="ag-card"><div><b>دفتر دوم و بودجه</b><p>یک دفتر موازی کنار دفتر اصلی قانونی که فقط اسناد اصلاحیه می‌گیرد؛ تراز تطبیقی دو دفتر و ردپای تغییرناپذیر دفتر دوم. تعریف بودجه برای حساب، مرکز هزینه و پروژه و گزارش بودجه در برابر عملکرد.</p></div></article>
      <article class="ag-card"><div><b>حسابرسی مالیاتی</b><p>پیش از رسیدگی مالیاتی، نرم‌افزار دوره را بررسی می‌کند و یافته‌ها را با توضیح نشان می‌دهد: تطبیق ارزش افزوده با دفتر، کنترل شناسهٔ خریداران و نرخ مالیات و تطبیق فروش با سامانهٔ مؤدیان.</p></div></article>
    </div>
    @include('studio::accounting-corporate-demos', ['n' => 5])
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <h2>دستیار مالی هوشمند داخل نرم‌افزار</h2>
    <p class="ag-lead">روی کامپیوتر خودتان و بدون فرستادن اطلاعات به اینترنت:</p>
    <ul class="ag-list">
      <li>حسابدار هوشمند: ثبت با زبان طبیعی</li>
      <li>مدیر مالی هوشمند و داشبورد سلامت مالی</li>
      <li>پیش‌بینی نقدینگی، تحلیل فروش، مشتریان، چک‌ها و بانک</li>
      <li>اسکن هوشمند فاکتور (OCR) و ورودی هوشمند اسناد</li>
      <li>حسابرس هوشمند</li>
      <li>گفت‌وگو با نوشتن یا صدای فارسی</li>
    </ul>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/ai') }}">بیشتر دربارهٔ هوش مصنوعی</a>
    </div>
  </div>
</section>

<section class="ag-sec" id="security">
  <div class="ag-wrap">
    <h2>امنیت و کنترل</h2>
    <ul class="ag-list">
      <li>هر کاربر فقط همان حساب‌ها، انبارها و اعمالی را می‌بیند که به او داده شده است.</li>
      <li>اسناد قطعی و دوره‌های قفل‌شده قابل تغییر نیستند؛ هر تغییر در رویدادهای ممیزی ثبت می‌شود.</li>
      <li>اطلاعات روی سرور خود شرکت می‌ماند؛ پشتیبان‌گیری با آزمایش سلامت.</li>
    </ul>
  </div>
</section>

<section class="ag-sec" id="compare">
  <div class="ag-wrap">
    <h2>مقایسه با نسخهٔ فروشگاهی</h2>
    <div class="ag-table-wrap">
      <table class="ag-cmp ag-cmp--two">
        <thead>
          <tr><th>امکان</th><th>فروشگاهی</th><th>شرکتی</th></tr>
        </thead>
        <tbody>
          <tr><th>حسابداری، خزانه، بازرگانی، انبار، مالیات</th><td>هست</td><td>هست</td></tr>
          <tr><th>فروش و خرید ارزی (با مجوز چندارزی)</th><td>هست</td><td>هست</td></tr>
          <tr><th>حقوق و دستمزد</th><td>—</td><td>هست</td></tr>
          <tr><th>قراردادها و مدیریت مشتریان</th><td>—</td><td>هست</td></tr>
          <tr><th>مدیریت وظایف و صورت‌وضعیت</th><td>—</td><td>هست</td></tr>
          <tr><th>دفتر دوم و بودجه</th><td>—</td><td>هست</td></tr>
          <tr><th>بایگانی پرونده‌ها</th><td>—</td><td>هست</td></tr>
        </tbody>
      </table>
    </div>
    <p class="ag-note">نیاز به همهٔ صنوف دارید؟ نسخهٔ «همهٔ صنوف» هم هست. <a href="{{ url('/contact') }}">تماس</a></p>
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
    <h2>نسخهٔ شرکتی را با اطلاعات شرکت خودتان ببینید</h2>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
  </div>
</section>
<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function faN(n){return String(n).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})}
  function fa(n){return faN(String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'٬'))}
  function sleep(ms){return new Promise(function(r){setTimeout(r, reduce ? 0 : ms)})}
  function el(id){return document.getElementById(id)}
  function count(node, to, ms){
    if (!node) return Promise.resolve();
    if (reduce) { node.textContent = fa(to); return Promise.resolve(); }
    return new Promise(function(res){
      var t0 = performance.now();
      (function f(t){
        var p = Math.min(1, (t - t0) / ms);
        node.textContent = fa(Math.round(to * (1 - Math.pow(1 - p, 3))));
        if (p < 1) requestAnimationFrame(f); else res();
      })(t0);
    });
  }
  function onVisible(node, fn){
    if (!node) return;
    if (reduce || !('IntersectionObserver' in window)) { fn(); return; }
    var io = new IntersectionObserver(function(es){ if (es[0].isIntersecting) { io.disconnect(); fn(); } }, {threshold: 0.3});
    io.observe(node);
  }
  async function loop(fn){ do { await fn(); } while (!reduce); }

  var MODS = [
    ['📒','حسابداری','سند شمارهٔ ۱۲۴۵ قطعی شد'],
    ['🏦','خزانه و چک','چک ۲۴۸۱۵۶ وصول شد · ۱۲۰ میلیون ریال'],
    ['🛒','بازرگانی','فاکتور فروش ۳۱۰۸ صادر شد'],
    ['📦','انبار','حوالهٔ انبار مرکزی ثبت شد'],
    ['👥','حقوق و دستمزد','سند حقوق مهر صادر شد'],
    ['🏢','دارایی ثابت','استهلاک ماهانه محاسبه شد'],
    ['🧾','سامانهٔ مؤدیان','۱۲ صورت‌حساب به سامانه ارسال شد'],
    ['📑','دفتر دوم','تراز تطبیقی دفتر اول و دوم آماده شد']
  ];
  var mods = el('cdMods'), feed = el('cdFeed');
  if (mods) {
    MODS.forEach(function(m){
      var d = document.createElement('div'); d.className = 'cd-mod';
      d.innerHTML = '<i>'+m[0]+'</i><span>'+m[1]+'</span>';
      mods.appendChild(d);
    });
  }
  function addEv(m){
    if (!feed) return;
    var e = document.createElement('div'); e.className = 'cd-ev';
    e.innerHTML = '<i>'+m[0]+'</i><div><div class="m">'+m[1]+'</div><div class="x">'+m[2]+'</div></div><div class="tm">همین حالا</div>';
    feed.insertBefore(e, feed.firstChild);
    while (feed.children.length > 4) feed.removeChild(feed.lastChild);
  }
  onVisible(el('cd1'), function(){
    count(el('cdK1'), 18450, 1600); count(el('cdK2'), 6320, 1600); count(el('cdK3'), 7, 1600);
    if (reduce) { MODS.slice(0, 4).forEach(addEv); return; }
    loop(async function(){
      for (var i = 0; i < MODS.length; i++) {
        [].forEach.call(mods.children, function(c, j){ c.classList.toggle('is-on', j === i); });
        addEv(MODS[i]); await sleep(1700);
      }
    });
  });

  var BS = [['دارایی‌های جاری',48200,39600],['دارایی‌های غیرجاری',31500,29100],['جمع دارایی‌ها',79700,68700,1],['بدهی‌های جاری',22400,19900],['بدهی‌های غیرجاری',9800,8600],['حقوق مالکانه',47500,40200],['جمع بدهی‌ها و حقوق مالکانه',79700,68700,1]];
  async function playBS(){
    var bs = el('cdBs'), eq = el('cdEq'); if (!bs) return;
    bs.innerHTML = ''; if (eq) eq.classList.remove('is-show');
    var cells = [];
    BS.forEach(function(r){
      var tr = document.createElement('tr'); if (r[3]) tr.className = 'is-sum';
      var ch = Math.round((r[1] - r[2]) / r[2] * 100);
      tr.innerHTML = '<td>'+r[0]+'</td><td class="n num">۰</td><td class="n num">۰</td><td class="n hide-m"><span class="'+(ch>=0?'up':'dn')+'">'+(ch>=0?'▲ ':'▼ ')+faN(Math.abs(ch))+'٪</span></td>';
      bs.appendChild(tr); cells.push([tr.children[1], r[1]], [tr.children[2], r[2]]);
    });
    await Promise.all(cells.map(function(c){ return count(c[0], c[1], 1500); }));
    await sleep(300); if (eq) eq.classList.add('is-show'); await sleep(900);
    var ex = document.querySelectorAll('#cd2 .cd-exp');
    for (var i = 0; i < ex.length; i++) { ex[i].classList.add('is-flash'); await sleep(500); ex[i].classList.remove('is-flash'); }
    await sleep(3500);
  }
  onVisible(el('cd2'), function(){ loop(playBS); });

  var PAY = [['سارا محمدی',30,182000000,24500000],['علی رضایی',30,156000000,19800000],['نرگس حسینی',28,141000000,16900000],['امیر کاظمی',30,128000000,14600000]];
  function step(n){
    [].forEach.call(document.querySelectorAll('#cdPst .cd-stp'), function(s, i){
      s.classList.toggle('is-on', i === n); s.classList.toggle('is-done', i < n);
    });
  }
  async function playPay(){
    var pay = el('cdPay'), vch = el('cdVch'); if (!pay) return;
    pay.innerHTML = ''; if (vch) vch.classList.remove('is-show');
    document.querySelectorAll('#cd3 .cd-file').forEach(function(f){ f.classList.remove('is-show'); });
    step(0);
    var rows = PAY.map(function(p){
      var tr = document.createElement('tr');
      tr.innerHTML = '<td>'+p[0]+'</td><td class="n num">'+faN(p[1])+'</td><td class="n num hide-m muted">—</td><td class="n num hide-m muted">—</td><td class="n num muted">—</td>';
      pay.appendChild(tr); return tr;
    });
    await sleep(1400); step(1);
    for (var i = 0; i < PAY.length; i++) {
      var c = rows[i].children;
      [2,3,4].forEach(function(k){ c[k].classList.remove('muted'); });
      count(c[2], PAY[i][2], 700); count(c[3], PAY[i][3], 700); await count(c[4], PAY[i][2] - PAY[i][3], 700);
      await sleep(150);
    }
    await sleep(500); step(2); if (vch) vch.classList.add('is-show');
    await sleep(1400); step(3);
    var fs = document.querySelectorAll('#cd3 .cd-file');
    for (var j = 0; j < fs.length; j++) { fs[j].classList.add('is-show'); await sleep(450); }
    step(4); await sleep(3500);
  }
  onVisible(el('cd3'), function(){ loop(playPay); });

  var INV = [['۳۱۰۴','شرکت نمونهٔ البرز',86400000],['۳۱۰۵','بازرگانی پارسه',42150000],['۳۱۰۶','فروشگاه نگین',18900000,1],['۳۱۰۷','شرکت نمونهٔ کیان',127300000],['۳۱۰۸','تولیدی سپهر',54600000]];
  function setCnt(){
    var q = el('cdQ'); if (!q) return;
    var c = [0,0,0,0];
    q.querySelectorAll('.cd-row').forEach(function(r){ c[+r.dataset.s]++; });
    for (var i = 0; i < 4; i++) { var n = el('cdM'+i); if (n) n.textContent = faN(c[i]); }
  }
  function setSt(r, s, why){
    r.dataset.s = s; r.classList.toggle('is-bad', s === 3);
    var html = [
      '<span class="cd-chip">در صف</span>',
      '<span class="cd-chip is-wait">ارسال شد · در انتظار پاسخ</span>',
      '<span class="cd-chip is-ok">✓ تأیید سامانه</span>',
      '<span class="cd-chip is-bad">نیاز به اصلاح</span><span class="why">'+(why||'')+'</span>'
    ][s];
    r.querySelector('.st').innerHTML = html; setCnt();
  }
  async function playQ(){
    var q = el('cdQ'), t4 = el('cdT4'); if (!q) return;
    q.innerHTML = ''; if (t4) t4.classList.remove('is-show');
    var rows = INV.map(function(v){
      var r = document.createElement('div'); r.className = 'cd-row';
      r.innerHTML = '<div class="num muted">#'+v[0]+'</div><div>'+v[1]+'</div><div class="num hide-m">'+fa(v[2])+' <span class="muted">ریال</span></div><div class="st"></div>';
      q.appendChild(r); setSt(r, 0); return r;
    });
    await sleep(1000);
    for (var i = 0; i < rows.length; i++) { setSt(rows[i], 1); await sleep(450); }
    await sleep(700);
    for (var k = 0; k < rows.length; k++) { if (INV[k][3]) setSt(rows[k], 3, 'شناسهٔ خریدار نامعتبر'); else setSt(rows[k], 2); await sleep(500); }
    await sleep(1500);
    var bad = rows.filter(function(r, i){ return INV[i][3]; })[0];
    if (bad) {
      var why = bad.querySelector('.why'); if (why) why.textContent = 'اصلاح شد · ارسال دوباره';
      await sleep(900); setSt(bad, 1); await sleep(1300); setSt(bad, 2);
    }
    if (t4) { t4.textContent = 'همهٔ صورت‌حساب‌ها با وضعیت سامانه تطبیق داده شد'; t4.classList.add('is-show'); }
    await sleep(3500);
  }
  onVisible(el('cd4'), function(){ loop(playQ); });

  var BUD = [['واحد فروش',3200,3460],['هزینه‌های اداری',4200,3950],['پروژهٔ توسعه',6000,3900]];
  var bud = el('cdBud');
  if (bud) {
    BUD.forEach(function(b){
      var d = document.createElement('div'); d.className = 'cd-bl';
      d.innerHTML = '<div class="tp"><span>'+b[0]+'</span><span class="muted"><span class="num">'+fa(b[2])+'</span> از <span class="num">'+fa(b[1])+'</span> · <span class="pct num">۰٪</span></span></div><div class="track"><div class="fill"></div><div class="mark"></div></div>';
      bud.appendChild(d);
    });
  }
  async function play5(){
    var b2 = el('cdB2'); if (!b2) return;
    b2.querySelectorAll('.doc').forEach(function(d){ d.remove(); });
    el('cdR2').textContent = fa(4200); el('cdRd').textContent = '—'; el('cdRd').className = 'n num';
    el('cdTrail').classList.remove('is-show');
    var bls = bud.querySelectorAll('.cd-bl');
    bls.forEach(function(d){
      var f = d.querySelector('.fill'); f.style.transition = 'none'; f.style.width = '0'; f.classList.remove('is-over');
      void f.offsetWidth; f.style.transition = '';
      var pe = d.querySelector('.pct'); pe.textContent = '۰٪'; pe.style.color = '';
    });
    await sleep(700);
    bls.forEach(function(d, i){
      var p = Math.round(BUD[i][2] / BUD[i][1] * 100);
      var f = d.querySelector('.fill');
      f.style.width = (Math.min(p, 120) / 1.2) + '%';
      if (p > 100) f.classList.add('is-over');
      var pe = d.querySelector('.pct');
      count(pe, p, 1400).then(function(){ pe.textContent = faN(p)+'٪'; if (p > 100) pe.style.color = '#ff5d6c'; });
    });
    await sleep(1600);
    var a = document.createElement('div'); a.className = 'doc is-adj'; a.textContent = 'اصلاحیهٔ ۱ · ذخیرهٔ هزینهٔ اداری · ۳۴۰';
    b2.appendChild(a); await sleep(900);
    el('cdR2').textContent = fa(4540); el('cdRd').textContent = fa(340); el('cdRd').className = 'n num is-diff';
    await sleep(700); el('cdTrail').classList.add('is-show');
    await sleep(4200);
  }
  onVisible(el('cd5'), function(){ loop(play5); });
})();
</script>
