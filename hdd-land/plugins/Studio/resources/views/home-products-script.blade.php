<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function $(id) { return document.getElementById(id); }
  function sleep(ms) { return new Promise(function (r) { setTimeout(r, reduce ? 0 : ms); }); }
  function faN(s) { return String(s).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
  function fa(n) { return faN(Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '٬')); }
  function count(el, to, ms) {
    if (!el) return;
    if (reduce) { el.textContent = fa(to); return; }
    var t0 = performance.now();
    (function f(t) {
      var p = Math.min(1, (t - t0) / ms);
      el.textContent = fa(to * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(f);
    })(t0);
  }
  async function loop(fn) { do { await fn(); } while (!reduce); }

  var ED = [
    ['فروشگاهی', ['پیشخوان', 'فروش', 'خرید', 'انبار', 'چک'], [84, 62, 310], [30, 44, 38, 52, 47, 60, 66, 82]],
    ['رستوران', ['پیشخوان', 'سالن', 'سفارش', 'آشپزخانه', 'منو'], [46, 8, 95], [40, 36, 52, 48, 58, 62, 55, 74]],
    ['پخش', ['پیشخوان', 'کارتابل', 'پخش', 'نقشه', 'طرح‌ها'], [212, 148, 920], [50, 58, 46, 64, 70, 61, 78, 90]],
    ['تولیدی', ['پیشخوان', 'فرمول', 'تولید', 'بهای تمام‌شده', 'باسکول'], [130, 96, 640], [35, 42, 50, 45, 57, 63, 70, 77]]
  ];
  var eds = $('hpEds');
  if (eds) ED.forEach(function (e) { var s = document.createElement('span'); s.textContent = e[0]; eds.appendChild(s); });
  async function p1() {
    for (var i = 0; i < ED.length; i++) {
      var e = ED[i];
      if (eds) [].forEach.call(eds.children, function (s, j) { s.classList.toggle('on', j === i); });
      if ($('hpEdn')) $('hpEdn').textContent = e[0];
      if ($('hpSd')) $('hpSd').innerHTML = e[1].map(function (m, k) { return '<i class="' + (k ? '' : 'on') + '">' + m + '</i>'; }).join('');
      count($('hpK1'), e[2][0], 900); count($('hpK2'), e[2][1], 900); count($('hpK3'), e[2][2], 900);
      var bars = $('hpB1');
      if (bars) [].forEach.call(bars.children, function (b, k) {
        b.style.height = '5%';
        setTimeout(function () { b.style.height = e[3][k] + '%'; }, reduce ? 0 : 80 + k * 70);
      });
      await sleep(2800);
    }
  }
  var QA = [['فروش این ماه چقدر بود؟', '۱٬۸۴۲ میلیون ریال؛ ۱۲٪ بیشتر از مهر.'], ['ماندهٔ آقای احمدی؟', '۴۸٬۵۰۰٬۰۰۰ ریال بدهکار است.'], ['چک‌های این هفته؟', '۳ چک، جمع ۲۱۰ میلیون ریال.']];
  async function p2() {
    var ms = $('hpMs'); if (!ms) return;
    ms.innerHTML = '';
    for (var i = 0; i < QA.length; i++) {
      if (ms.children.length >= 4) { ms.removeChild(ms.firstChild); ms.removeChild(ms.firstChild); }
      var q = document.createElement('div'); q.className = 'hp-m me';
      q.innerHTML = '<span class="hp-wave"><i></i><i></i><i></i></span>…';
      ms.appendChild(q);
      await sleep(900); q.textContent = QA[i][0];
      await sleep(600);
      var a = document.createElement('div'); a.className = 'hp-m bot'; a.textContent = QA[i][1]; ms.appendChild(a);
      await sleep(1800);
    }
    await sleep(1200);
  }
  var IT = [['ماست ۹۰۰ گرمی × ۱۲', 18000000], ['پنیر ۴۰۰ گرمی × ۶', 9600000], ['🎁 اشانتیون', '۱ کارتن']];
  async function p3() {
    var box = $('hpItems'); if (!box) return;
    box.innerHTML = '';
    if ($('hpPs')) $('hpPs').textContent = '۰';
    if ($('hpSend')) { $('hpSend').className = 'hp-send'; $('hpSend').textContent = 'ثبت سفارش'; }
    if ($('hpOff')) { $('hpOff').textContent = '● بدون اینترنت'; $('hpOff').style.color = '#f5b942'; }
    var sum = 0;
    for (var i = 0; i < IT.length; i++) {
      await sleep(700);
      var d = document.createElement('div'); d.className = 'hp-it';
      d.innerHTML = '<span>' + IT[i][0] + '</span>';
      box.appendChild(d);
      if (typeof IT[i][1] === 'number') { sum += IT[i][1]; count($('hpPs'), sum, 500); }
    }
    await sleep(700);
    if ($('hpSend')) $('hpSend').textContent = 'در صف ارسال';
    await sleep(1200);
    if ($('hpOff')) { $('hpOff').textContent = '● متصل'; $('hpOff').style.color = '#2ecc8f'; }
    if ($('hpSend')) { $('hpSend').className = 'hp-send ok'; $('hpSend').textContent = '✓ به دفتر رسید'; }
    await sleep(2600);
  }
  async function p4() {
    var fl = $('hpFl'); if (!fl) return;
    if ($('hpSms')) $('hpSms').classList.remove('on');
    [].forEach.call(fl.children, function (s) { s.className = ''; });
    for (var i = 0; i < fl.children.length; i++) {
      await sleep(900);
      [].forEach.call(fl.children, function (s, j) { s.className = j < i ? 'done' : j === i ? 'now' : ''; });
      if (i === 2 && $('hpSms')) $('hpSms').classList.add('on');
    }
    await sleep(2400);
  }
  function start() { loop(p1); loop(p2); loop(p3); loop(p4); }
  var root = $('hpProducts');
  if (!root) return;
  if (reduce || !('IntersectionObserver' in window)) start();
  else {
    var io = new IntersectionObserver(function (es) { if (es[0].isIntersecting) { io.disconnect(); start(); } }, { threshold: 0.25 });
    io.observe(root);
  }
})();
</script>
