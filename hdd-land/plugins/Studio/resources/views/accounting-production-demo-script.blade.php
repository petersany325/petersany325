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
    for (var i = 1; i <= txt.length; i++) { node.textContent = txt.slice(0, i); await sleep(50); }
    node.classList.remove('is-type');
  }

  var MAT = [['شیر','۱۰۰ کیلو',16000000],['ظرف','۲۰۰ عدد',2400000]];
  async function play1(){
    if (!el('mf1')) return;
    ['mfP0','mfP1','mfP2'].forEach(function(k){ el(k).textContent = ''; });
    el('mfMat').innerHTML = '';
    ['mfV1','mfV2','mfV3'].forEach(function(k){ el(k).classList.remove('is-on'); });
    ['mfK1','mfK2','mfK3','mfK4'].forEach(function(k){ el(k).textContent = '۰'; });
    await sleep(400); await type(el('mfP0'), 'ماست ۱ کیلویی'); await type(el('mfP1'), '۲۰۰ عدد'); await type(el('mfP2'), 'انبار محصول');
    for (var i = 0; i < MAT.length; i++) {
      var tr = document.createElement('tr');
      tr.innerHTML = '<td>'+MAT[i][0]+'</td><td class="n num">'+MAT[i][1]+'</td><td class="n num">'+fa(MAT[i][2])+'</td>';
      el('mfMat').appendChild(tr); await sleep(350);
    }
    await sleep(500); await press(el('mfPb'));
    el('mfV1').classList.add('is-on'); await count(el('mfK1'), 18400000, 600); await sleep(250);
    el('mfV2').classList.add('is-on'); await count(el('mfK2'), 10000000, 600); await sleep(250);
    el('mfV3').classList.add('is-on'); await count(el('mfK3'), 28400000, 600);
    await count(el('mfK4'), 142000, 600);
    await sleep(4000);
  }
  onVisible(el('mf1'), function(){ loop(play1); });

  var TREE = [['is-root','ماست موسیر','۱۰۰ عدد'],['is-l1 is-semi','ماست پایه (نیم‌ساخته)','۹۰ کیلو'],['is-l2','شیر','۹۵ کیلو'],['is-l2','مایهٔ ماست','۱ کیلو'],['is-l1','موسیر خشک','۳ کیلو'],['is-l1','ظرف ۱ کیلویی','۱۰۰ عدد'],['is-l1','دستمزد و سربار هر واحد','۵۵٬۰۰۰ ریال']];
  var PL = [['ماست پایه','نیم‌ساخته','۰٫۹ کیلو',118000],['موسیر خشک','مادهٔ اولیه','۰٫۰۳ کیلو',27000],['ظرف ۱ کیلویی','مادهٔ اولیه','۱ عدد',12000]];
  async function play2(){
    var tree = el('mfTree'); if (!tree) return;
    tree.innerHTML = ''; el('mfPl').innerHTML = '';
    el('mfRules').querySelectorAll('.mf-rule').forEach(function(r){ r.classList.remove('is-show'); });
    var nodes = TREE.map(function(t){
      var d = document.createElement('div'); d.className = 'mf-node '+t[0];
      d.innerHTML = '<span>'+t[1]+'</span><span class="q num">'+t[2]+'</span>';
      tree.appendChild(d); return d;
    });
    for (var i = 0; i < nodes.length; i++) { nodes[i].classList.add('is-show'); await sleep(380); }
    await sleep(400);
    for (var j = 0; j < PL.length; j++) {
      var r = document.createElement('tr');
      r.innerHTML = '<td>'+PL[j][0]+'</td><td>'+(PL[j][1]==='نیم‌ساخته' ? '<span class="mf-chip is-blue">نیم‌ساخته</span>' : '<span class="mf-chip is-mute">مادهٔ اولیه</span>')+'</td><td class="n num">'+PL[j][2]+'</td><td class="n num hide-m">'+fa(PL[j][3])+'</td>';
      el('mfPl').appendChild(r); await sleep(350);
    }
    var rs = el('mfRules').querySelectorAll('.mf-rule');
    for (var k = 0; k < rs.length; k++) { rs[k].classList.add('is-show'); await sleep(350); }
    await sleep(4000);
  }
  onVisible(el('mf2'), function(){ loop(play2); });

  var CP = [['ماست ۱ کیلویی',[840,620,220],['ظرف ۱ کیلویی','ظرف ۱ کیلویی','شیر']],['ماست موسیر',[310,310,0],['موسیر خشک','موسیر خشک','ماست پایه']],['دوغ ۱٫۵ لیتری',[1200,900,300],['—','—','بطری']],['کره ۱۰۰ گرمی',[0,0,0],['خامه','خامه','خامه']]];
  async function play3(){
    var root = el('mf3'); if (!root) return;
    var w = root.querySelectorAll('.mf-wh span'), tb = el('mfCap');
    for (var k = 0; k < 3; k++) {
      w.forEach(function(s, i){ s.classList.toggle('is-on', i === k); });
      tb.innerHTML = '';
      var rows = CP.map(function(c){
        var n = c[1][k], tr = document.createElement('tr'); if (n === 0) tr.classList.add('is-zero');
        tr.innerHTML = '<td>'+c[0]+'</td><td><div class="mf-cap"><div class="tr"><i></i></div></div></td><td class="n num">۰</td><td>'+(c[2][k]==='—' ? '<span class="muted">—</span>' : '<span class="mf-chip '+(n===0?'is-bad':'is-warn')+'">'+c[2][k]+'</span>')+'</td>';
        tb.appendChild(tr); return [tr, n];
      });
      await sleep(150);
      rows.forEach(function(r){ r[0].querySelector('.tr i').style.width = (r[1]===0 ? 100 : Math.min(100, r[1]/1200*100))+'%'; count(r[0].children[2], r[1], 900); });
      await sleep(2600);
    }
  }
  onVisible(el('mf3'), function(){ loop(play3); });

  var BT = [['B-0812','۱۴۰۵/۰۷/۱۲','۱۴۰۵/۰۸/۲۵',60,'exp'],['B-0819','۱۴۰۵/۰۷/۲۰','۱۴۰۵/۰۹/۰۸',120,'near'],['B-0903','۱۴۰۵/۰۸/۰۳','۱۴۰۵/۱۰/۰۲',300,'ok'],['B-0915','۱۴۰۵/۰۸/۱۵','۱۴۰۵/۱۰/۱۵',420,'ok']];
  var ST = {exp:'<span class="mf-chip is-bad">منقضی · کنار گذاشته در فروش</span>', near:'<span class="mf-chip is-warn">نزدیک انقضا · پیش‌فرض ۳۰ روز</span>', ok:'<span class="mf-chip is-ok">سالم</span>'};
  async function play4(){
    var tb = el('mfBts'); if (!tb) return;
    tb.innerHTML = ''; el('mfPick').innerHTML = '<b>فاکتور فروش · ۴۰ عدد</b><div class="muted">انتخاب سری…</div>';
    var rows = BT.map(function(b){
      var tr = document.createElement('tr');
      tr.innerHTML = '<td class="num mf-ltr">'+b[0]+'</td><td class="num hide-m">'+b[1]+'</td><td class="num">'+b[2]+'</td><td class="n num">'+fa(b[3])+'</td><td class="s"><span class="mf-chip is-mute">…</span></td>';
      tb.appendChild(tr); return tr;
    });
    await sleep(900);
    for (var i = 0; i < rows.length; i++) {
      if (BT[i][4] === 'exp') rows[i].classList.add('is-exp');
      if (BT[i][4] === 'near') rows[i].classList.add('is-near');
      rows[i].querySelector('.s').innerHTML = ST[BT[i][4]];
      await sleep(500);
    }
    await sleep(800);
    el('mfPick').innerHTML = '<b>فاکتور فروش · ۴۰ عدد</b><div><span class="mf-chip is-bad">B-0812</span> منقضی است و در فروش نمی‌آید</div><div><span class="mf-chip is-warn">B-0819</span> نزدیک انقضا · هشدار</div><div><span class="mf-chip is-ok">✓</span> سری ساخت روی ردیف فاکتور ثبت شد</div>';
    await sleep(4200);
  }
  onVisible(el('mf4'), function(){ loop(play4); });

  function countKg(node, to, ms){
    if (!node) return Promise.resolve();
    if (reduce) { node.textContent = to.toLocaleString('en-US')+' kg'; return Promise.resolve(); }
    return new Promise(function(res){
      var t0 = performance.now();
      (function f(t){
        var p = Math.min(1, (t - t0) / ms);
        node.textContent = Math.round(to * (1 - Math.pow(1 - p, 3))).toLocaleString('en-US')+' kg';
        if (p < 1) requestAnimationFrame(f); else res();
      })(t0);
    });
  }
  async function play5(){
    var trk = el('mfTrk'), wd = el('mfWd'); if (!trk) return;
    ['mfC1','mfC2','mfC3','mfC4','mfC5'].forEach(function(k){ el(k).classList.remove('is-on'); });
    el('mfT5').classList.remove('is-show');
    trk.style.transition = 'none'; trk.style.left = '-40%'; el('mfLoad').style.opacity = 1; void trk.offsetWidth; trk.style.transition = '';
    wd.textContent = '0 kg';
    await sleep(500); trk.style.left = '30%'; await sleep(1700);
    await countKg(wd, 42000, 800); el('mfC1').classList.add('is-on'); await sleep(900);
    trk.style.left = '110%'; wd.textContent = '0 kg'; await sleep(1500);
    el('mfLoad').style.opacity = 0; trk.style.transition = 'none'; trk.style.left = '-40%'; void trk.offsetWidth; trk.style.transition = '';
    await sleep(200); trk.style.left = '30%'; await sleep(1700);
    await countKg(wd, 15000, 700); el('mfC2').classList.add('is-on'); await sleep(700);
    el('mfC3').classList.add('is-on'); await sleep(600); el('mfC4').classList.add('is-on'); await sleep(600); el('mfC5').classList.add('is-on');
    await sleep(800); await press(el('mfWbtn'));
    el('mfT5').textContent = 'فاکتور خرید با ۲۶٬۷۳۰ کیلو آرد ساخته شد'; el('mfT5').classList.add('is-show');
    await sleep(3600);
  }
  onVisible(el('mf5'), function(){ loop(play5); });

  var CR = [['ماست ۱ کیلویی',6,1200,92000,50000,185000],['ماست موسیر',3,400,157000,55000,260000],['دوغ ۱٫۵ لیتری',4,2400,48000,22000,95000],['کره ۱۰۰ گرمی',2,800,118000,31000,140000]];
  async function play6(){
    var tb = el('mfCr'); if (!tb) return;
    tb.innerHTML = ''; el('mfT6').classList.remove('is-show');
    var mx = Math.max.apply(null, CR.map(function(c){ return Math.max(c[3]+c[4], c[5]); })) * 1.08;
    var rows = CR.map(function(c){
      var u = c[3]+c[4], tr = document.createElement('tr');
      tr.innerHTML = '<td>'+c[0]+'</td><td class="n num hide-m">'+faN(c[1])+'</td><td class="n num hide-m">'+fa(c[2])+'</td><td class="n num">۰</td><td class="n num hide-m">'+fa(c[5])+'</td><td><div class="mf-stack"><i class="m"></i><i class="o"></i><span class="pr" style="inset-inline-start:'+(c[5]/mx*100)+'%"></span></div></td>';
      tb.appendChild(tr); return [tr, c, u];
    });
    await sleep(300);
    rows.forEach(function(r){
      var c = r[1];
      r[0].querySelector('.m').style.width = (c[3]/mx*100)+'%';
      r[0].querySelector('.o').style.width = (c[4]/mx*100)+'%';
      count(r[0].children[3], r[2], 1000);
    });
    await sleep(1400);
    rows.forEach(function(r){ if (r[2] > r[1][5]) { r[0].classList.add('is-loss'); r[0].children[3].style.color = '#ff5d6c'; } });
    el('mfT6').textContent = 'بهای واحد «کره ۱۰۰ گرمی» از قیمت فروشش بالاتر رفته است'; el('mfT6').classList.add('is-show');
    await sleep(4000);
  }
  onVisible(el('mf6'), function(){ loop(play6); });
})();
</script>
