@extends('layouts.app')
@section('title', 'طرح اقساط جدید | '.shop_name())
@section('page_title', 'ثبت طرح اقساط')
@section('window_title', 'اقساط جدید')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'طرح جدید',
    'accSub' => 'زمان‌بندی خودکار یا دستی روی مانده مشتری',
])

<div class="acc-desk">
    <section class="acc-panel">
        <form method="post" action="{{ route('installments.store') }}" class="form-grid" id="inst-form">
            @csrf

            <label>مشتری *
                <select name="customer_id" required id="customer_id">
                    <option value="">انتخاب کنید</option>
                    @if($customer)
                        <option value="{{ $customer->id }}" selected>{{ $customer->displayName() }} — {{ $customer->phone }}</option>
                    @endif
                    @foreach($customers as $c)
                        @if(!$customer || $c->id !== $customer->id)
                            <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->displayName() }} — {{ $c->phone }}</option>
                        @endif
                    @endforeach
                </select>
                @error('customer_id')<span class="err">{{ $message }}</span>@enderror
                <span class="muted" style="font-size:11px;">اگر مشتری در لیست نیست، از صفحه مشتری «طرح اقساط» را باز کنید یا شناسه را از بدهکاران بگیرید.</span>
            </label>

            <label>قبض مرتبط (اختیاری)
                <select name="reception_id">
                    <option value="">— بدون پیوند مستقیم —</option>
                    @foreach($receptions as $r)
                        <option value="{{ $r->id }}" @selected(old('reception_id') == $r->id)>
                            {{ $r->ticket_no }} — مانده {{ number_format($r->remainingAmount()) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>عنوان
                <input type="text" name="title" value="{{ old('title') }}" maxlength="200" placeholder="مثلاً تسویه هارد / نسیه تعمیر">
            </label>

            <label>حالت زمان‌بندی *
                <select name="schedule_mode" id="schedule_mode">
                    <option value="auto" @selected(old('schedule_mode', 'auto') === 'auto')>خودکار</option>
                    <option value="manual" @selected(old('schedule_mode') === 'manual')>دستی</option>
                </select>
            </label>

            <div id="auto-fields" class="form-grid" style="grid-column:1/-1;">
                <label>مبلغ کل (تومان)
                    <input type="number" name="total_amount" min="0" value="{{ old('total_amount') }}" dir="ltr">
                    @error('total_amount')<span class="err">{{ $message }}</span>@enderror
                </label>
                <label>پیش‌پرداخت
                    <input type="number" name="down_payment" min="0" value="{{ old('down_payment', 0) }}" dir="ltr">
                </label>
                <label>تعداد اقساط
                    <input type="number" name="installment_count" min="1" max="120" value="{{ old('installment_count', 3) }}" dir="ltr">
                </label>
                <label>فاصله (روز)
                    <input type="number" name="interval_days" min="1" max="365" value="{{ old('interval_days', 30) }}" dir="ltr">
                </label>
                <label>تاریخ شروع
                    @include('partials.jalali-date', ['name' => 'start_date', 'value' => old('start_date')])
                </label>
            </div>

            <div id="manual-fields" style="grid-column:1/-1;display:none;">
                <p class="muted" style="margin:0 0 8px;">تاریخ و مبلغ هر قسط را وارد کنید (حداکثر ۱۲ ردیف خالی آماده‌اند).</p>
                <div class="table-wrap">
                    <table class="compact-table acc-table">
                        <thead><tr><th>#</th><th>سررسید</th><th>مبلغ</th><th>یادداشت</th></tr></thead>
                        <tbody>
                        @for($i = 0; $i < 12; $i++)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>@include('partials.jalali-date', ['name' => "items[$i][due_date]", 'value' => old("items.$i.due_date")])</td>
                                <td><input type="number" name="items[{{ $i }}][amount]" min="0" value="{{ old("items.$i.amount") }}" dir="ltr" style="width:120px;"></td>
                                <td><input type="text" name="items[{{ $i }}][notes]" value="{{ old("items.$i.notes") }}" maxlength="500"></td>
                            </tr>
                        @endfor
                        </tbody>
                    </table>
                </div>
                @error('items')<span class="err">{{ $message }}</span>@enderror
            </div>

            <label style="grid-column:1/-1;">یادداشت طرح
                <textarea name="notes" rows="2">{{ old('notes') }}</textarea>
            </label>

            <fieldset style="grid-column:1/-1;border:1px solid #e5e7eb;border-radius:8px;padding:12px;">
                <legend>ضامن (اختیاری)</legend>
                <div class="form-grid">
                    <label>نام ضامن<input type="text" name="guarantor_name" value="{{ old('guarantor_name') }}"></label>
                    <label>موبایل ضامن<input type="text" name="guarantor_phone" value="{{ old('guarantor_phone') }}" dir="ltr"></label>
                    <label>کد ملی<input type="text" name="guarantor_national_code" value="{{ old('guarantor_national_code') }}" dir="ltr"></label>
                    <label>آدرس<input type="text" name="guarantor_address" value="{{ old('guarantor_address') }}"></label>
                    <label style="grid-column:1/-1;">توضیح ضامن<textarea name="guarantor_notes" rows="2">{{ old('guarantor_notes') }}</textarea></label>
                </div>
            </fieldset>

            <div class="actions" style="grid-column:1/-1;">
                <button type="submit" class="btn btn-primary">ثبت طرح</button>
                <a href="{{ route('installments.index') }}" class="btn btn-ghost">انصراف</a>
            </div>
        </form>
    </section>
</div>

<script>
(function () {
    const mode = document.getElementById('schedule_mode');
    const auto = document.getElementById('auto-fields');
    const man = document.getElementById('manual-fields');
    function sync() {
        const m = mode.value === 'manual';
        auto.style.display = m ? 'none' : '';
        man.style.display = m ? '' : 'none';
    }
    mode.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
