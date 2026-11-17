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
  async function press(node){ if (!node) return; node.classList.add('is-hit'); await sleep(320); node.classList.remove('is-hit'); }

  var A = [['6260000111222','شیر کم‌چرب ۱ لیتری','۲',196000],['2100345004850','پنیر لیقوان','۰٫۴۸۵ کیلو',388000,1],['6260000333444','نان تست','۱',72000]];
  var B = [['6260000555666','آب معدنی ۱٫۵ لیتری','۶',150000],['2100789012000','موز','۱٫۲۰۰ کیلو',204000,1]];
  function rowHtml(it){ return '<td>'+it[1]+(it[4] ? '<span class="sm-wt">بارکد ترازو</span>' : '')+'</td><td class="n num">'+it[2]+'</td><td class="n num">'+fa(it[3])+'</td>'; }
  async function scan(list){
    var tb = el('smCart'), sum = 0;
    for (var i = 0; i < list.length; i++) {
      var it = list[i];
      el('smBc').textContent = it[0]; el('smScan').classList.add('is-beep'); await sleep(300); el('smScan').classList.remove('is-beep');
      var tr = document.createElement('tr'); tr.innerHTML = rowHtml(it); tb.appendChild(tr);
      var o = sum; sum += it[3]; count(el('smTot'), sum, 400, o); await sleep(750);
    }
    return sum;
  }
  function clearCart(){ el('smCart').innerHTML = ''; el('smTot').textContent = '۰'; el('smCash').textContent = '۰'; el('smChg').textContent = '۰'; el('smBc').textContent = '—'; }
  async function play1(){
    if (!el('sm1')) return;
    el('smSusp').innerHTML = ''; el('smT1').classList.remove('is-show'); clearCart();
    var s1 = await scan(A);
    await sleep(500); await press(el('smF8'));
    var sb = document.createElement('span'); sb.className = 'sm-sb'; sb.textContent = 'معلق ۱ — ۳ قلم — '+fa(s1); el('smSusp').appendChild(sb); clearCart();
    await sleep(700);
    var s2 = await scan(B);
    await sleep(300); await count(el('smCash'), 500000, 500); count(el('smChg'), 500000 - s2, 500);
    await sleep(900); await press(el('smF9'));
    el('smT1').textContent = 'رسید ۱۲۰۴۸ ثبت شد · باقی پول '+fa(500000 - s2)+' ریال'; el('smT1').classList.add('is-show');
    await sleep(1600); el('smT1').classList.remove('is-show'); clearCart();
    await sleep(500); await press(el('smF7'));
    el('smSusp').innerHTML = '';
    A.forEach(function(it){ var tr = document.createElement('tr'); tr.innerHTML = rowHtml(it); el('smCart').appendChild(tr); });
    el('smTot').textContent = fa(s1);
    await sleep(3200);
  }
  onVisible(el('sm1'), function(){ loop(play1); });

  function rc(no, tm, amt, st){
    var d = document.createElement('div'); d.className = 'sm-rc';
    d.innerHTML = '<span class="no num">'+no+'</span><span class="num muted">'+tm+'</span><span class="num hide-m">'+fa(amt)+' ریال</span><span class="s">'+st+'</span>';
    el('smRcs').appendChild(d); return d;
  }
  async function play2(){
    if (!el('sm2')) return;
    el('smRcs').innerHTML = ''; var ban = el('smBan'); ban.className = 'sm-banner is-on'; el('smBant').textContent = 'متصل به سرور'; el('smRt').textContent = '';
    rc('فاکتور ۸۴۱۹','۱۸:۰۲',412000,'<span class="sm-chip is-ok">ثبت شد</span>'); await sleep(900);
    ban.className = 'sm-banner is-off'; el('smBant').textContent = 'بدون اتصال به سرور — فروش ادامه دارد';
    var offs = [], data = [['آفلاین ۱','۱۸:۰۴',268000],['آفلاین ۲','۱۸:۰۶',1135000],['آفلاین ۳','۱۸:۰۷',94000]];
    for (var i = 0; i < data.length; i++) {
      offs.push(rc(data[i][0], data[i][1], data[i][2], '<span class="sm-chip is-wait">ذخیره روی همین صندوق</span>'));
      for (var s = 30; s > 0; s -= 10) { el('smRt').textContent = 'تلاش دوباره تا '+faN(s)+' ثانیه'; await sleep(260); }
    }
    el('smRt').textContent = 'در حال اتصال…'; await sleep(900);
    ban.className = 'sm-banner is-on'; el('smBant').textContent = 'اتصال برگشت — ارسال رسیدهای بی‌اتصال'; el('smRt').textContent = '';
    var real = ['فاکتور ۸۴۲۰','فاکتور ۸۴۲۱',null];
    for (var j = 0; j < offs.length; j++) {
      var o = offs[j]; o.querySelector('.s').innerHTML = '<span class="sm-chip is-wait">در حال ثبت</span>'; await sleep(700);
      if (real[j]) { o.querySelector('.no').textContent = real[j]; o.querySelector('.s').innerHTML = '<span class="sm-chip is-ok">ثبت شد · همان ساعت فروش</span>'; }
      else { o.classList.add('is-rev'); o.querySelector('.s').innerHTML = '<span class="sm-chip is-bad">نیاز به بررسی · قیمت عوض شده</span>'; }
    }
    await sleep(4000);
  }
  onVisible(el('sm2'), function(){ loop(play2); });

  var M = [['شیر کم‌چرب ۱ لیتری',98000,2],['پنیر لیقوان ۰٫۴۸۵ کیلو',388000,1],['نان تست',72000,1]];
  function pad(a, b){ return a + ' '.repeat(Math.max(1, 20 - a.length - b.length)) + b; }
  async function play3(){
    if (!el('sm3')) return;
    el('smMw').textContent = 'به فروشگاه نمونه خوش آمدید'; el('smMi').textContent = '—'; el('smMp').textContent = ''; el('smMd').textContent = ''; el('smMc').textContent = ''; el('smMs').textContent = '۰';
    el('smV1').textContent = '  WELCOME - SALAM'; el('smV2').textContent = pad('TOTAL','0'); el('smDbox').classList.remove('is-open'); el('smDlab').textContent = 'بسته';
    await sleep(1200); var sum = 0;
    for (var i = 0; i < M.length; i++) {
      var m = M[i], line = m[1] * m[2], o = sum; sum += line;
      el('smMi').textContent = m[0]; el('smMp').textContent = (m[2] > 1 ? faN(m[2])+' × ' : '')+fa(m[1])+' ریال';
      count(el('smMs'), sum, 450, o);
      el('smV1').textContent = pad('ITEM '+(i+1), String(line)); el('smV2').textContent = pad('TOTAL', String(sum));
      await sleep(1300);
    }
    var d = Math.round(sum * 0.05 / 1000) * 1000;
    el('smMd').textContent = 'تخفیف رسید: '+fa(d)+' ریال'; await count(el('smMs'), sum - d, 500, sum); el('smV2').textContent = pad('TOTAL', String(sum - d));
    await sleep(1000);
    var paid = 700000, ch = paid - (sum - d);
    el('smMc').textContent = 'پرداخت نقد '+fa(paid)+' · باقی پول '+fa(ch);
    el('smV1').textContent = pad('CASH', String(paid)); el('smV2').textContent = pad('CHANGE', String(ch));
    await sleep(400); el('smDbox').classList.add('is-open'); el('smDlab').textContent = 'بعد از پرداخت نقد خودکار باز شد';
    await sleep(2400); el('smDbox').classList.remove('is-open'); el('smDlab').textContent = 'بسته · باز کردن دستی (F11) در رویدادهای ممیزی ثبت می‌شود';
    await sleep(2600);
  }
  onVisible(el('sm3'), function(){ loop(play3); });

  async function play4(){
    var root = el('sm4'); if (!root) return;
    var steps = root.querySelectorAll('.sm-s4');
    steps.forEach(function(e){ e.classList.remove('is-on'); }); el('smAcct').classList.remove('is-show');
    el('smPt').textContent = '۰'; el('smPtv').textContent = 'ارزش: ۰ ریال';
    var pg = el('smPg'); pg.style.transition = 'none'; pg.style.width = '58%'; void pg.offsetWidth; pg.style.transition = '';
    await sleep(600); steps[0].classList.add('is-on'); await count(el('smPt'), 1240, 800); el('smPtv').textContent = 'ارزش: '+fa(124000)+' ریال';
    await sleep(800); steps[1].classList.add('is-on'); await sleep(1000);
    steps[2].classList.add('is-on'); await count(el('smPt'), 940, 700, 1240); el('smPtv').textContent = 'ارزش: '+fa(94000)+' ریال';
    await sleep(900); steps[3].classList.add('is-on'); await count(el('smPt'), 1123, 700, 940); el('smPtv').textContent = 'ارزش: '+fa(112300)+' ریال'; pg.style.width = '66%';
    await sleep(700); el('smAcct').classList.add('is-show');
    await sleep(4200);
  }
  onVisible(el('sm4'), function(){ loop(play4); });

  var H = [8,9,10,11,12,13,14,15,16,17,18,19,20,21,22];
  var S = [[4,9,14,18,22,16,10,9,13,24,38,41,33,21,8],[3,6,9,11,13,9,6,5,8,14,22,24,19,12,5],[1,3,5,7,9,7,4,4,5,10,16,17,14,9,3]];
  var R = [[96,23500000],[57,24100000],[41,22900000]];
  (function buildChart(){
    var chart = el('smChart'), hrs = el('smHrs'); if (!chart || chart.childElementCount) return;
    H.forEach(function(h){
      var c = document.createElement('div'); c.className = 'sm-col'; c.innerHTML = '<span class="v num"></span><i></i>'; chart.appendChild(c);
      var s = document.createElement('span'); s.textContent = faN(h); hrs.appendChild(s);
    });
  })();
  async function play5(){
    var root = el('sm5'); if (!root) return;
    var sts = root.querySelectorAll('.sm-st span'), cols = root.querySelectorAll('.sm-col'), pks = root.querySelectorAll('.sm-pk');
    for (var k = 0; k < 3; k++) {
      sts.forEach(function(e, j){ e.classList.toggle('is-on', j === k); });
      pks.forEach(function(p){ p.classList.remove('is-show'); });
      var data = S[k], mx = Math.max.apply(null, data), pi = data.indexOf(mx), tot = data.reduce(function(a, b){ return a + b; }, 0);
      cols.forEach(function(c, i){ c.classList.remove('is-peak'); c.querySelector('.v').textContent = faN(data[i]); c.querySelector('i').style.height = (data[i] / 41 * 88)+'%'; });
      await sleep(1000); cols[pi].classList.add('is-peak');
      el('smPkh').textContent = faN(H[pi])+' تا '+faN(H[pi]+1);
      el('smPkr').textContent = faN(R[k][0])+' رسید';
      el('smPka').textContent = fa(Math.round(data[pi] * 1000000 / R[k][0] / 1000) * 1000)+' ریال';
      el('smPks').textContent = faN(Math.round(data[pi] / tot * 100))+'٪';
      pks.forEach(function(p){ p.classList.add('is-show'); });
      await sleep(2800);
    }
  }
  onVisible(el('sm5'), function(){ loop(play5); });
})();
</script>
