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
      (function f(t){ var p = Math.min(1,(t-t0)/ms); node.textContent = fa(Math.round(to*(1-Math.pow(1-p,3)))); if (p<1) requestAnimationFrame(f); else res(); })(t0);
    });
  }
  function onVisible(node, fn){
    if (!node) return;
    if (reduce || !('IntersectionObserver' in window)) { fn(); return; }
    var io = new IntersectionObserver(function(es){ if (es[0].isIntersecting) { io.disconnect(); fn(); } }, {threshold:0.25});
    io.observe(node);
  }
  async function loop(fn){ do { await fn(); } while (!reduce); }
  function setSteps(root, n){
    if (!root) return;
    [].forEach.call(root.querySelectorAll('.cd-stp'), function(s,i){ s.classList.toggle('is-on', i===n); s.classList.toggle('is-done', i<n); });
  }
  function addEv(m){
    var feed = el('dnFeed'); if (!feed) return;
    var e = document.createElement('div'); e.className = 'cd-ev';
    e.innerHTML = '<i>'+m[0]+'</i><div><div class="m">'+m[1]+'</div><div class="x">'+m[2]+'</div></div><div class="tm">همین حالا</div>';
    feed.insertBefore(e, feed.firstChild);
    while (feed.children.length > 4) feed.removeChild(feed.lastChild);
  }
  var CALLS = [
    {no:7, who:'نوبت خانم احمدی', room:'یونیت ۱'},
    {no:8, who:'نوبت آقای کاظمی', room:'یونیت ۱'},
    {no:9, who:'نوبت خانم رضایی', room:'یونیت ۱'},
    {no:10, who:'نوبت آقای محمدی', room:'یونیت ۲'}
  ];
  var EVENTS = [
    ['👤','پذیرش','بیمار جدید ثبت شد · پیامک خوش‌آمد'],
    ['📅','نوبت یونیت','رزرو حضوری یونیت ۱ · ۱۴۰۵/۰۹/۰۵ ساعت ۱۶:۳۰'],
    ['🎫','شماره نوبت','شماره ۹ در یونیت ۱ صادر شد'],
    ['🦷','ثبت درمان','دندان ۳۶ · ترمیم کامپوزیت (نمونه)'],
    ['✅','تسویه','درمان انجام شد و نوبت بعدی ثبت شد']
  ];
  function paint(numEl, whoEl, roomEl, lineEl, item){
    if (numEl) numEl.textContent = faN(item.no);
    if (whoEl) whoEl.textContent = item.who;
    if (roomEl) roomEl.textContent = item.room;
    if (lineEl){ lineEl.textContent = 'شماره ' + faN(item.no) + ' — ' + item.who; lineEl.classList.add('is-show'); }
  }
  onVisible(el('dn1'), function(){
    count(el('dnK1'),18,1800); count(el('dnK2'),4,1800); count(el('dnK3'),9,1800); count(el('dnK4'),64,1800);
    function hero(item){ paint(el('dnHeroNum'), el('dnHeroWho'), el('dnHeroRoom'), el('dnHeroLine'), item); if (el('dnK3')) el('dnK3').textContent = faN(item.no); }
    if (reduce){ hero(CALLS[2]); EVENTS.forEach(addEv); return; }
    hero(CALLS[1]);
    loop(async function(){ var feed = el('dnFeed'); if (feed) feed.innerHTML = ''; for (var i=0;i<EVENTS.length;i++){ addEv(EVENTS[i]); hero(CALLS[i%CALLS.length]); await sleep(1900);} await sleep(1500); });
  });
  function renderNext(active){
    var box = el('dnQNext'); if (!box) return; box.innerHTML = '';
    [7,8,9,10,11].forEach(function(n){ var s = document.createElement('span'); s.className = 'dn-chip'+(n===active?' is-on':''); s.textContent = 'نوبت '+faN(n); box.appendChild(s); });
  }
  onVisible(el('dnQ'), function(){
    if (reduce){ paint(el('dnQNum'), el('dnQWho'), el('dnQRoom'), el('dnQLine'), CALLS[2]); renderNext(9); return; }
    loop(async function(){ for (var i=0;i<CALLS.length;i++){ renderNext(CALLS[i].no); paint(el('dnQNum'), el('dnQWho'), el('dnQRoom'), el('dnQLine'), CALLS[i]); await sleep(2400); if (el('dnQLine')) el('dnQLine').classList.remove('is-show'); await sleep(400);} });
  });
  function pathTo(n){ var root = el('dnPath'); if (!root) return; [].forEach.call(root.querySelectorAll('.st'), function(s,i){ s.classList.toggle('is-on', i===n); s.classList.toggle('is-done', i<n); }); }
  onVisible(el('dnPath'), function(){
    if (reduce){ pathTo(5); return; }
    loop(async function(){ for (var i=0;i<=5;i++){ pathTo(i); await sleep(1000);} [].forEach.call(el('dnPath').querySelectorAll('.st'), function(s){ s.classList.add('is-done'); s.classList.remove('is-on'); }); el('dnPath').querySelectorAll('.st')[5].classList.add('is-on'); await sleep(2400); });
  });
  var UP = [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28];
  var LO = [48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38];
  var toothMap = {};
  function buildArch(id, nums){
    var box = el(id); if (!box) return; box.innerHTML = '';
    nums.forEach(function(n){ var b = document.createElement('span'); b.className = 'dn-tooth'; b.textContent = faN(n); b.setAttribute('aria-hidden','true'); box.appendChild(b); toothMap[n] = b; });
  }
  buildArch('dnArchUp', UP); buildArch('dnArchLo', LO);
  function clearTeeth(){ Object.keys(toothMap).forEach(function(k){ toothMap[k].className = 'dn-tooth'; }); }
  onVisible(el('dnTeeth'), function(){
    if (reduce){ if (toothMap[36]) toothMap[36].className = 'dn-tooth is-done'; if (el('dnToothFdi')) el('dnToothFdi').textContent = 'FDI: '+faN(36); if (el('dnToothChip')) el('dnToothChip').textContent = 'وضعیت: ترمیم کامپوزیت'; return; }
    loop(async function(){
      clearTeeth();
      if (el('dnToothFdi')) el('dnToothFdi').textContent = 'FDI: —';
      if (el('dnToothChip')) el('dnToothChip').textContent = 'وضعیت: انتخاب دندان';
      await sleep(600);
      if (toothMap[36]) toothMap[36].classList.add('is-on');
      if (el('dnToothFdi')) el('dnToothFdi').textContent = 'FDI: '+faN(36);
      if (el('dnToothChip')) el('dnToothChip').textContent = 'وضعیت: پوسیدگی';
      await sleep(1400);
      if (el('dnToothChip')) el('dnToothChip').textContent = 'وضعیت: ترمیم کامپوزیت';
      if (toothMap[36]){ toothMap[36].classList.remove('is-on'); toothMap[36].classList.add('is-done'); }
      await sleep(2800);
    });
  });
  function setLab(n){ var root = el('dnLabSteps'); if (!root) return; [].forEach.call(root.querySelectorAll('.st'), function(s,i){ s.classList.toggle('is-on', i===n); s.classList.toggle('is-done', i<n); }); }
  onVisible(el('dnLab'), function(){ if (reduce){ setLab(2); return; } loop(async function(){ for (var i=0;i<=2;i++){ setLab(i); await sleep(1400);} setLab(2); await sleep(2600); }); });
  async function typeText(node, text){ if (!node) return; if (reduce){ node.textContent = text; return; } node.textContent = ''; for (var i=1;i<=text.length;i++){ node.innerHTML = text.slice(0,i)+'<span class="caret"></span>'; await sleep(38);} node.textContent = text; }
  var SMS = 'خانم رضایی عزیز، نوبت شما در مطب نمونه لبخند برای ۱۴۰۵/۰۹/۰۵ ساعت ۱۶:۳۰ ثبت شد. شماره نوبت شما: ۹';
  onVisible(el('dnRsv'), function(){
    loop(async function(){
      var steps = el('dnRsvSteps'), q = el('dnRsvQ'), hit = el('dnRsvHit'), opts = el('dnRsvOpts'), sum = el('dnRsvSum');
      if (q) q.textContent = ''; if (hit) hit.classList.remove('is-show'); if (opts) opts.innerHTML = ''; if (sum){ sum.classList.remove('is-show'); sum.textContent = ''; }
      setSteps(steps, 0);
      if (q){ if (reduce) q.textContent = 'رضایی'; else { for (var i=1;i<='رضایی'.length;i++){ q.textContent = 'رضایی'.slice(0,i); await sleep(180);} } }
      await sleep(450); if (hit) hit.classList.add('is-show'); await sleep(700); setSteps(steps, 1);
      if (opts){ ['ترمیم کامپوزیت (نمونه)','پزشک: دکتر نمونه','یونیت ۱ · کانال حضوری'].forEach(function(row,i){ var d = document.createElement('span'); d.className = 'dn-opt'+(i<2?' is-on':''); d.textContent = row; opts.appendChild(d); }); }
      await sleep(850); setSteps(steps, 2);
      if (opts){ opts.innerHTML = ''; ['۱۴۰۵/۰۹/۰۵ ۱۵:۳۰','۱۴۰۵/۰۹/۰۵ ۱۶:۳۰','۱۴۰۵/۰۹/۰۵ ۱۷:۳۰'].forEach(function(t,i){ var d = document.createElement('span'); d.className = 'dn-opt'+(i===1?' is-on':''); d.textContent = t; opts.appendChild(d); }); }
      await sleep(1000); setSteps(steps, 3);
      if (sum){ sum.innerHTML = 'تأیید رزرو: خانم سارا رضایی · یونیت ۱ · ۱۴۰۵/۰۹/۰۵ ساعت ۱۶:۳۰<br>شماره نوبت صادر شد: <b>'+faN(9)+'</b> · پیامک ارسال شد'; sum.classList.add('is-show'); }
      await sleep(3400);
    });
  });
  onVisible(el('dnSms'), function(){ loop(async function(){ await typeText(el('dnSmsText'), SMS); await sleep(3000); }); });
  onVisible(el('dnSet'), function(){
    loop(async function(){
      var steps = el('dnSetSteps'), pay = el('dnSetPay'), next = el('dnSetNext'), amt = el('dnSetAmt');
      if (amt) amt.textContent = '—'; if (pay) pay.innerHTML = ''; if (next){ next.classList.remove('is-show'); next.textContent = 'نوبت بعدی هنوز ثبت نشده است.'; }
      setSteps(steps, 0); await sleep(750); setSteps(steps, 1); await count(amt, 4500000, 900);
      if (amt) amt.textContent = fa(4500000)+' ریال';
      if (pay) pay.innerHTML = '<span class="cd-chip is-ok">تسویه شد</span><span class="cd-chip">روش: کارت (نمونه)</span>';
      await sleep(1000); setSteps(steps, 2);
      if (next){ next.innerHTML = 'نوبت بعدی ثبت شد: <b>۱۴۰۵/۰۹/۱۲ ساعت ۱۶:۳۰</b> · یونیت ۱'; next.classList.add('is-show'); }
      await sleep(900); setSteps(steps, 3);
      if (next) next.innerHTML += '<br>پیامک تاریخ و ساعت نوبت بعدی ارسال شد.';
      await sleep(3400);
    });
  });
  var form = el('dnDemo');
  if (form){
    form.phone.addEventListener('input', function(){ form.phone.setCustomValidity(''); });
    form.addEventListener('submit', function(e){
      form.phone.setCustomValidity('');
      var latin = (form.phone.value||'').replace(/[۰-۹]/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }).replace(/[^\d]/g,'');
      if (!/^0?9\d{9}$/.test(latin)){ e.preventDefault(); form.phone.setCustomValidity('شماره موبایل ایرانی را وارد کنید'); form.phone.reportValidity(); return; }
      if (latin.length === 10) latin = '0'+latin;
      form.phone.value = latin;
      el('dnBody').value = 'درخواست دموی حساب مطب، نسخهٔ دندانپزشکی. مطب: '+((el('dnClinic')&&el('dnClinic').value)||'')+' · شهر: '+((el('dnCity')&&el('dnCity').value)||'');
    });
  }
})();
</script>
