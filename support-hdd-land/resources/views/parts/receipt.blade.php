@extends('layouts.app')
@section('title', 'رسید ورود انبار | سرزمین هارد')
@section('page_title', 'رسید ورود انبار')
@section('window_title', 'خرید / ورود قطعه — سند حسابداری ۱۳۱۰')

@section('content')
@include('parts._nav', [
    'whTitle' => 'رسید ورود انبار',
    'whSub' => 'ثبت خرید قطعه با بهای تمام‌شده و سند دوطرفه (موجودی / صندوق)',
])

<div class="panel" style="max-width:720px;">
    <form method="POST" action="{{ route('parts.receipt.store') }}">
        @csrf
        <div class="form-grid">
            <div class="full">
                <label>کالا</label>
                <select name="part_id" required>
                    <option value="">— انتخاب قطعه —</option>
                    @foreach($parts as $part)
                        <option value="{{ $part->id }}" @selected(old('part_id') == $part->id)>
                            {{ $part->name }}
                            @if($part->warehouse) [{{ $part->warehouse->name }}] @endif
                            (موجودی {{ $part->stock }}) — بهای فعلی {{ number_format($part->purchase_price) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>تعداد ورود</label>
                <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required>
            </div>
            <div>
                <label>بهای خرید واحد (تومان)</label>
                <input type="number" name="unit_cost" min="0" value="{{ old('unit_cost') }}" placeholder="خالی = بهای کارت کالا">
            </div>
            <div class="full">
                <label>شرح سند / فاکتور تأمین‌کننده</label>
                <input type="text" name="note" value="{{ old('note') }}" placeholder="مثلاً فاکتور ۱۲۳ — فروشگاه قطعه">
            </div>
            <div class="full">
                @include('partials.toggle', [
                    'name' => 'update_purchase_price',
                    'label' => 'به‌روزرسانی بهای خرید روی کارت کالا',
                    'checked' => (bool) old('update_purchase_price', true),
                    'on' => 'بله',
                    'off' => 'خیر',
                ])
            </div>
        </div>
        <p class="muted" style="font-size:11.5px;margin:10px 0;">
            سند حسابداری: بدهکار موجودی قطعات (۱۳۱۰) / بستانکار صندوق (۱۱۱۰) — فرض خرید نقدی.
        </p>
        <div class="actions">
            <button class="btn btn-primary" type="submit">ثبت رسید ورود</button>
            <a class="btn btn-ghost" href="{{ route('parts.index') }}">انصراف</a>
        </div>
    </form>
</div>
@endsection
