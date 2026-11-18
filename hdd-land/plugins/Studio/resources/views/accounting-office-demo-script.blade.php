<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function faN(n){return String(n).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})}
  function sleep(ms){return new Promise(function(r){setTimeout(r, reduce ? 0 : ms)})}
  function el(id){return document.getElementById(id)}
  function onVisible(node, fn){
    if (!node) return;
    if (reduce || !('IntersectionObserver' in window)) { fn(); return; }
    var io = new IntersectionObserver(function(es){ if (es[0].isIntersecting) { io.disconnect(); fn(); } }, {threshold: 0.3});
    io.observe(node);
  }
  async function loop(fn){ do { await fn(); } while (!reduce); }
  async function press(node){ if (!node) return; node.classList.add('is-hit'); await sleep(280); node.classList.remove('is-hit'); }
  var PR = {n:'<span class="au-chip is-mute">عادی</span>', u:'<span class="au-chip is-bad">فوری</span>', w:'<span class="au-chip is-warn">هشدار</span>'};

  var TK = [['تأیید فاکتور خرید ۳۴۱۰','از: مدیر مالی · مهلت فردا ۱۲:۰۰','u','در جریان'],['پیگیری چک برگشتی مشتری','از: مدیر فروش · مهلت ۳ روز','w','در جریان'],['بررسی قرارداد اجاره','از: مدیر عامل','n','پاسخ داده شد'],['تهیهٔ گزارش فصل','از: خودم','n','بسته شد']];
  function tkRow(t, isNew){
    var d = document.createElement('div');
    d.className = 'au-tk'+(isNew ? ' is-new' : '')+(t[3]==='بسته شد' ? ' is-read' : '');
    var st = t[3]==='بسته شد' ? 'is-ok' : (t[3]==='پاسخ داده شد' ? 'is-blue' : 'is-mute');
    d.innerHTML = '<span class="dot"></span><div><div>'+t[0]+'</div><div class="sub">'+t[1]+'</div></div><span class="hide-m">'+PR[t[2]]+'</span><span class="au-chip '+st+'">'+t[3]+'</span>';
    return d;
  }
  async function play1(){
    var tl = el('auTl'); if (!tl) return;
    tl.innerHTML = ''; el('auB1').textContent = '۲'; el('auT1').classList.remove('is-show');
    TK.forEach(function(t){ tl.appendChild(tkRow(t)); });
    await sleep(1500);
    var n = tkRow(['شمارش موجودی انبار ۲','ارجاع به نقش انباردار · ناظر: مدیر مالی · مهلت ۱۴:۰۰','u','جدید'], true);
    tl.insertBefore(n, tl.firstChild);
    var b = el('auB1'); b.textContent = '۳'; b.classList.remove('is-pop'); void b.offsetWidth; b.classList.add('is-pop');
    el('auT1').textContent = 'اعلان: کار تازه به شما ارجاع شد'; el('auT1').classList.add('is-show');
    await sleep(2200); el('auT1').classList.remove('is-show');
    n.classList.add('is-read'); n.classList.remove('is-new'); b.textContent = '۲';
    await sleep(900);
    tl.children[1].querySelector('.au-chip:last-child').outerHTML = '<span class="au-chip is-blue">پاسخ داده شد</span>';
    await sleep(900);
    var r2 = tl.children[2]; r2.querySelector('.au-chip:last-child').outerHTML = '<span class="au-chip is-ok">بسته شد</span>'; r2.classList.add('is-read');
    await sleep(3500);
  }
  onVisible(el('au1'), function(){ loop(play1); });

  async function play2(){
    if (!el('au2')) return;
    el('auLno').classList.remove('is-show'); el('auLno').textContent = '';
    el('auRcp').querySelectorAll('.au-rc').forEach(function(r){ r.classList.remove('is-seen'); });
    var sec = el('auSec'); sec.innerHTML = '';
    function row(code, cls, sub, st){
      var d = document.createElement('div'); d.className = 'au-row';
      d.innerHTML = '<span class="code '+cls+'">'+code+'</span><span>'+sub+'</span><span>'+st+'</span>';
      sec.insertBefore(d, sec.firstChild); return d;
    }
    row('OUT-1405-0118','is-out','پاسخ استعلام بیمه','<span class="au-chip is-mute">صادره</span>');
    row('IN-1405-0207','is-in','نامهٔ بانک نمونه','<span class="au-chip is-mute">وارده</span>');
    await sleep(1200); await press(el('auSnd'));
    el('auLno').textContent = 'INT-1405-0042'; el('auLno').classList.add('is-show');
    row('INT-1405-0042','is-int','بستن حساب‌های فصل پاییز','<span class="au-chip is-ok">داخلی · ارسال شد</span>');
    var rs = el('auRcp').querySelectorAll('.au-rc');
    for (var i = 0; i < rs.length; i++) { await sleep(800); rs[i].classList.add('is-seen'); }
    await sleep(900);
    row('IN-1405-0208','is-in','دعوت‌نامهٔ اتاق بازرگانی','<span class="au-chip is-mute">وارده</span>');
    await sleep(3500);
  }
  onVisible(el('au2'), function(){ loop(play2); });

  function sigSvg(seed){ return '<svg viewBox="0 0 90 26" aria-hidden="true"><path d="M4 18 C14 2, 20 26, 30 12 S 46 4, 52 16 S 70 22, 86 8" fill="none" stroke="#a48bff" stroke-width="2" stroke-linecap="round" transform="rotate('+seed+' 45 13)"/></svg>'; }
  async function play3(){
    if (!el('au3')) return;
    el('auPin').querySelectorAll('i').forEach(function(p){ p.classList.remove('is-f'); });
    el('auSgt').innerHTML = ''; el('auEv').classList.remove('is-show');
    var r0 = document.createElement('tr');
    r0.innerHTML = '<td>مریم احمدی</td><td class="hide-m">کارشناس مالی</td><td>'+sigSvg(-3)+'</td><td class="num">۰۹:۱۵</td>';
    el('auSgt').appendChild(r0);
    await sleep(900);
    var pins = el('auPin').querySelectorAll('i');
    for (var i = 0; i < 4; i++) { pins[i].classList.add('is-f'); await sleep(300); }
    await sleep(300); await press(el('auSb'));
    var r = document.createElement('tr');
    r.innerHTML = '<td>علی رضایی</td><td class="hide-m">مدیر عامل</td><td>'+sigSvg(4)+'</td><td class="num">۱۰:۴۲</td>';
    el('auSgt').appendChild(r);
    await sleep(600); el('auEv').classList.add('is-show');
    await sleep(4000);
  }
  onVisible(el('au3'), function(){ loop(play3); });

  var H = [['مدیر مالی','کار را ثبت کرد و به نقش «انباردار» سپرد','۱۴۰۵/۰۸/۲۷ ۰۹:۰۰'],['عباس (انباردار)','پاسخ داد: شمارش شروع شد','۱۴۰۵/۰۸/۲۷ ۱۱:۲۰'],['عباس','ارجاع به حمید (انباردار شیفت عصر)','۱۴۰۵/۰۸/۲۷ ۱۶:۰۵'],['حمید','پیوست گذاشت: فهرست شمارش','۱۴۰۵/۰۸/۲۸ ۱۳:۱۰'],['حمید','کار را با نتیجه بست · به‌موقع','۱۴۰۵/۰۸/۲۸ ۱۳:۴۵']];
  async function play4(){
    var tl = el('auTlh'); if (!tl) return;
    tl.innerHTML = '';
    el('auRem').querySelectorAll('span').forEach(function(r){ r.classList.remove('is-on'); });
    var els = H.map(function(h){
      var d = document.createElement('div'); d.className = 'au-st';
      d.innerHTML = '<b>'+h[0]+'</b> · '+h[1]+'<div class="muted num">'+h[2]+'</div>';
      tl.appendChild(d); return d;
    });
    var rem = el('auRem').querySelectorAll('span');
    for (var i = 0; i < els.length; i++) {
      els[i].classList.add('is-show');
      if (i === 2) rem[0].classList.add('is-on');
      if (i === 3) rem[1].classList.add('is-on');
      await sleep(900);
    }
    await sleep(4000);
  }
  onVisible(el('au4'), function(){ loop(play4); });

  var E = [['مریم احمدی',22,2,3],['عباس کریمی',15,6,2],['حمید نوری',18,1,4],['سارا محمدی',9,7,5]];
  var U = [['مالی',41,5,8],['انبار',33,7,6],['فروش',24,11,7]];
  async function show5(data, k){
    var tabs = el('auPtabs').querySelectorAll('span');
    tabs.forEach(function(t, j){ t.classList.toggle('is-on', j === k); });
    var p = el('auPrs'); p.innerHTML = '';
    var mx = Math.max.apply(null, data.map(function(d){ return d[1]+d[2]+d[3]; }));
    var rows = data.map(function(d){
      var r = document.createElement('div'); r.className = 'au-pr'; var tot = d[1]+d[2]+d[3];
      r.innerHTML = '<span>'+d[0]+'</span><div class="au-stk"><i class="a"></i><i class="b"></i><i class="c"></i></div><span class="num muted">'+faN(Math.round(d[1]/tot*100))+'٪ به‌موقع</span>';
      p.appendChild(r); return [r, d];
    });
    await sleep(100);
    rows.forEach(function(x){
      var d = x[1], is = x[0].querySelectorAll('.au-stk i');
      is[0].style.width = (d[1]/mx*100)+'%'; is[1].style.width = (d[2]/mx*100)+'%'; is[2].style.width = (d[3]/mx*100)+'%';
    });
    await sleep(3200);
  }
  async function play5(){ if (!el('au5')) return; await show5(E, 0); await show5(U, 1); }
  onVisible(el('au5'), function(){ loop(play5); });

  var OD = [['تأیید فاکتور خرید ۳۴۱۰','u','مدیر مالی','۱۴۰۵/۰۸/۲۷ ۱۲:۰۰',20],['پیگیری چک برگشتی','w','سارا محمدی','۱۴۰۵/۰۸/۲۶ ۱۷:۰۰',39],['تمدید بیمه‌نامهٔ خودرو','n','عباس کریمی','۱۴۰۵/۰۸/۲۵ ۱۰:۰۰',70]];
  async function play6(){
    var tb = el('auOdt'); if (!tb) return;
    el('auClk').textContent = 'امروز ۱۴۰۵/۰۸/۲۸ · ۰۸:۰۰';
    tb.innerHTML = '';
    var rows = OD.map(function(o){
      var tr = document.createElement('tr'); tr.classList.add('is-od'); tr.dataset.h = o[4];
      tr.innerHTML = '<td>'+o[0]+'</td><td class="hide-m">'+PR[o[1]]+'</td><td>'+o[2]+'</td><td class="num hide-m">'+o[3]+'</td><td><span class="au-chip is-warn">۲۴ ساعت</span> <span class="au-chip is-warn">۱ ساعت</span> <span class="au-chip is-bad">گذشت</span></td><td class="n num is-late h">'+faN(o[4])+' ساعت</td>';
      tb.appendChild(tr); return tr;
    });
    for (var t = 1; t <= 4; t++) {
      await sleep(1000);
      var hour = 8 + t;
      el('auClk').textContent = 'امروز ۱۴۰۵/۰۸/۲۸ · '+faN((hour < 10 ? '0' : '')+hour+':00');
      rows.forEach(function(r){ var h = +r.dataset.h + 1; r.dataset.h = h; r.querySelector('.h').textContent = faN(h)+' ساعت'; });
    }
    await sleep(600);
    var nr = document.createElement('tr'); nr.classList.add('is-od');
    nr.innerHTML = '<td>ارسال لیست بیمهٔ مهر</td><td class="hide-m">'+PR.u+'</td><td>مریم احمدی</td><td class="num hide-m">۱۴۰۵/۰۸/۲۸ ۱۲:۰۰</td><td><span class="au-chip is-warn">۲۴ ساعت</span> <span class="au-chip is-warn">۱ ساعت</span> <span class="au-chip is-bad">گذشت</span></td><td class="n num is-late">تازه معوق شد</td>';
    tb.insertBefore(nr, tb.firstChild);
    await sleep(3500);
  }
  onVisible(el('au6'), function(){ loop(play6); });
})();
</script>
