@extends('accounting::layouts.acc')
@section('title',$types[$type] ?? 'سند جدید')
@section('content')
@php
  $isVoucher = $type === 'voucher';
  $isBuy = $type === 'purchase';
  $isSale = in_array($type, ['sale','proforma'], true);
@endphp
<div class="top">
  <div>
    <h1>{{ $types[$type] ?? 'سند جدید' }}</h1>
    <p>
      شماره پیشنهادی: <strong>{{ $number }}</strong>
      @if($isVoucher) — سند دو طرفه: جمع بدهکار باید با بستانکار برابر باشد
      @elseif($isBuy) — کد کالا، واحد، تخفیف٪ و مالیات٪ روی هر سطر (استاندارد سپیدار / حسابفا)
      @elseif($isSale) — هم‌خوان با فروشگاه: صدور، موجودی سایت و سریال را کم می‌کند
      @endif
    </p>
  </div>
</div>
<form method="post" action="{{ url('/admin/accounting/docs') }}" class="form" id="acc-doc-form">
  @csrf
  <input type="hidden" name="type" value="{{ $type }}">
  <div class="panel"><div class="bd">
    <div class="row">
      <label>تاریخ سند<input type="date" name="doc_date" value="{{ now()->toDateString() }}"></label>
      <label>نوع سند<input value="{{ $types[$type] ?? $type }}" disabled></label>
    </div>
    <div class="row">
      <label>طرف حساب (متن)<input name="party_name" value="{{ old('party_name') }}" placeholder="{{ $isBuy ? 'تأمین‌کننده' : 'مشتری / شرح سند' }}"></label>
      <label>{{ $isBuy ? 'تأمین‌کننده ثبت‌نام‌شده' : 'مشتری ثبت‌نام‌شده' }}
        <select name="party_user_id">
          <option value="">— انتخاب از کاربران فروشگاه —</option>
          @foreach(($customers ?? []) as $c)
            <option value="{{ $c->id }}" @selected((string)old('party_user_id')===(string)$c->id)>{{ $c->name }}@if(!empty($c->mobile)) — {{ $c->mobile }}@elseif(!empty($c->phone)) — {{ $c->phone }}@endif</option>
          @endforeach
        </select>
      </label>
    </div>
    @unless($isVoucher)
    <div class="row">
      <label>انبار
        <select name="warehouse_id">
          <option value="">انبار پیش‌فرض</option>
          @foreach($warehouses as $w)
            <option value="{{ $w->id }}" @selected($w->is_default ?? false)>{{ $w->name }} ({{ $w->code }})</option>
          @endforeach
        </select>
      </label>
      @if($type==='transfer')
        <label>انبار مقصد
          <select name="warehouse_to_id">
            <option value="">—</option>
            @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
          </select>
        </label>
      @else
        <label>بانک
          <select name="bank_id">
            <option value="">—</option>
            @foreach($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
          </select>
        </label>
      @endif
    </div>
    <div class="row">
      <label>کارمند / ویزیتور
        <select name="staff_id" id="acc-staff">
          <option value="" data-rate="0" data-profit="0">—</option>
          @foreach($staff as $s)
            <option value="{{ $s->id }}" data-rate="{{ $s->commission_rate ?? 0 }}" data-profit="{{ $s->profit_rate ?? 0 }}" data-kind="{{ $s->kind ?? 'employee' }}">
              {{ $s->name }} — {{ ($s->kind ?? '')==='visitor' ? 'ویزیتور' : 'کارمند' }}
              @if(($s->commission_rate ?? 0)>0) کمیسیون ٪{{ $s->commission_rate }}@endif
              @if(($s->profit_rate ?? 0)>0) سود ٪{{ $s->profit_rate }}@endif
            </option>
          @endforeach
        </select>
      </label>
      <label>درصد کمیسیون / سود سند<input name="commission_rate" id="acc-comm" type="number" step="0.01" min="0" max="100" value="0"></label>
    </div>
    @endunless
    @if($type==='expense')
      <div class="row">
        <label>دسته هزینه
          <select name="category_id">
            <option value="">—</option>
            @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
          </select>
        </label>
        <label>مبلغ (بدون قلم)<input name="total" value="{{ old('total',0) }}"></label>
      </div>
    @endif
  </div></div>

  @if($isVoucher)
  <div class="panel">
    <div class="hd"><strong>مواد سند (معین / تفصیل / بدهکار / بستانکار)</strong><button type="button" class="btn g" id="acc-add-line">+ ماده</button></div>
    <div class="bd" style="padding:0;overflow:auto">
      <table>
        <thead>
          <tr>
            <th>ردیف</th>
            <th>معین</th>
            <th>تفصیل</th>
            <th>شرح</th>
            <th>بدهکار</th>
            <th>بستانکار</th>
          </tr>
        </thead>
        <tbody id="acc-lines">
          @for($i=0;$i<4;$i++)
          <tr class="line">
            <td class="acc-rn">{{ $i+1 }}</td>
            <td>
              <select name="line_account_id[]">
                <option value="">— حساب معین —</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>
                @endforeach
              </select>
            </td>
            <td><input name="line_tafsil[]" placeholder="تفصیل شناور"></td>
            <td><input name="line_title[]" placeholder="شرح ماده"></td>
            <td><input name="line_debit[]" class="acc-debit" value="" inputmode="numeric"></td>
            <td><input name="line_credit[]" class="acc-credit" value="" inputmode="numeric"></td>
          </tr>
          @endfor
        </tbody>
        <tfoot>
          <tr>
            <th colspan="4">جمع کل سند</th>
            <th id="acc-debit-sum">0</th>
            <th id="acc-credit-sum">0</th>
          </tr>
        </tfoot>
      </table>
      <p id="acc-balance-msg" style="padding:.7rem 1rem;color:var(--warn);margin:0">سند نامتراز است.</p>
    </div>
  </div>
  @else
  <div class="panel">
    <div class="hd"><strong>{{ $isBuy ? 'اقلام فاکتور خرید' : 'اقلام و سریال‌ها' }}</strong><button type="button" class="btn g" id="acc-add-line">+ سطر</button></div>
    <div class="bd" id="acc-lines">
      @for($i=0;$i<3;$i++)
        <div class="line" style="display:grid;gap:.55rem;margin-bottom:.8rem;padding-bottom:.8rem;border-bottom:1px dashed var(--line)">
          <div class="row">
            <label>کالای فروشگاه
              <select name="line_product_id[]" class="acc-product">
                <option value="">— خدمت / عنوان آزاد —</option>
                @foreach(($products ?? []) as $p)
                  <option value="{{ $p->id }}"
                    data-price="{{ (int)($p->price ?? 0) }}"
                    data-cost="{{ (int)($p->cost_price ?? 0) }}"
                    data-name="{{ $p->name }}"
                    data-sku="{{ $p->sku ?? '' }}"
                    data-unit="{{ $p->unit ?? 'عدد' }}"
                    data-vat="{{ $p->vat_rate ?? 0 }}"
                    data-stock="{{ $p->stock ?? '' }}">{{ $p->name }}@if(!empty($p->sku)) ({{ $p->sku }})@endif @if(isset($p->stock)) — موجودی {{ $p->stock }}@endif</option>
                @endforeach
              </select>
            </label>
            <label>کد کالا<input name="line_sku[]" placeholder="SKU"></label>
          </div>
          <div class="row">
            <label>نام کالا<input name="line_title[]" placeholder="اگر کالا انتخاب شود پر می‌شود"></label>
            <label>واحد<input name="line_unit[]" value="عدد"></label>
          </div>
          <div class="row">
            <label>مقدار<input name="line_qty[]" value="1"></label>
            <label>نرخ (تومان)<input name="line_price[]" value="0"></label>
          </div>
          <div class="row">
            <label>تخفیف ٪<input name="line_discount_rate[]" value="0" step="0.01"></label>
            <label>مالیات ٪<input name="line_vat_rate[]" value="0" step="0.01"></label>
          </div>
          <label>بهای تمام‌شده<input name="line_cost[]" value="0"></label>
          @if($isSale || $type==='stock_in' || $isBuy)
            <label>سریال‌ها (ویرگول یا خط جدید)<textarea name="line_serials[]" rows="2" placeholder="SN1, SN2"></textarea></label>
          @endif
        </div>
      @endfor
    </div>
  </div>
  @endif

  <div class="panel"><div class="bd">
    @unless($isVoucher)
    <div class="row">
      <label>تخفیف سرسند<input name="discount" value="0"></label>
      <label>مالیات سرسند<input name="tax" value="0"></label>
    </div>
    <label>روش پرداخت<input name="payment_method" placeholder="نقد / کارت / نسیه / چک"></label>
    @endunless
    <label>یادداشت / شرح سند<textarea name="notes" rows="2"></textarea></label>
    <label class="check"><input type="checkbox" name="issue_now" value="1" checked> صدور فوری و به‌روزرسانی موجودی فروشگاه / انبار</label>
    <div class="actions">
      <button class="btn" type="submit">ذخیره سند</button>
      <a class="btn g" href="{{ url('/admin/accounting/docs') }}">انصراف</a>
    </div>
  </div></div>
</form>
@endsection
@push('scripts')
<script>
const box=document.getElementById('acc-lines');
document.getElementById('acc-add-line')?.addEventListener('click',()=>{
  const first=box?.querySelector('.line');
  if(!first) return;
  const c=first.cloneNode(true);
  c.querySelectorAll('input,textarea').forEach(el=>{
    if(el.name?.includes('qty')) el.value='1';
    else if(el.name?.includes('unit') && !el.name.includes('price')) el.value='عدد';
    else if(el.type!=='hidden') el.value='';
  });
  c.querySelectorAll('select').forEach(el=>{ el.selectedIndex=0; });
  box.appendChild(c);
  renumber();
  totals();
});
box?.addEventListener('change',(e)=>{
  const sel=e.target.closest('select.acc-product');
  if(!sel) return;
  const line=sel.closest('.line');
  const opt=sel.options[sel.selectedIndex];
  if(!line||!opt||!opt.value) return;
  const set=(name,val)=>{ const el=line.querySelector('[name="'+name+'"]'); if(el && (!el.value || el.value==='0' || el.value==='عدد')) el.value=val; };
  set('line_title[]', opt.dataset.name||'');
  set('line_price[]', opt.dataset.price||'0');
  set('line_cost[]', opt.dataset.cost||'0');
  set('line_sku[]', opt.dataset.sku||'');
  set('line_unit[]', opt.dataset.unit||'عدد');
  set('line_vat_rate[]', opt.dataset.vat||'0');
});
document.getElementById('acc-staff')?.addEventListener('change',(e)=>{
  const opt=e.target.options[e.target.selectedIndex];
  const comm=document.getElementById('acc-comm');
  if(!comm||!opt) return;
  comm.value = opt.dataset.kind==='visitor' ? (opt.dataset.profit||0) : (opt.dataset.rate||0);
});
function num(v){ return parseInt(String(v||'0').replace(/[^\d-]/g,''),10)||0; }
function totals(){
  const d=[...document.querySelectorAll('.acc-debit')].reduce((s,el)=>s+num(el.value),0);
  const c=[...document.querySelectorAll('.acc-credit')].reduce((s,el)=>s+num(el.value),0);
  const ds=document.getElementById('acc-debit-sum');
  const cs=document.getElementById('acc-credit-sum');
  if(ds) ds.textContent=d.toLocaleString('fa-IR');
  if(cs) cs.textContent=c.toLocaleString('fa-IR');
  const msg=document.getElementById('acc-balance-msg');
  if(msg){
    const ok=d>0 && d===c;
    msg.textContent=ok?'سند متراز است.':'سند نامتراز است — جمع بدهکار باید با بستانکار برابر باشد.';
    msg.style.color=ok?'var(--ok)':'var(--warn)';
  }
}
function renumber(){
  box?.querySelectorAll('.acc-rn').forEach((el,i)=>{ el.textContent=String(i+1); });
}
box?.addEventListener('input',totals);
document.getElementById('acc-doc-form')?.addEventListener('submit',(e)=>{
  const ds=document.querySelectorAll('.acc-debit');
  if(!ds.length) return;
  const d=[...ds].reduce((s,el)=>s+num(el.value),0);
  const c=[...document.querySelectorAll('.acc-credit')].reduce((s,el)=>s+num(el.value),0);
  if(d<=0 || d!==c){
    e.preventDefault();
    alert('سند دستی باید متراز باشد (جمع بدهکار = جمع بستانکار).');
  }
});
totals();
</script>
@endpush
