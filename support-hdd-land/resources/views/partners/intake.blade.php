@extends('layouts.app')

@section('title', 'پذیرش از نماینده | '.shop_name())
@section('page_title', 'پذیرش ارجاع از نماینده همکار')
@section('window_title', 'طرف حساب = نماینده؛ مشتری نهایی فقط با او کار دارد')

@section('content')
<section class="panel" style="margin-bottom:12px;background:#f3f7ff;border-color:#b7c8e8;">
    <strong>قانون طرف حساب:</strong>
    <p class="muted" style="margin:6px 0 0;">مشتری نهایی فقط با نماینده مبدأ طرف است. در این قبض، نماینده مشتری شماست؛ هزینه و قطعات به نام او ثبت می‌شود.</p>
</section>

@if($partners->isEmpty())
    <div class="alert alert-error">اول از منوی نمایندگان، همکار را ثبت کنید. <a href="{{ route('partners.create') }}">نماینده جدید</a></div>
@else
<section class="panel" style="max-width:860px;">
    <form method="POST" action="{{ route('partners.intake.store') }}">
        @csrf
        <div class="accept-row accept-row-2">
            <div>
                <label>نماینده مبدأ *</label>
                <select name="partner_id" required>
                    <option value="">— انتخاب —</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" @selected(old('partner_id') == $p->id)>{{ $p->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>شماره قبض مبدأ همکار</label>
                <input type="text" name="partner_peer_receipt_no" value="{{ old('partner_peer_receipt_no') }}" dir="ltr" style="text-align:left;" placeholder="قبض سیستم همکار">
            </div>
            <div>
                <label>یادداشت مشتری نهایی (اختیاری)</label>
                <input type="text" name="partner_end_customer_note" value="{{ old('partner_end_customer_note') }}" placeholder="فقط برای پیگیری داخلی؛ تماس با او نگیرید">
            </div>
            <div>
                <label>شماره قبض این مجموعه</label>
                <input type="text" value="{{ $nextReceipt }}" readonly dir="ltr" style="text-align:left;">
            </div>
        </div>
        <h3 style="margin:16px 0 8px;">دستگاه</h3>
        <div class="accept-row accept-row-2">
            <div>
                <label>نام کالا</label>
                <input type="text" name="product_name" value="{{ old('product_name') }}">
            </div>
            <div>
                <label>برند</label>
                <input type="text" name="brand" value="{{ old('brand') }}">
            </div>
            <div>
                <label>مدل</label>
                <input type="text" name="model" value="{{ old('model') }}">
            </div>
            <div>
                <label>سریال</label>
                <input type="text" name="serial_number" value="{{ old('serial_number') }}" data-barcode data-ascii-en dir="ltr" style="text-align:left;">
            </div>
        </div>
        <div style="margin-top:10px;">
            <label>عیب اظهارشده</label>
            <textarea name="reported_fault" rows="2">{{ old('reported_fault') }}</textarea>
        </div>
        <div class="accept-row accept-row-2" style="margin-top:10px;">
            <div>
                <label>لوازم همراه</label>
                <input type="text" name="accessories" value="{{ old('accessories') }}">
            </div>
            <div>
                <label>وضعیت ظاهری</label>
                <input type="text" name="appearance_notes" value="{{ old('appearance_notes') }}">
            </div>
            <div>
                <label>هزینه تخمینی (برای نماینده)</label>
                <input type="number" name="estimated_cost" min="0" step="1000" value="{{ old('estimated_cost', 0) }}">
            </div>
        </div>
        <div class="actions" style="margin-top:14px;">
            <button class="btn btn-primary" type="submit">ثبت قبض ارجاع نماینده</button>
            <a class="btn btn-ghost" href="{{ route('partners.cartable') }}">کارتابل ارجاع نماینده</a>
        </div>
    </form>
</section>
@endif
@endsection
