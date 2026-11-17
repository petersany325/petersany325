<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function faN(n){return String(n).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})}
  function fa(n){return faN(String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'٬'))}
  function sleep(ms){return new Promise(function(r){setTimeout(r, reduce ? 0 : ms)})}
  function el(id){return document.getElementById(id)}
  function count(node, to, ms, from){
    from = from || 0;
    if (!node) return Promise.resolve();
    if (reduce) { node.textContent = fa(to); return Promise.resolve(); }
    return new Promise(function(res){
      var t0 = performance.now();
      (function f(t){
        var p = Math.min(1, (t - t0) / ms);
        node.textContent = fa(Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3))));
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
  async function type(node, txt){
    if (!node) return;
    node.classList.add('is-type'); node.textContent = '';
    for (var i = 1; i <= txt.length; i++) { node.textContent = txt.slice(0, i); await sleep(55); }
    node.classList.remove('is-type');
  }

  var CATS = {'نوشیدنی گرم':[['☕','اسپرسو'],['🥛','لاته'],['☕','کاپوچینو'],['🍫','موکا']],'نوشیدنی سرد':[['🧊','آیس لاته'],['🍹','لیموناد'],['🥤','اسموتی'],['🧋','آیس موکا']],'کیک و دسر':[['🍰','چیزکیک'],['🍫','کیک شکلاتی'],['🥐','کروسان'],['🍪','کوکی']],'صبحانه و میان‌وعده':[['🍳','املت'],['🥪','ساندویچ'],['🥣','پنکیک'],['🥗','سالاد']]};
  var CN = Object.keys(CATS);
  (function(){
    var cats = el('cfCats'); if (!cats || cats.childElementCount) return;
    CN.forEach(function(c){ var s = document.createElement('span'); s.textContent = c; cats.appendChild(s); });
  })();
  function showCat(i){
    var cats = el('cfCats'), tiles = el('cfTiles'); if (!cats || !tiles) return;
    [].forEach.call(cats.children, function(s, j){ s.classList.toggle('is-on', j === i); });
    tiles.innerHTML = '';
    CATS[CN[i]].forEach(function(m){ var d = document.createElement('div'); d.className = 'cf-tile'; d.innerHTML = '<div class="e">'+m[0]+'</div><div class="n">'+m[1]+'</div>'; tiles.appendChild(d); });
  }
  async function play1(){
    if (!el('cf1')) return;
    el('cfOl').innerHTML = ''; el('cfTot').textContent = '۰'; el('cfT1').classList.remove('is-show'); el('cfMod').classList.remove('is-show');
    showCat(0); await sleep(700); await press(el('cfTiles').children[1]);
    var mb = el('cfMbox');
    mb.innerHTML = '<h5>لاته · اندازه</h5><div class="cf-sizes"><div class="cf-opt">کوچک<span class="num muted">۱۸۰٬۰۰۰</span></div><div class="cf-opt" id="cfS2">متوسط<span class="num muted">۲۲۰٬۰۰۰</span></div><div class="cf-opt">بزرگ<span class="num muted">۲۶۰٬۰۰۰</span></div></div>';
    el('cfMod').classList.add('is-show'); await sleep(800); el('cfS2').classList.add('is-on'); await sleep(600);
    mb.innerHTML = '<h5>لاته (متوسط) · افزودنی</h5><div class="cf-opts"><div class="cf-opt" id="cfA1"><span>شات اضافه</span><span class="num muted">+۴۰٬۰۰۰</span></div><div class="cf-opt" id="cfA2"><span>شیر جو دوسر</span><span class="num muted">+۵۵٬۰۰۰</span></div><div class="cf-opt"><span>سیروپ کارامل</span><span class="num muted">+۳۵٬۰۰۰</span></div><div class="cf-opt" id="cfA3"><span>بدون شکر</span><span class="muted">بدون</span></div></div>';
    await sleep(700); el('cfA1').classList.add('is-on'); await sleep(450); el('cfA2').classList.add('is-on'); await sleep(450); el('cfA3').classList.add('is-on'); await sleep(700);
    el('cfMod').classList.remove('is-show');
    var l = document.createElement('div'); l.className = 'cf-ol';
    l.innerHTML = '<div class="r1"><span>۱ × لاته (متوسط)</span><span class="num">۳۱۵٬۰۰۰</span></div><small>شات اضافه · شیر جو دوسر · <b>بدون شکر</b></small>';
    el('cfOl').appendChild(l); await count(el('cfTot'), 315000, 400);
    showCat(2); await sleep(700); await press(el('cfTiles').children[0]);
    var l2 = document.createElement('div'); l2.className = 'cf-ol'; l2.innerHTML = '<div class="r1"><span>۱ × چیزکیک</span><span class="num">۲۹۰٬۰۰۰</span></div>';
    el('cfOl').appendChild(l2); await count(el('cfTot'), 605000, 400, 315000);
    await sleep(700); await press(el('cfBb'));
    el('cfT1').textContent = 'فیش بار چاپ شد · لاته در صف باریستا نشست'; el('cfT1').classList.add('is-show');
    await sleep(3400);
  }
  onVisible(el('cf1'), function(){ loop(play1); });

  var BQ = [['۷۸','۰۸:۱۸','—','آیس موکا','۱','',19],['۸۰','۰۸:۲۹','میز ۳','کاپوچینو (بزرگ)','۲','یکی بدون شکر',11],['۸۲','۰۸:۳۴','—','اسپرسو','۱','دبل',6],['۸۴','۰۸:۳۸','—','لاته (متوسط)','۱','شات اضافه · شیر جو دوسر · بدون شکر',2]];
  async function play2(){
    var bq = el('cfBq'); if (!bq) return;
    bq.innerHTML = '';
    var rows = BQ.map(function(k){
      var tr = document.createElement('tr'); tr.dataset.m = k[6];
      tr.innerHTML = '<td class="num">'+k[0]+'</td><td class="num hide-m">'+k[1]+'</td><td>'+k[2]+'</td><td>'+k[3]+'</td><td class="n num">'+k[4]+'</td><td class="hide-m note">'+k[5]+'</td><td class="s"><span class="cf-chip is-prep">در حال آماده‌سازی</span></td><td class="n num min">'+faN(k[6])+'</td>';
      bq.appendChild(tr); return tr;
    });
    for (var t = 0; t < 5; t++) {
      await sleep(900);
      rows.forEach(function(r){ if (r.classList.contains('is-gone')) return; var m = +r.dataset.m + 1; r.dataset.m = m; r.querySelector('.min').textContent = faN(m); if (m > 20) r.classList.add('is-late'); });
      el('cfClk').textContent = 'ساعت '+faN('08:'+(40+t+1))+' · تازه شدن خودکار';
      if (t === 1) rows[2].querySelector('.s').innerHTML = '<span class="cf-chip is-ready">آماده شد</span>';
      if (t === 3) { rows[2].querySelector('.s').innerHTML = '<span class="cf-chip is-served">سرو شد</span>'; rows[2].classList.add('is-gone'); }
    }
    await sleep(600); rows[0].classList.remove('is-late'); rows[0].querySelector('.s').innerHTML = '<span class="cf-chip is-ready">آماده شد</span>';
    await sleep(3000);
  }
  onVisible(el('cf2'), function(){ loop(play2); });

  var SZ = [['کوچک',180000,1,'۱۸ گرم','۱۵۰ میلی‌لیتر',46000],['متوسط',220000,2,'۱۸ گرم','۲۲۰ میلی‌لیتر',58000],['بزرگ',260000,3,'۲۷ گرم','۳۰۰ میلی‌لیتر',79000]];
  async function play3(){
    if (!el('cf3')) return;
    ['cfF0','cfF1','cfF2','cfF3'].forEach(function(k){ el(k).textContent = ''; });
    var cups = el('cfCups'); cups.innerHTML = '';
    await sleep(500); await type(el('cfF0'), 'کاپوچینو'); await type(el('cfF1'), fa(180000)); await type(el('cfF2'), fa(220000)); await type(el('cfF3'), fa(260000));
    await sleep(400); await press(el('cfFs'));
    var els = SZ.map(function(s){
      var d = document.createElement('div'); d.className = 'cf-cup'; d.style.setProperty('--s', s[2]);
      d.innerHTML = '<div class="ic">☕</div><div class="nm">کاپوچینو ('+s[0]+')</div><div class="num muted">'+fa(s[1])+' ریال</div><div class="rl"><span>قهوه</span><span>'+s[3]+'</span></div><div class="rl"><span>شیر</span><span>'+s[4]+'</span></div><div class="pc"><span>بهای فنجان</span><b class="num">'+fa(s[5])+'</b></div>';
      cups.appendChild(d); return d;
    });
    for (var i = 0; i < els.length; i++) { els[i].classList.add('is-show'); await sleep(450); }
    await sleep(3800);
  }
  onVisible(el('cf3'), function(){ loop(play3); });

  (function(){
    var s = el('cfQr'); if (!s || s.childElementCount) return;
    var seed = 11, h = '';
    function r(){ seed = (seed * 9301 + 49297) % 233280; return seed / 233280; }
    function fin(x, y){ h += '<rect x="'+x+'" y="'+y+'" width="7" height="7" fill="#111"/><rect x="'+(x+1)+'" y="'+(y+1)+'" width="5" height="5" fill="#fff"/><rect x="'+(x+2)+'" y="'+(y+2)+'" width="3" height="3" fill="#111"/>'; }
    for (var y = 0; y < 21; y++) for (var x = 0; x < 21; x++) {
      if ((x < 8 && y < 8) || (x > 12 && y < 8) || (x < 8 && y > 12)) continue;
      if (r() > 0.5) h += '<rect x="'+x+'" y="'+y+'" width="1" height="1" fill="#111"/>';
    }
    fin(0,0); fin(14,0); fin(0,14); s.innerHTML = h;
  })();
  var PM = [['اسپرسو',150000],['لاته (متوسط)',220000],['کاپوچینو (متوسط)',220000],['موکا (متوسط)',250000]];
  async function play4(){
    var root = el('cf4'); if (!root) return;
    var md = root.querySelectorAll('.cf-modes span'), pb = el('cfPb'), nt = el('cfNt');
    md[0].classList.add('is-on'); md[1].classList.remove('is-on');
    el('cfQt').textContent = 'QR عمومی پیشخوان'; el('cfHint').textContent = 'منو را ببینید'; el('cfPt').textContent = 'فقط مشاهده';
    pb.innerHTML = ''; nt.innerHTML = '';
    for (var i = 0; i < PM.length; i++) {
      var d = document.createElement('div'); d.className = 'cf-pl';
      d.innerHTML = '<span>'+PM[i][0]+'</span><span class="num muted">'+fa(PM[i][1])+'</span>';
      pb.appendChild(d); await sleep(250);
    }
    nt.innerHTML = '<div>کافهٔ بدون میز: یک QR روی پیشخوان، فقط برای دیدن منو.</div><div class="muted">منو با قیمت روز؛ قلم ناموجود یا بی‌قیمت نشان داده نمی‌شود.</div>';
    await sleep(3200);
    md[1].classList.add('is-on'); md[0].classList.remove('is-on');
    el('cfQt').textContent = 'میز ۴'; el('cfHint').textContent = 'منو و سفارش'; el('cfPt').textContent = 'میز ۴';
    nt.innerHTML = '<div>کافهٔ با میز: کارت QR جدا برای هر میز، چاپ گروهی کارت‌ها.</div>';
    var rows = pb.querySelectorAll('.cf-pl');
    rows[1].innerHTML = '<span>لاته (متوسط)</span><b class="num">۰</b>';
    rows[3].innerHTML = '<span>موکا (متوسط)</span><b class="num">۰</b>';
    await sleep(900); rows[1].querySelector('b').textContent = '۲'; await sleep(400); rows[3].querySelector('b').textContent = '۱'; await sleep(500);
    var sb = document.createElement('div'); sb.className = 'cf-pl is-send'; sb.textContent = 'ارسال سفارش'; pb.appendChild(sb);
    await sleep(500); await press(sb); sb.textContent = 'منتظر تأیید کافه'; sb.classList.remove('is-send');
    var q = document.createElement('div'); q.className = 'cf-qo';
    q.innerHTML = '<b>میز ۴</b> · <span class="cf-chip is-prep">در انتظار تأیید</span><div class="muted">۲ × لاته (متوسط) · ۱ × موکا (متوسط)</div><div class="cf-acts"><span class="cf-btn is-p" id="cfQa">تأیید</span><span class="cf-btn">رد</span></div>';
    nt.appendChild(q); await sleep(1400); await press(el('cfQa'));
    q.querySelector('.cf-chip').outerHTML = '<span class="cf-chip is-ready">پذیرفته · به صف بار رفت</span>';
    var act = q.querySelector('.cf-acts'); if (act) act.remove();
    sb.textContent = 'سفارش پذیرفته شد'; sb.classList.add('is-ok');
    await sleep(3200);
  }
  onVisible(el('cf4'), function(){ loop(play4); });

  var SB = [['تعداد فاکتور','۱۴۲',0],['ابطالی','۲',0],['نقد',9840000,1],['کارتخوان',21460000,1],['نسیه',650000,1],['نزد پیک',1200000,1],['دریافت از پیک',1200000,1],['استرداد',180000,1]];
  var EXP = 9840000 + 1200000 - 180000;
  async function play5(){
    var sb = el('cfSb'); if (!sb) return;
    sb.innerHTML = ''; el('cfEx').textContent = '۰'; el('cfCt').textContent = ''; el('cfDf').textContent = '—'; el('cfDf').style.color = '';
    el('cfLk').classList.remove('is-show'); el('cfT5').classList.remove('is-show');
    for (var i = 0; i < SB.length; i++) {
      var d = document.createElement('div'); d.className = 'cf-sl';
      d.innerHTML = '<span class="muted">'+SB[i][0]+'</span><span class="num">'+(SB[i][2] ? fa(SB[i][1]) : SB[i][1])+'</span>';
      sb.appendChild(d); await sleep(220);
    }
    var e = document.createElement('div'); e.className = 'cf-sl is-exp';
    e.innerHTML = '<span>نقد مورد انتظار صندوق</span><span class="num">'+fa(EXP)+'</span>';
    sb.appendChild(e); await count(el('cfEx'), EXP, 700); await sleep(500);
    await type(el('cfCt'), fa(EXP - 40000));
    await sleep(400); el('cfDf').textContent = 'کسری '+fa(40000); el('cfDf').style.color = '#ff5d6c';
    await sleep(900); await press(el('cfOk'));
    el('cfT5').textContent = 'سند «کسر و اضافهٔ صندوق» با کسری ۴۰٬۰۰۰ ریال ثبت شد'; el('cfT5').classList.add('is-show');
    await sleep(700); el('cfLk').classList.add('is-show');
    await sleep(3600);
  }
  onVisible(el('cf5'), function(){ loop(play5); });

  var CP = [['نوشیدنی','لاته (متوسط)',412,90640000,23560000],['نوشیدنی','کاپوچینو (بزرگ)',236,61360000,17180000],['نوشیدنی','موکا (بزرگ)',198,57420000,21820000],['نوشیدنی','اسپرسو',344,51600000,8770000],['غذا و دسر','چیزکیک',151,43790000,13140000]];
  var LIM = 32;
  async function play6(){
    var tb = el('cfCpb'); if (!tb) return;
    tb.innerHTML = ''; el('cfAi').classList.remove('is-show');
    var rows = CP.map(function(c){
      var p = Math.round(c[4] / c[3] * 100), tr = document.createElement('tr'); tr.dataset.p = p;
      tr.innerHTML = '<td class="muted">'+c[0]+'</td><td>'+c[1]+'</td><td class="n num hide-m">'+faN(c[2])+'</td><td class="n num hide-m">'+fa(c[3])+'</td><td class="n num hide-m">'+fa(c[4])+'</td><td><div class="cf-bar2"><div class="tr"><i></i><span class="lim"></span></div><span class="num pct">۰٪</span></div></td>';
      tb.appendChild(tr); return tr;
    });
    await sleep(500);
    rows.forEach(function(r){
      var p = +r.dataset.p;
      r.querySelector('.tr i').style.width = Math.min(100, p / 50 * 100)+'%';
      r.querySelector('.lim').style.insetInlineStart = (LIM / 50 * 100)+'%';
      var s = r.querySelector('.pct');
      count(s, p, 1000).then(function(){ s.textContent = faN(p)+'٪'; if (p > LIM) r.classList.add('is-over'); });
    });
    await sleep(1800); el('cfAi').classList.add('is-show');
    await sleep(4200);
  }
  onVisible(el('cf6'), function(){ loop(play6); });
})();
</script>
