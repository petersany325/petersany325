<script>
(function () {
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function $(id) { return document.getElementById(id); }
  function sleep(ms) { return new Promise(function (r) { setTimeout(r, reduce ? 0 : ms); }); }
  function faN(s) { return String(s).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
  function onVisible(el, fn) {
    if (!el) return;
    if (reduce || !('IntersectionObserver' in window)) { fn(); return; }
    var io = new IntersectionObserver(function (es) { if (es[0].isIntersecting) { io.disconnect(); fn(); } }, { threshold: 0.3 });
    io.observe(el);
  }
  async function loop(fn) { do { await fn(); } while (!reduce); }
  async function type(el, t, ms) { if (!el) return; for (var k = 1; k <= t.length; k++) { el.textContent = t.slice(0, k); await sleep(ms || 35); } }
  function add(box, cls, html) { var d = document.createElement('div'); d.className = cls; d.innerHTML = html; box.appendChild(d); return d; }

  async function p1() {
    var c = $('aiC1'); if (!c) return;
    c.innerHTML = ''; await sleep(500);
    var q = add(c, 'm me', '<span class="wave"><i></i><i></i><i></i><i></i></span>در حال شنیدن…');
    await sleep(1300); q.textContent = 'فروش این ماه چقدر بود؟';
    var a = add(c, 'm ai', '<span class="dots"><i></i><i></i><i></i></span>'); await sleep(900);
    a.innerHTML = 'فروش آبان تا امروز <b class="num">۱٬۸۴۲ میلیون ریال</b>؛ ۱۲٪ بیشتر از مهر.<div class="mini"><i></i><i></i><i></i><i></i><i></i><i class="last"></i></div><div class="speak">🔊 با صدای فارسی خوانده شد</div>';
    var hs = [38, 52, 47, 61, 70, 86];
    a.querySelectorAll('.mini i').forEach(function (b, i) { setTimeout(function () { b.style.height = hs[i] + '%'; }, reduce ? 0 : 60 + i * 90); });
    await sleep(2200); add(c, 'm me', 'ماندهٔ آقای احمدی چند است؟');
    a = add(c, 'm ai', '<span class="dots"><i></i><i></i><i></i></span>'); await sleep(900);
    a.innerHTML = 'آقای احمدی <b class="num">۴۸٬۵۰۰٬۰۰۰</b> ریال بدهکار است؛ آخرین فاکتور ۱۲ آبان.';
    await sleep(3600);
  }
  onVisible($('ai1'), function () { loop(p1); });

  var Q2 = [['فروش این هفته را با هفتهٔ قبل مقایسه کن', 2], ['کدام کالاها رو به اتمام‌اند؟', 3], ['سود و زیان این ماه را خلاصه کن', 1], ['۵ میلیون از علی رضایی نقد دریافت کردم', 0]];
  async function p2() {
    var box = $('aiAg'); if (!box) return;
    var ag = box.children;
    for (var i = 0; i < Q2.length; i++) {
      [].forEach.call(ag, function (x) { x.classList.remove('on'); });
      if ($('aiQ2')) $('aiQ2').textContent = '';
      await type($('aiQ2'), Q2[i][0], 32); await sleep(500); ag[Q2[i][1]].classList.add('on'); await sleep(2300);
    }
  }
  onVisible($('ai2'), function () { loop(p2); });

  async function p3() {
    var d3 = $('aiD3'); if (!d3) return;
    var rows = d3.querySelectorAll('.row');
    rows.forEach(function (r) { r.classList.remove('on'); });
    if ($('aiSt3')) $('aiSt3').classList.remove('on');
    if ($('aiT3')) $('aiT3').textContent = '';
    await sleep(400);
    await type($('aiT3'), 'امروز یک هارد ۱۲ میلیون تومانی نسیه به فروشگاه یاس فروختم.', 35);
    for (var i = 0; i < rows.length; i++) { await sleep(380); rows[i].classList.add('on'); }
    await sleep(1100);
    if ($('aiOk3')) $('aiOk3').classList.add('press');
    await sleep(220);
    if ($('aiOk3')) $('aiOk3').classList.remove('press');
    if ($('aiSt3')) $('aiSt3').classList.add('on');
    await sleep(3200);
  }
  onVisible($('ai3'), function () { loop(p3); });

  var D4 = [
    ['📷', 'عکس گوشی · فاکتور خرید', [['خوانده شد', 1], ['فروشنده: پخش نمونهٔ البرز', 1], ['۶ کالا تطبیق شد', 1], ['جمع کنترل شد', 1]], 'پیش‌نویس آماده', ''],
    ['📄', 'PDF · فاکتور فروشنده', [['خوانده شد', 1], ['فروشنده: بازرگانی نمونهٔ سپید', 1], ['۱ کالای ناشناس', 0], ['جمع کنترل شد', 1]], 'کالا را انتخاب کنید', 'amber'],
    ['📊', 'Excel · فاکتور فروشنده', [['خوانده شد', 1], ['فروشنده: پخش نمونهٔ البرز', 1], ['تکراری: همان فاکتور عکس گوشی', -1]], 'تکراری؛ ثبت نمی‌شود', 'red']
  ];
  async function p4() {
    var q = $('aiQ4'); if (!q) return;
    q.innerHTML = '';
    for (var i = 0; i < D4.length; i++) {
      var d = D4[i];
      var el = add(q, 'doc', '<div class="ic">' + d[0] + '</div><div><b>' + d[1] + '</b><div class="steps"></div></div><span class="st muted">در حال خواندن…</span>');
      var st = el.querySelector('.steps');
      for (var k = 0; k < d[2].length; k++) {
        await sleep(420);
        var s = document.createElement('span');
        s.textContent = (d[2][k][1] > 0 ? '✓ ' : d[2][k][1] < 0 ? '✕ ' : '! ') + d[2][k][0];
        s.className = d[2][k][1] > 0 ? 'ok' : 'bad';
        st.appendChild(s);
      }
      await sleep(300);
      var t = el.querySelector('.st');
      t.textContent = d[3];
      t.className = 'st';
      t.style.color = d[4] === 'red' ? '#ff5d6c' : d[4] === 'amber' ? '#f5b942' : '#2ecc8f';
      await sleep(500);
    }
    await sleep(3200);
  }
  onVisible($('ai4'), function () { loop(p4); });

  var CK = [['سند نامتوازن', 0], ['فاکتور قطعی بدون سند', 0], ['موجودی منفی', 0], ['فاکتور تکراری', 0], ['دریافت/پرداخت تکراری', 0], ['چک سررسید گذشته', 'چک ۲۱۴۵ از ۳۴ روز پیش در دست مانده؛ اهمیت: مهم'], ['تغییر غیرعادی قیمت خرید', 'روغن ۱ لیتری ۳۶٪ گران‌تر از میانگین خریدهای قبلی'], ['مغایرت بانک و دفتر', 0], ['ویرایش پس از قطعی', 0]];
  var ck = $('aiCk');
  if (ck) CK.forEach(function (c) { add(ck, 'ck', '<span>○</span><span>' + c[0] + '</span><span class="r"></span>' + (c[1] ? '<span class="why">دلیل: ' + c[1] + '</span>' : '')); });
  var FC = [60, 58, 62, 55, 50, 47, 40, 30, 18, 24, 33, 41], LO = [50, 46, 48, 39, 32, 27, 18, 6, -8, -2, 6, 13];
  function Y(v) { return 96 - v * 1.2; }
  function X(i) { return 300 - i * (300 / 11); }
  async function p5() {
    if (!ck) return;
    var rows = ck.children;
    [].forEach.call(rows, function (r) { r.className = 'ck'; r.children[0].textContent = '○'; r.children[2].textContent = ''; });
    var ga = $('aiGa'), gv = $('aiGv'), gl = $('aiGl'), ln = $('aiLn'), band = $('aiBand'), rd = $('aiRd'), fcn = $('aiFcn');
    if (ga) ga.style.strokeDashoffset = '389.6';
    if (gv) gv.textContent = '۰';
    if (gl) gl.textContent = 'امتیاز سلامت';
    if (ln) ln.setAttribute('d', '');
    if (band) band.setAttribute('d', '');
    if (rd) rd.setAttribute('opacity', '0');
    if (fcn) fcn.style.opacity = '0';
    await sleep(400);
    for (var i = 0; i < rows.length; i++) {
      await sleep(260);
      var f = !!CK[i][1];
      rows[i].className = 'ck done' + (f ? ' flag' : '');
      rows[i].children[0].textContent = f ? '⚠' : '✓';
      rows[i].children[0].style.color = f ? '#ff5d6c' : '#2ecc8f';
      rows[i].children[2].textContent = f ? '۱ یافته' : '';
      rows[i].children[2].style.color = '#ff5d6c';
    }
    var score = 72;
    if (ga) ga.style.strokeDashoffset = String(389.6 * (1 - score / 100));
    for (var s = 0; s <= score; s += 4) { if (gv) gv.textContent = faN(s); await sleep(30); }
    if (gv) gv.textContent = faN(score);
    if (gl) gl.textContent = 'قابل قبول';
    for (var k = 1; k <= FC.length; k++) {
      var d = 'M' + FC.slice(0, k).map(function (v, j) { return X(j) + ',' + Y(v); }).join(' L');
      if (ln) ln.setAttribute('d', d);
      var hi = FC.slice(0, k).map(function (v, j) { return X(j) + ',' + Y(v + 10); });
      var lo = LO.slice(0, k).map(function (v, j) { return X(j) + ',' + Y(v); }).reverse();
      if (band) band.setAttribute('d', 'M' + hi.join(' L') + ' L' + lo.join(' L') + 'Z');
      await sleep(140);
    }
    if (rd) { rd.setAttribute('cx', String(X(8))); rd.setAttribute('cy', String(Y(LO[8]))); rd.setAttribute('opacity', '1'); }
    if (fcn) fcn.style.opacity = '1';
    await sleep(4200);
  }
  onVisible($('ai5'), function () { loop(p5); });

  var LG = [['۰۹:۱۲', 'حسابدار', 'بدهکاران بالای ۹۰ روز', '۱٫۲ث'], ['۰۹:۲۰', 'صندوق‌دار', 'فروش امروز', '۰٫۸ث'], ['۰۹:۳۱', 'حسابدار', 'ثبت دریافت ۵ میلیونی ← تأیید شد', '۲٫۱ث'], ['۰۹:۴۰', 'انباردار', 'دستیار برای این نقش بسته است', '—'], ['۰۹:۵۲', 'مدیر', 'خلاصهٔ سود و زیان آبان', '۱٫۶ث']];
  async function p6() {
    var l = $('aiLg'); if (!l) return;
    l.innerHTML = '';
    for (var i = 0; i < LG.length; i++) {
      await sleep(700);
      var r = LG[i];
      add(l, '', '<span class="num muted">' + r[0] + '</span><span class="hide-m">' + r[1] + '</span><span>' + r[2] + '</span><span class="num muted">' + r[3] + '</span>');
    }
    await sleep(3600);
  }
  onVisible($('ai6'), function () { loop(p6); });

  var ED = [
    ['فروشگاهی', ['سود فروش امروز چقدر شد؟', 'کدام کالاها رو به اتمام‌اند؟', 'بدهکاران بالای ۹۰ روز را نشان بده']],
    ['رستوران', ['فروش امروز رستوران چقدر بود؟', 'پرفروش‌ترین غذای این هفته کدام است؟', 'موجودی مواد اولیه را نشان بده']],
    ['پخش', ['فروش این ماه را با ماه قبل مقایسه کن', 'ماندهٔ فروشگاه نمونهٔ یاس چند است؟', 'چک‌های ۳۰ روز آینده را نشان بده']],
    ['تولیدی', ['موجودی مواد اولیه را نشان بده', 'سود و زیان این ماه را خلاصه کن', 'چک‌های ۳۰ روز آینده را نشان بده']],
    ['شرکتی', ['سود و زیان این ماه را خلاصه کن', 'موجودی بانک‌ها چقدر است؟', 'خلاصهٔ حقوق این ماه را بده']]
  ];
  var e7 = $('aiE7');
  if (e7) ED.forEach(function (e) { var s = document.createElement('span'); s.textContent = e[0]; e7.appendChild(s); });
  async function p7() {
    if (!e7) return;
    for (var i = 0; i < ED.length; i++) {
      [].forEach.call(e7.children, function (s, j) { s.classList.toggle('on', j === i); });
      var q = $('aiQs7'); if (!q) return;
      q.innerHTML = '';
      for (var k = 0; k < 3; k++) { var d = add(q, '', ED[i][1][k]); d.style.animationDelay = (k * 120) + 'ms'; }
      await sleep(2800);
    }
  }
  onVisible($('ai7'), function () { loop(p7); });
})();
</script>
