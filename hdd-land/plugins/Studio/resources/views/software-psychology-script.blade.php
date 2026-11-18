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
    var io = new IntersectionObserver(function(es){ if (es[0].isIntersecting) { io.disconnect(); fn(); } }, {threshold: 0.25});
    io.observe(node);
  }
  async function loop(fn){ do { await fn(); } while (!reduce); }
  function setSteps(root, n){
    if (!root) return;
    [].forEach.call(root.querySelectorAll('.cd-stp'), function(s, i){
      s.classList.toggle('is-on', i === n);
      s.classList.toggle('is-done', i < n);
    });
  }
  function addEv(m){
    var feed = el('psFeed'); if (!feed) return;
    var e = document.createElement('div'); e.className = 'cd-ev';
    e.innerHTML = '<i>'+m[0]+'</i><div><div class="m">'+m[1]+'</div><div class="x">'+m[2]+'</div></div><div class="tm">همین حالا</div>';
    feed.insertBefore(e, feed.firstChild);
    while (feed.children.length > 4) feed.removeChild(feed.lastChild);
  }
  var CALLS = [
    {no:6, who:'جلسهٔ خانم احمدی', room:'اتاق مشاوره ۱'},
    {no:7, who:'جلسهٔ آقای کاظمی', room:'اتاق مشاوره ۱'},
    {no:8, who:'جلسهٔ خانم رضایی', room:'اتاق مشاوره ۱'},
    {no:9, who:'جلسهٔ خانم موسوی', room:'اتاق مشاوره ۱'}
  ];
  var EVENTS = [
    ['👤','پذیرش','مراجع جدید ثبت شد · پیامک خوش‌آمد'],
    ['📅','نوبت جلسه','رزرو حضوری برای ۱۴۰۵/۰۹/۰۳ ساعت ۱۷:۰۰'],
    ['🎫','شماره نوبت','شماره ۸ صادر شد'],
    ['🚪','ورود به جلسه','نوبت ۸ — جلسهٔ خانم رضایی'],
    ['✅','تسویه','جلسه انجام شد و جلسهٔ بعد ثبت شد']
  ];
  function paint(numEl, whoEl, roomEl, lineEl, item){
    if (numEl) numEl.textContent = faN(item.no);
    if (whoEl) whoEl.textContent = item.who;
    if (roomEl) roomEl.textContent = item.room;
    if (lineEl){ lineEl.textContent = 'نوبت ' + faN(item.no) + ' — ' + item.who; lineEl.classList.add('is-show'); }
  }
  onVisible(el('ps1'), function(){
    count(el('psK1'), 12, 1800); count(el('psK2'), 3, 1800); count(el('psK3'), 8, 1800); count(el('psK4'), 42, 1800);
    function hero(item){
      paint(el('psHeroNum'), el('psHeroWho'), el('psHeroRoom'), el('psHeroLine'), item);
      if (el('psK3')) el('psK3').textContent = faN(item.no);
    }
    if (reduce){ hero(CALLS[2]); EVENTS.forEach(addEv); return; }
    hero(CALLS[1]);
    loop(async function(){
      var feed = el('psFeed'); if (feed) feed.innerHTML = '';
      for (var i = 0; i < EVENTS.length; i++){ addEv(EVENTS[i]); hero(CALLS[i % CALLS.length]); await sleep(2000); }
      await sleep(1600);
    });
  });
  function renderNext(active){
    var box = el('psQNext'); if (!box) return;
    box.innerHTML = '';
    [6,7,8,9,10].forEach(function(n){
      var s = document.createElement('span');
      s.className = 'ps-chip' + (n === active ? ' is-on' : '');
      s.textContent = 'نوبت ' + faN(n);
      box.appendChild(s);
    });
  }
  onVisible(el('psQ'), function(){
    if (reduce){ paint(el('psQNum'), el('psQWho'), el('psQRoom'), el('psQLine'), CALLS[2]); renderNext(8); return; }
    loop(async function(){
      for (var i = 0; i < CALLS.length; i++){
        renderNext(CALLS[i].no);
        paint(el('psQNum'), el('psQWho'), el('psQRoom'), el('psQLine'), CALLS[i]);
        await sleep(2600);
        if (el('psQLine')) el('psQLine').classList.remove('is-show');
        await sleep(500);
      }
    });
  });
  function pathTo(n){
    var root = el('psPath'); if (!root) return;
    [].forEach.call(root.querySelectorAll('.st'), function(s, i){
      s.classList.toggle('is-on', i === n);
      s.classList.toggle('is-done', i < n);
    });
  }
  onVisible(el('psPath'), function(){
    if (reduce){ pathTo(5); return; }
    loop(async function(){
      for (var i = 0; i <= 5; i++){ pathTo(i); await sleep(1100); }
      [].forEach.call(el('psPath').querySelectorAll('.st'), function(s){ s.classList.add('is-done'); s.classList.remove('is-on'); });
      el('psPath').querySelectorAll('.st')[5].classList.add('is-on');
      await sleep(2600);
    });
  });
  async function typeText(node, text){
    if (!node) return;
    if (reduce){ node.textContent = text; return; }
    node.textContent = '';
    for (var i = 1; i <= text.length; i++){
      node.innerHTML = text.slice(0, i) + '<span class="caret"></span>';
      await sleep(40);
    }
    node.textContent = text;
  }
  var SMS = 'خانم رضایی عزیز، نوبت شما در مطب نمونه آرامش روز ۱۴۰۵/۰۹/۰۳ ساعت ۱۷:۰۰ است. شماره نوبت شما: ۸';
  onVisible(el('psRsv'), function(){
    loop(async function(){
      var steps = el('psRsvSteps'), q = el('psRsvQ'), hit = el('psRsvHit'), opts = el('psRsvOpts'), sum = el('psRsvSum');
      if (q) q.textContent = '';
      if (hit) hit.classList.remove('is-show');
      if (opts) opts.innerHTML = '';
      if (sum){ sum.classList.remove('is-show'); sum.textContent = ''; }
      setSteps(steps, 0);
      var typed = 'رضایی';
      if (q){
        if (reduce) q.textContent = typed;
        else { for (var i = 1; i <= typed.length; i++){ q.textContent = typed.slice(0, i); await sleep(180); } }
      }
      await sleep(500);
      if (hit) hit.classList.add('is-show');
      await sleep(800);
      setSteps(steps, 1);
      if (opts){
        ['جلسه مشاوره (نمونه)','درمانگر: روان‌شناس نمونه','کانال: حضوری'].forEach(function(row, i){
          var d = document.createElement('span'); d.className = 'ps-opt' + (i < 2 ? ' is-on' : ''); d.textContent = row; opts.appendChild(d);
        });
      }
      await sleep(900);
      setSteps(steps, 2);
      if (opts){
        opts.innerHTML = '';
        ['۱۴۰۵/۰۹/۰۳ ۱۶:۰۰','۱۴۰۵/۰۹/۰۳ ۱۷:۰۰','۱۴۰۵/۰۹/۰۳ ۱۸:۰۰'].forEach(function(t, i){
          var d = document.createElement('span'); d.className = 'ps-opt' + (i === 1 ? ' is-on' : ''); d.textContent = t; opts.appendChild(d);
        });
      }
      await sleep(1100);
      setSteps(steps, 3);
      if (sum){
        sum.innerHTML = 'تأیید رزرو: خانم سارا رضایی · ۱۴۰۵/۰۹/۰۳ ساعت ۱۷:۰۰ · کانال حضوری<br>شماره نوبت صادر شد: <b>' + faN(8) + '</b> · پیامک یادآوری ارسال شد';
        sum.classList.add('is-show');
      }
      await sleep(3600);
    });
  });
  onVisible(el('psSms'), function(){ loop(async function(){ await typeText(el('psSmsText'), SMS); await sleep(3200); }); });
  onVisible(el('psSet'), function(){
    loop(async function(){
      var steps = el('psSetSteps'), pay = el('psSetPay'), next = el('psSetNext'), amt = el('psSetAmt');
      if (amt) amt.textContent = '—';
      if (pay) pay.innerHTML = '';
      if (next){ next.classList.remove('is-show'); next.textContent = 'جلسهٔ بعد هنوز ثبت نشده است.'; }
      setSteps(steps, 0);
      await sleep(800);
      setSteps(steps, 1);
      await count(amt, 9500000, 1000);
      if (amt) amt.textContent = fa(9500000) + ' ریال';
      if (pay) pay.innerHTML = '<span class="cd-chip is-ok">تسویه شد</span><span class="cd-chip">روش: کارت (نمونه)</span>';
      await sleep(1100);
      setSteps(steps, 2);
      if (next){ next.innerHTML = 'جلسهٔ بعد ثبت شد: <b>۱۴۰۵/۰۹/۱۰ ساعت ۱۷:۰۰</b> · کانال حضوری'; next.classList.add('is-show'); }
      await sleep(1000);
      setSteps(steps, 3);
      if (next) next.innerHTML += '<br>پیامک تاریخ و ساعت جلسهٔ بعد ارسال شد.';
      await sleep(3600);
    });
  });
  var form = el('psDemo');
  if (form){
    form.phone.addEventListener('input', function(){ form.phone.setCustomValidity(''); });
    form.addEventListener('submit', function(e){
      form.phone.setCustomValidity('');
      var latin = (form.phone.value || '').replace(/[۰-۹]/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }).replace(/[^\d]/g,'');
      if (!/^0?9\d{9}$/.test(latin)){
        e.preventDefault();
        form.phone.setCustomValidity('شماره موبایل ایرانی را وارد کنید');
        form.phone.reportValidity();
        return;
      }
      if (latin.length === 10) latin = '0' + latin;
      form.phone.value = latin;
      el('psBody').value = 'درخواست دموی حساب مطب، نسخهٔ روانشناسی. مطب: ' + ((el('psClinic') && el('psClinic').value) || '') + ' · شهر: ' + ((el('psCity') && el('psCity').value) || '');
    });
  }
})();
</script>
