<script>
(function () {
  var root = document.querySelector('.ab');
  if (!root) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function $(id) { return document.getElementById(id); }
  function sleep(ms) { return new Promise(function (r) { setTimeout(r, reduce ? 0 : ms); }); }
  function faN(s) { return String(s).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
  function onVisible(el, fn) {
    if (!el) return;
    if (reduce || !('IntersectionObserver' in window)) { fn(); return; }
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { io.disconnect(); fn(); } });
    }, { threshold: 0.3 });
    io.observe(el);
  }
  function mob() { return matchMedia('(max-width:900px)').matches; }

  onVisible($('abStage'), async function () {
    var cs = root.querySelectorAll('.ab-chip');
    for (var i = 0; i < cs.length; i++) { await sleep(450); cs[i].classList.add('on'); }
  });
  onVisible($('abTl'), async function () {
    var st = root.querySelectorAll('.ab-st'), f = $('abFill');
    if (f) { if (mob()) f.style.height = '100%'; else f.style.width = '100%'; }
    for (var i = 0; i < st.length; i++) { st[i].classList.add('on'); await sleep(900); }
  });
  root.querySelectorAll('.ab-fade').forEach(function (el, i) {
    onVisible(el, function () { setTimeout(function () { el.classList.add('on'); }, reduce ? 0 : (i % 4) * 140); });
  });

  var N = 24 * 7, sect = $('abSect');
  if (sect) { for (var i = 0; i < N; i++) sect.appendChild(document.createElement('i')); }
  var BAD = [37, 38, 91, 140, 141, 142], RD = [12, 55, 56, 103, 120, 160];
  async function rec() {
    if (!sect) return;
    var c = sect.children;
    [].forEach.call(c, function (x) { x.className = ''; });
    if ($('abRbar')) $('abRbar').style.width = '0';
    if ($('abRp')) $('abRp').textContent = '۰';
    if ($('abRs')) $('abRs').textContent = 'در حال خواندن…';
    for (var n = 0; n < N; n++) {
      c[n].className = BAD.indexOf(n) >= 0 ? 'bad' : RD.indexOf(n) >= 0 ? 'rd' : 'ok';
      var p = Math.round((n + 1) / N * 1000) / 10;
      if ($('abRbar')) $('abRbar').style.width = p + '%';
      if ($('abRp')) $('abRp').textContent = faN(p.toFixed(1)).replace('.', '٫');
      if (!reduce) await sleep(22);
    }
    if ($('abRs')) $('abRs').textContent = '۹۶٫۴٪ اطلاعات بازیابی شد';
    if (!reduce) { await sleep(3500); rec(); }
  }
  onVisible(sect, rec);
})();
</script>
