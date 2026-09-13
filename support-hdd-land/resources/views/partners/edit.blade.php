@extends('layouts.app')

@section('title', 'یادداشت همکار | '.shop_name())
@section('page_title', 'یادداشت همکار شبکه')
@section('window_title', $partner->displayName())

@section('content')
<section class="panel" style="max-width:760px;">
    <p class="muted">هویت شبکه (نام/دامنه/لایسنس) از لایسنس‌های فعال همگام می‌شود و اینجا فقط یادداشت محلی قابل ویرایش است.</p>
    <div class="accept-row accept-row-2" style="margin:12px 0;">
        <div><label>نام</label><input type="text" value="{{ $partner->name }}" disabled></div>
        <div><label>دامنه</label><input type="text" value="{{ $partner->domain }}" disabled dir="ltr" style="text-align:left;"></div>
        <div><label>لایسنس</label><input type="text" value="{{ $partner->license_key }}" disabled dir="ltr" style="text-align:left;"></div>
        <div><label>موبایل</label><input type="text" value="{{ $partner->phone }}" disabled dir="ltr" style="text-align:left;"></div>
    </div>
    <form method="POST" action="{{ route('partners.update', $partner) }}">
        @csrf
        @method('PUT')
        <div class="accept-row accept-row-2">
            <div style="grid-column:1/-1;">
                <label>یادداشت محلی</label>
                <textarea name="notes" rows="3">{{ old('notes', $partner->notes) }}</textarea>
            </div>
            <div>
                <label class="inline" style="display:inline-flex;align-items:center;gap:8px;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $partner->is_active))>
                    نمایش در فهرست فعال
                </label>
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button class="btn btn-primary" type="submit">ذخیره</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">بازگشت</a>
        </div>
    </form>
</section>
@endsection
