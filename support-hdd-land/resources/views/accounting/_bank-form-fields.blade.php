@php $b = $bank ?? null; @endphp
<div class="form-grid" style="grid-template-columns:1fr 1fr;">
    <div><label>نام نمایشی</label><input type="text" name="name" value="{{ old('name', $b?->name) }}" required placeholder="مثلاً کارتخوان اصلی"></div>
    <div><label>نام بانک</label><input type="text" name="bank_name" value="{{ old('bank_name', $b?->bank_name) }}" placeholder="ملی / تجارت / …"></div>
    <div>
        <label>نوع حساب</label>
        <select name="account_type" required>
            @php $type = old('account_type', $b?->account_type ?? 'transfer'); @endphp
            <option value="cash" @selected($type === 'cash')>صندوق نقد</option>
            <option value="card" @selected($type === 'card')>کارتخوان</option>
            <option value="transfer" @selected($type === 'transfer')>کارت‌به‌کارت / شبا</option>
        </select>
    </div>
    <div>
        <label>حساب کل</label>
        <select name="gl_code">
            @php $gl = old('gl_code', $b?->gl_code ?? ''); @endphp
            <option value="">خودکار از نوع</option>
            <option value="1110" @selected($gl === '1110')>1110 صندوق</option>
            <option value="1120" @selected($gl === '1120')>1120 کارتخوان</option>
            <option value="1130" @selected($gl === '1130')>1130 کارت‌به‌کارت</option>
        </select>
    </div>
    <div><label>شماره حساب</label><input type="text" name="account_number" value="{{ old('account_number', $b?->account_number) }}" dir="ltr"></div>
    <div><label>شبا</label><input type="text" name="iban" value="{{ old('iban', $b?->iban) }}" dir="ltr" placeholder="IR…"></div>
    <div><label>شماره کارت</label><input type="text" name="card_number" value="{{ old('card_number', $b?->card_number) }}" dir="ltr"></div>
    <div><label>ترتیب</label><input type="number" name="sort_order" value="{{ old('sort_order', $b?->sort_order ?? 0) }}" min="0" dir="ltr"></div>
    <div class="full"><label>یادداشت</label><input type="text" name="note" value="{{ old('note', $b?->note) }}"></div>
    <div>@include('partials.toggle', ['name'=>'is_active','label'=>'فعال','checked'=>old('is_active', $b?->is_active ?? true)])</div>
    <div>@include('partials.toggle', ['name'=>'is_default','label'=>'پیش‌فرض','checked'=>old('is_default', $b?->is_default ?? false)])</div>
</div>
