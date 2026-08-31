@extends('layouts.admin')
@section('title','ثبت سریال — '.$product->name)
@section('content')
@php $s = $s ?? []; $companies = $companies ?? collect(); @endphp
<style>
.ps-hero{background:linear-gradient(135deg,#1a1d23 0%,#2a3140 60%,#3d2318 100%);color:#fff;border-radius:18px;padding:1.2rem 1.4rem;margin-bottom:1rem}
.ps-hero h1{margin:0;font-size:1.25rem}.ps-hero p{margin:.4rem 0 0;opacity:.85;font-size:.9rem}
.ps-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;margin:1rem 0}
.ps-stat{background:#fff;border:1px solid var(--line);border-radius:14px;padding:.85rem;text-align:center}
.ps-stat strong{display:block;font-size:1.4rem}
.ps-steps{display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap}
.ps-step{flex:1;min-width:140px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:.7rem .85rem;display:flex;gap:.65rem;align-items:center;opacity:.55}
.ps-step.on,.ps-step.done{opacity:1}
.ps-step.on{border-color:#f0b09d;box-shadow:0 0 0 3px rgba(240,120,80,.12)}
.ps-step.done{border-color:#86efac;background:#f0fdf4}
.ps-num{width:28px;height:28px;border-radius:50%;background:#1a1d23;color:#fff;display:grid;place-items:center;font-weight:800;font-size:.85rem;flex-shrink:0}
.ps-step.on .ps-num{background:var(--brand)}
.ps-step.done .ps-num{background:#16a34a}
.ps-step strong{display:block;font-size:.9rem}
.ps-step span{font-size:.75rem;color:#6b7280}
.ps-pane{display:none}.ps-pane.on{display:block}
.ps-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:.6rem}
.ps-card{border:1.5px solid var(--line);border-radius:14px;padding:.9rem;background:#fff;cursor:pointer;text-align:right}
.ps-card:hover{border-color:#f0b09d}
.ps-card.sel{border-color:var(--brand);background:#fff7f4;box-shadow:0 0 0 3px rgba(232,93,59,.12)}
.ps-card strong{display:block}.ps-card small{color:#6b7280}
.ps-chip{display:inline-block;background:#1a1d23;color:#fff;border-radius:999px;padding:.3rem .8rem;font-size:.82rem;font-weight:700;margin-bottom:.8rem}
.ps-tabs{display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:.9rem}
.ps-tab{border:1px solid var(--line);background:#fff;border-radius:999px;padding:.45rem .9rem;cursor:pointer;font-weight:700;font-size:.85rem}
.ps-tab.on{background:#fff1ec;border-color:#f0b09d;color:var(--brand)}
.ps-entry{display:none}.ps-entry.on{display:block}
.scan-console{display:grid;grid-template-columns:1fr auto;gap:.7rem;align-items:end;padding:1rem;border:1px solid #cbd5e1;border-radius:16px;background:linear-gradient(135deg,#f8fafc,#fff)}
.scan-console input{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:1.18rem;letter-spacing:.04em;padding:.85rem!important}
.scan-status{margin-top:.7rem;padding:.7rem .85rem;border-radius:11px;background:#f1f5f9;color:#475569;font-weight:700}
.scan-status.ok{background:#ecfdf5;color:#047857}.scan-status.err{background:#fff1f2;color:#be123c}
.scan-counters{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.65rem}.scan-counter{background:#fff;border:1px solid var(--line);border-radius:10px;padding:.45rem .7rem;font-size:.8rem}
@media(max-width:700px){.ps-stats{grid-template-columns:1fr}}
</style>

<div class="ps-hero">
  <div class="row" style="justify-content:space-between;gap:.8rem;flex-wrap:wrap">
    <div>
      <h1>ثبت سریال محصول</h1>
      <p>{{ $product->name }} · SKU: {{ $product->sku ?: '—' }}</p>
    </div>
    <div class="row" style="gap:.4rem">
      <a class="btn btn-outline btn-sm" style="color:#fff;border-color:#6b7280" href="{{ route('admin.products.edit',$product) }}">ویرایش محصول</a>
      <a class="btn btn-outline btn-sm" style="color:#fff;border-color:#6b7280" href="{{ url('/admin/warranty-companies') }}">شرکت‌های گارانتی</a>
      <a class="btn btn-outline btn-sm" style="color:#fff;border-color:#6b7280" href="{{ url('/admin/serial-sales?tab=add') }}">ثبت سریال عمومی</a>
    </div>
  </div>
</div>

<div class="ps-stats">
  <div class="ps-stat"><span class="muted">کل</span><strong>{{ $stats['total'] }}</strong></div>
  <div class="ps-stat"><span class="muted">موجود</span><strong style="color:#0f7a4b">{{ $stats['available'] }}</strong></div>
  <div class="ps-stat"><span class="muted">فروخته</span><strong style="color:#a32012">{{ $stats['sold'] }}</strong></div>
</div>

<div class="ps-steps">
  <div class="ps-step on" data-step-label="1"><div class="ps-num">۱</div><div><strong>شرکت گارانتی</strong><span>از لیست ثبت‌شده</span></div></div>
  <div class="ps-step" data-step-label="2"><div class="ps-num">۲</div><div><strong>محصول</strong><span>{{ \Illuminate\Support\Str::limit($product->name, 28) }}</span></div></div>
  <div class="ps-step" data-step-label="3"><div class="ps-num">۳</div><div><strong>ورود سریال</strong><span>دستی / بارکد / اکسل</span></div></div>
</div>

{{-- Step 1 --}}
<div class="panel ps-pane on" data-step="1">
  <h3 style="margin-top:0">کدام شرکت گارانتی؟</h3>
  @if($companies->isEmpty())
    <p class="muted">هنوز شرکت گارانتی ثبت نشده. <a href="{{ url('/admin/warranty-companies') }}">ثبت شرکت</a></p>
  @else
    <div class="ps-cards" id="psCompanyCards">
      @foreach($companies as $c)
        <button type="button" class="ps-card" data-company="{{ $c->name }}" data-months="{{ (int)$c->default_months }}">
          <strong>{{ $c->name }}</strong>
          <small>{{ (int)$c->default_months }} ماه</small>
        </button>
      @endforeach
    </div>
  @endif
  <div style="margin-top:1rem">
    <button type="button" class="btn btn-primary" id="psNext" disabled>ادامه → ورود سریال</button>
  </div>
</div>

{{-- Step 3 (product is fixed as step 2) --}}
<div class="panel ps-pane" data-step="3">
  <span class="ps-chip" id="psChip">گارانتی: —</span>
  <div class="ps-tabs">
    <button type="button" class="ps-tab on" data-pane="barcode">بارکدخوان</button>
    <button type="button" class="ps-tab" data-pane="manual">دستی / گروهی</button>
    <button type="button" class="ps-tab" data-pane="excel">CSV / TXT</button>
    <button type="button" class="ps-tab" data-pane="list">لیست سریال‌ها</button>
  </div>

  <div class="ps-entry on" data-pane="barcode">
    <h3 style="margin-top:0">اسکن بارکد</h3>
    <p class="muted">نشانگر را در کادر بگذارید و با بارکدخوان اسکن کنید (Enter = ثبت).</p>
    <form method="post" action="{{ url('/admin/products/'.$product->id.'/serials') }}" id="barcodeForm">@csrf
      <input type="hidden" name="source" value="barcode">
      <input type="hidden" name="warranty_company" class="ps-f-company">
      <input type="hidden" name="company_warranty_months" class="ps-f-months" value="12">
      <input type="hidden" name="has_company_warranty" value="1">
      <div class="scan-console">
        <label style="margin:0">سریال<input name="serial" id="barcodeInput" required autocomplete="off" placeholder="بارکد را اسکن کنید…"></label>
        <button class="btn btn-primary" type="submit" id="scanSubmit">ثبت</button>
      </div>
      <div class="scan-status" id="scanStatus">بارکدخوان آماده است؛ اسکن کنید.</div>
      <div class="scan-counters"><span class="scan-counter">موفق این نوبت: <b id="scanOk">0</b></span><span class="scan-counter">تکراری/نامعتبر: <b id="scanSkip">0</b></span><span class="scan-counter">موجودی سریالی: <b id="scanAvailable">{{ $stats['available'] }}</b></span></div>
    </form>
  </div>

  <div class="ps-entry" data-pane="manual">
    <h3 style="margin-top:0">ورود دستی یا گروهی</h3>
    <form method="post" action="{{ url('/admin/products/'.$product->id.'/serials') }}">@csrf
      <input type="hidden" name="source" value="manual">
      <input type="hidden" name="warranty_company" class="ps-f-company">
      <input type="hidden" name="company_warranty_months" class="ps-f-months" value="12">
      <input type="hidden" name="has_company_warranty" value="1">
      <label>یک سریال<input name="serial" placeholder="اختیاری اگر لیست گروهی دارید"></label>
      <label>لیست گروهی (هر خط یا با ویرگول)<textarea name="bulk" rows="8" placeholder="SN001&#10;SN002&#10;SN003"></textarea></label>
      <button class="btn btn-primary" type="submit">ثبت سریال‌ها</button>
    </form>
  </div>

  <div class="ps-entry" data-pane="excel">
    <h3 style="margin-top:0">ورود از CSV / TXT</h3>
    <p class="muted">ستون اول = سریال. فایل UTF-8 باشد؛ ردیف اول می‌تواند عنوان serial باشد.</p>
    <form method="post" action="{{ url('/admin/products/'.$product->id.'/serials/import') }}" enctype="multipart/form-data">@csrf
      <input type="hidden" name="warranty_company" class="ps-f-company">
      <input type="hidden" name="company_warranty_months" class="ps-f-months" value="12">
      <input type="hidden" name="has_company_warranty" value="1">
      <label>فایل<input type="file" name="file" accept=".csv,.txt,text/csv,text/plain" required></label>
      <button class="btn btn-primary" type="submit">وارد کردن</button>
    </form>
  </div>

  <div class="ps-entry" data-pane="list" style="padding:0;overflow:auto">
    <div style="padding:.75rem"><input type="search" id="serialSearch" placeholder="جستجو در سریال، وضعیت یا گارانتی…" style="width:100%"></div>
    <table class="table" id="serialTable">
      <thead><tr><th>سریال</th><th>وضعیت</th><th>گارانتی</th><th>منبع</th><th></th></tr></thead>
      <tbody>
      @forelse($serials as $row)
        <tr>
          <td><code>{{ $row->serial }}</code></td>
          <td>{{ ($row->status ?? '') === 'available' ? 'موجود' : (($row->status ?? '') === 'sold' ? 'فروخته‌شده' : ($row->status ?? '—')) }}</td>
          <td>{{ $row->warranty_company ?: '—' }} @if($row->company_warranty_months)· {{ $row->company_warranty_months }}م@endif</td>
          <td>{{ $row->source ?? '—' }}</td>
          <td>
            @if($row->status !== 'sold')
            <form method="post" action="{{ url('/admin/products/'.$product->id.'/serials/'.$row->id) }}" onsubmit="return confirm('حذف؟')">@csrf @method('DELETE')
              <button class="btn btn-outline btn-sm" type="submit">حذف</button>
            </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted" style="text-align:center;padding:1.5rem">هنوز سریالی ثبت نشده.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div style="margin-top:1rem">
    <button type="button" class="btn btn-outline" id="psBack">← تغییر گارانتی</button>
  </div>
</div>

<script>
(function(){
  var company='', months=12;
  var steps=document.querySelectorAll('.ps-step');
  var panes=document.querySelectorAll('.ps-pane');
  function go(n){
    panes.forEach(function(p){ p.classList.toggle('on', p.getAttribute('data-step')==String(n)); });
    steps.forEach(function(s){
      var sn=parseInt(s.getAttribute('data-step-label'),10);
      // step 2 is auto-done (product fixed)
      s.classList.toggle('on', sn===n || (n===3 && sn===2));
      s.classList.toggle('done', sn<n || (n===3 && sn===2 && sn!==n));
      if(n===3 && sn===2){ s.classList.add('done'); s.classList.remove('on'); }
      if(n===3 && sn===3){ s.classList.add('on'); }
      if(n===1){ s.classList.remove('done'); if(sn===1) s.classList.add('on'); else s.classList.remove('on'); }
    });
    if(n===3){
      document.getElementById('psChip').textContent='گارانتی: '+company+' · محصول: '+@json($product->name);
      setTimeout(function(){ document.getElementById('barcodeInput')?.focus(); }, 60);
    }
  }
  function sync(){
    document.querySelectorAll('.ps-f-company').forEach(function(el){ el.value=company; });
    document.querySelectorAll('.ps-f-months').forEach(function(el){ el.value=months; });
  }
  document.querySelectorAll('#psCompanyCards .ps-card').forEach(function(card){
    card.addEventListener('click', function(){
      document.querySelectorAll('#psCompanyCards .ps-card').forEach(function(c){ c.classList.remove('sel'); });
      card.classList.add('sel');
      company=card.getAttribute('data-company')||'';
      months=parseInt(card.getAttribute('data-months')||'12',10)||12;
      sync();
      document.getElementById('psNext').disabled=!company;
    });
  });
  document.getElementById('psNext')?.addEventListener('click', function(){ if(company){ sync(); go(3); } });
  document.getElementById('psBack')?.addEventListener('click', function(){ go(1); });

  document.querySelectorAll('.ps-tab').forEach(function(tab){
    tab.addEventListener('click', function(){
      document.querySelectorAll('.ps-tab').forEach(function(t){ t.classList.remove('on'); });
      document.querySelectorAll('.ps-entry').forEach(function(p){ p.classList.remove('on'); });
      tab.classList.add('on');
      document.querySelector('.ps-entry[data-pane="'+tab.dataset.pane+'"]')?.classList.add('on');
      if(tab.dataset.pane==='barcode') document.getElementById('barcodeInput')?.focus();
    });
  });
  var scanOk=0, scanSkip=0, scanBusy=false;
  function scanTone(ok){
    try{ var ctx=new (window.AudioContext||window.webkitAudioContext)(),osc=ctx.createOscillator(),gain=ctx.createGain(); osc.frequency.value=ok?880:220; gain.gain.value=.05; osc.connect(gain);gain.connect(ctx.destination);osc.start();osc.stop(ctx.currentTime+.1); }catch(e){}
  }
  document.getElementById('barcodeForm')?.addEventListener('submit', async function(e){
    e.preventDefault(); if(scanBusy) return;
    var form=this,input=document.getElementById('barcodeInput'),status=document.getElementById('scanStatus'),button=document.getElementById('scanSubmit');
    if(!input?.value.trim()) return;
    scanBusy=true; button.disabled=true; status.className='scan-status'; status.textContent='در حال ثبت…';
    try{
      var res=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
      var json=await res.json();
      if(!res.ok || !json.ok) throw new Error(json.message || Object.values(json.errors||{})[0]?.[0] || 'ثبت انجام نشد');
      scanOk+=Number(json.added||0); scanSkip+=Number(json.skipped||0);
      document.getElementById('scanOk').textContent=scanOk; document.getElementById('scanSkip').textContent=scanSkip; document.getElementById('scanAvailable').textContent=json.available;
      status.className='scan-status '+(json.added>0?'ok':'err'); status.textContent=json.message; scanTone(json.added>0);
      input.value='';
    }catch(error){ status.className='scan-status err'; status.textContent=error.message||'خطا در ثبت سریال'; scanSkip++; document.getElementById('scanSkip').textContent=scanSkip; scanTone(false); }
    finally{ scanBusy=false;button.disabled=false;input?.focus();input?.select(); }
  });
  document.getElementById('serialSearch')?.addEventListener('input', function(){
    var q=(this.value||'').trim().toLocaleLowerCase('fa');
    document.querySelectorAll('#serialTable tbody tr').forEach(function(row){
      row.style.display=!q || row.textContent.toLocaleLowerCase('fa').includes(q) ? '' : 'none';
    });
  });

  // پیش‌انتخاب شرکت گارانتی ذخیره‌شده روی محصول
  var preCompany = @json($product->warranty_company ?? '');
  var preMonths = {{ (int) ($product->warranty_months ?? 0) }};
  if(preCompany){
    var match = null;
    document.querySelectorAll('#psCompanyCards .ps-card').forEach(function(card){
      if(card.getAttribute('data-company') === preCompany) match = card;
    });
    if(match){
      match.click();
      if(preMonths > 0){ months = preMonths; sync(); }
      go(3);
    }
  }
})();
</script>
@endsection
