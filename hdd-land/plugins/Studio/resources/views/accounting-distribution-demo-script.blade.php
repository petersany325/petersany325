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
  async function press(node){ if (!node) return; node.classList.add('is-hit'); await sleep(300); node.classList.remove('is-hit'); }
  var C = {green:'#2ecc8f', amber:'#f59e42', blue:'#4fa8ff', gray:'#6b7099'};

  var VP = [[90,205,'g'],[160,160,'a'],[300,75,'g'],[370,120,'g'],[440,160,'a'],[510,110,'g']];
  var TP = [[460,250,'g'],[370,200,'g'],[230,200,'a'],[160,250,'g'],[90,120,'g']];
  function nearestLen(path, x, y){
    var L = path.getTotalLength(), best = 0, bd = 1e9;
    for (var s = 0; s <= L; s += 3) {
      var q = path.getPointAtLength(s);
      var d = (q.x - x) * (q.x - x) + (q.y - y) * (q.y - y);
      if (d < bd) { bd = d; best = s; }
    }
    return best;
  }
  function mkPin(p, col){
    var c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    c.setAttribute('cx', p[0]); c.setAttribute('cy', p[1]); c.setAttribute('r', 7);
    c.setAttribute('fill', col); c.setAttribute('stroke', '#0e1124'); c.setAttribute('stroke-width', '2.5');
    el('ddPins').appendChild(c); return c;
  }
  async function play1(){
    var pins = el('ddPins'), vp = el('ddVp'), tp = el('ddTp'), vis = el('ddVis'), trk = el('ddTrk');
    if (!pins || !vp) return;
    pins.innerHTML = '';
    var vPins = VP.map(function(p){ return {el: mkPin(p, C.gray), len: nearestLen(vp, p[0], p[1]), r: p[2], done: false}; });
    var tPins = TP.map(function(p){ return {el: mkPin(p, C.blue), len: nearestLen(tp, p[0], p[1]), r: p[2], done: false}; });
    var n = [0,0,0];
    ['ddN0','ddN1','ddN2'].forEach(function(id){ el(id).textContent = '۰'; });
    var LV = vp.getTotalLength(), LT = tp.getTotalLength(), dur = reduce ? 1 : 11000, t0 = performance.now();
    await new Promise(function(res){
      (function f(t){
        var p = reduce ? 1 : Math.min(1, (t - t0) / dur);
        var a = vp.getPointAtLength(LV * p), b = tp.getPointAtLength(LT * p);
        vis.setAttribute('transform', 'translate('+a.x+','+a.y+')');
        trk.setAttribute('transform', 'translate('+b.x+','+b.y+')');
        vPins.forEach(function(k){
          if (!k.done && LV * p >= k.len) {
            k.done = true; k.el.setAttribute('fill', k.r === 'g' ? C.green : C.amber);
            var ix = k.r === 'g' ? 0 : 1; n[ix]++; el('ddN'+ix).textContent = faN(n[ix]);
          }
        });
        tPins.forEach(function(k){
          if (!k.done && LT * p >= k.len) {
            k.done = true; k.el.setAttribute('fill', k.r === 'g' ? C.green : C.amber);
            if (k.r === 'g') { n[2]++; el('ddN2').textContent = faN(n[2]); }
          }
        });
        el('ddClk').textContent = 'آخرین مکان: '+faN('10:'+(42 + Math.floor(p * 15)));
        if (p < 1 && !reduce) requestAnimationFrame(f); else res();
      })(t0);
    });
    await sleep(3000);
  }
  onVisible(el('dd1'), function(){ loop(play1); });

  async function play2(){
    var root = el('dd2'); if (!root) return;
    var rcs = root.querySelectorAll('.dd-rc');
    rcs.forEach(function(r){ r.classList.remove('is-cur'); r.lastElementChild.className = 'dd-chip'; r.lastElementChild.textContent = 'ویزیت نشده'; });
    var net = el('ddNet'); net.className = 'dd-net is-off'; net.textContent = 'بدون اینترنت';
    var items = el('ddItems'); el('ddPst').innerHTML = ''; el('ddSend').textContent = 'ثبت سفارش';
    rcs[0].classList.add('is-cur'); el('ddCust').textContent = 'سوپرمارکت ستاره';
    items.innerHTML = '<div class="dd-it"><span>ماست ۹۰۰ گرمی</span><b class="num" id="ddAq">۰</b></div><div class="dd-it"><span>شیر یک لیتری</span><b class="num">۶</b></div><div class="dd-hint" id="ddAh">۱۰ کارتن دیگر ← پلهٔ بعد</div>';
    await sleep(700);
    for (var q = 1; q <= 12; q++) {
      el('ddAq').textContent = faN(q);
      if (q < 10) el('ddAh').textContent = faN(10 - q)+' کارتن دیگر ← پلهٔ بعد';
      if (q === 10) {
        var g = document.createElement('div'); g.className = 'dd-it is-gift';
        g.innerHTML = '<span>اشانتیون ماست</span><b class="num">۱</b>';
        items.insertBefore(g, el('ddAh'));
      }
      if (q >= 10) el('ddAh').textContent = faN(20 - q)+' کارتن دیگر ← پلهٔ بعد (۳ اشانتیون)';
      await sleep(q < 9 ? 160 : 420);
    }
    await sleep(500); await press(el('ddSend'));
    el('ddPst').innerHTML = '<span class="dd-chip is-wait">روی گوشی ذخیره شد · در صف ارسال</span>';
    rcs[0].lastElementChild.className = 'dd-chip is-wait'; rcs[0].lastElementChild.textContent = 'در صف ارسال';
    await sleep(1600);
    rcs[0].classList.remove('is-cur'); rcs[1].classList.add('is-cur'); el('ddCust').textContent = 'بقالی امید';
    items.innerHTML = '<div class="dd-it"><span>دلیل</span><span class="muted">موجودی دارد</span></div>';
    el('ddSend').textContent = 'ثبت بی‌خرید'; el('ddPst').innerHTML = '';
    await sleep(900); await press(el('ddSend'));
    rcs[1].lastElementChild.className = 'dd-chip is-org'; rcs[1].lastElementChild.textContent = 'بی‌خرید';
    await sleep(900);
    net.className = 'dd-net is-on'; net.textContent = 'آنلاین';
    el('ddPst').innerHTML = '<span class="dd-chip is-ok">✓ ارسال شد · فقط یک بار ثبت شد</span>';
    rcs[0].lastElementChild.className = 'dd-chip is-ok'; rcs[0].lastElementChild.textContent = 'سفارش ✓';
    await sleep(3200);
  }
  onVisible(el('dd2'), function(){ loop(play2); });

  var ORD = [['فروشگاه گلستان',3,18],['سوپرمارکت ستاره',1,22],['مینی‌مارکت باران',4,12],['هایپر آفتاب',2,26],['بقالی امید',5,9]];
  async function play3(){
    var appr = el('ddAppr'), load = el('ddLoad'); if (!appr) return;
    appr.innerHTML = ''; load.innerHTML = ''; el('ddSettle').classList.remove('is-show'); el('ddT3').classList.remove('is-show');
    el('ddCapb').style.width = '0'; el('ddCapv').textContent = '۰٪ ظرفیت';
    var els = ORD.map(function(o){
      var d = document.createElement('div'); d.className = 'dd-ord';
      d.innerHTML = '<span>'+o[0]+'</span><span class="dd-chip is-blue">تأیید برای لیست پخش</span>';
      appr.appendChild(d); return d;
    });
    await sleep(1000); var cap = 0;
    for (var i = 0; i < ORD.length; i++) {
      els[i].remove();
      var d = document.createElement('div'); d.className = 'dd-ord'; d.dataset.k = ORD[i][1];
      d.innerHTML = '<span class="seq">'+faN(i+1)+'</span><span class="nm">'+ORD[i][0]+'</span><span class="st dd-chip">در لیست</span>';
      load.appendChild(d);
      cap += ORD[i][2]; el('ddCapb').style.width = cap+'%'; el('ddCapv').textContent = faN(cap)+'٪ ظرفیت';
      await sleep(450);
    }
    await sleep(400); await press(el('ddSort'));
    var rows = [].slice.call(load.children).sort(function(a, b){ return a.dataset.k - b.dataset.k; });
    rows.forEach(function(r, i){ r.querySelector('.seq').textContent = faN(i+1); load.appendChild(r); });
    await sleep(900); await press(el('ddLoadBtn'));
    rows.forEach(function(r){ var s = r.querySelector('.st'); s.className = 'st dd-chip is-blue'; s.textContent = 'فاکتور شد · در راه'; });
    await sleep(1300);
    var res = [['is-ok','تحویل شد'],['is-ok','تحویل شد'],['is-org','برگشتی · آسیب‌دیده'],['is-ok','تحویل شد'],['is-org','تحویل نشد']];
    for (var j = 0; j < rows.length; j++) { var s = rows[j].querySelector('.st'); s.className = 'st dd-chip '+res[j][0]; s.textContent = res[j][1]; await sleep(650); }
    await sleep(500); await press(el('ddSet')); el('ddSettle').classList.add('is-show');
    el('ddT3').textContent = 'تسویه ثبت و لیست پخش بسته شد'; el('ddT3').classList.add('is-show');
    await sleep(3500);
  }
  onVisible(el('dd3'), function(){ loop(play3); });

  var TI = [[10,1],[20,3],[40,7]];
  async function play4(){
    var root = el('dd4'); if (!root) return;
    var tiers = root.querySelectorAll('.dd-tier'), ticks = root.querySelectorAll('.dd-tick');
    tiers.forEach(function(t){ t.classList.remove('is-on'); }); ticks.forEach(function(t){ t.classList.remove('is-hit'); });
    el('ddGr').hidden = true; el('ddLf').style.width = '0';
    for (var q = 0; q <= 24; q++) {
      el('ddQ').innerHTML = faN(q)+' <span class="muted">کارتن</span>';
      el('ddRq').textContent = faN(q)+' کارتن';
      el('ddLf').style.width = (q / 40 * 100)+'%';
      var g = 0, lvl = -1;
      TI.forEach(function(t, i){ if (q >= t[0]) { g = t[1]; lvl = i; } });
      tiers.forEach(function(t, i){ t.classList.toggle('is-on', i === lvl); });
      ticks.forEach(function(t, i){ t.classList.toggle('is-hit', i <= lvl); });
      if (g) { el('ddGr').hidden = false; el('ddGq').textContent = faN(g)+' کارتن'; }
      var nxt = TI.filter(function(t){ return t[0] > q; })[0];
      el('ddNx').textContent = nxt ? faN(nxt[0]-q)+' کارتن دیگر ← پلهٔ بعد ('+faN(nxt[1])+' کارتن اشانتیون)' : 'بالاترین پله';
      await sleep(q === 10 || q === 20 ? 900 : 170);
    }
    await sleep(3200);
  }
  onVisible(el('dd4'), function(){ loop(play4); });

  var VT = [['رضا کریمی',18,16,2,0,92],['مریم احمدی',20,17,3,1,81],['حسین نوری',18,15,2,0,64]];
  async function play5(){
    var root = el('dd5'), vt = el('ddVt'); if (!root || !vt) return;
    root.querySelectorAll('[data-c]').forEach(function(e){ e.textContent = '۰'; });
    root.querySelectorAll('[data-w]').forEach(function(e){ e.style.transition = 'none'; e.style.width = '0'; void e.offsetWidth; e.style.transition = ''; });
    vt.innerHTML = '';
    VT.forEach(function(v){
      var tr = document.createElement('tr');
      tr.innerHTML = '<td>'+v[0]+'</td><td class="n num hide-m">'+faN(v[1])+'</td><td class="n num">'+faN(v[2])+'</td><td class="n num hide-m">'+faN(v[3])+'</td><td class="n num hide-m'+(v[4] ? ' is-far' : '')+'">'+faN(v[4])+'</td><td><div class="dd-tg"><div class="tr"><i data-w="'+v[5]+'"></i></div><span class="num">۰٪</span></div></td>';
      vt.appendChild(tr);
    });
    await sleep(300);
    root.querySelectorAll('[data-c]').forEach(function(e){ count(e, +e.dataset.c, 1300); });
    root.querySelectorAll('[data-w]').forEach(function(e){ e.style.width = e.dataset.w+'%'; });
    vt.querySelectorAll('tr').forEach(function(tr, i){
      var s = tr.querySelector('.dd-tg .num');
      count(s, VT[i][5], 1300).then(function(){ s.textContent = faN(VT[i][5])+'٪'; });
    });
    await sleep(6000);
  }
  onVisible(el('dd5'), function(){ loop(play5); });
})();
</script>
