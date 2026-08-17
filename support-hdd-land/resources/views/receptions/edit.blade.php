@extends('layouts.app')
@section('title', 'ویرایش '.$reception->ticket_no.' | '.shop_name())
@section('page_title', 'ویرایش قبض '.$reception->ticket_no)
@section('window_title', 'ویرایش قبض — اصلاح مشخصات')

@section('content')
@php
    $c = $customer;
    $r = $reception;
    $brandModelValue = old('brand_model', trim(($r->brand ? $r->brand.' ' : '').($r->model ?: $r->product_name ?: '')));
@endphp
@if($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('receptions.update', $r) }}" enctype="multipart/form-data" class="accept-form">
    @csrf
    @method('PUT')
    <input type="hidden" name="customer_id" value="{{ old('customer_id', $c->id) }}">

    <div class="panel" style="margin-bottom:10px;">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <h3 style="margin:0 0 4px;">مشتری — ویرایش کامل</h3>
                <p class="muted" style="margin:0;font-size:11.5px;">
                    اگر نام قبض اشتباه خورده، اینجا اصلاح کنید.
                    به‌صورت پیش‌فرض نام/موبایل روی <strong>پرونده همین مشتری</strong> ذخیره می‌شود.
                    اگر فقط همین قبض باید جدا شود و قبض‌های دیگر دست نخورند، گزینه پایین را بزنید.
                </p>
            </div>
            <div class="actions" style="margin:0;">
                @if(auth()->user()->canAccess('customers'))
                    <a class="btn btn-ghost btn-sm" href="{{ route('customers.edit', $c) }}" target="_blank">پرونده مشتری</a>
                @endif
            </div>
        </div>
        <div class="accept-row accept-row-3" style="margin-top:10px;">
            <div>
                <label>نام</label>
                <input type="text" name="customer_name" value="{{ old('customer_name', $c->name) }}" required maxlength="120">
            </div>
            <div>
                <label>موبایل</label>
                <input type="text" name="customer_phone" value="{{ old('customer_phone', $c->phone) }}" dir="ltr" style="text-align:left;" required maxlength="20">
            </div>
            <div>
                <label>کد ملی</label>
                <input type="text" name="national_code" value="{{ old('national_code', $c->national_code) }}">
            </div>
            <div>
                <label>شغل</label>
                <input type="text" name="job" value="{{ old('job', $c->job) }}">
            </div>
            <div>
                <label>نحوه آشنایی</label>
                <select name="referral_source_id">
                    <option value="">—</option>
                    @foreach($referralSources as $src)
                        <option value="{{ $src->id }}" @selected((string) old('referral_source_id', $c->referral_source_id) === (string) $src->id)>{{ $src->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:1/-1;">
                <label>آدرس</label>
                <input type="text" name="address" value="{{ old('address', $c->address) }}">
            </div>
            <div style="grid-column:1/-1;">
                @include('partials.toggle', [
                    'name' => 'fix_customer_scope',
                    'value' => 'ticket_only',
                    'offValue' => 'profile',
                    'label' => 'فقط همین قبض را اصلاح کن (قبض‌های دیگر این مشتری دست نخورند)',
                    'checked' => old('fix_customer_scope', 'profile') === 'ticket_only',
                    'on' => 'فعال',
                    'off' => 'خاموش',
                ])
            </div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:10px;">
        <h3 style="margin:0 0 8px;">دستگاه / سریال</h3>
        <div class="accept-row accept-row-3">
            <div>
                <label>نام کالا</label>
                <input type="text" name="product_name" value="{{ old('product_name', $r->product_name) }}">
            </div>
            <div>
                <label>برند و مدل <span class="latin-hint">EN</span></label>
                <input type="text" name="brand_model" value="{{ $brandModelValue }}" list="brand-models"
                       class="field-latin"
                       data-barcode data-ascii-en data-fa-en
                       lang="en" spellcheck="false" autocapitalize="characters"
                       autocomplete="off" dir="ltr" inputmode="latin"
                       placeholder="BRAND MODEL" style="text-align:left;">
            </div>
            <div>
                <label>سریال <span class="latin-hint">EN</span></label>
                <input type="text" name="serial_number" value="{{ old('serial_number', $r->serial_number) }}"
                       class="field-latin"
                       dir="ltr" style="text-align:left;"
                       data-barcode data-ascii-en data-fa-en
                       lang="en" spellcheck="false" autocapitalize="characters"
                       autocomplete="off" inputmode="latin" placeholder="SERIAL">
            </div>
            <div>
                <label>ظرفیت هارد</label>
                <select name="hdd_capacity">
                    <option value="">—</option>
                    @foreach($hddCapacities as $name)
                        <option value="{{ $name }}" @selected(old('hdd_capacity', $r->hdd_capacity) == $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>تعمیرکار</label>
                <select name="technician_id">
                    <option value="">—</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" @selected((string) old('technician_id', $r->technician_id) === (string) $tech->id)>{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>نوع خدمات</label>
                <select name="service_type">
                    <option value="">—</option>
                    @foreach($serviceTypes as $name)
                        <option value="{{ $name }}" @selected(old('service_type', $r->service_type) == $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>نوع تعمیر</label>
                <select name="repair_type">
                    <option value="">—</option>
                    @foreach($repairTypes as $name)
                        <option value="{{ $name }}" @selected(old('repair_type', $r->repair_type) == $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>نوع ایراد</label>
                <select name="fault_type_id">
                    <option value="">—</option>
                    @foreach($faultTypes as $fault)
                        <option value="{{ $fault->id }}" @selected((string) old('fault_type_id', $r->fault_type_id) === (string) $fault->id)>{{ $fault->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:1/-1;" class="note-field">
                <label>ایراد اعلامی مشتری</label>
                <select class="note-menu" data-note-target="reported_fault" aria-label="منوی عیب اظهار مشتری">
                    <option value="">انتخاب از منو…</option>
                    @foreach($reportedFaultOptions as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                <textarea name="reported_fault" rows="3">{{ old('reported_fault', $r->reported_fault) }}</textarea>
            </div>
            <div style="grid-column:1/-1;" class="note-field">
                <label>لوازم همراه</label>
                <select class="note-menu" data-note-target="accessories" aria-label="منوی لوازم همراه">
                    <option value="">انتخاب از منو…</option>
                    @foreach($accessoriesOptions as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                <textarea name="accessories" rows="2">{{ old('accessories', $r->accessories) }}</textarea>
            </div>
            <div style="grid-column:1/-1;" class="note-field">
                <label>وضعیت ظاهری</label>
                <select class="note-menu" data-note-target="appearance_notes" aria-label="منوی وضعیت ظاهری">
                    <option value="">انتخاب از منو…</option>
                    @foreach($appearanceOptions as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                <textarea name="appearance_notes" rows="2">{{ old('appearance_notes', $r->appearance_notes) }}</textarea>
            </div>
            <div style="grid-column:1/-1;">
                <label>ایراد نهایی / یادداشت تعمیرکار</label>
                <textarea name="final_fault" rows="2" placeholder="ایراد نهایی">{{ old('final_fault', $r->final_fault) }}</textarea>
                <textarea name="technician_notes" rows="2" placeholder="یادداشت تعمیرکار" style="margin-top:6px;">{{ old('technician_notes', $r->technician_notes) }}</textarea>
            </div>
            <div>
                <label>عکس دستگاه (اختیاری)</label>
                <input type="file" name="photo" accept="image/*">
                @if($r->photo_path)
                    <p class="muted" style="margin:4px 0 0;font-size:11px;">عکس فعلی ثبت است — با انتخاب فایل جدید جایگزین می‌شود.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:10px;">
        <h3 style="margin:0 0 8px;">پذیرش / گارانتی / مبالغ اولیه</h3>
        <div class="accept-row accept-row-4">
            <div>
                <label>نوع پذیرش</label>
                <select name="admission_type">
                    <option value="">—</option>
                    @foreach($admissionTypes as $name)
                        <option value="{{ $name }}" @selected(old('admission_type', $r->admission_type) == $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>کد حساب</label><input type="text" name="account_code" value="{{ old('account_code', $r->account_code) }}"></div>
            <div><label>تحویل‌دهنده</label><input type="text" name="delivered_by" value="{{ old('delivered_by', $r->delivered_by) }}"></div>
            <div><label>معرف</label><input type="text" name="referrer" value="{{ old('referrer', $r->referrer) }}"></div>
            <div><label>تاریخ پذیرش</label>@include('partials.jalali-date', ['name' => 'received_at', 'value' => old('received_at', jalali_input($r->received_at))])</div>
            <div><label>ساعت</label><input type="time" name="received_time" value="{{ old('received_time', optional($r->received_at)->format('H:i') ?: now()->format('H:i')) }}"></div>
            <div>
                @include('partials.toggle', ['name' => 'warranty_return', 'label' => 'برگشت گارانتی', 'checked' => (bool) old('warranty_return', $r->warranty_return)])
            </div>
            <div>
                <label>گارانتی</label>
                <select name="warranty_type">
                    <option value="">—</option>
                    @foreach($warrantyTypes as $name)
                        <option value="{{ $name }}" @selected(old('warranty_type', $r->warranty_type) == $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>شماره کارت</label><input type="text" name="card_number" value="{{ old('card_number', $r->card_number) }}"></div>
            <div><label>پایان گارانتی</label>@include('partials.jalali-date', ['name' => 'warranty_end_date', 'value' => old('warranty_end_date', jalali_input($r->warranty_end_date))])</div>
            <div><label>بیعانه</label><input type="number" name="deposit" min="0" value="{{ old('deposit', (int) $r->deposit) }}"></div>
            <div><label>کارت‌خوان</label><input type="number" name="pos_amount" min="0" value="{{ old('pos_amount', (int) $r->pos_amount) }}"></div>
            <div><label>هزینه پذیرش</label><input type="number" name="admission_fee" min="0" value="{{ old('admission_fee', (int) $r->admission_fee) }}"></div>
            <div><label>هزینه تخمینی</label><input type="number" name="estimated_cost" min="0" value="{{ old('estimated_cost', (int) $r->estimated_cost) }}"></div>
            <div>
                <label>روش پرداخت اولیه</label>
                <select name="payment_method">
                    @foreach($paymentMethods as $key => $label)
                        <option value="{{ $key }}" @selected(old('payment_method', $r->payment_method ?: 'cash') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>پورسانت</label><input type="number" name="commission" min="0" value="{{ old('commission', (int) $r->commission) }}"></div>
            <div><label>تاریخ تخمینی تحویل</label>@include('partials.jalali-date', ['name' => 'estimated_delivery_at', 'value' => old('estimated_delivery_at', jalali_input($r->estimated_delivery_at))])</div>
            <div><label>مراجعه بعدی</label>@include('partials.jalali-date', ['name' => 'next_visit_at', 'value' => old('next_visit_at', jalali_input($r->next_visit_at))])</div>
            <div><label>نام تحویل‌گیرنده</label><input type="text" name="pickup_name" value="{{ old('pickup_name', $r->pickup_name) }}"></div>
            <div><label>موبایل تحویل‌گیرنده</label><input type="text" name="pickup_phone" value="{{ old('pickup_phone', $r->pickup_phone) }}" dir="ltr" style="text-align:left;"></div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:10px;border-color:#9ec3e8;background:#f4f9ff;">
        <h3 style="margin:0 0 8px;">پیامک به مشتری پس از ذخیره</h3>
        @include('partials.toggle', [
            'name' => 'send_sms',
            'label' => 'بعد از ذخیره، پیامک به‌روزرسانی برای مشتری برود',
            'checked' => (string) old('send_sms', '1') === '1',
            'on' => 'ارسال شود',
            'off' => 'نرود',
        ])
        <div style="margin-top:8px;">
            <label>متن اضافی پیامک (اختیاری)</label>
            <input type="text" name="sms_note" value="{{ old('sms_note') }}" placeholder="مثلاً: سریال اصلاح شد">
        </div>
        <p class="muted" style="margin:8px 0 0;font-size:11px;">
            شماره قبض {{ $r->ticket_no }} / {{ $r->receipt_no ?: '—' }} تغییر نمی‌کند.
        </p>
    </div>

    <div class="accept-actions">
        <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
        <a class="btn btn-ghost" href="{{ route('receptions.show', $r) }}">انصراف</a>
    </div>
</form>

<datalist id="brand-models">
    @foreach($brandModels as $name)
        <option value="{{ $name }}"></option>
    @endforeach
</datalist>
@endsection
