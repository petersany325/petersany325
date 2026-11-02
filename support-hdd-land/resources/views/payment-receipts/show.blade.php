@extends('layouts.app')
@section('title', 'بررسی فیش #'.$receipt->id.' | سرزمین هارد')
@section('page_title', 'بررسی فیش بانکی')
@section('window_title', 'تأیید یا رد فیش کارت‌به‌کارت')

@section('content')
<div class="panel" style="margin-bottom:12px;">
    <div class="actions" style="margin:0 0 10px;justify-content:space-between;">
        <a class="btn btn-ghost" href="{{ route('payment-receipts.index') }}">← بازگشت به لیست</a>
        @if($receipt->reception && auth()->user()->canAccess('receptions'))
            <a class="btn btn-secondary" href="{{ route('receptions.show', $receipt->reception) }}">مشاهده قبض</a>
        @endif
    </div>

    <h2 style="margin-top:0;">فیش #{{ $receipt->id }} — {{ $statusLabels[$receipt->status] ?? $receipt->status }}</h2>

    <div class="split-2">
        <div>
            <p><strong>قبض:</strong>
                @if($receipt->reception)
                    <a href="{{ route('receptions.show', $receipt->reception) }}">{{ $receipt->reception->ticket_no }}</a>
                    <span class="muted">مانده فعلی: {{ number_format($receipt->reception->remainingAmount()) }} تومان</span>
                @else
                    —
                @endif
            </p>
            <p><strong>مشتری:</strong> {{ $receipt->customer?->name }} <span class="muted" dir="ltr">{{ $receipt->customer?->phone }}</span></p>
            <p><strong>مبلغ اعلامی:</strong> {{ number_format((int) $receipt->amount) }} تومان</p>
            <p><strong>تاریخ واریز:</strong> {{ $receipt->transfer_date ? jalali_like($receipt->transfer_date) : '—' }}</p>
            <p><strong>توضیح مشتری:</strong> {{ $receipt->note ?: '—' }}</p>
            <p><strong>ارسال:</strong> {{ jalali_like($receipt->created_at) }}</p>
            @if($receipt->reviewed_at)
                <p><strong>بررسی‌کننده:</strong> {{ $receipt->reviewer?->name ?: '—' }} — {{ jalali_like($receipt->reviewed_at) }}</p>
                <p><strong>یادداشت بررسی:</strong> {{ $receipt->review_note ?: '—' }}</p>
            @endif
            @if($receipt->payment_id)
                <p><strong>پرداخت ثبت‌شده:</strong> #{{ $receipt->payment_id }} — {{ number_format((int) ($receipt->payment?->amount ?? $receipt->amount)) }} تومان</p>
            @endif
        </div>
        <div>
            <h3 style="margin-top:0;">تصویر فیش</h3>
            @if($receipt->hasImage())
                @php $ext = strtolower(pathinfo($receipt->image_path, PATHINFO_EXTENSION)); @endphp
                @if(in_array($ext, ['jpg','jpeg','png','webp'], true))
                    <a href="{{ route('payment-receipts.image', $receipt) }}" target="_blank">
                        <img src="{{ route('payment-receipts.image', $receipt) }}" alt="فیش بانکی" style="max-width:100%;border:1px solid #ddd;border-radius:6px;">
                    </a>
                @else
                    <a class="btn btn-primary" href="{{ route('payment-receipts.image', $receipt) }}" target="_blank">دانلود / مشاهده فایل</a>
                @endif
            @else
                <div class="muted">تصویر موجود نیست@if($receipt->status === 'rejected') (پس از رد حذف شده)@endif.</div>
            @endif
        </div>
    </div>
</div>

@if($receipt->isPending())
<div class="split-2">
    <div class="panel">
        <h3 style="margin-top:0;">تأیید فیش</h3>
        <p class="muted">با تأیید، پرداخت کارت‌به‌کارت در صندوق و گزارش مالی ثبت می‌شود و تصویر نگه داشته می‌شود.</p>
        <form method="POST" action="{{ route('payment-receipts.approve', $receipt) }}">
            @csrf
            <div class="form-grid" style="grid-template-columns:1fr;">
                <div>
                    <label>مبلغ تأیید (تومان)</label>
                    <input type="number" name="amount" value="{{ old('amount', $receipt->amount) }}" min="1000" required>
                </div>
                <div>
                    <label>یادداشت (اختیاری)</label>
                    <textarea name="review_note" rows="2">{{ old('review_note') }}</textarea>
                </div>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit" onclick="return confirm('فیش تأیید و به‌عنوان پرداخت واقعی ثبت شود؟')">تأیید و ثبت پرداخت</button>
            </div>
        </form>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">رد فیش</h3>
        <p class="muted">اگر فیش بانکی معتبر نیست، رد کنید؛ تصویر به‌صورت خودکار حذف می‌شود و واریز ثبت نمی‌گردد.</p>
        <form method="POST" action="{{ route('payment-receipts.reject', $receipt) }}">
            @csrf
            <div>
                <label>دلیل رد</label>
                <textarea name="review_note" rows="3" placeholder="مثلاً فیش نامعتبر / مبلغ اشتباه">{{ old('review_note', 'فیش بانکی معتبر نیست.') }}</textarea>
            </div>
            <div class="actions">
                <button class="btn btn-danger" type="submit" onclick="return confirm('فیش رد شود و تصویر حذف گردد؟')">رد و حذف تصویر</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
