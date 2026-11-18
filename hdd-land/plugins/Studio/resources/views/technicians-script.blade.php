<script>
(function () {
  if (!document.getElementById('tnD1')) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function $(id) { return document.getElementById(id); }
  function sleep(ms) { return new Promise(function (r) { setTimeout(r, reduce ? 0 : ms); }); }
  function faN(s) { return String(s).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
  function fa(n) { return faN(Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '٬')); }
  function count(el, to, ms) {
    if (!el) return;
    if (reduce) { el.textContent = fa(to); return; }
    var t0 = performance.now();
    (function f(t) { var p = Math.min(1, (t - t0) / ms); el.textContent = fa(to * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(f); })(t0);
  }
  async function type(el, s, ms) {
    if (!el) return;
    el.textContent = '';
    if (reduce) { el.textContent = s; return; }
    for (var i = 0; i < s.length; i++) { el.textContent += s[i]; await sleep(ms || 45); }
  }
  function onVisible(el, fn) {
    if (!el) return;
    if (reduce || !('IntersectionObserver' in window)) { fn(); return; }
    var io = new IntersectionObserver(function (es) { if (es[0].isIntersecting) { io.disconnect(); fn(); } }, { threshold: 0.25 });
    io.observe(el);
  }
  async function loop(fn) { do { await fn(); } while (!reduce); }
  var CH = { open: 'پذیرش‌شده', fix: 'در حال تعمیر', wait: 'منتظر قطعه', ready: 'آماده تحویل' };
  var R1 = [['۱۰۸۴','نمونه‌پور','گوشی','open'],['۱۰۸۳','شرکت آلفا','لپ‌تاپ','fix'],['۱۰۸۱','کریمی','هارد اکسترنال','wait'],['۱۰۷۹','نوری','لپ‌تاپ','ready'],['۱۰۷۶','احمدی','تبلت','ready']];
  async function d1() {
    var tb = $('tnT1'); if (!tb) return;
    tb.innerHTML = '';
    count($('tnK1'), 37, 1100); count($('tnK2'), 9, 1100); count($('tnK3'), 48600000, 1300); count($('tnK4'), 3, 1100);
    for (var i = 0; i < R1.length; i++) {
      await sleep(420);
      var r = R1[i], tr = document.createElement('tr');
      tr.innerHTML = '<td class="num">' + r[0] + '</td><td>' + r[1] + '</td><td>' + r[2] + '</td><td>' + CH[r[3]] + '</td>';
      tb.appendChild(tr);
    }
    await sleep(4000);
  }
  async function d2() {
    ['tnMob','tnDev','tnBrand','tnTech','tnFault','tnAcc','tnDep','tnType'].forEach(function (id) { if ($(id)) $(id).textContent = ''; });
    if ($('tnCust')) $('tnCust').classList.remove('on');
    if ($('tnSug')) $('tnSug').classList.remove('on');
    if ($('tnRange')) $('tnRange').classList.remove('on');
    if ($('tnDone')) $('tnDone').classList.remove('on');
    await sleep(500); await type($('tnMob'), '0912 000 0000', 60); await sleep(300);
    if ($('tnCust')) $('tnCust').classList.add('on');
    await sleep(700);
    await type($('tnDev'), 'گوشی'); await type($('tnBrand'), 'نمونه‌تل A50'); await type($('tnTech'), 'آقای رحیمی');
    await type($('tnFault'), 'شارژ نمی‌شود، با شارژر هم روشن نمی‌شود', 38);
    await sleep(400);
    if ($('tnSug')) $('tnSug').classList.add('on');
    if ($('tnRange')) $('tnRange').classList.add('on');
    await sleep(1300); await type($('tnType'), 'خرابی آی‌سی شارژ', 30);
    await type($('tnAcc'), 'قاب، سیم‌کارت'); await type($('tnDep'), '۱٬۰۰۰٬۰۰۰');
    await sleep(500); if ($('tnDone')) $('tnDone').classList.add('on'); await sleep(4200);
  }
  var DEV = [['۱۰۸۴','گوشی نمونه‌تل'],['۱۰۸۳','لپ‌تاپ'],['۱۰۸۱','هارد اکسترنال']];
  async function d3() {
    var L = [$('tnL0'), $('tnL1'), $('tnL2')], t = $('tnHt');
    if (!L[0] || !t) return;
    t.className = 'tn-toast';
    L.forEach(function (l) { [].slice.call(l.querySelectorAll('.tn-dev')).forEach(function (d) { d.remove(); }); });
    var els = DEV.map(function (d) {
      var e = document.createElement('div'); e.className = 'tn-dev';
      e.innerHTML = '<b>قبض ' + d[0] + '</b>' + d[1] + ' · <span class="st">' + CH.open + '</span>';
      L[0].appendChild(e); return e;
    });
    async function send(e, to, who) {
      await sleep(900); e.classList.add('move'); e.querySelector('.st').textContent = 'در انتظار تأیید دریافت';
      t.textContent = 'اعلان برای ' + who + ': «ارجاع دستگاه برای دریافت»'; t.classList.add('on');
      await sleep(1100); L[to].appendChild(e); await sleep(900); e.classList.remove('move');
      e.querySelector('.st').textContent = CH.fix + ' · دست ' + who;
      t.textContent = who + ' «تأیید دریافت» زد؛ محل دستگاه روی قبض عوض شد.';
    }
    await send(els[0], 1, 'آقای رحیمی'); await send(els[1], 2, 'آقای کاظمی');
    await sleep(1400); t.className = 'tn-toast warn on';
    t.textContent = 'بازگشت قبض ۱۰۸۴ به پذیرش: «قبل از بازگشت به پذیرش، گزارش کار این قبض را ثبت کنید.»';
    await sleep(2200); t.className = 'tn-toast on';
    t.textContent = 'گزارش کار ثبت شد: «آی‌سی شارژ تعویض شد» · وضعیت نتیجه: آماده تحویل';
    await sleep(900); L[0].appendChild(els[0]); els[0].querySelector('.st').textContent = CH.ready;
    await sleep(3200);
  }
  async function d4() {
    ['tnA1','tnA2','tnA3','tnA4'].forEach(function (id) { if ($(id)) $(id).textContent = '—'; });
    if ($('tnCode')) $('tnCode').textContent = '';
    if ($('tnOk')) $('tnOk').classList.remove('on');
    if ($('tnSms')) $('tnSms').classList.remove('on');
    await sleep(500); count($('tnA1'), 2200000, 600); await sleep(350); count($('tnA2'), 3400000, 600); await sleep(350); count($('tnA3'), 200000, 600);
    await sleep(700); count($('tnA4'), 5400000, 800); await sleep(1000);
    if ($('tnSend')) $('tnSend').classList.add('press'); await sleep(220); if ($('tnSend')) $('tnSend').classList.remove('press');
    await sleep(700); if ($('tnSms')) $('tnSms').classList.add('on'); await sleep(2200);
    await type($('tnCode'), '4827', 220); await sleep(400); if ($('tnOk')) $('tnOk').classList.add('on'); await sleep(4200);
  }
  var R5 = [['۱۰۷۹','لپ‌تاپ',3200000,1800000,1000000],['۱۰۷۶','تبلت',0,1500000,500000],['۱۰۷۴','گوشی',2100000,900000,0]];
  async function d5() {
    var tb = $('tnT5'); if (!tb) return;
    tb.innerHTML = ''; if ($('tnSum')) $('tnSum').textContent = '۰';
    R5.forEach(function (r) {
      var tr = document.createElement('tr');
      tr.innerHTML = '<td class="num">' + r[0] + '</td><td>' + r[1] + '</td><td class="num hide-m">' + fa(r[2]) + '</td><td class="num hide-m">' + fa(r[3]) + '</td><td class="num hide-m">' + fa(r[4]) + '</td><td class="num">' + fa(r[2] + r[3] - r[4]) + '</td><td>—</td>';
      tb.appendChild(tr);
    });
    var rows = tb.children, sum = 0;
    for (var i = 0; i < rows.length; i++) { await sleep(700); rows[i].classList.add('sel'); }
    await sleep(800); if ($('tnDl')) $('tnDl').classList.add('press'); await sleep(220); if ($('tnDl')) $('tnDl').classList.remove('press');
    for (var j = 0; j < rows.length; j++) {
      await sleep(550); var r = R5[j];
      rows[j].lastChild.innerHTML = '<b>ف-' + faN(5210 + j) + '</b>';
      sum += r[2] + r[3] - r[4]; count($('tnSum'), sum, 400);
    }
    await sleep(4200);
  }
  var QA = [
    ['کدام دستگاه‌ها آماده تحویل‌اند؟', '۹ دستگاه آماده تحویل است. قدیمی‌ترین: قبض ۹۸۷ (لپ‌تاپ)، ۴۱ روز.'],
    ['کدام تأیید هزینه معطل مانده؟', 'قبض ۱۰۵۲، مبلغ ۵٬۴۰۰٬۰۰۰ ریال؛ ۶ روز در انتظار تأیید.'],
    ['اجرت تعویض آی‌سی شارژ معمولاً چقدر بوده؟', 'در ۱۲ تعمیر مشابه همین تعمیرگاه، بین ۱٬۸۰۰٬۰۰۰ تا ۲٬۶۰۰٬۰۰۰ ریال.']
  ];
  async function d6() {
    var c = $('tnChat'); if (!c) return;
    c.innerHTML = '';
    ['tnF1','tnF2','tnF3'].forEach(function (id) { if ($(id)) $(id).classList.remove('on'); });
    for (var i = 0; i < QA.length; i++) {
      await sleep(700);
      var q = document.createElement('div'); q.className = 'tn-me'; q.textContent = QA[i][0]; c.appendChild(q);
      await sleep(900);
      var a = document.createElement('div'); a.className = 'tn-ai'; a.textContent = QA[i][1]; c.appendChild(a);
      var f = $('tnF' + (i + 1)); if (f) f.classList.add('on');
      await sleep(1500);
    }
    await sleep(3500);
  }
  [['tnD1', d1], ['tnD2', d2], ['tnD3', d3], ['tnD4', d4], ['tnD5', d5], ['tnD6', d6]].forEach(function (x) { onVisible($(x[0]), function () { loop(x[1]); }); });
})();
</script>
