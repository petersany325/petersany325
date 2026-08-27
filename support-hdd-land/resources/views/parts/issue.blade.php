@extends('layouts.app')
@section('title', 'حواله خروج انبار | سرزمین هارد')
@section('page_title', 'حواله خروج انبار')
@section('window_title', 'خروج غیرقبض — بهای تمام‌شده / COGS')

@section('content')
@include('parts._nav', [
    'whTitle' => 'حواله خروج انبار',
    'whSub' => 'خروج قطعه خارج از قبض تعمیر (ضایعات، مصرف داخلی، انتقال)',
])

<div class="panel" style="max-width:720px;">
    <div class="alert" style="background:#fff6e5;border-color:#efd7a4;color:#8a5a12;margin-bottom:12px;">
        برای مصرف روی قبض مشتری از صفحه قبض «افزودن قطعه» استفاده کنید تا به همان سریال وصل شود.
        این فرم برای خروج‌های مستقل انبار است.
    </div>
    <form method="POST" action="{{ route('parts.issue.store') }}">
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
                            (موجودی {{ $part->stock }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>تعداد خروج</label>
                <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required>
            </div>
            <div class="full">
                <label>شرح حواله</label>
                <input type="text" name="note" value="{{ old('note') }}" placeholder="دلیل خروج" required>
            </div>
        </div>
        <p class="muted" style="font-size:11.5px;margin:10px 0;">
            سند حسابداری: بدهکار بهای تمام‌شده (۵۱۱۰) / بستانکار موجودی قطعات (۱۳۱۰).
        </p>
        <div class="actions">
            <button class="btn btn-primary" type="submit">ثبت حواله خروج</button>
            <a class="btn btn-ghost" href="{{ route('parts.index') }}">انصراف</a>
        </div>
    </form>
</div>
@endsection
