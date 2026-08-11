@extends('layouts.app')

@section('title', 'پذیرش جدید | '.shop_name())
@section('page_title', 'پذیرش جدید')
@section('window_title', 'پنجره پذیرش — تکی / گروهی')

@section('content')
@php
    $skipPhone = old('customer_id') || old('customer_phone') || old('customer_name') || $errors->any();
    $oldItems = old('items', []);
    if (! is_array($oldItems)) {
        $oldItems = [];
    }
    $itemSerialErrors = [];
    foreach ($errors->keys() as $errorKey) {
        if (preg_match('/^items\.(\d+)\.serial_number$/', (string) $errorKey, $m)) {
            $itemSerialErrors[(int) $m[1]] = $errors->first($errorKey);
        }
    }
    $hasSerialErrors = $errors->has('serial_number') || $itemSerialErrors !== [];
    // After a failed group save, always reopen group mode with the same cards.
    $oldMode = old('intake_mode', 'single');
    if ($oldItems !== [] || $itemSerialErrors !== []) {
        $oldMode = 'group';
    }
    $restoreGroupCards = $oldMode === 'group' && $oldItems !== [];
@endphp

@if($errors->any())
    <div class="alert alert-error" style="margin-bottom:10px;">
        <strong>ذخیره انجام نشد.</strong>
        @if($hasSerialErrors)
            <div style="margin-top:4px;">سریال تکراری را در کادر قرمز اصلاح کنید و دوباره ذخیره کنید.</div>
        @endif
        <ul style="margin:6px 0 0;padding-right:18px;">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="receipt-seq-bar" aria-label="شماره قبض">
    <div class="receipt-seq-item">
        <span class="receipt-seq-label">آخرین قبض</span>
        <strong class="receipt-seq-value" dir="ltr">{{ $lastReception?->receipt_no ?: '—' }}</strong>
    </div>
    <div class="receipt-seq-item">
        <span class="receipt-seq-label">سریال آخرین قبض</span>
        <strong class="receipt-seq-value" dir="ltr">{{ $lastReception?->serial_number ?: '—' }}</strong>
    </div>
    <div class="receipt-seq-item receipt-seq-next">
        <span class="receipt-seq-label">قبض جدید</span>
        <strong class="receipt-seq-value" id="receipt-seq-next-value" dir="ltr">{{ $nextReceipt }}</strong>
    </div>
</div>

