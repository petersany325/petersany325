@extends('layouts.staff')
@section('title','فروش قطعه')
@section('content')
@php $nf = fn($n)=>number_format((int)$n); @endphp
<h1 style="margin:0 0 .35rem">فروش قطعه</h1>
<p class="muted" style="margin:0 0 1rem">
  قیمت خرید و فروش را مشخص کنید · سیستم سود را حساب می‌کند ·
  @if($staff) کمیسیون شما <b>{{ $staff->commission_rate }}٪ از سود</b> خودکار به کیف پول واریز می‌شود @endif
</p>

<form method="post" action="{{ url('/staff/sell') }}" class="panel" style="padding:1.1rem" id="sellForm">@csrf
  <div class="form-2">
    <label>انتخاب مشتری
      <select name="user_id" id="userSel">
        <option value="">— مشتری حضوری / جدید —</option>
        @foreach($customers as $c)
          <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-email="{{ $c->email }}" data-phone="{{ $c->phone }}">{{ $c->name }} — {{ $c->phone ?: $c->email }}</option>
        @endforeach
      </select>
    </label>
    <label>نام مشتری<input name="customer_name" id="cName" required></label>
    <label>موبایل<input name="customer_phone" id="cPhone"></label>
    <label>ایمیل<input type="email" name="customer_email" id="cEmail"></label>
  </div>

  <div class="row" style="justify-content:space-between;align-items:center;margin:1rem 0 .5rem">
    <h3 style="margin:0;font-size:1rem">اقلام فروش</h3>
    <button type="button" class="btn btn-outline btn-sm" id="addRow">+ کالا</button>
  </div>
  <div style="overflow:auto">
    <table class="table" id="itemsTable">
      <thead><tr><th>محصول</th><th>تعداد</th><th>خرید</th><th>فروش</th><th>سود</th><th></th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="staff-grid" style="margin-top:.8rem">
    <div class="staff-stat"><span class="muted">فروش</span><strong id="sumSell">0</strong></div>
    <div class="staff-stat"><span class="muted">بهای خرید</span><strong id="sumBuy">0</strong></div>
    <div class="staff-stat" style="border-color:#86efac"><span class="muted">سود</span><strong id="sumProfit" style="color:#047857">0</strong></div>
    <div class="staff-stat" style="background:#f0fdf4"><span class="muted">کمیسیون شما {{ $staff->commission_rate ?? 0 }}٪</span><strong id="sumComm" style="color:#047857">0</strong></div>
  </div>
  <label style="margin-top:.8rem">یادداشت<textarea name="notes" rows="2"></textarea></label>
  <button class="btn btn-primary" type="submit" style="margin-top:.8rem">ثبت فروش + سند حسابداری</button>
</form>

<div class="panel" style="margin-top:1rem">
  <h3 style="margin-top:0">فروش‌های ۳۰ روز اخیر شما</h3>
  <table class="table">
    <thead><tr><th>شماره</th><th>مشتری</th><th>فروش</th><th>سود</th><th>کمیسیون</th><th>وضعیت سند</th></tr></thead>
    <tbody>
    @forelse($myDocs['rows'] as $d)
      <tr>
        <td><code dir="ltr">{{ $d->doc_number }}</code></td>
        <td>{{ $d->customer_name }}</td>
        <td>{{ $nf($d->subtotal) }}</td>
        <td style="color:#047857">{{ $nf($d->profit_total) }}</td>
        <td>{{ $nf($d->commission_amount) }}</td>
        <td>{{ !empty($d->ledger_posted) ? 'ثبت‌شده' : '—' }}</td>
      </tr>
    @empty
      <tr><td colspan="6" class="muted" style="text-align:center;padding:1rem">هنوز فروشی ثبت نکرده‌اید.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<template id="rowTpl">
  <tr>
    <td>
      <select class="pSel" name="items[__i__][product_id]">
        <option value="">انتخاب محصول</option>
        @foreach($products as $p)
          <option value="{{ $p->id }}" data-name="{{ $p->name }}" data-sku="{{ $p->sku }}" data-cost="{{ (int)($p->cost_price??0) }}" data-price="{{ (int)$p->price }}">{{ $p->name }}</option>
        @endforeach
      </select>
      <input type="text" name="items[__i__][product_name]" class="pName" required placeholder="نام" style="margin-top:.3rem">
      <input type="hidden" name="items[__i__][sku]" class="pSku">
    </td>
    <td><input type="number" class="qty" name="items[__i__][quantity]" value="1" min="1" style="width:70px"></td>
    <td><input type="number" class="cost" name="items[__i__][unit_cost]" value="0" min="0" style="width:100px"></td>
    <td><input type="number" class="price" name="items[__i__][unit_price]" value="0" min="0" style="width:100px"></td>
    <td class="lineProfit" style="font-weight:700;color:#047857">0</td>
    <td><button type="button" class="btn btn-outline btn-sm rm">حذف</button></td>
  </tr>
</template>
<script>
(function(){
  var i=0, rate={{ (float)($staff->commission_rate ?? 0) }}, body=document.querySelector('#itemsTable tbody');
  function nf(n){return (n||0).toLocaleString('fa-IR');}
  function add(){ body.insertAdjacentHTML('beforeend', document.getElementById('rowTpl').innerHTML.replaceAll('__i__',String(i++))); bind(body.lastElementChild); recalc(); }
  function bind(tr){
    tr.querySelector('.pSel').addEventListener('change', function(){
      var o=this.options[this.selectedIndex]; if(!o.value) return;
      tr.querySelector('.pName').value=o.dataset.name||'';
      tr.querySelector('.pSku').value=o.dataset.sku||'';
      tr.querySelector('.cost').value=o.dataset.cost||0;
      tr.querySelector('.price').value=o.dataset.price||0; recalc();
    });
    tr.querySelectorAll('.qty,.cost,.price').forEach(function(el){ el.addEventListener('input', recalc); });
    tr.querySelector('.rm').addEventListener('click', function(){ tr.remove(); recalc(); });
  }
  function recalc(){
    var sell=0,buy=0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(function(tr){
      var q=+tr.querySelector('.qty').value||0,c=+tr.querySelector('.cost').value||0,p=+tr.querySelector('.price').value||0;
      sell+=p*q; buy+=c*q; tr.querySelector('.lineProfit').textContent=nf((p-c)*q);
    });
    var profit=sell-buy, comm=Math.round(Math.max(0,profit)*rate/100);
    document.getElementById('sumSell').textContent=nf(sell);
    document.getElementById('sumBuy').textContent=nf(buy);
    document.getElementById('sumProfit').textContent=nf(profit);
    document.getElementById('sumComm').textContent=nf(comm);
  }
  document.getElementById('addRow').addEventListener('click', add);
  document.getElementById('userSel').addEventListener('change', function(){
    var o=this.options[this.selectedIndex]; if(!o.value) return;
    document.getElementById('cName').value=o.dataset.name||'';
    document.getElementById('cEmail').value=o.dataset.email||'';
    document.getElementById('cPhone').value=o.dataset.phone||'';
  });
  add();
})();
</script>
@endsection
