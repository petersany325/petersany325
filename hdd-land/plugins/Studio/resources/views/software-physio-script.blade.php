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
  function setSteps(root, n){ if (!root) return; [].forEach.call(root.querySelectorAll('.cd-stp'), function(s,i){ s.classList.toggle('is-on', i===n); s.classList.toggle('is-done', i<n); }); }
  function addEv(m){
    var feed = el('pyFeed'); if (!feed) return;
    var e = document.createElement('div'); e.className = 'cd-ev';
    e.innerHTML = '<i>'+m[0]+'</i><div><div class="m">'+m[1]+'</div><div class="x">'+m[2]+'</div></div><div class="tm">همین حالا</div>';
    feed.insertBefore(e, feed.firstChild);
    while (feed.children.length > 4) feed.removeChild(feed.lastChild);
  }
  var CALLS = [
    {no:4, who:'نوبت آقای کاظمی', room:'ورزش‌درمانی'},
    {no:5, who:'نوبت خانم احمدی', room:'الکتروتراپی'},
    {no:6, who:'نوبت خانم رضایی', room:'الکتروتراپی'},
    {no:3, who:'نوبت آقای محمدی', room:'دستی (منوال)'}
  ];
  var EVENTS = [
    ['👤','پذیرش','بیمار جدید ثبت شد · پیامک خوش‌آمد'],
    ['📅','نوبت واحد','رزرو حضوری الکتروتراپی · ۱۴۰۵/۰۹/۰۸ ساعت ۱۰:۳۰'],
    ['🎫','شماره نوبت','شماره ۶ در واحد الکتروتراپی صادر شد'],
    ['🩺','ثبت جلسه','جلسه فیزیوتراپی · کمردرد (نمونه)'],
    ['✅','تسویه','جلسه انجام شد و نوبت بعدی ثبت شد']
  ];
  var UNITS = [
    {id:'el', name:'الکتروتراپی', no:6, who:'خانم رضایی'},
    {id:'ex', name:'ورزش‌درمانی', no:4, who:'آقای کاظمی'},
    {id:'ls', name:'لیزر و شاک‌ویو', no:2, who:'خانم احمدی'},
    {id:'mn', name:'دستی (منوال)', no:3, who:'آقای محمدی'}
  ];
  function paint(numEl, whoEl, roomEl, lineEl, item){
    if (numEl) numEl.textContent = faN(item.no);
    if (whoEl) whoEl.textContent = item.who;
    if (roomEl) roomEl.textContent = item.room;
    if (lineEl){ lineEl.textContent = 'شماره '+faN(item.no)+' — '+item.who; lineEl.classList.add('is-show'); }
  }
  onVisible(el('py1'), function(){
    count(el('pyK1'),12,1800); count(el('pyK2'),3,1800); count(el('pyK3'),6,1800); count(el('pyK4'),4,1800);
    function hero(item){ paint(el('pyHeroNum'), el('pyHeroWho'), el('pyHeroRoom'), el('pyHeroLine'), item); if (item.room==='الکتروتراپی' && el('pyK3')) el('pyK3').textContent = faN(item.no); }
    if (reduce){ hero(CALLS[2]); EVENTS.forEach(addEv); return; }
    hero(CALLS[1]);
    loop(async function(){ var feed=el('pyFeed'); if (feed) feed.innerHTML=''; for (var i=0;i<EVENTS.length;i++){ addEv(EVENTS[i]); hero(CALLS[i%CALLS.length]); await sleep(1900);} await sleep(1500); });
  });
  var unitEls = {};
  (function(){
    var box = el('pyUnitBoard'); if (!box) return;
    UNITS.forEach(function(u){ var d=document.createElement('div'); d.className='py-unit'; d.innerHTML='<b>'+u.name+'</b><span class="num">'+faN(u.no)+'</span><em>'+u.who+'</em>'; box.appendChild(d); unitEls[u.id]=d; });
  })();
  onVisible(el('pyUnits'), function(){
    if (reduce){ if (unitEls.el) unitEls.el.classList.add('is-on'); return; }
    loop(async function(){ var keys=UNITS.map(function(u){return u.id}); for (var i=0;i<keys.length;i++){ Object.keys(unitEls).forEach(function(k){ unitEls[k].classList.remove('is-on'); }); unitEls[keys[i]].classList.add('is-on'); await sleep(1600);} });
  });
  (function(){ var box=el('pyDots'); if(!box) return; for (var i=0;i<=10;i++){ var s=document.createElement('span'); s.dataset.n=String(i); s.textContent=faN(i); box.appendChild(s);} })();
  function setPain(n){ [].forEach.call(el('pyDots').querySelectorAll('span'), function(d){ d.classList.toggle('is-on', +d.dataset.n===n); }); }
  onVisible(el('pyIntake'), function(){
    if (reduce){ el('pyAreaV').textContent='کمر'; el('pySurgV').textContent='خیر'; setPain(7); ['pyArea','pyPain','pySurg'].forEach(function(id){ el(id).classList.add('is-done'); }); return; }
    loop(async function(){
      ['pyArea','pyPain','pySurg'].forEach(function(id){ el(id).classList.remove('is-on','is-done'); });
      el('pyAreaV').textContent='—'; el('pySurgV').textContent='—'; setPain(-1); await sleep(500);
      el('pyArea').classList.add('is-on'); el('pyAreaV').textContent='کمر'; await sleep(1100);
      el('pyArea').classList.remove('is-on'); el('pyArea').classList.add('is-done');
      el('pyPain').classList.add('is-on');
      for (var i=0;i<=7;i++){ setPain(i); await sleep(140); }
      await sleep(700); el('pyPain').classList.remove('is-on'); el('pyPain').classList.add('is-done');
      el('pySurg').classList.add('is-on'); el('pySurgV').textContent='خیر'; await sleep(1200);
      el('pySurg').classList.remove('is-on'); el('pySurg').classList.add('is-done');
      await sleep(2600);
    });
  });
  function pathTo(n){ [].forEach.call(el('pyPath').querySelectorAll('.st'), function(s,i){ s.classList.toggle('is-on', i===n); s.classList.toggle('is-done', i<n); }); }
  onVisible(el('pyPath'), function(){ if (reduce){ pathTo(5); return; } loop(async function(){ for (var i=0;i<=5;i++){ pathTo(i); await sleep(1000);} [].forEach.call(el('pyPath').querySelectorAll('.st'), function(s){ s.classList.add('is-done'); s.classList.remove('is-on'); }); el('pyPath').querySelectorAll('.st')[5].classList.add('is-on'); await sleep(2400); }); });
  function renderNext(active){ var box=el('pyQNext'); if(!box) return; box.innerHTML=''; [4,5,6,7,8].forEach(function(n){ var s=document.createElement('span'); s.className='py-chip'+(n===active?' is-on':''); s.textContent='نوبت '+faN(n); box.appendChild(s); }); }
  var QCALLS=[{no:5,who:'نوبت خانم احمدی',room:'الکتروتراپی'},{no:6,who:'نوبت خانم رضایی',room:'الکتروتراپی'},{no:7,who:'نوبت آقای نوری',room:'الکتروتراپی'}];
  onVisible(el('pyQ'), function(){
    if (reduce){ paint(el('pyQNum'),el('pyQWho'),el('pyQRoom'),el('pyQLine'),QCALLS[1]); renderNext(6); return; }
    loop(async function(){ for (var i=0;i<QCALLS.length;i++){ renderNext(QCALLS[i].no); paint(el('pyQNum'),el('pyQWho'),el('pyQRoom'),el('pyQLine'),QCALLS[i]); await sleep(2400); if (el('pyQLine')) el('pyQLine').classList.remove('is-show'); await sleep(400);} });
  });
  function paint(numEl,whoEl,roomEl,lineEl,item){ if(numEl) numEl.textContent=faN(item.no); if(whoEl) whoEl.textContent=item.who; if(roomEl) roomEl.textContent=item.room; if(lineEl){ lineEl.textContent='شماره '+faN(item.no)+' — '+item.who; lineEl.classList.add('is-show'); } }
  async function typeText(node, text){ if(!node) return; if(reduce){ node.textContent=text; return; } for (var i=1;i<=text.length;i++){ node.innerHTML=text.slice(0,i)+'<span class="caret"></span>'; await sleep(38);} node.textContent=text; }
  var SMS='خانم رضایی عزیز، نوبت شما در کلینیک نمونه حرکت برای ۱۴۰۵/۰۹/۰۸ ساعت ۱۰:۳۰ ثبت شد. شماره نوبت شما: ۶';
  onVisible(el('pyRsv'), function(){
    loop(async function(){
      var steps=el('pyRsvSteps'), q=el('pyRsvQ'), hit=el('pyRsvHit'), opts=el('pyRsvOpts'), sum=el('pyRsvSum');
      if (q) q.textContent=''; if (hit) hit.classList.remove('is-show'); if (opts) opts.innerHTML=''; if (sum){ sum.classList.remove('is-show'); sum.textContent=''; }
      setSteps(steps,0);
      if (q){ if (reduce) q.textContent='رضایی'; else { for (var i=1;i<='رضایی'.length;i++){ q.textContent='رضایی'.slice(0,i); await sleep(180);} } }
      await sleep(450); if (hit) hit.classList.add('is-show'); await sleep(700); setSteps(steps,1);
      if (opts){ ['جلسه فیزیوتراپی (نمونه)','درمانگر: فیزیوتراپیست نمونه','واحد الکتروتراپی · حضوری'].forEach(function(row,i){ var d=document.createElement('span'); d.className='py-opt'+(i<2?' is-on':''); d.textContent=row; opts.appendChild(d); }); }
      await sleep(850); setSteps(steps,2);
      if (opts){ opts.innerHTML=''; ['۱۴۰۵/۰۹/۰۸ ۰۹:۴۰','۱۴۰۵/۰۹/۰۸ ۱۰:۳۰','۱۴۰۵/۰۹/۰۸ ۱۱:۲۰'].forEach(function(t,i){ var d=document.createElement('span'); d.className='py-opt'+(i===1?' is-on':''); d.textContent=t; opts.appendChild(d); }); }
      await sleep(1000); setSteps(steps,3);
      if (sum){ sum.innerHTML='تأیید رزرو: خانم سارا رضایی · الکتروتراپی · ۱۴۰۵/۰۹/۰۸ ساعت ۱۰:۳۰<br>شماره نوبت: <b>'+faN(6)+'</b> · پیامک ارسال شد'; sum.classList.add('is-show'); }
      await sleep(3400);
    });
  });
  onVisible(el('pySms'), function(){ loop(async function(){ await typeText(el('pySmsText'), SMS); await sleep(3000); }); });
  onVisible(el('pySet'), function(){
    loop(async function(){
      var steps=el('pySetSteps'), pay=el('pySetPay'), next=el('pySetNext'), amt=el('pySetAmt');
      if (amt) amt.textContent='—'; if (pay) pay.innerHTML=''; if (next){ next.classList.remove('is-show'); next.textContent='نوبت بعدی هنوز ثبت نشده است.'; }
      setSteps(steps,0); await sleep(750); setSteps(steps,1); await count(amt, 900000, 900);
      if (amt) amt.textContent=fa(900000)+' ریال';
      if (pay) pay.innerHTML='<span class="cd-chip is-ok">تسویه شد</span><span class="cd-chip">روش: کارت (نمونه)</span>';
      await sleep(1000); setSteps(steps,2);
      if (next){ next.innerHTML='نوبت بعدی ثبت شد: <b>۱۴۰۵/۰۹/۱۰ ساعت ۱۰:۳۰</b> · الکتروتراپی'; next.classList.add('is-show'); }
      await sleep(900); setSteps(steps,3);
      if (next) next.innerHTML+='<br>پیامک تاریخ و ساعت نوبت بعدی ارسال شد.';
      await sleep(3400);
    });
  });
  var form=el('pyDemo');
  if (form){
    form.phone.addEventListener('input', function(){ form.phone.setCustomValidity(''); });
    form.addEventListener('submit', function(e){
      form.phone.setCustomValidity('');
      var latin=(form.phone.value||'').replace(/[۰-۹]/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }).replace(/[^\d]/g,'');
      if (!/^0?9\d{9}$/.test(latin)){ e.preventDefault(); form.phone.setCustomValidity('شماره موبایل ایرانی را وارد کنید'); form.phone.reportValidity(); return; }
      if (latin.length===10) latin='0'+latin;
      form.phone.value=latin;
      el('pyBody').value='درخواست دموی حساب مطب، نسخهٔ فیزیوتراپی. کلینیک: '+((el('pyClinic')&&el('pyClinic').value)||'')+' · شهر: '+((el('pyCity')&&el('pyCity').value)||'');
    });
  }
})();
</script>
