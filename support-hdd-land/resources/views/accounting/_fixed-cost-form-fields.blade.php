@php $c = $cost ?? null; @endphp
<div class="form-grid" style="grid-template-columns:1fr 1fr;">
    <div class="full"><label>عنوان</label><input type="text" name="title" value="{{ old('title', $c?->title) }}" required placeholder="اجاره مغازه / اینترنت / حقوق…"></div>
    <div><label>دسته</label><input type="text" name="category" value="{{ old('category', $c?->category) }}" placeholder="اجاره / حقوق / آب‌برق"></div>
    <div><label>مبلغ ماهانه</label><input type="number" name="amount" value="{{ old('amount', $c?->amount ?? 0) }}" min="0" step="1000" dir="ltr" required></div>
    <div><label>روز ماه (۱–۲۸)</label><input type="number" name="day_of_month" value="{{ old('day_of_month', $c?->day_of_month ?? 1) }}" min="1" max="28" dir="ltr"></div>
    <div><label>شروع</label><input type="date" name="start_date" value="{{ old('start_date', optional($c?->start_date)->toDateString()) }}" dir="ltr"></div>
    <div><label>پایان</label><input type="date" name="end_date" value="{{ old('end_date', optional($c?->end_date)->toDateString()) }}" dir="ltr"></div>
    <div>
        <label>روش پرداخت</label>
        @php $pm = old('pay_method', $c?->pay_method ?? 'cash'); @endphp
        <select name="pay_method" required>
            <option value="cash" @selected($pm === 'cash')>نقد</option>
            <option value="card" @selected($pm === 'card')>کارتخوان</option>
            <option value="transfer" @selected($pm === 'transfer')>کارت‌به‌کارت</option>
        </select>
    </div>
    <div>
        <label>حساب بانکی (اختیاری)</label>
        <select name="bank_account_id">
            <option value="">— از روش پرداخت —</option>
            @foreach($banks as $bank)
                <option value="{{ $bank->id }}" @selected((int) old('bank_account_id', $c?->bank_account_id) === $bank->id)>{{ $bank->name }}</option>
            @endforeach
        </select>
    </div>
    <div><label>کد حساب هزینه</label><input type="text" name="expense_account_code" value="{{ old('expense_account_code', $c?->expense_account_code ?? '5310') }}" dir="ltr"></div>
    <div class="full"><label>یادداشت</label><input type="text" name="note" value="{{ old('note', $c?->note) }}"></div>
    <div>@include('partials.toggle', ['name'=>'is_active','label'=>'فعال','checked'=>old('is_active', $c?->is_active ?? true)])</div>
</div>
