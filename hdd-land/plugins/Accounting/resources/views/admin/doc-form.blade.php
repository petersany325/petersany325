@extends('accounting::layouts.acc')
@section('title',$types[$type] ?? 'سند جدید')
@section('content')
<div class="top">
  <div>
    <h1>{{ $types[$type] ?? 'سند جدید' }}</h1>
    <p>شماره پیشنهادی: <strong>{{ $number }}</strong> — سریال هر قلم را در همان سطر وارد کنید</p>
  </div>
</div>
<form method="post" action="{{ route('admin.accounting.docs.store') }}" class="form">
  @csrf
  <input type="hidden" name="type" value="{{ $type }}">
  <div class="panel"><div class="bd">
    <div class="row">
      <label>تاریخ سند<input type="date" name="doc_date" value="{{ now()->toDateString() }}"></label>
      <label>طرف حساب<input name="party_name" value="{{ old('party_name') }}" placeholder="مشتری / تأمین‌کننده"></label>
    </div>
    <div class="row">
      <label>انبار
        <select name="warehouse_id">
          <option value="">—</option>
          @foreach($warehouses as $w)
            <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
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
      <label>فروشنده / کارمند
        <select name="staff_id">
          <option value="">—</option>
          @foreach($staff as $s)
            <option value="{{ $s->id }}">{{ $s->name }}@if(($s->commission_rate ?? 0)>0) (٪{{ $s->commission_rate }})@endif</option>
          @endforeach
        </select>
      </label>
      <label>درصد کمیسیون سند<input name="commission_rate" type="number" step="0.01" min="0" max="100" value="0"></label>
    </div>
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

  <div class="panel">
    <div class="hd"><strong>اقلام و سریال‌ها</strong><button type="button" class="btn g" id="acc-add-line">+ سطر</button></div>
    <div class="bd" id="acc-lines">
      @for($i=0;$i<3;$i++)
        <div class="line" style="display:grid;gap:.55rem;margin-bottom:.8rem;padding-bottom:.8rem;border-bottom:1px dashed var(--line)">
          <div class="row">
            <label>عنوان<input name="line_title[]" placeholder="نام کالا / شرح"></label>
            <label>تعداد<input name="line_qty[]" value="1"></label>
          </div>
          <div class="row">
            <label>فی (تومان)<input name="line_price[]" value="0"></label>
            <label>بهای تمام‌شده<input name="line_cost[]" value="0"></label>
          </div>
          <label>سریال‌ها (ویرگول یا خط جدید)<textarea name="line_serials[]" rows="2" placeholder="SN1, SN2"></textarea></label>
          @if($type==='voucher')
            <div class="row">
              <label>طرف
                <select name="line_side[]"><option value="debit">بدهکار</option><option value="credit">بستانکار</option></select>
              </label>
              <label>حساب
                <select name="line_account_id[]">
                  <option value="">—</option>
                  @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>@endforeach
                </select>
              </label>
            </div>
          @endif
        </div>
      @endfor
    </div>
  </div>

  <div class="panel"><div class="bd">
    <div class="row">
      <label>تخفیف<input name="discount" value="0"></label>
      <label>مالیات<input name="tax" value="0"></label>
    </div>
    <label>روش پرداخت<input name="payment_method" placeholder="نقد / کارت / کارت‌به‌کارت"></label>
    <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
    <label class="check"><input type="checkbox" name="issue_now" value="1" checked> صدور فوری و به‌روزرسانی موجودی</label>
    <div class="actions">
      <button class="btn" type="submit">ذخیره سند</button>
      <a class="btn g" href="{{ route('admin.accounting.docs') }}">انصراف</a>
    </div>
  </div></div>
</form>
@endsection
@push('scripts')
<script>
document.getElementById('acc-add-line')?.addEventListener('click',()=>{
  const box=document.getElementById('acc-lines');
  const first=box?.querySelector('.line');
  if(!first) return;
  const c=first.cloneNode(true);
  c.querySelectorAll('input,textarea').forEach(el=>{
    if(el.name?.includes('qty')) el.value='1';
    else if(el.type!=='hidden') el.value='';
  });
  box.appendChild(c);
});
</script>
@endpush
