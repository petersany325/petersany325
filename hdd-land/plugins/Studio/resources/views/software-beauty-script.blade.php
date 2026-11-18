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
    var feed = el('byFeed'); if (!feed) return;
    var e = document.createElement('div'); e.className = 'cd-ev';
    e.innerHTML = '<i>'+m[0]+'</i><div><div class="m">'+m[1]+'</div><div class="x">'+m[2]+'</div></div><div class="tm">همین حالا</div>';
    feed.insertBefore(e, feed.firstChild);
    while (feed.children.length > 4) feed.removeChild(feed.lastChild);
  }
  var CALLS = [
    {no:10, who:'نوبت خانم احمدی', room:'اتاق لیزر ۱'},
    {no:11, who:'نوبت آقای کاظمی', room:'اتاق لیزر ۱'},
    {no:12, who:'نوبت خانم رضایی', room:'اتاق لیزر ۱'},
    {no:13, who:'نوبت خانم موسوی', room:'اتاق لیزر ۲'}
  ];
  var EVENTS = [
    ['👤','پذیرش','بیمار جدید ثبت شد · پیامک خوش‌آمد'],
    ['📅','نوبت','رزرو حضوری برای ۱۴۰۵/۰۸/۲۶ ساعت ۱۷:۱۵'],
    ['🎫','شماره نوبت','شماره ۱۲ صادر شد'],
    ['🔊','فراخوان','شماره ۱۲ — نوبت خانم رضایی'],
    ['✅','تسویه','خدمت انجام شد و نوبت بعدی ثبت شد']
  ];
  function paintCall(numEl, whoEl, roomEl, voiceEl, item, pulse){
    if (numEl){ numEl.textContent = faN(item.no); numEl.classList.toggle('is-call', !!pulse && !reduce); }
    if (whoEl) whoEl.textContent = item.who;
    if (roomEl) roomEl.textContent = item.room;
    if (voiceEl){ voiceEl.textContent = 'شماره ' + faN(item.no) + ' — ' + item.who; voiceEl.classList.add('is-show'); }
  }
  onVisible(el('by1'), function(){
    count(el('byK1'), 24, 1600); count(el('byK2'), 5, 1600); count(el('byK3'), 12, 1600); count(el('byK4'), 86, 1600);
    function heroCall(item, pulse){
      paintCall(el('byHeroNum'), el('byHeroWho'), el('byHeroRoom'), el('byHeroVoice'), item, pulse);
      if (el('byK3')) el('byK3').textContent = faN(item.no);
    }
    if (reduce){ heroCall(CALLS[2], false); EVENTS.forEach(addEv); return; }
    heroCall(CALLS[1], false);
    loop(async function(){
      var feed = el('byFeed'); if (feed) feed.innerHTML = '';
      for (var i = 0; i < EVENTS.length; i++){
        addEv(EVENTS[i]);
        heroCall(CALLS[i % CALLS.length], i >= 2);
        await sleep(1700);
      }
      await sleep(1200);
    });
  });
  function renderNext(active){
    var box = el('byQNext'); if (!box) return;
    box.innerHTML = '';
    [10,11,12,13,14].forEach(function(n){
      var s = document.createElement('span');
      s.className = 'by-chip' + (n === active ? ' is-on' : '');
      s.textContent = 'شماره ' + faN(n);
      box.appendChild(s);
    });
  }
  onVisible(el('byQ'), function(){
    if (reduce){ paintCall(el('byQNum'), el('byQWho'), el('byQRoom'), el('byQVoice'), CALLS[2], false); renderNext(12); return; }
    loop(async function(){
      for (var i = 0; i < CALLS.length; i++){
        renderNext(CALLS[i].no);
        paintCall(el('byQNum'), el('byQWho'), el('byQRoom'), el('byQVoice'), CALLS[i], true);
        await sleep(2200);
        if (el('byQVoice')) el('byQVoice').classList.remove('is-show');
        await sleep(400);
      }
    });
  });
  function pathTo(n){
    var root = el('byPath'); if (!root) return;
    [].forEach.call(root.querySelectorAll('.st'), function(s, i){
      s.classList.toggle('is-on', i === n);
      s.classList.toggle('is-done', i < n);
    });
  }
  onVisible(el('byPath'), function(){
    if (reduce){ pathTo(5); return; }
    loop(async function(){
      for (var i = 0; i <= 5; i++){ pathTo(i); await sleep(900); }
      [].forEach.call(el('byPath').querySelectorAll('.st'), function(s){ s.classList.add('is-done'); s.classList.remove('is-on'); });
      el('byPath').querySelectorAll('.st')[5].classList.add('is-on');
      await sleep(2200);
    });
  });
  async function typeText(node, text){
    if (!node) return;
    if (reduce){ node.textContent = text; return; }
    node.textContent = '';
    for (var i = 1; i <= text.length; i++){
      node.innerHTML = text.slice(0, i) + '<span class="caret"></span>';
      await sleep(28);
    }
    node.textContent = text;
  }
  var SMS = 'خانم رضایی عزیز، نوبت شما در کلینیک نمونه رز برای ۱۴۰۵/۰۸/۲۶ ساعت ۱۷:۱۵ ثبت شد. شماره نوبت شما: ۱۲';
  onVisible(el('byRsv'), function(){
    loop(async function(){
      var steps = el('byRsvSteps'), q = el('byRsvQ'), hit = el('byRsvHit'), opts = el('byRsvOpts'), sum = el('byRsvSum');
      if (q) q.textContent = '';
      if (hit) hit.classList.remove('is-show');
      if (opts) opts.innerHTML = '';
      if (sum){ sum.classList.remove('is-show'); sum.textContent = ''; }
      setSteps(steps, 0);
      var typed = 'رضایی';
      if (q){
        if (reduce) q.textContent = typed;
        else { for (var i = 1; i <= typed.length; i++){ q.textContent = typed.slice(0, i); await sleep(160); } }
      }
      await sleep(400);
      if (hit) hit.classList.add('is-show');
      await sleep(700);
      setSteps(steps, 1);
      if (opts){
        [['لیزر بانوان · تیتانیوم · صورت', true],['پزشک: دکتر نمونه', true],['کانال: حضوری', false]].forEach(function(row){
          var d = document.createElement('span'); d.className = 'by-opt' + (row[1] ? ' is-on' : ''); d.textContent = row[0]; opts.appendChild(d);
        });
      }
      await sleep(800);
      setSteps(steps, 2);
      if (opts){
        opts.innerHTML = '';
        ['۱۴۰۵/۰۸/۲۶ ۱۶:۳۰','۱۴۰۵/۰۸/۲۶ ۱۷:۱۵','۱۴۰۵/۰۸/۲۶ ۱۸:۰۰'].forEach(function(t, i){
          var d = document.createElement('span'); d.className = 'by-opt' + (i === 1 ? ' is-on' : ''); d.textContent = t; opts.appendChild(d);
        });
      }
      await sleep(900);
      setSteps(steps, 3);
      if (sum){
        sum.innerHTML = 'تأیید رزرو: خانم سارا رضایی · ۱۴۰۵/۰۸/۲۶ ساعت ۱۷:۱۵ · کانال حضوری<br>شماره نوبت صادر شد: <b>' + faN(12) + '</b> · پیامک ارسال شد';
        sum.classList.add('is-show');
      }
      await sleep(3200);
    });
  });
  onVisible(el('bySms'), function(){ loop(async function(){ await typeText(el('bySmsText'), SMS); await sleep(2800); }); });
  onVisible(el('bySet'), function(){
    loop(async function(){
      var steps = el('bySetSteps'), pay = el('bySetPay'), next = el('bySetNext'), amt = el('bySetAmt');
      if (amt) amt.textContent = '—';
      if (pay) pay.innerHTML = '';
      if (next){ next.classList.remove('is-show'); next.textContent = 'نوبت بعدی هنوز ثبت نشده است.'; }
      setSteps(steps, 0);
      await sleep(700);
      setSteps(steps, 1);
      await count(amt, 12500000, 900);
      if (amt) amt.textContent = fa(12500000) + ' ریال';
      if (pay) pay.innerHTML = '<span class="cd-chip is-ok">تسویه شد</span><span class="cd-chip">روش: کارت (نمونه)</span>';
      await sleep(1000);
      setSteps(steps, 2);
      if (next){ next.innerHTML = 'نوبت بعدی ثبت شد: <b>۱۴۰۵/۰۹/۱۰ ساعت ۱۷:۰۰</b> · کانال حضوری'; next.classList.add('is-show'); }
      await sleep(900);
      setSteps(steps, 3);
      if (next) next.innerHTML += '<br>پیامک تاریخ و ساعت نوبت بعدی ارسال شد.';
      await sleep(3200);
    });
  });
  var form = el('byDemo');
  if (form){
    form.addEventListener('submit', function(e){
      var phone = form.phone.value || '';
      var latin = phone.replace(/[۰-۹]/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }).replace(/[^\d]/g,'');
      if (!/^0?9\d{9}$/.test(latin)){
        e.preventDefault();
        form.phone.setCustomValidity('شماره موبایل ایرانی را وارد کنید');
        form.phone.reportValidity();
        return;
      }
      form.phone.setCustomValidity('');
      if (latin.length === 10) latin = '0' + latin;
      form.phone.value = latin;
      var clinic = (el('byClinic') && el('byClinic').value) || '';
      var city = (el('byCity') && el('byCity').value) || '';
      el('byBody').value = 'درخواست دموی حساب مطب، نسخهٔ کلینیک زیبایی. کلینیک: ' + clinic + ' · شهر: ' + city;
    });
  }
})();
</script>