<form method="POST"
      action="{{ route('receptions.store') }}"
      enctype="multipart/form-data"
      class="accept-form"
      id="reception-wizard"
      data-lookup-url="{{ route('receptions.lookup-phone') }}"
      data-lookup-customers-url="{{ route('receptions.lookup-customers') }}"
      data-ensure-customer-url="{{ route('receptions.ensure-customer') }}"
      data-skip-phone="{{ $skipPhone ? '1' : '0' }}"
      data-old-mode="{{ $oldMode }}"
      data-next-receipt="{{ $nextReceipt }}"
      data-next-ticket="{{ $nextTicket }}"
      data-old-items="{{ json_encode($oldItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
      data-item-serial-errors="{{ json_encode($itemSerialErrors, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
      data-create-sms-mode="{{ $createSmsMode ?? 'always' }}">
    @csrf
    <input type="hidden" name="customer_id" value="{{ old('customer_id') }}">
    <input type="hidden" name="customer_phone" value="{{ old('customer_phone') }}">
    <input type="hidden" name="intake_mode" id="intake-mode" value="{{ $oldMode }}">
    <input type="hidden" name="action" id="form-action" value="save_close">
    <input type="hidden" name="send_sms" id="create-send-sms" value="1">
    {{-- Fallback submit target for non-barcode Enter (barcode Enter is handled in app.js and does not submit). --}}
    <button type="submit" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">ثبت</button>

    <div id="step-phone" class="phone-step {{ $skipPhone ? 'hidden' : '' }}">
        <div class="phone-box">
            <h2>شروع پذیرش</h2>
            <p class="hint">مشتری را با موبایل یا نام پیدا کنید. مشتری‌های قبلی دوباره ثبت نمی‌شوند. بعد نوع پذیرش (تکی / گروهی) را انتخاب کنید.</p>

            <div class="start-tabs" role="tablist" aria-label="روش پیدا کردن مشتری">
                <button type="button" class="start-tab is-active" data-start-tab="phone" id="start-tab-phone">با موبایل</button>
                <button type="button" class="start-tab" data-start-tab="name" id="start-tab-name">با نام</button>
            </div>

            <div class="start-pane is-active" data-start-pane="phone" id="start-pane-phone">
                <div class="phone-row">
                    <div>
                        <label>شماره موبایل</label>
                        <input type="text"
                               id="lookup-phone"
                               value="{{ old('customer_phone') }}"
                               inputmode="tel"
                               autocomplete="tel"
                               placeholder="09xxxxxxxxx"
                               maxlength="15">
                    </div>
                    <button type="button" class="btn btn-primary" id="lookup-phone-btn">تایید</button>
                </div>
            </div>

            <div class="start-pane" data-start-pane="name" id="start-pane-name">
                <div class="phone-row">
                    <div>
                        <label>نام مشتری</label>
                        <input type="text"
                               id="lookup-name"
                               value=""
                               autocomplete="off"
                               placeholder="مثلاً رضا محمدی"
                               maxlength="120">
                    </div>
                    <button type="button" class="btn btn-primary" id="lookup-name-btn">جستجو</button>
                </div>
                <div class="customer-pick-list" id="customer-pick-list" hidden></div>
            </div>

            <div class="lookup-status" id="lookup-status"></div>
        </div>
    </div>

    <div id="mode-modal" class="mode-modal hidden" role="dialog" aria-modal="true">
        <div class="mode-dialog">
            <h2>نوع پذیرش را انتخاب کنید</h2>
            <p class="hint">اگر مشتری چند دستگاه آورده، پذیرش گروهی سریع‌تر است. سریال، مدل و خرابی هر دستگاه جدا ثبت می‌شود.</p>
            <div class="mode-cards">
                <button type="button" class="mode-card" data-choose-mode="single">
                    <strong>قبض تکی</strong>
                    <span>یک دستگاه — همان روال قبلی با تب‌ها</span>
                </button>
                <button type="button" class="mode-card mode-card-accent" data-choose-mode="group">
                    <strong>پذیرش گروهی</strong>
                    <span>چند دستگاه برای یک مشتری — هر کدام مشخصات جدا</span>
                </button>
            </div>
            <button type="button" class="btn btn-ghost" id="mode-modal-cancel">بازگشت به جستجوی مشتری</button>
        </div>
    </div>

    <div id="step-body" class="{{ $skipPhone ? '' : 'hidden' }}">
        <div id="existing-customer-card" class="existing-card hidden">
            <div>
                <strong id="existing-customer-name">—</strong>
                <div class="meta" id="existing-customer-meta"></div>
            </div>
            <div class="actions" style="margin:0;">
                <button type="button" class="btn" id="change-mode-btn">تغییر نوع پذیرش</button>
                <button type="button" class="btn" id="change-phone-btn">تغییر مشتری</button>
            </div>
        </div>

        <div id="new-customer-fields" class="panel {{ old('customer_id') ? 'hidden' : '' }}">
            <h3>مشتری جدید — ذخیره در لیست مشتریان</h3>
            <p class="lead" style="margin-top:0;">پس از وارد کردن نام، مشتری در لیست مشتریان ثبت می‌شود و در مراجعات بعدی با همین موبایل پیدا می‌شود.</p>
            <div class="accept-row accept-row-4">
                <div>
                    <label>نام و نام خانوادگی</label>
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="نام کامل">
                </div>
                <div>
                    <label>کد ملی</label>
                    <input type="text" name="national_code" value="{{ old('national_code') }}">
                </div>
                <div>
                    <label>شغل</label>
                    <input type="text" name="job" value="{{ old('job') }}">
                </div>
                <div>
                    <label>نحوه آشنایی</label>
                    <select name="referral_source_id">
                        <option value="">—</option>
                        @foreach($referralSources as $source)
                            <option value="{{ $source->id }}" @selected(old('referral_source_id') == $source->id)>{{ $source->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1">
                    <label>آدرس</label>
                    <input type="text" name="address" value="{{ old('address') }}">
                </div>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-primary" id="save-customer-btn">ثبت در لیست مشتریان</button>
                <span class="lookup-status" id="customer-save-status"></span>
                <button type="button" class="btn" id="change-mode-btn-2">تغییر نوع پذیرش</button>
                <button type="button" class="btn" id="back-to-phone-btn">بازگشت به جستجوی مشتری</button>
            </div>
        </div>

        <div id="mode-badge" class="mode-badge hidden">حالت: <strong id="mode-badge-text">تکی</strong></div>

        {{-- ========== SINGLE ========== --}}
        <div id="mode-single" class="{{ $oldMode === 'group' && $skipPhone ? 'hidden' : '' }}" data-workspace-tabs>
            <div class="ws-tabs">
                <button type="button" class="active" data-ws-tab="device">دستگاه</button>
                <button type="button" data-ws-tab="fault">عیب و لوازم</button>
                <button type="button" data-ws-tab="warranty">گارانتی</button>
                <button type="button" data-ws-tab="money">مالی و زمان</button>
                <button type="button" data-ws-tab="meta">کد و تاریخ</button>
            </div>
            <div class="ws-panes">
                <div class="ws-pane active" data-ws-pane="device">
                    <div class="accept-row accept-row-5">
                        <div>
                            <label>سریال دستگاه</label>
                            <input type="text"
                                   name="serial_number"
                                   value="{{ old('serial_number') }}"
                                   class="{{ $errors->has('serial_number') ? 'is-invalid' : '' }}"
                                   data-barcode data-ascii-en data-fa-en autocomplete="off" dir="ltr" style="text-align:left;">
                            @error('serial_number')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label>نوع خدمات</label>
                            <select name="service_type">
                                <option value="">—</option>
                                @foreach($serviceTypes as $name)
                                    <option value="{{ $name }}" @selected(old('service_type') == $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>نوع تعمیر</label>
                            <select name="repair_type">
                                <option value="">—</option>
                                @foreach($repairTypes as $name)
                                    <option value="{{ $name }}" @selected(old('repair_type') == $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>برند و مدل</label>
                            <input type="text" name="brand_model" value="{{ old('brand_model', old('model')) }}" list="brand-models" data-barcode data-ascii-en data-fa-en autocomplete="off" dir="ltr" style="text-align:left;">
                        </div>
                        <div>
                            <label>ظرفیت هارد</label>
                            <select name="hdd_capacity">
                                <option value="">—</option>
                                @foreach($hddCapacities as $name)
                                    <option value="{{ $name }}" @selected(old('hdd_capacity') == $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label>تحویل‌دهنده</label><input type="text" name="delivered_by" value="{{ old('delivered_by') }}"></div>
                        <div><label>معرف</label><input type="text" name="referrer" value="{{ old('referrer') }}"></div>
                        <div>
                            <label>تعمیرکار</label>
                            <select name="technician_id">
                                <option value="">—</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" @selected(old('technician_id') == $tech->id)>{{ $tech->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>نوع ایراد</label>
                            <select name="fault_type_id">
                                <option value="">—</option>
                                @foreach($faultTypes as $fault)
                                    <option value="{{ $fault->id }}" @selected(old('fault_type_id') == $fault->id)>{{ $fault->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="photo-box"><label>عکس دستگاه</label><input type="file" name="photo" accept="image/*"></div>
                    </div>
                </div>
                <div class="ws-pane" data-ws-pane="fault">
                    <div class="accept-texts">
                        <div class="note-field">
                            <label>عیب به اظهار مشتری</label>
                            <select class="note-menu" data-note-target="reported_fault" aria-label="منوی عیب اظهار مشتری">
                                <option value="">انتخاب از منو…</option>
                                @foreach($reportedFaultOptions as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <textarea name="reported_fault" rows="4">{{ old('reported_fault') }}</textarea>
                        </div>
                        <div class="note-field">
                            <label>لوازم همراه</label>
                            <select class="note-menu" data-note-target="accessories" aria-label="منوی لوازم همراه">
                                <option value="">انتخاب از منو…</option>
                                @foreach($accessoriesOptions as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <textarea name="accessories" rows="4">{{ old('accessories', 'ندارد') }}</textarea>
                        </div>
                        <div class="note-field">
                            <label>وضعیت ظاهری و توضیحات</label>
                            <select class="note-menu" data-note-target="appearance_notes" aria-label="منوی وضعیت ظاهری">
                                <option value="">انتخاب از منو…</option>
                                @foreach($appearanceOptions as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <textarea name="appearance_notes" rows="4">{{ old('appearance_notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="ws-pane" data-ws-pane="warranty">
                    <div class="accept-row accept-row-4">
                        <div>
                            @include('partials.toggle', ['name' => 'warranty_return', 'label' => 'برگشت گارانتی', 'checked' => (bool) old('warranty_return')])
                        </div>
                        <div>
                            <label>گارانتی</label>
                            <select name="warranty_type">
                                <option value="">—</option>
                                @foreach($warrantyTypes as $name)
                                    <option value="{{ $name }}" @selected(old('warranty_type') == $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label>شماره کارت</label><input type="text" name="card_number" value="{{ old('card_number') }}"></div>
                        <div><label>پایان گارانتی</label>@include('partials.jalali-date', ['name' => 'warranty_end_date', 'value' => old('warranty_end_date')])</div>
                    </div>
                </div>
                <div class="ws-pane" data-ws-pane="money">
                    <div class="accept-row accept-row-4">
                        <div><label>بیعانه</label><input type="number" name="deposit" min="0" value="{{ old('deposit', 0) }}"></div>
                        <div><label>کارت‌خوان</label><input type="number" name="pos_amount" min="0" value="{{ old('pos_amount', 0) }}"></div>
                        <div><label>هزینه پذیرش</label><input type="number" name="admission_fee" min="0" value="{{ old('admission_fee', 0) }}"></div>
                        <div><label>هزینه تخمینی</label><input type="number" name="estimated_cost" min="0" value="{{ old('estimated_cost', 0) }}"></div>
                        <div>
                            <label>روش پرداخت</label>
                            <select name="payment_method">
                                <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>نقد</option>
                                <option value="card" @selected(old('payment_method') === 'card')>کارت</option>
                                <option value="transfer" @selected(old('payment_method') === 'transfer')>کارت‌به‌کارت</option>
                            </select>
                        </div>
                        <div><label>پورسانت</label><input type="number" name="commission" min="0" value="{{ old('commission', 0) }}"></div>
                        <div><label>تاریخ تخمینی تحویل</label>@include('partials.jalali-date', ['name' => 'estimated_delivery_at', 'value' => old('estimated_delivery_at')])</div>
                        <div><label>مراجعه بعدی</label>@include('partials.jalali-date', ['name' => 'next_visit_at', 'value' => old('next_visit_at')])</div>
                    </div>
                </div>
                <div class="ws-pane" data-ws-pane="meta">
                    <div class="accept-row accept-row-4">
                        <div><label>کد حساب</label><input type="text" name="account_code" value="{{ old('account_code') }}"></div>
                        <div>
                            <label>نوع پذیرش</label>
                            <select name="admission_type">
                                <option value="">—</option>
                                @foreach($admissionTypes as $name)
                                    <option value="{{ $name }}" @selected(old('admission_type', 'حضوری') == $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label>کد پذیرش</label><input type="text" value="{{ $nextTicket }}" readonly></div>
                        <div><label>شماره قبض</label><input type="text" value="{{ $nextReceipt }}" readonly dir="ltr" placeholder="T-20N1000"></div>
                        <div><label>تاریخ پذیرش</label>@include('partials.jalali-date', ['name' => 'received_at', 'value' => old('received_at', jalali_input(now()))])</div>
                        <div><label>ساعت</label><input type="time" name="received_time" value="{{ old('received_time', now()->format('H:i')) }}"></div>
                    </div>
                </div>
            </div>
            <div class="accept-actions">
                <button class="btn btn-primary" type="submit" data-set-action="save_close">ثبت و بستن</button>
                <button class="btn btn-secondary" type="submit" data-set-action="save_continue">ثبت و ادامه</button>
                <button class="btn btn-secondary" type="button" data-set-action="save_print">ثبت و چاپ</button>
                <a class="btn btn-ghost" href="{{ route('receptions.index') }}">انصراف</a>
            </div>
        </div>

        {{-- ========== GROUP ========== --}}
        <div id="mode-group" class="{{ $oldMode === 'group' && $skipPhone ? '' : 'hidden' }}">
            <div class="win-strip">
                <span class="win-strip-title">اطلاعات مشترک</span>
                <div class="win-strip-fields">
                    <label>نوع
                        <select name="admission_type" data-group-shared>
                            <option value="">—</option>
                            @foreach($admissionTypes as $name)
                                <option value="{{ $name }}" @selected(old('admission_type', 'حضوری') == $name)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>تاریخ
                        @include('partials.jalali-date', ['name' => 'received_at', 'value' => old('received_at', jalali_input(now())), 'attrs' => 'data-group-shared'])
                    </label>
                    <label>ساعت
                        <input type="time" name="received_time" data-group-shared value="{{ old('received_time', now()->format('H:i')) }}">
                    </label>
                    <label>پرداخت
                        <select name="payment_method" data-group-shared>
                            <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>نقد</option>
                            <option value="card" @selected(old('payment_method') === 'card')>کارت</option>
                            <option value="transfer" @selected(old('payment_method') === 'transfer')>کارت‌به‌کارت</option>
                        </select>
                    </label>
                    <label>تحویل‌دهنده
                        <input type="text" name="delivered_by" data-group-shared value="{{ old('delivered_by') }}">
                    </label>
                    <label>معرف
                        <input type="text" name="referrer" data-group-shared value="{{ old('referrer') }}">
                    </label>
                </div>
            </div>

            <div id="group-device-list" class="group-device-list" data-ssr-restored="{{ $restoreGroupCards ? '1' : '0' }}">
                @if($restoreGroupCards)
                    @foreach($oldItems as $idx => $oldItem)
                        @include('partials.group-device-card', [
                            'item' => is_array($oldItem) ? $oldItem : [],
                            'index' => (int) $idx,
                            'serialError' => $itemSerialErrors[(int) $idx] ?? null,
                        ])
                    @endforeach
                @endif
            </div>

            <div class="win-cmdbar" id="group-next-menu">
                <div class="win-cmdbar-label">منوی قبض بعدی</div>
                <div class="win-cmdbar-actions">
                    <button type="button" class="btn btn-primary" id="group-add-btn">قبض بعدی</button>
                    <button type="button" class="btn btn-secondary" id="group-dup-btn">کپی و قبض بعدی</button>
                    <span class="win-cmd-sep"></span>
                    <button type="button" class="btn" id="group-expand-last-btn">ویرایش آخرین</button>
                    <button type="button" class="btn btn-ghost" id="group-collapse-all-btn">جمع‌کردن همه</button>
                </div>
                <div class="win-cmdbar-meta">
                    <span id="group-count">۱ قبض</span>
                    <span class="muted">|</span>
                    <span>بیعانه: <strong id="group-summary-deposit">0</strong></span>
                </div>
            </div>

            <div class="accept-actions compact-actions">
                <button class="btn btn-primary" type="submit" data-set-action="save_close">ثبت همه قبض‌ها</button>
                <button class="btn btn-secondary" type="button" data-set-action="save_print">ثبت و چاپ اولی</button>
                <a class="btn btn-ghost" href="{{ route('receptions.index') }}">انصراف</a>
                <span class="muted {{ $itemSerialErrors !== [] ? 'is-error' : '' }}" id="group-hint">
                    @if($itemSerialErrors !== [])
                        سریال تکراری را در کادر قرمز اصلاح کنید، سپس دوباره ذخیره کنید.
                    @else
                        حداقل ۲ قبض برای پذیرش گروهی لازم است.
                    @endif
                </span>
            </div>
        </div>
    </div>
</form>

<datalist id="brand-models">
    @foreach($brandModels as $name)
        <option value="{{ $name }}"></option>
    @endforeach
</datalist>

<template id="group-device-template">
    <div class="device-card is-active" data-device-card>
        <div class="device-card-head">
            <button type="button" class="device-card-toggle" data-device-toggle>
                <span class="device-index">قبض ۱</span>
                <span class="device-receipt" data-device-receipt dir="ltr">—</span>
                <span class="device-preview muted" data-device-preview>در حال تکمیل…</span>
            </button>
            <div class="device-card-tools">
                <button type="button" class="btn btn-ghost" data-device-collapse title="جمع/باز">▾</button>
                <button type="button" class="btn btn-danger" data-device-remove title="حذف">×</button>
            </div>
        </div>
        <div class="device-card-body">
            <div class="dense-grid">
                <label>سریال<input type="text" data-name="serial_number" data-barcode data-ascii-en data-fa-en autocomplete="off" dir="ltr" style="text-align:left;"></label>
                <label>خدمات
                    <select data-name="service_type">
                        <option value="">—</option>
                        @foreach($serviceTypes as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                </label>
                <label>تعمیر
                    <select data-name="repair_type">
                        <option value="">—</option>
                        @foreach($repairTypes as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                </label>
                <label>برند و مدل<input type="text" data-name="brand_model" list="brand-models" data-barcode data-ascii-en data-fa-en autocomplete="off" dir="ltr" style="text-align:left;"></label>
                <label>ظرفیت
                    <select data-name="hdd_capacity">
                        <option value="">—</option>
                        @foreach($hddCapacities as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                </label>
                <label>تعمیرکار
                    <select data-name="technician_id">
                        <option value="">—</option>
                        @foreach($technicians as $tech)<option value="{{ $tech->id }}">{{ $tech->name }}</option>@endforeach
                    </select>
                </label>
                <label>نوع ایراد
                    <select data-name="fault_type_id">
                        <option value="">—</option>
                        @foreach($faultTypes as $fault)<option value="{{ $fault->id }}">{{ $fault->name }}</option>@endforeach
                    </select>
                </label>
                <label>گارانتی
                    <select data-name="warranty_type">
                        <option value="">—</option>
                        @foreach($warrantyTypes as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                </label>
                <label class="chk"><input type="checkbox" data-name="warranty_return" value="1"> برگشت گارانتی</label>
                <label>بیعانه<input type="number" data-name="deposit" min="0" value="0" data-deposit></label>
                <label>تخمینی<input type="number" data-name="estimated_cost" min="0" value="0"></label>
                <label>کارت‌خوان<input type="number" data-name="pos_amount" min="0" value="0"></label>
                <label>پذیرش<input type="number" data-name="admission_fee" min="0" value="0"></label>
                <label>تحویل<input type="text" class="jalali-date" data-name="estimated_delivery_at" placeholder="1404/05/16" dir="ltr" style="text-align:left;" inputmode="numeric" autocomplete="off"></label>
                <label>کارت گارانتی<input type="text" data-name="card_number"></label>
            </div>
            <div class="dense-notes">
                <div class="note-field">
                    <label>عیب اظهار مشتری</label>
                    <select class="note-menu" data-note-target="reported_fault" aria-label="منوی عیب اظهار مشتری">
                        <option value="">انتخاب از منو…</option>
                        @foreach($reportedFaultOptions as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                    <textarea data-name="reported_fault" rows="2"></textarea>
                </div>
                <div class="note-field">
                    <label>لوازم همراه</label>
                    <select class="note-menu" data-note-target="accessories" aria-label="منوی لوازم همراه">
                        <option value="">انتخاب از منو…</option>
                        @foreach($accessoriesOptions as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                    <textarea data-name="accessories" rows="2">ندارد</textarea>
                </div>
                <div class="note-field">
                    <label>وضعیت ظاهری</label>
                    <select class="note-menu" data-note-target="appearance_notes" aria-label="منوی وضعیت ظاهری">
                        <option value="">انتخاب از منو…</option>
                        @foreach($appearanceOptions as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                    <textarea data-name="appearance_notes" rows="2"></textarea>
                </div>
            </div>
            <div class="device-card-footer">
                <button type="button" class="btn btn-secondary" data-device-done>تأیید این قبض و ادامه</button>
                <span class="muted">بعد از تأیید، منوی قبض بعدی زیر لیست فعال می‌شود</span>
            </div>
        </div>
    </div>
</template>
@endsection
