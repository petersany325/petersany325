<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function sleep(ms){return new Promise(function(r){setTimeout(r, reduce ? 0 : ms)})}
  function el(id){return document.getElementById(id)}
  function onVisible(node, fn){
    if (!node) return;
    if (reduce || !('IntersectionObserver' in window)) { fn(); return; }
    var io = new IntersectionObserver(function(es){ if (es[0].isIntersecting) { io.disconnect(); fn(); } }, {threshold: 0.3});
    io.observe(node);
  }
  async function loop(fn){ do { await fn(); } while (!reduce); }
  var ED = [
    {n:'فروشگاهی', m:[['🧾','فروش'],['🛒','خرید'],['📦','انبار و کالا'],['🏦','چک و اقساط'],['🏷️','تخفیف و مشترکین'],['📈','گزارش سود']],
     t:'فاکتور فروش ۴۸۲۱', note:'سود ۷٬۸۰۵٬۰۰۰', l:[['۱ × اتو بخار','۱۲٬۴۰۰٬۰۰۰'],['۲ × چای‌ساز رومیزی','۱۷٬۲۰۰٬۰۰۰'],['۳ × سشوار حرفه‌ای','۱۸٬۳۰۰٬۰۰۰'],['تخفیف ۵٪','−۲٬۳۹۵٬۰۰۰']], s:'۴۵٬۵۰۵٬۰۰۰'},
    {n:'رستوران', m:[['🪑','نمای سالن و میزها'],['👆','ثبت سفارش'],['🍳','صف آشپزخانه'],['📖','منو و دستور پخت'],['🛵','صندوق و دلیوری'],['📊','گزارش‌های رستوران']],
     t:'سفارش ۳۱۲ · میز ۷', l:[['۲ × چلوکباب کوبیده','۲٬۹۰۰٬۰۰۰'],['۴ × دوغ','۴۴۰٬۰۰۰'],['حق سرویس','۳۳۴٬۰۰۰']], s:'۳٬۶۷۴٬۰۰۰'},
    {n:'پخش', m:[['📥','کارتابل سفارش ویزیتورها'],['🚚','لیست پخش و ماشین'],['🗺️','نقشه و GPS'],['🎁','طرح‌های فروش'],['🎯','سهمیه و پورسانت']],
     t:'سفارش گوشی ویزیتور', l:[['۱۲ × ماست ۹۰۰ گرمی','۱۸٬۰۰۰٬۰۰۰'],['🎁 اشانتیون پلهٔ ۱','۱ کارتن'],['کنترل سقف اعتبار','✓']], s:'۱۸٬۰۰۰٬۰۰۰'},
    {n:'تولیدی', m:[['🧪','فرمول ساخت'],['🏭','دستور تولید'],['💰','بهای تمام‌شده'],['⚖️','باسکول'],['📦','انبار مواد و محصول']],
     t:'دستور تولید · ماست ۱ کیلویی', l:[['مواد (۱۰۰ کیلو شیر، ۲۰۰ ظرف)','۱۸٬۴۰۰٬۰۰۰'],['دستمزد و سربار','۱۰٬۰۰۰٬۰۰۰'],['بهای هر واحد','۱۴۲٬۰۰۰']], s:'۲۸٬۴۰۰٬۰۰۰'},
    {n:'شرکتی', m:[['📒','حسابداری و دفاتر'],['🏦','خزانه‌داری'],['🛒','بازرگانی'],['👥','حقوق و دستمزد'],['🏢','دارایی ثابت'],['📊','دفتر دوم و بودجه'],['🧾','مالیات و مؤدیان']],
     t:'سند حسابداری ۱۲۴۵', l:[['هزینهٔ حقوق و دستمزد','بدهکار'],['حقوق پرداختنی','بستانکار'],['حق بیمهٔ پرداختنی','بستانکار']], s:'قطعی شد'}
  ];
  var eds = el('hbEds');
  if (eds) {
    ED.forEach(function(e){ var s = document.createElement('span'); s.textContent = e.n; eds.appendChild(s); });
  }
  async function show(i){
    var e = ED[i];
    [].forEach.call(eds.children, function(s, j){ s.classList.toggle('is-on', j === i); });
    var menu = el('hbMenu'); menu.innerHTML = '';
    e.m.forEach(function(x, k){
      var d = document.createElement('div'); d.className = 'hb-mi'; d.style.animationDelay = (k * 60) + 'ms';
      d.innerHTML = '<i>' + x[0] + '</i>' + x[1]; menu.appendChild(d);
    });
    var scr = el('hbScr');
    scr.innerHTML = '<div class="hb-tag">نسخهٔ ' + e.n + (e.note ? ' · ' + e.note : '') + '</div><h5>' + e.t + '</h5>';
    for (var k = 0; k < e.l.length; k++) {
      await sleep(380);
      var line = document.createElement('div'); line.className = 'hb-ln';
      line.innerHTML = '<span>' + e.l[k][0] + '</span><b class="num">' + e.l[k][1] + '</b>';
      scr.appendChild(line);
    }
    await sleep(300);
    var tot = document.createElement('div'); tot.className = 'hb-tot';
    tot.innerHTML = '<span>' + (e.n === 'شرکتی' ? 'وضعیت' : 'جمع') + '</span><span class="num">' + e.s + '</span>';
    scr.appendChild(tot);
    await sleep(2600);
  }
  async function play1(){ for (var i = 0; i < ED.length; i++) await show(i); }
  onVisible(el('hb1'), function(){ loop(play1); });

  function add(cls, html){
    var d = document.createElement('div'); d.className = 'hb-msg ' + cls; d.innerHTML = html;
    el('hbChat').appendChild(d); return d;
  }
  async function botSay(html){
    var d = add('is-bot', '<span class="hb-typing"><i></i><i></i><i></i></span>');
    await sleep(900); d.innerHTML = html; return d;
  }
  async function play2(){
    if (!el('hb2')) return;
    el('hbChat').innerHTML = '';
    document.querySelectorAll('#hb2 .hb-card').forEach(function(c){ c.classList.remove('is-on'); });
    await sleep(500);
    var q = add('is-me', '<span class="hb-mic" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>در حال شنیدن…');
    await sleep(1300); q.textContent = 'فروش این ماه چقدر بود؟';
    var a = await botSay('فروش آبان تا امروز <b class="num">۱٬۸۴۲ میلیون ریال</b> است؛ ۱۲٪ بیشتر از مهر.<div class="hb-bars"><i></i><i></i><i></i><i></i><i></i><i class="is-last"></i></div>');
    var hs = [38, 52, 47, 61, 70, 82];
    a.querySelectorAll('.hb-bars i').forEach(function(b, i){ b.style.height = hs[i] + '%'; });
    await sleep(1800);
    add('is-me', 'ماندهٔ آقای احمدی چند است؟');
    await botSay('ماندهٔ آقای احمدی <b class="num">۴۲٬۸۰۰٬۰۰۰ ریال</b> بدهکار است؛ یک چک <span class="num">۱۵ میلیونی</span> هم سررسید هفتهٔ بعد دارد.');
    var cs = ['hbS1', 'hbS2', 'hbS3', 'hbS4'];
    for (var i = 0; i < cs.length; i++) { await sleep(550); el(cs[i]).classList.add('is-on'); }
    await sleep(3800);
  }
  onVisible(el('hb2'), function(){ loop(play2); });
})();
</script>
