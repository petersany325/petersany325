<script>
(function () {
  if (!document.getElementById('rsDesk')) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function faN(s) { return String(s).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
  function fa(n) { return faN(String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '٬')); }
  function sleep(ms) { return new Promise(function (r) { setTimeout(r, reduce ? 0 : ms); }); }
  function el(id) { return document.getElementById(id); }
  function count(node, to, ms) {
    if (!node) return;
    if (reduce) { node.textContent = fa(to); return; }
    var t0 = performance.now();
    (function f(t) {
      var p = Math.min(1, (t - t0) / ms);
      node.textContent = fa(to * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(f);
    })(t0);
  }
  function onVisible(node, fn) {
    if (!node) return;
    if (reduce || !('IntersectionObserver' in window)) { fn(); return; }
    var io = new IntersectionObserver(function (es) { if (es[0].isIntersecting) { io.disconnect(); fn(); } }, { threshold: 0.3 });
    io.observe(node);
  }
  async function loop(fn) { do { await fn(); } while (!reduce); }

  var MODS = [
    ['📥', 'پذیرش', 'قبض ۱۰۸۴ برای آقای نمونه‌پور ثبت شد'],
    ['🔧', 'ارجاع', 'قبض ۱۰۸۴ روی میز آقای رحیمی است'],
    ['✓', 'تأیید هزینه', 'قبض ۱۰۵۲ هنوز منتظر تأیید مشتری است'],
    ['📦', 'آمادهٔ تحویل', 'قبض ۱۰۷۹ آمادهٔ تحویل شد'],
    ['✉', 'پیامک', 'وضعیت قبض ۱۰۷۹ برای مشتری فرستاده شد']
  ];
  var mods = el('rsMods'), feed = el('rsFeed');
  if (mods) {
    MODS.forEach(function (m) {
      var d = document.createElement('div'); d.className = 'cd-mod';
      d.innerHTML = '<i>' + m[0] + '</i><span>' + m[1] + '</span>';
      mods.appendChild(d);
    });
  }
  function addEv(m) {
    if (!feed) return;
    var e = document.createElement('div'); e.className = 'cd-ev';
    e.innerHTML = '<i>' + m[0] + '</i><div><div class="m">' + m[1] + '</div><div class="x">' + m[2] + '</div></div><div class="tm">نمونه</div>';
    feed.insertBefore(e, feed.firstChild);
    while (feed.children.length > 4) feed.removeChild(feed.lastChild);
  }
  onVisible(el('rsDesk'), function () {
    count(el('rsK1'), 37, 1100); count(el('rsK2'), 18, 1100); count(el('rsK3'), 9, 1100);
    if (reduce) { MODS.slice(0, 4).forEach(addEv); return; }
    loop(async function () {
      for (var i = 0; i < MODS.length; i++) {
        [].forEach.call(mods.children, function (c, j) { c.classList.toggle('is-on', j === i); });
        addEv(MODS[i]);
        await sleep(1500);
      }
    });
  });

  var steps = function (id) { return el(id) ? [].slice.call(el(id).children) : []; };
  function mark(list, n) {
    list.forEach(function (s, i) {
      s.classList.toggle('is-on', i === n);
      s.classList.toggle('is-done', i < n);
    });
  }
  async function intake() {
    var st = steps('rsInSt');
    ['rsIn1', 'rsIn2', 'rsIn3', 'rsIn4'].forEach(function (id) { if (el(id)) el(id).textContent = '—'; });
    var toast = el('rsInT'); if (toast) toast.classList.remove('is-show');
    mark(st, 0); await sleep(700); if (el('rsIn1')) el('rsIn1').textContent = 'آقای نمونه‌پور';
    mark(st, 1); await sleep(700); if (el('rsIn2')) el('rsIn2').textContent = 'گوشی نمونه‌تل';
    mark(st, 2); await sleep(700); if (el('rsIn3')) el('rsIn3').textContent = 'شارژ نمی‌شود';
    mark(st, 3); await sleep(600); if (el('rsIn4')) el('rsIn4').textContent = '۱۰۸۴';
    if (toast) { toast.textContent = 'برچسب قبض ۱۰۸۴ چاپ شد'; toast.classList.add('is-show'); }
    await sleep(2800);
  }
  onVisible(el('rsIn'), function () { loop(intake); });

  async function customer() {
    var st = steps('rsCuSt'), say = el('rsCuSay'), chip = el('rsCuChip');
    var frames = [
      ['در دست تعمیر', 'قبض ۱۰۸۴ روی میز آقای رحیمی است.', 'is-wait'],
      ['منتظر تأیید شما', 'مبلغ قابل تأیید ۵٬۴۰۰٬۰۰۰ ریال است.', 'is-wait'],
      ['آمادهٔ تحویل', 'هزینه تأیید شد. مانده را از همین کارتابل می‌توانید بپردازید.', 'is-ok']
    ];
    for (var i = 0; i < frames.length; i++) {
      mark(st, i);
      if (chip) { chip.className = 'cd-chip ' + frames[i][2]; chip.textContent = frames[i][0]; }
      if (say) say.textContent = frames[i][1];
      await sleep(1600);
    }
    await sleep(1800);
  }
  onVisible(el('rsCu'), function () { loop(customer); });

  var QUEUE = [
    ['۱۰۸۴', 'نمونه‌پور', 'در انتظار', ''],
    ['۱۰۵۲', 'شرکت آلفا', 'در انتظار', ''],
    ['۱۰۷۶', 'احمدی', 'رد شد', 'is-bad']
  ];
  async function approvals() {
    var box = el('rsAQ'); if (!box) return;
    box.innerHTML = '';
    count(el('rsA0'), 2, 600); count(el('rsA1'), 0, 400); count(el('rsA2'), 1, 600);
    QUEUE.forEach(function (r) {
      var row = document.createElement('div');
      row.className = 'cd-row' + (r[3] ? ' ' + r[3] : '');
      row.innerHTML = '<b class="num">' + r[0] + '</b><span>' + r[1] + '</span><span class="st">' + r[2] + '</span>';
      box.appendChild(row);
    });
    await sleep(1200);
    var first = box.children[0];
    if (first) {
      var st = first.querySelector('.st');
      if (st) st.textContent = 'تأیید شد · کد ۴۸۲۷';
      count(el('rsA0'), 1, 400); count(el('rsA1'), 1, 400);
    }
    await sleep(2600);
  }
  onVisible(el('rsAp'), function () { loop(approvals); });
})();
</script>
