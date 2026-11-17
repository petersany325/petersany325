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
  async function type(node, txt){
    if (!node) return;
    node.classList.add('is-type'); node.textContent = '';
    for (var i = 1; i <= txt.length; i++) { node.textContent = txt.slice(0, i); await sleep(55); }
    node.classList.remove('is-type');
  }

  var W = [['ساختمان اداری',10000,8000,4000,4600],['پل روستایی',6500,5200,3900,5300],['شبکهٔ آب',3000,3400,1700,1400]];
  function derive(w, c){
    var pr = Math.min(1, c / w[2]), rev = Math.round(w[1] * pr);
    return {pr:pr, rev:rev, asset:Math.max(0, rev - w[4]), liab:Math.max(0, w[4] - rev), prov:Math.round(Math.max(0, w[2] - w[1]) * (1 - pr))};
  }
  async function play1(){
    var tb = el('ctWip'); if (!tb) return;
    tb.innerHTML = '';
    var rows = W.map(function(w){
      var tr = document.createElement('tr');
      tr.innerHTML = '<td>'+w[0]+'</td><td class="n num hide-m">'+fa(w[1])+'</td><td class="n num hide-m">'+fa(w[2])+'</td><td class="n num c">۰</td><td><div class="ct-prog"><div class="tr"><i></i></div><span class="num p">۰٪</span></div></td><td class="n num rv">۰</td><td class="n num hide-m">'+fa(w[4])+'</td><td class="n num as">—</td><td class="n num lb">—</td><td class="n num pv">—</td>';
      tb.appendChild(tr); return tr;
    });
    await sleep(500);
    var dur = reduce ? 1 : 2600, t0 = performance.now();
    await new Promise(function(res){
      (function f(t){
        var p = reduce ? 1 : Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3);
        rows.forEach(function(r, i){
          var w = W[i], c = Math.round(w[3] * e), d = derive(w, c);
          r.querySelector('.c').textContent = fa(c);
          r.querySelector('.tr i').style.width = (d.pr * 100)+'%';
          r.querySelector('.p').textContent = faN(Math.round(d.pr * 100))+'٪';
          r.querySelector('.rv').textContent = fa(d.rev);
          r.querySelector('.as').innerHTML = d.asset ? '<span class="is-pos">'+fa(d.asset)+'</span>' : '—';
          r.querySelector('.lb').innerHTML = d.liab ? '<span class="is-neg">'+fa(d.liab)+'</span>' : '—';
          r.querySelector('.pv').innerHTML = d.prov ? '<span class="is-loss">'+fa(d.prov)+'</span>' : '—';
        });
        if (p < 1 && !reduce) requestAnimationFrame(f); else res();
      })(t0);
    });
    await sleep(4500);
  }
  onVisible(el('ct1'), function(){ loop(play1); });

  var P = [['ساختمان اداری','شرکت نمونهٔ آرمان',10000,8000,'۵٪','۵٪'],['پل روستایی','دهیاری نمونه',6500,5200,'۱۰٪','۵٪'],['شبکهٔ آب','شرکت نمونهٔ آبراه',3000,3400,'۵٪','۵٪']];
  async function play2(){
    var pc = el('ctPcs'); if (!pc) return;
    pc.innerHTML = '';
    var els = P.map(function(p){
      var g = p[2] - p[3], d = document.createElement('div'); d.className = 'ct-pc';
      d.innerHTML = '<div class="h">'+p[0]+'</div><div class="muted">کارفرما: '+p[1]+'</div><div class="l"><span>مبلغ پیمان</span><b class="num">'+fa(p[2])+'</b></div><div class="l"><span>برآورد کل بها</span><b class="num">'+fa(p[3])+'</b></div><div class="l"><span>حسن انجام کار (نرخ این پروژه)</span><b class="num">'+p[4]+'</b></div><div class="l"><span>بیمه (نرخ این پروژه)</span><b class="num">'+p[5]+'</b></div><div class="gp"><span>سود ناخالص برآوردی</span><span class="num '+(g<0?'is-loss':'is-pos')+'">'+(g<0?'زیان '+fa(-g):fa(g))+'</span></div>'+(g<0?'<div class="tag"><span class="ct-chip is-bad">قرارداد زیان‌بار · ذخیرهٔ زیان لازم است</span></div>':'');
      pc.appendChild(d); return d;
    });
    for (var i = 0; i < els.length; i++) { els[i].classList.add('is-show'); await sleep(500); }
    await sleep(4500);
  }
  onVisible(el('ct2'), function(){ loop(play2); });

  var S = {
    emp:{lines:[['ناخالص (کارکرد دوره)',1200],['ارزش افزوده',120],['حسن انجام کار',-60],['بیمه',-60],['علی‌الحساب مالیات',-36]], net:1164,
      vc:[['حساب کارفرما (خالص)','بدهکار'],['سپردهٔ حسن انجام کار نزد کارفرما','بدهکار'],['کسور بیمه نزد کارفرما','بدهکار'],['مالیات تکلیفی کسرشده','بدهکار'],['دارایی قرارداد','بستانکار']],
      note:'درآمد اینجا ثبت نمی‌شود؛ فقط با «درصد پیشرفت».'},
    sub:{lines:[['ناخالص (کار پیمانکار جزء)',480],['ارزش افزوده خرید',48],['حسن انجام کار',-24],['بیمه',-24]], net:480,
      vc:[['بهای پیمان (به بهای پروژه)','بدهکار'],['ارزش افزوده خرید','بدهکار'],['پیمانکار جزء','بستانکار'],['سپردهٔ حسن انجام کار پیمانکاران','بستانکار'],['کسور بیمه','بستانکار']],
      note:'این مبلغ در «بها در برابر بودجه» زیر سرفصل «پیمانکار جزء» می‌آید.'}
  };
  async function show3(k, i){
    var tabs = el('ctTabs').querySelectorAll('span');
    tabs.forEach(function(t, j){ t.classList.toggle('is-on', j === i); });
    var s = S[k], L = el('ctLines'); L.innerHTML = ''; el('ctVc').innerHTML = '';
    el('ctSst').className = 'ct-chip is-mute'; el('ctSst').textContent = 'پیش‌نویس'; el('ctT3').classList.remove('is-show');
    var els = s.lines.map(function(l){
      var d = document.createElement('div'); d.className = 'ct-ln';
      d.innerHTML = '<span>'+(l[1]<0?'− ':'')+l[0]+'</span><b class="num">'+fa(Math.abs(l[1]))+'</b>';
      L.appendChild(d); return d;
    });
    var nt = document.createElement('div'); nt.className = 'ct-ln is-net'; nt.innerHTML = '<span>خالص</span><b class="num">۰</b>'; L.appendChild(nt);
    for (var j = 0; j < els.length; j++) { els[j].classList.add('is-on'); await sleep(380); }
    nt.classList.add('is-on'); await count(nt.querySelector('b'), s.net, 600);
    await sleep(600); el('ctSst').className = 'ct-chip is-ok'; el('ctSst').textContent = 'تأییدشده';
    s.vc.forEach(function(v){ var d = document.createElement('div'); d.className = 'l'; d.innerHTML = '<span>'+v[0]+'</span><span>'+v[1]+'</span>'; el('ctVc').appendChild(d); });
    var n = document.createElement('div'); n.className = 'note'; n.textContent = s.note; el('ctVc').appendChild(n);
    await sleep(3200);
  }
  async function play3(){ if (!el('ct3')) return; await show3('emp', 0); await show3('sub', 1); }
  onVisible(el('ct3'), function(){ loop(play3); });

  async function play4(){
    if (!el('ct4')) return;
    ['ctA1','ctA2','ctA3'].forEach(function(k){ el(k).textContent = ''; el(k).classList.remove('is-req'); });
    el('ctE1').textContent = ''; el('ctE2').textContent = ''; el('ctT4').classList.remove('is-show');
    count(el('ctR1'), 540, 900); count(el('ctR2'), 540, 900); count(el('ctR3'), 216, 900); await count(el('ctR4'), 216, 900);
    await sleep(900);
    await type(el('ctA1'), fa(540)); await press(el('ctAb1')); await count(el('ctR1'), 0, 700, 540);
    el('ctT4').textContent = 'حسن انجام کار پس از تحویل آزاد شد'; el('ctT4').classList.add('is-show'); await sleep(1600); el('ctT4').classList.remove('is-show');
    await type(el('ctA2'), fa(540)); await sleep(300); await press(el('ctAb2'));
    el('ctA3').classList.add('is-req'); el('ctE2').textContent = 'برای آزادسازی بیمه شمارهٔ مفاصاحساب لازم است.';
    await sleep(1500); el('ctA3').classList.remove('is-req'); el('ctE2').textContent = ''; await type(el('ctA3'), '۱۴۰۵-۸۷۲۱۴');
    await press(el('ctAb2')); await count(el('ctR2'), 0, 700, 540);
    el('ctT4').textContent = 'بیمه با شمارهٔ مفاصاحساب آزاد شد'; el('ctT4').classList.add('is-show');
    await sleep(3500);
  }
  onVisible(el('ct4'), function(){ loop(play4); });

  var B = [['مصالح',3200,2140],['دستمزد',1800,1010],['ماشین‌آلات',900,980],['پیمانکار جزء',1500,480],['سربار',600,390]];
  var MX = 3200 * 1.15;
  (function(){
    var bud = el('ctBud'); if (!bud || bud.childElementCount) return;
    B.forEach(function(b){
      var d = document.createElement('div'); d.className = 'ct-bl';
      d.innerHTML = '<div class="tp"><span>'+b[0]+'</span><span class="muted"><span class="num v">۰</span> از <span class="num">'+fa(b[1])+'</span> · <span class="num pc">۰٪</span></span></div><div class="track"><div class="fill"></div><span class="mark" style="inset-inline-start:'+(b[1]/MX*100)+'%"></span></div>';
      bud.appendChild(d);
    });
  })();
  async function play5(){
    var bud = el('ctBud'); if (!bud) return;
    var bls = bud.querySelectorAll('.ct-bl');
    bls.forEach(function(d){ var f = d.querySelector('.fill'); f.style.transition = 'none'; f.style.width = '0'; f.classList.remove('is-over'); void f.offsetWidth; f.style.transition = ''; d.querySelector('.pc').style.color = ''; });
    await sleep(400);
    bls.forEach(function(d, i){
      var b = B[i], p = Math.round(b[2] / b[1] * 100), f = d.querySelector('.fill');
      f.style.width = (b[2] / MX * 100)+'%'; if (p > 100) f.classList.add('is-over');
      count(d.querySelector('.v'), b[2], 1300);
      var pc = d.querySelector('.pc');
      count(pc, p, 1300).then(function(){ pc.textContent = faN(p)+'٪'; if (p > 100) pc.style.color = '#ff5d6c'; });
    });
    await sleep(5500);
  }
  onVisible(el('ct5'), function(){ loop(play5); });

  async function play6(){
    if (!el('ct6')) return;
    el('ctX3').textContent = '۰'; el('ctX4').textContent = '۰٪';
    var gi = el('ctGi'); gi.style.transition = 'none'; gi.style.width = '0'; void gi.offsetWidth; gi.style.transition = '';
    el('ctDlg').classList.remove('is-show'); el('ctT6').classList.remove('is-show');
    await sleep(500); count(el('ctX3'), 4, 1200); gi.style.width = '50%'; await count(el('ctX4'), 50, 1200, 0, '٪');
    await sleep(700); el('ctDlg').classList.add('is-show');
    await sleep(2200); await press(el('ctYes'));
    el('ctT6').textContent = 'سند درآمد این دوره ثبت شد · ۴۰۰ میلیون ریال دارایی قرارداد'; el('ctT6').classList.add('is-show');
    await sleep(3800);
  }
  onVisible(el('ct6'), function(){ loop(play6); });
})();
</script>
