@php
  $c = \App\Support\StudioChat::widget();
@endphp
@if(!empty($c['enabled']))
<link rel="stylesheet" href="{{ asset('css/studio-chat.css') }}?v=6">
<div class="fc {{ ($c['corner'] ?? 'right') === 'left' ? 'is-left' : '' }}" id="fcChat">
  <button class="fc-fab" type="button" id="fcOpen" aria-expanded="false" aria-controls="fcPanel">
    <img src="{{ asset($c['avatar']) }}" alt="" width="54" height="54">
    <span>{{ $c['button'] }}</span>
  </button>
  <section class="fc-panel" id="fcPanel" hidden>
    <header class="fc-head">
      <img src="{{ asset($c['avatar']) }}" alt="" width="42" height="42">
      <div>
        <b>{{ $c['title'] }}</b>
        <small>{{ $c['subtitle'] }}</small>
      </div>
      <button type="button" id="fcClose" aria-label="بستن">×</button>
    </header>
    <div class="fc-log" id="fcLog" aria-live="polite"></div>
    <div class="fc-suggest" id="fcSuggest"></div>
    <form class="fc-form" id="fcForm">
      <input id="fcInput" name="message" maxlength="500" placeholder="{{ $c['placeholder'] }}" autocomplete="off">
      <button type="submit">ارسال</button>
    </form>
    @if(!empty($c['show_sales']) || !empty($c['show_support']) || !empty($c['consult_phone']))
      <footer class="fc-human {{ (empty($c['show_sales']) || empty($c['show_support'])) ? 'is-one' : '' }}">
        @if(!empty($c['show_sales']))
          <a href="{{ $c['sales_url'] }}" @if(str_starts_with($c['sales_url'], 'http')) target="_blank" rel="noopener" @endif>{{ $c['sales_label'] }}</a>
        @endif
        @if(!empty($c['show_support']))
          <a href="{{ $c['support_url'] }}" @if(str_starts_with($c['support_url'], 'http')) target="_blank" rel="noopener" @endif>{{ $c['support_label'] }}</a>
        @endif
        @if(!empty($c['consult_phone']))
          <a class="fc-call" href="tel:{{ $c['consult_phone'] }}">{{ $c['consult_label'] }} {{ \App\Support\StudioChat::persianDigits($c['consult_phone']) }}</a>
        @endif
      </footer>
    @endif
  </section>
</div>
<script>
(function(){
  var root=document.getElementById('fcChat');
  if(!root) return;
  var panel=document.getElementById('fcPanel');
  var log=document.getElementById('fcLog');
  var form=document.getElementById('fcForm');
  var input=document.getElementById('fcInput');
  var suggest=document.getElementById('fcSuggest');
  var token=document.querySelector('meta[name="csrf-token"]');
  var suggestions=@json($c['suggestions']);
  var greeting=@json($c['greeting']);
  var humans={
    sales: @json(!empty($c['show_sales']) ? ['label' => $c['sales_label'], 'url' => $c['sales_url']] : null),
    support: @json(!empty($c['show_support']) ? ['label' => $c['support_label'], 'url' => $c['support_url']] : null),
    call: @json(!empty($c['consult_phone']) ? ['label' => $c['consult_label'].' '.\App\Support\StudioChat::persianDigits($c['consult_phone']), 'url' => $c['consult_url']] : null)
  };
  function esc(s){return String(s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});}
  function bubble(text, links, who){
    var el=document.createElement('div');
    el.className='fc-msg fc-msg--'+who;
    var html='<p>'+esc(text)+'</p>';
    if(links && links.length){
      html+='<div class="fc-links">';
      links.forEach(function(l){
        var url=String(l.url||'');
        if(url.charAt(0)!=='/' && url.indexOf('https://')!==0 && url.indexOf('tel:')!==0) return;
        html+='<a href="'+esc(url)+'">'+esc(l.label||'')+'</a>';
      });
      html+='</div>';
    }
    el.innerHTML=html;
    log.appendChild(el);
    log.scrollTop=log.scrollHeight;
  }
  function chips(){
    suggest.innerHTML='';
    suggestions.forEach(function(q){
      var b=document.createElement('button');
      b.type='button';
      b.textContent=q;
      b.addEventListener('click', function(){ ask(q); });
      suggest.appendChild(b);
    });
  }
  function ask(text){
    text=(text||'').trim();
    if(!text) return;
    bubble(text, [], 'user');
    input.value='';
    fetch(@json(url('/chat/ask')), {
      method:'POST',
      headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': token?token.content:''},
      body:JSON.stringify({message:text})
    }).then(function(r){return r.json();}).then(function(data){
      var links=(data.links||[]).slice();
      var hand=humans[data.handoff]||null;
      root.querySelectorAll('.fc-human a.is-hot').forEach(function(a){ a.classList.remove('is-hot'); });
      if(hand && hand.url){
        var seen=links.some(function(l){ return l.url===hand.url; });
        if(!seen) links.push(hand);
        var footer=root.querySelector('.fc-human a[href="'+hand.url+'"]');
        if(footer) footer.classList.add('is-hot');
      }
      if(data.handoff==='sales' && humans.call && humans.call.url){
        var hasCall=links.some(function(l){ return l.url===humans.call.url; });
        if(!hasCall) links.push(humans.call);
        var callBtn=root.querySelector('.fc-call');
        if(callBtn) callBtn.classList.add('is-hot');
      }
      bubble(data.text||'', links, 'bot');
    }).catch(function(){
      bubble('ارتباط برقرار نشد. از دکمه‌های فروش یا پشتیبانی استفاده کنید.', [], 'bot');
    });
  }
  function open(on){
    panel.hidden=!on;
    root.classList.toggle('is-open', !!on);
    document.getElementById('fcOpen').setAttribute('aria-expanded', on?'true':'false');
    if(on && !log.childElementCount){ bubble(greeting, [], 'bot'); chips(); }
    if(on && window.matchMedia('(min-width:701px)').matches) input.focus();
  }
  document.getElementById('fcOpen').addEventListener('click', function(){ open(panel.hidden); });
  document.getElementById('fcClose').addEventListener('click', function(){ open(false); });
  form.addEventListener('submit', function(e){ e.preventDefault(); ask(input.value); });
})();
</script>
@endif
