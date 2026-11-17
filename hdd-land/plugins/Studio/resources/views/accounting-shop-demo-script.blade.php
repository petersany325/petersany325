<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function faN(n){return String(n).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})}
  function fa(n){return faN(String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'٬'))}
  function sleep(ms){return new Promise(function(r){setTimeout(r, reduce ? 0 : ms)})}
  function el(id){return document.getElementById(id)}
  function count(node, to, ms, from){
    if (!node) return Promise.resolve();
    from = from || 0;
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
  async function press(node){ if (!node) return; node.classList.add('is-hit'); await sleep(350); node.classList.remove('is-hit'); }

  var ITEMS = [['6260000112345','اتو بخار',1,12400000,9800000],['6260000167890','چای‌ساز رومیزی',2,8600000,6900000],['6260000154321','سشوار حرفه‌ای',3,6100000,4700000]];
  async function play1(){
    var tb = el('sdInv'), scan = el('sdScan'), bc = el('sdBc'); if (!tb) return;
    tb.innerHTML = ''; el('sdProf').classList.remove('is-show'); el('sdT1').classList.remove('is-show');
    ['sdSum','sdDsc','sdNet'].forEach(function(k){ el(k).textContent = '۰'; });
    bc.textContent = '—';
    var sum = 0, cost = 0;
    for (var i = 0; i < ITEMS.length; i++) {
      var it = ITEMS[i];
      bc.textContent = it[0]; scan.classList.add('is-beep'); await sleep(350); scan.classList.remove('is-beep');
      var line = it[2] * it[3];
      var tr = document.createElement('tr');
      tr.innerHTML = '<td>'+it[1]+'</td><td class="n num">'+faN(it[2])+'</td><td class="n num hide-m">'+fa(it[3])+'</td><td class="n num hide-m muted">—</td><td class="n num">'+fa(line)+'</td>';
      tb.appendChild(tr);
      var old = sum; sum += line; cost += it[2] * it[4];
      count(el('sdSum'), sum, 500, old); count(el('sdNet'), sum, 500, old);
      await sleep(900);
    }
    var d = Math.round(sum * 0.05);
    tb.querySelectorAll('td:nth-child(4)').forEach(function(td){ td.textContent = '۵٪'; td.classList.remove('muted'); td.classList.add('is-disc'); });
    count(el('sdDsc'), d, 600); await count(el('sdNet'), sum - d, 600, sum);
    await sleep(400); el('sdProf').classList.add('is-show'); count(el('sdProfv'), sum - d - cost, 800);
    await sleep(1400); await press(el('sdF9')); await sleep(500); await press(el('sdF10'));
    el('sdT1').textContent = 'فاکتور ۴۸۲۱ ثبت شد · سند حسابداری و حوالهٔ انبار خودکار ساخته شد';
    el('sdT1').classList.add('is-show');
    await sleep(3500);
  }
  onVisible(el('sd1'), function(){ loop(play1); });

  var TOTAL = 45505000;
  var PMS = [[10000000,'«همه نقد» یا مبلغ دلخواه'],[15505000,'ارسال به کارتخوان… تأیید شد'],[5000000,'کارت به کارت · بدون کارتخوان'],[10000000,'سررسید ۱۴۰۵/۱۰/۱۵ · بانک نمونه']];
  async function play2(){
    var pms = document.querySelectorAll('#sdPms .sd-pm');
    var ms = document.querySelectorAll('#sdMeter span');
    if (!pms.length) return;
    pms.forEach(function(p){ p.classList.remove('is-on','is-done'); p.querySelector('.am').textContent = '۰'; p.querySelector('.sub').innerHTML = '&nbsp;'; });
    ms.forEach(function(s){ s.style.width = '0'; });
    el('sdRem').textContent = fa(TOTAL); el('sdT2').classList.remove('is-show');
    await sleep(800);
    var paid = 0;
    for (var i = 0; i < PMS.length; i++) {
      var p = pms[i]; p.classList.add('is-on');
      if (i === 1) { p.querySelector('.sub').innerHTML = '<span class="sd-chip is-wait">ارسال به کارتخوان…</span>'; await sleep(1200); }
      p.querySelector('.sub').textContent = PMS[i][1];
      await count(p.querySelector('.am'), PMS[i][0], 600);
      var old = TOTAL - paid; paid += PMS[i][0];
      ms[i].style.width = (PMS[i][0] / TOTAL * 100) + '%';
      count(el('sdRem'), TOTAL - paid, 600, old);
      p.classList.remove('is-on'); p.classList.add('is-done');
      await sleep(700);
    }
    await sleep(500); await press(el('sdK2'));
    el('sdT2').textContent = 'فاکتور ثبت شد · ۵٬۰۰۰٬۰۰۰ ریال نسیه به حساب مشتری رفت · سند خزانه ساخته شد';
    el('sdT2').classList.add('is-show');
    await sleep(3800);
  }
  onVisible(el('sd2'), function(){ loop(play2); });

  var STK = [['اتو بخار',22,6,10,9800000],['چای‌ساز رومیزی',4,2,8,6900000],['سشوار حرفه‌ای',3,1,10,4700000],['جاروبرقی',14,5,6,21500000],['آب‌میوه‌گیری',9,4,5,7300000]];
  async function play3(){
    var whs = document.querySelectorAll('#sdWh span'), tb = el('sdStk'), sug = el('sdSug');
    if (!tb) return;
    for (var w = 0; w < 3; w++) {
      whs.forEach(function(s, j){ s.classList.toggle('is-on', j === w); });
      tb.innerHTML = ''; sug.classList.remove('is-show');
      STK.forEach(function(s){
        var q = w === 0 ? s[1] + s[2] : (w === 1 ? s[1] : s[2]);
        var rp = w === 0 ? s[3] : Math.max(2, Math.round(s[3] / (w === 1 ? 1.4 : 3)));
        var low = q < rp;
        var tr = document.createElement('tr'); tr.className = low ? 'is-low' : '';
        tr.innerHTML = '<td>'+s[0]+'</td><td class="n num">'+faN(q)+'</td><td class="n num hide-m muted">'+faN(rp)+'</td><td class="hide-m"><div class="sd-barline"><i></i></div></td><td class="n num hide-m">'+fa(q * s[4])+'</td><td>'+(low ? '<span class="sd-chip is-bad">زیر نقطهٔ سفارش</span>' : '<span class="sd-chip is-ok">کافی</span>')+'</td>';
        tb.appendChild(tr);
        var bar = tr.querySelector('.sd-barline i');
        if (bar) bar.style.width = Math.min(100, q / (rp * 2) * 100) + '%';
      });
      await sleep(900);
      if (w === 0) { sug.classList.add('is-show'); await sleep(2600); } else await sleep(1800);
    }
  }
  onVisible(el('sd3'), function(){ loop(play3); });

  var CHQ = [['۲۴۸۱۵۶','فروشگاه نمونهٔ امید',12000000,'امروز','is-due'],['۷۳۱۰۴۲','آقای نمونه',8500000,'۳ روز دیگر',''],['۵۵۰۹۱۸','شرکت نمونهٔ آوا',21000000,'۶ روز دیگر',''],['۱۹۲۷۷۴','خانم نمونه',6400000,'۱۸ روز دیگر','']];
  function cnt(){
    for (var i = 0; i < 4; i++) {
      var c = el('sdC'+i); if (!c) return;
      c.querySelector('.h span:last-child').textContent = faN(c.querySelectorAll('.sd-chq').length);
    }
  }
  function mk(c){
    var d = document.createElement('div'); d.className = 'sd-chq'+(c[4] ? ' '+c[4] : '');
    d.innerHTML = '<div class="a num">'+fa(c[2])+' <span class="muted">ریال</span></div><div>'+c[1]+'</div><div class="d"><span>#'+c[0]+'</span><span>'+c[3]+'</span></div>';
    return d;
  }
  function move(node, col){
    node.remove();
    var n = node.cloneNode(true);
    el(col).appendChild(n); cnt(); return n;
  }
  async function play4(){
    if (!el('sdC0')) return;
    for (var i = 0; i < 4; i++) el('sdC'+i).querySelectorAll('.sd-chq').forEach(function(e){ e.remove(); });
    el('sdRmd').classList.remove('is-show'); cnt();
    var els = [];
    for (var k = 0; k < CHQ.length; k++) { var e = mk(CHQ[k]); el('sdC0').appendChild(e); els.push(e); cnt(); await sleep(450); }
    await sleep(500); el('sdRmd').classList.add('is-show'); await sleep(1500);
    els[0] = move(els[0], 'sdC1'); await sleep(900);
    els[1] = move(els[1], 'sdC1'); await sleep(1100);
    els[0] = move(els[0], 'sdC2'); els[0].classList.remove('is-due'); els[0].querySelector('.d span:last-child').textContent = 'وصول شد ✓'; await sleep(1100);
    els[1] = move(els[1], 'sdC3'); els[1].classList.add('is-bounced'); els[1].querySelector('.d span:last-child').textContent = 'برگشت خورد';
    await sleep(4000);
  }
  onVisible(el('sd4'), function(){ loop(play4); });

  var PRF = [['جاروبرقی',412,318],['اتو بخار',286,226],['سشوار حرفه‌ای',198,152],['چای‌ساز رومیزی',174,139],['آب‌میوه‌گیری',121,98]];
  async function play5(){
    var g = document.querySelectorAll('#sdGrp span'), tb = el('sdPrf'); if (!tb) return;
    g.forEach(function(s, j){ s.classList.toggle('is-on', j === 0); });
    tb.innerHTML = '';
    var tp = PRF.reduce(function(a, r){ return a + (r[1] - r[2]); }, 0);
    var ts = PRF.reduce(function(a, r){ return a + r[1]; }, 0);
    var tc = PRF.reduce(function(a, r){ return a + r[2]; }, 0);
    var jobs = [];
    PRF.forEach(function(r, i){
      var p = r[1] - r[2], sh = Math.round(p / tp * 100);
      var tr = document.createElement('tr'); if (i === 0) tr.className = 'is-top';
      tr.innerHTML = '<td>'+r[0]+'</td><td class="n num hide-m">۰</td><td class="n num hide-m">۰</td><td class="n num is-green">۰</td><td><div class="sd-share"><div class="tr"><i></i></div><span class="num">۰٪</span></div></td>';
      tb.appendChild(tr); jobs.push([tr, r, p, sh]);
    });
    var sr = document.createElement('tr'); sr.className = 'is-sum';
    sr.innerHTML = '<td>جمع</td><td class="n num hide-m">'+fa(ts)+'</td><td class="n num hide-m">'+fa(tc)+'</td><td class="n num">'+fa(tp)+'</td><td>۱۰۰٪</td>';
    await sleep(400);
    jobs.forEach(function(j){
      var c = j[0].children;
      count(c[1], j[1][1], 1100); count(c[2], j[1][2], 1100); count(c[3], j[2], 1100);
      j[0].querySelector('.tr i').style.width = (j[3] * 2) + '%';
      var s = j[0].querySelector('.sd-share .num');
      count(s, j[3], 1100).then(function(){ s.textContent = faN(j[3])+'٪'; });
    });
    await sleep(1300); tb.appendChild(sr); await sleep(1200);
    for (var k = 1; k < g.length; k++) { g.forEach(function(s, j){ s.classList.toggle('is-on', j === k); }); await sleep(500); }
    g.forEach(function(s, j){ s.classList.toggle('is-on', j === 0); });
    await sleep(3000);
  }
  onVisible(el('sd5'), function(){ loop(play5); });
})();
</script>
