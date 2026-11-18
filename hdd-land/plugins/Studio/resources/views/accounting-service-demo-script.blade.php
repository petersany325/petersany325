<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function faN(n){return String(n).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]}).replace('.','٫')}
  function fa(n){return faN(String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'٬'))}
  function sleep(ms){return new Promise(function(r){setTimeout(r, reduce ? 0 : ms)})}
  function el(id){return document.getElementById(id)}
  function count(node, to, ms, from, suf){
    from = from || 0; suf = suf || '';
    if (!node) return Promise.resolve();
    if (reduce) { node.textContent = fa(to)+suf; return Promise.resolve(); }
    return new Promise(function(res){
      var t0 = performance.now();
      (function f(t){
        var p = Math.min(1, (t - t0) / ms);
        node.textContent = fa(Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3))))+suf;
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
  async function press(node){ if (!node) return; node.classList.add('is-hit'); await sleep(280); node.classList.remove('is-hit'); }

  var WP = [['پشتیبانی','پشتیبانی',120,600,60,1.8],['اجرای پروژه','اجرا',80,560,60,3.2],['مشاوره','مشاوره',40,320,60,2.5],['آموزش','آموزش',24,144,90,2.2],['نظارت','نظارت',30,135,60,3.9]];
  async function play1(){
    var tb = el('svWp'); if (!tb) return;
    tb.innerHTML = '';
    var rows = WP.map(function(w){
      var h = Math.round(w[2] * w[4] / 60), c = Math.round(h * w[5]), m = w[3] - c, mp = Math.round(m / w[3] * 100);
      var tr = document.createElement('tr'); if (mp < 20) tr.classList.add('is-low');
      tr.innerHTML = '<td>'+w[0]+'</td><td class="hide-m muted">'+w[1]+'</td><td class="n num">'+faN(w[2])+'</td><td class="n num rv">۰</td><td class="n num hide-m">'+faN(h)+'</td><td class="n num hide-m">'+fa(c)+'</td><td><div class="sv-mbar"><div class="tr"><i></i></div><span class="num mg">۰</span></div></td>';
      tb.appendChild(tr); return [tr, w, m, mp];
    });
    await sleep(300);
    rows.forEach(function(r){
      count(r[0].querySelector('.rv'), r[1][3], 1100);
      r[0].querySelector('.tr i').style.width = Math.max(4, r[3])+'%';
      var g = r[0].querySelector('.mg');
      count(g, r[2], 1100).then(function(){ g.textContent = fa(r[2])+' · '+faN(r[3])+'٪'; });
    });
    await sleep(5200);
  }
  onVisible(el('sv1'), function(){ loop(play1); });

  async function play2(){
    var root = el('sv2'); if (!root) return;
    var tabs = root.querySelectorAll('.sv-tabs span');
    function tab(i){ tabs.forEach(function(t, j){ t.classList.toggle('is-on', j === i); }); }
    tab(0); el('svCst').className = 'sv-chip is-mute'; el('svCst').textContent = 'پیش‌نویس'; el('svIns').innerHTML = ''; el('svT2').classList.remove('is-show');
    await sleep(900); tab(1); await sleep(900); await press(el('svAct'));
    el('svCst').className = 'sv-chip is-ok'; el('svCst').textContent = 'فعال';
    el('svT2').textContent = 'قرارداد فعال شد و سند تعهد شمارهٔ ۴۱۲ ثبت شد.'; el('svT2').classList.add('is-show');
    await sleep(1600); el('svT2').classList.remove('is-show'); tab(2);
    var D = ['۱۴۰۵/۰۲/۰۱','۱۴۰۵/۰۴/۰۱','۱۴۰۵/۰۶/۰۱','۱۴۰۵/۰۸/۰۱','۱۴۰۵/۱۰/۰۱','۱۴۰۵/۱۲/۰۱'];
    var rows = D.map(function(d, i){
      var tr = document.createElement('tr');
      tr.innerHTML = '<td class="num">'+faN(i+1)+'</td><td class="num">'+d+'</td><td class="n num">۸۰</td><td class="s"><span class="sv-chip is-mute">باز</span></td>';
      el('svIns').appendChild(tr); return tr;
    });
    await sleep(900);
    for (var i = 0; i < 3; i++) { rows[i].classList.add('is-paid'); rows[i].querySelector('.s').innerHTML = '<span class="sv-chip is-ok">دریافت شد</span>'; await sleep(500); }
    rows[3].classList.add('is-late'); rows[3].querySelector('.s').innerHTML = '<span class="sv-chip is-bad">معوق · ۲۶ روز</span>';
    await sleep(3800);
  }
  onVisible(el('sv2'), function(){ loop(play2); });

  var IL = [['پشتیبانی ماهانه',10,'ساعت',5000000],['جلسهٔ آموزش کاربران',2,'جلسه',7500000],['میزبانی سرور',1,'ماه',12000000]];
  async function play3(){
    var tb = el('svIl'), it = el('svIt'); if (!tb) return;
    tb.innerHTML = '';
    it.innerHTML = '<div class="sv-ln"><span>جمع خدمات</span><b class="num" id="svS1">۰</b></div><div class="sv-ln"><span>کسورات (تخفیف)</span><b class="num" id="svS2">۰</b></div><div class="sv-ln"><span>ارزش افزوده</span><b class="num" id="svS3">۰</b></div><div class="sv-ln is-big"><span>جمع فاکتور</span><b class="num" id="svS4">۰</b></div>';
    var sum = 0;
    for (var i = 0; i < IL.length; i++) {
      var l = IL[i], a = l[1] * l[3], tr = document.createElement('tr');
      tr.innerHTML = '<td>'+l[0]+'</td><td class="n num">'+faN(l[1])+'</td><td class="hide-m">'+l[2]+'</td><td class="n num">'+fa(l[3])+'</td><td class="n num">'+fa(a)+'</td>';
      tb.appendChild(tr); sum += a; await sleep(500);
    }
    var lns = it.querySelectorAll('.sv-ln');
    lns[0].classList.add('is-on'); await count(el('svS1'), sum, 500);
    var d = 3000000; lns[1].classList.add('is-on'); await count(el('svS2'), d, 400);
    var v = Math.round((sum - d) * 0.1); lns[2].classList.add('is-on'); await count(el('svS3'), v, 400);
    lns[3].classList.add('is-on'); await count(el('svS4'), sum - d + v, 500);
    await sleep(4000);
  }
  onVisible(el('sv3'), function(){ loop(play3); });

  var REV = 85000000, C = [28400000,18000000,2600000];
  async function play4(){
    var root = el('sv4'); if (!root) return;
    var cc = root.querySelectorAll('.sv-cc'), st = root.querySelectorAll('.sv-stack i');
    cc.forEach(function(c){ c.classList.remove('is-show'); }); st.forEach(function(s){ s.style.width = '0'; });
    el('svGp').textContent = '۰'; el('svGm').textContent = '۰٪';
    await sleep(500); var spent = 0;
    for (var i = 0; i < 3; i++) { cc[i].classList.add('is-show'); st[i].style.width = (C[i] / REV * 100)+'%'; spent += C[i]; await sleep(800); }
    var gp = REV - spent; st[3].style.width = (gp / REV * 100)+'%';
    await count(el('svGp'), gp, 700); el('svGm').textContent = faN(Math.round(gp / REV * 100))+'٪';
    await sleep(4000);
  }
  onVisible(el('sv4'), function(){ loop(play4); });

  var WT = [['مشاوره','مشاوره','۶۰','۲٬۵۰۰٬۰۰۰'],['آموزش','آموزش','۹۰','۲٬۲۰۰٬۰۰۰'],['پشتیبانی','پشتیبانی','۶۰','۱٬۸۰۰٬۰۰۰'],['اجرای پروژه','اجرا','۶۰','۳٬۲۰۰٬۰۰۰'],['نظارت','نظارت','۶۰','۳٬۹۰۰٬۰۰۰'],['طراحی و مهندسی','طراحی','۱۲۰','۴٬۴۰۰٬۰۰۰']];
  async function play5(){
    var w = el('svWts'); if (!w) return;
    w.innerHTML = '';
    var els = WT.map(function(t){
      var d = document.createElement('div'); d.className = 'sv-wc';
      d.innerHTML = '<div class="h">'+t[0]+'</div><div class="l"><span>دسته</span><b>'+t[1]+'</b></div><div class="l"><span>زمان استاندارد</span><b class="num">'+t[2]+' دقیقه</b></div><div class="l"><span>نرخ دستمزد استاندارد</span><b class="num">'+t[3]+'</b></div><div class="l"><span>حساب درآمد پیشنهادی</span><b>درآمد خدمات</b></div>';
      w.appendChild(d); return d;
    });
    for (var i = 0; i < els.length; i++) { els[i].classList.add('is-show'); await sleep(320); }
    await sleep(800);
    var n = document.createElement('div'); n.className = 'sv-wc is-new';
    n.innerHTML = '<div class="h">نوع کار خودتان</div><div class="l"><span>مثلاً</span><b>تعمیر در محل</b></div><div class="l"><span>زمان استاندارد</span><b class="num">۴۵ دقیقه</b></div><div class="l"><span>نرخ دستمزد استاندارد</span><b class="num">۱٬۶۰۰٬۰۰۰</b></div><div class="l"><span>بازهٔ تاریخ</span><b>از ۱۴۰۵/۰۷/۰۱</b></div>';
    w.appendChild(n); await sleep(50); n.classList.add('is-show');
    await sleep(4200);
  }
  onVisible(el('sv5'), function(){ loop(play5); });

  var OD = [['۲۳۱','شرکت نمونهٔ ارغوان',65,104],['۲۵۸','شرکت نمونهٔ ارغوان',40,61],['۲۶۴','دفتر نمونهٔ پارس',22,38],['۲۷۰','آموزشگاه نمونه',18,12]];
  async function play6(){
    var tb = el('svOd'); if (!tb) return;
    tb.innerHTML = ''; el('svAi').classList.remove('is-show');
    ['svAl1','svAl2'].forEach(function(k){ el(k).classList.remove('is-on'); });
    var rows = OD.map(function(o){
      var tr = document.createElement('tr'); if (o[3] > 90) tr.classList.add('is-over');
      tr.innerHTML = '<td class="num">'+o[0]+'</td><td>'+o[1]+'</td><td class="n num m">'+faN(o[2])+'</td><td class="n num'+(o[3]>90?' is-neg':'')+'">'+faN(o[3])+'</td>';
      tb.appendChild(tr); return tr;
    });
    await sleep(1000); el('svAi').classList.add('is-show'); await sleep(2000);
    await press(el('svAlb')); el('svAl1').classList.add('is-on'); rows[0].classList.add('is-done'); await sleep(700);
    el('svAl2').classList.add('is-on'); rows[1].querySelector('.m').textContent = '۱۵';
    await sleep(4000);
  }
  onVisible(el('sv6'), function(){ loop(play6); });

  async function play7(){
    if (!el('sv7')) return;
    ['svF1','svF2','svF3'].forEach(function(k){ el(k).classList.remove('is-on'); });
    el('svFv').textContent = '۰';
    await sleep(500); el('svF1').classList.add('is-on'); await sleep(1000); el('svF2').classList.add('is-on'); await sleep(1000);
    el('svF3').classList.add('is-on'); await count(el('svFv'), 32000000, 800);
    await sleep(4000);
  }
  onVisible(el('sv7'), function(){ loop(play7); });
})();
</script>
