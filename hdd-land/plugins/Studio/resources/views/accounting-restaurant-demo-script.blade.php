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

  var MENU = {
    'پیش‌غذا': [['🥗','سالاد فصل',380000],['🍲','سوپ جو',290000],['🫒','زیتون پرورده',260000],['🥣','ماست و خیار',180000]],
    'غذای اصلی': [['🍢','چلوکباب کوبیده',1450000],['🍗','جوجه‌کباب',1280000],['🐟','ماهی قزل‌آلا',1350000],['🍛','قورمه‌سبزی',980000]],
    'نوشیدنی': [['🥤','نوشابه',120000],['🥛','دوغ',110000],['💧','آب معدنی',60000],['🍹','لیموناد',240000]],
    'دسر': [['🍮','فرنی',220000],['🍨','بستنی سنتی',260000],['🍰','کیک روز',310000],['🍯','باقلوا',280000]]
  };
  var CAT = Object.keys(MENU);
  (function(){
    var cats = el('rdCats'); if (!cats || cats.childElementCount) return;
    CAT.forEach(function(c){ var s = document.createElement('span'); s.textContent = c; cats.appendChild(s); });
  })();
  function showCat(i){
    var cats = el('rdCats'), tiles = el('rdTiles'); if (!cats || !tiles) return;
    [].forEach.call(cats.children, function(s, j){ s.classList.toggle('is-on', j === i); });
    tiles.innerHTML = '';
    MENU[CAT[i]].forEach(function(m){
      var d = document.createElement('div'); d.className = 'rd-tile';
      d.innerHTML = '<div class="e">'+m[0]+'</div><div class="n">'+m[1]+'</div><div class="p num">'+fa(m[2])+'</div>';
      tiles.appendChild(d);
    });
  }
  var PICK = [[0,0,1,''],[1,0,2,'بدون پیاز'],[1,1,2,''],[2,1,4,''],[3,1,2,'']];
  async function play1(){
    if (!el('rd1')) return;
    el('rdOl').innerHTML = ''; ['rdO1','rdO2','rdO3'].forEach(function(k){ el(k).textContent = '۰'; }); el('rdT1').classList.remove('is-show');
    var sum = 0;
    for (var i = 0; i < PICK.length; i++) {
      var p = PICK[i]; showCat(p[0]); await sleep(500);
      var tile = el('rdTiles').children[p[1]]; await press(tile);
      var m = MENU[CAT[p[0]]][p[1]], line = m[2] * p[2];
      var d = document.createElement('div'); d.className = 'rd-ol';
      d.innerHTML = '<span>'+faN(p[2])+' × '+m[1]+(p[3] ? '<small>'+p[3]+'</small>' : '')+'</span><span class="num">'+fa(line)+'</span>';
      el('rdOl').appendChild(d);
      var o = sum; sum += line;
      var svc = Math.round(sum * 0.1 / 1000) * 1000, osvc = Math.round(o * 0.1 / 1000) * 1000;
      count(el('rdO1'), sum, 400, o); count(el('rdO2'), svc, 400, osvc); count(el('rdO3'), sum + svc, 400, o + osvc);
      await sleep(650);
    }
    await sleep(500); await press(el('rdBk'));
    el('rdT1').textContent = 'فیش آشپزخانه چاپ شد · فقط قلم‌های تازه، به تفکیک ایستگاه'; el('rdT1').classList.add('is-show');
    await sleep(3500);
  }
  onVisible(el('rd1'), function(){ showCat(0); loop(play1); });

  var T1 = [['۱',4],['۲',2],['۳',4],['۴',6],['۵',4],['۶',2],['۷',4],['۸',8]], T2 = [['۹',4],['۱۰',2],['۱۱',4],['۱۲',4]];
  function paint(node, st, html){ node.className = 'rd-tb '+st; node.innerHTML = html; }
  function free(node, n, c){ paint(node, 'is-free', '<div class="nm">میز '+n+'</div>آزاد · '+faN(c)+' نفر'); }
  function busy(node, n, o, a, w){ paint(node, 'is-busy', '<div class="nm">میز '+n+'</div>سفارش '+faN(o)+'<br><span class="num">'+fa(a)+'</span> · '+w); }
  function reserved(node, n, h, g){ paint(node, 'is-res', '<div class="nm">میز '+n+'</div>رزرو '+h+'<br>'+g); }
  function tally(){
    var root = el('rd2'); if (!root) return;
    el('rdSa').textContent = faN(root.querySelectorAll('.rd-tb').length);
    el('rdSf').textContent = faN(root.querySelectorAll('.rd-tb.is-free').length);
    el('rdSb').textContent = faN(root.querySelectorAll('.rd-tb.is-busy').length);
    el('rdSr').textContent = faN(root.querySelectorAll('.rd-tb.is-res').length);
  }
  async function flash(node){ node.classList.add('is-flash'); await sleep(600); node.classList.remove('is-flash'); }
  async function play2(){
    var h1 = el('rdH1'), h2 = el('rdH2'); if (!h1) return;
    h1.innerHTML = ''; h2.innerHTML = '';
    var E1 = T1.map(function(t){ var d = document.createElement('div'); h1.appendChild(d); free(d, t[0], t[1]); return d; });
    var E2 = T2.map(function(t){ var d = document.createElement('div'); h2.appendChild(d); free(d, t[0], t[1]); return d; });
    busy(E1[1], '۲', 305, 1840000, 'رضا'); busy(E1[4], '۵', 308, 3260000, 'علی'); busy(E2[1], '۱۰', 309, 920000, 'سارا'); tally();
    await sleep(1500);
    busy(E1[6], '۷', 312, 3740000, 'علی'); tally(); await flash(E1[6]); await sleep(1100);
    reserved(E2[3], '۱۲', '۲۰:۳۰', 'خانوادهٔ نمونه'); tally(); await flash(E2[3]); await sleep(1100);
    busy(E1[3], '۴', 313, 0, 'رضا'); E1[3].innerHTML = '<div class="nm">میز ۴</div>سفارش ۳۱۳<br>تازه باز شد · رضا'; E1[3].className = 'rd-tb is-busy'; tally(); await flash(E1[3]); await sleep(1100);
    free(E1[1], '۲', 2); tally(); await flash(E1[1]); E1[1].insertAdjacentHTML('beforeend', '<br><span class="is-paid">تسویه شد</span>');
    await sleep(4000);
  }
  onVisible(el('rd2'), function(){ loop(play2); });

  var CH = {n:['is-new','جدید'], p:['is-prep','در تهیه'], r:['is-ready','آماده'], s:['is-served','سرو شد']};
  function chip(k){ return '<span class="rd-chip '+CH[k][0]+'">'+CH[k][1]+'</span>'; }
  async function play3(){
    var wb = el('rdWb'); if (!wb) return;
    el('rdWt').textContent = 'انتخاب میز'; wb.innerHTML = '';
    var g = document.createElement('div'); g.className = 'rd-picks';
    ['۳','۶','۸','۹','۱۱','۱۲'].forEach(function(n, i){
      var b = document.createElement('div'); b.className = 'rd-pl'; b.textContent = 'میز '+n; if (i === 2) b.id = 'rdPick'; g.appendChild(b);
    });
    wb.appendChild(g); await sleep(1000);
    var pk = el('rdPick'); pk.classList.add('is-on'); await sleep(600);
    el('rdWt').textContent = 'میز ۸ · ۲ نفر'; wb.innerHTML = '';
    var items = [['جوجه‌کباب','',1],['سالاد فصل','بدون سس',1],['دوغ','',2]], els = [];
    for (var i = 0; i < items.length; i++) {
      var d = document.createElement('div'); d.className = 'rd-pl';
      d.innerHTML = '<span>'+faN(items[i][2])+' × '+items[i][0]+(items[i][1] ? '<br><small class="note">'+items[i][1]+'</small>' : '')+'</span>'+chip('n');
      wb.appendChild(d); els.push(d); await sleep(550);
    }
    var send = document.createElement('div'); send.className = 'rd-pl is-send'; send.textContent = 'ارسال سفارش'; wb.appendChild(send);
    await sleep(700); await press(send); send.remove();
    var seq = [[0,'p'],[1,'p'],[2,'r'],[1,'r'],[0,'r'],[2,'s'],[1,'s'],[0,'s']];
    for (var k = 0; k < seq.length; k++) { els[seq[k][0]].lastElementChild.outerHTML = chip(seq[k][1]); await sleep(650); }
    await sleep(3000);
  }
  onVisible(el('rd3'), function(){ loop(play3); });

  (function(){
    var s = el('rdQr'); if (!s || s.childElementCount) return;
    var seed = 7, h = '';
    function r(){ seed = (seed * 9301 + 49297) % 233280; return seed / 233280; }
    function fin(x, y){ h += '<rect x="'+x+'" y="'+y+'" width="7" height="7" fill="#111"/><rect x="'+(x+1)+'" y="'+(y+1)+'" width="5" height="5" fill="#fff"/><rect x="'+(x+2)+'" y="'+(y+2)+'" width="3" height="3" fill="#111"/>'; }
    for (var y = 0; y < 21; y++) for (var x = 0; x < 21; x++) {
      if ((x < 8 && y < 8) || (x > 12 && y < 8) || (x < 8 && y > 12)) continue;
      if (r() > 0.52) h += '<rect x="'+x+'" y="'+y+'" width="1" height="1" fill="#111"/>';
    }
    fin(0, 0); fin(14, 0); fin(0, 14); s.innerHTML = h;
  })();
  var QM = [['🍢','چلوکباب کوبیده',1450000],['🍗','جوجه‌کباب',1280000],['🍛','قورمه‌سبزی',980000]];
  async function play4(){
    var qb = el('rdQb'); if (!qb) return;
    qb.innerHTML = '<div class="muted rd-wait">منتظر اسکن…</div>';
    el('rdQs').innerHTML = '<div class="muted">سفارشی در انتظار نیست.</div>';
    el('rdT4').classList.remove('is-show');
    await sleep(600);
    var sl = el('rdSl'); sl.classList.remove('is-go'); void sl.offsetWidth; sl.classList.add('is-go'); await sleep(2400);
    qb.innerHTML = ''; var qs = [];
    QM.forEach(function(m){
      var d = document.createElement('div'); d.className = 'rd-pl';
      d.innerHTML = '<span>'+m[0]+' '+m[1]+'<br><small class="muted num">'+fa(m[2])+' ریال</small></span><b class="num">۰</b>';
      qb.appendChild(d); qs.push(d.querySelector('b'));
    });
    await sleep(900); qs[0].textContent = '۲'; await sleep(500); qs[2].textContent = '۱'; await sleep(600);
    var sb = document.createElement('div'); sb.className = 'rd-pl is-send'; sb.textContent = 'ارسال سفارش'; qb.appendChild(sb);
    await sleep(500); await press(sb); sb.textContent = 'ارسال شد · منتظر تأیید رستوران'; sb.classList.remove('is-send');
    await sleep(700);
    el('rdQs').innerHTML = '<div class="rd-qo"><b>میز ۱۲</b> · <span class="rd-chip is-prep">در انتظار تأیید</span><div class="muted">۲ × چلوکباب کوبیده · ۱ × قورمه‌سبزی</div><div class="rd-acts"><span class="rd-btn is-p" id="rdQa">تأیید</span><span class="rd-btn">رد سفارش</span></div></div>';
    await sleep(1500); await press(el('rdQa'));
    el('rdQs').querySelector('.rd-chip').outerHTML = '<span class="rd-chip is-ready">پذیرفته</span>';
    var act = el('rdQs').querySelector('.rd-acts'); if (act) act.remove();
    sb.textContent = 'سفارش شما پذیرفته شد'; sb.classList.add('is-ok');
    el('rdT4').textContent = 'به سفارش باز میز ۱۲ اضافه شد و به آشپزخانه رفت'; el('rdT4').classList.add('is-show');
    await sleep(3500);
  }
  onVisible(el('rd4'), function(){ loop(play4); });

  var KQ = [['۳۰۵','۱۹:۵۷','۲','آشپزخانه','۲ × جوجه‌کباب','',17],['۳۰۸','۲۰:۰۲','۵','آشپزخانه','۱ × ماهی قزل‌آلا','کم‌نمک',12],['۳۰۸','۲۰:۰۲','۵','قنادی','۲ × کیک روز','',12],['۳۱۲','۲۰:۰۹','۷','آشپزخانه','۲ × چلوکباب کوبیده','بدون پیاز',5],['بیرون‌بر ۴۱','۲۰:۱۱','—','آشپزخانه','۳ × قورمه‌سبزی','',3]];
  async function play5(){
    var kq = el('rdKq'); if (!kq) return;
    kq.innerHTML = '';
    var stn = el('rdStn').querySelectorAll('span');
    stn.forEach(function(s, i){ s.classList.toggle('is-on', i === 0); });
    var rows = KQ.map(function(k){
      var tr = document.createElement('tr'); tr.dataset.st = k[3]; tr.dataset.m = k[6];
      tr.innerHTML = '<td class="num">'+k[0]+'</td><td class="num hide-m">'+k[1]+'</td><td>'+k[2]+'</td><td class="hide-m">'+k[3]+'</td><td>'+k[4]+'</td><td class="hide-m note">'+k[5]+'</td><td class="s"><span class="rd-chip is-prep">در حال آماده‌سازی</span></td><td class="n num min">'+faN(k[6])+'</td>';
      kq.appendChild(tr); return tr;
    });
    for (var t = 0; t < 5; t++) {
      await sleep(900);
      rows.forEach(function(r){ var m = +r.dataset.m + 1; r.dataset.m = m; r.querySelector('.min').textContent = faN(m); if (m > 20) r.classList.add('is-late'); });
      el('rdClk').textContent = 'ساعت '+faN('20:'+(14 + t + 1));
      if (t === 1) rows[2].querySelector('.s').innerHTML = '<span class="rd-chip is-ready">آمادهٔ سرو</span>';
    }
    await sleep(800);
    stn.forEach(function(s, i){ s.classList.toggle('is-on', i === 2); });
    rows.forEach(function(r){ r.classList.toggle('is-hide', r.dataset.st !== 'قنادی'); }); await sleep(1500);
    stn.forEach(function(s, i){ s.classList.toggle('is-on', i === 1); });
    rows.forEach(function(r){ r.classList.toggle('is-hide', r.dataset.st !== 'آشپزخانه'); }); await sleep(1500);
    stn.forEach(function(s, i){ s.classList.toggle('is-on', i === 0); });
    rows.forEach(function(r){ r.classList.remove('is-hide'); });
    rows[0].querySelector('.s').innerHTML = '<span class="rd-chip is-ready">آمادهٔ سرو</span>';
    await sleep(3000);
  }
  onVisible(el('rd5'), function(){ loop(play5); });

  var RC = [['گوشت چرخ‌کرده','۲۲۰ گرم',418000,6.2,5,0.22],['برنج ایرانی','۱۵۰ گرم',96000,38,10,0.15],['گوجه‌فرنگی','۱۰۰ گرم',12000,9,3,0.1],['کره','۱۵ گرم',27000,4,1,0.015],['پیاز و ادویه','۴۰ گرم',15000,6,2,0.04]];
  var PRICE = 1450000, PLATES = 6;
  async function play6(){
    var rc = el('rdRc'); if (!rc) return;
    rc.innerHTML = ''; ['rdCp','rdSp','rdPp'].forEach(function(k){ el(k).textContent = '۰'; });
    el('rdWr').classList.remove('is-show'); el('rdEv').textContent = 'پیش از تسویه';
    var stk = el('rdStk'); stk.innerHTML = '';
    var bars = RC.map(function(r){
      var d = document.createElement('div'); d.className = 'rd-sk'; var mx = r[3] * 1.3;
      d.innerHTML = '<div class="tp"><span>'+r[0]+'</span><span class="num muted"><span class="q">'+faN(r[3])+'</span> کیلو</span></div><div class="tr"><i style="width:'+(r[3]/mx*100)+'%"></i><span class="min" style="inset-inline-start:'+(r[4]/mx*100)+'%"></span></div>';
      stk.appendChild(d); return [d, mx];
    });
    var cost = 0;
    for (var i = 0; i < RC.length; i++) {
      var r = RC[i], tr = document.createElement('tr');
      tr.innerHTML = '<td>'+r[0]+'</td><td class="n">'+r[1]+'</td><td class="n num">'+fa(r[2])+'</td>';
      rc.appendChild(tr); var o = cost; cost += r[2]; count(el('rdCp'), cost, 350, o); await sleep(450);
    }
    await sleep(300); count(el('rdSp'), PRICE, 600); await count(el('rdPp'), PRICE - cost, 600);
    await sleep(1400); el('rdEv').textContent = 'پس از تسویهٔ '+faN(PLATES)+' پرس';
    RC.forEach(function(r, i){
      var left = Math.round((r[3] - r[5] * PLATES) * 100) / 100, b = bars[i];
      b[0].querySelector('i').style.width = (left / b[1] * 100)+'%';
      b[0].querySelector('.q').textContent = faN(String(left).replace('.', '٫'));
      if (left < r[4]) b[0].classList.add('is-low');
    });
    await sleep(1000); el('rdWr').classList.add('is-show');
    await sleep(4200);
  }
  onVisible(el('rd6'), function(){ loop(play6); });
})();
</script>
