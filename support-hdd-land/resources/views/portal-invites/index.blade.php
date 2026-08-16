@extends('layouts.app')
@section('title', 'ارسال لینک کارتابل مشتری | '.shop_name())
@section('page_title', 'لینک کارتابل مشتری')
@section('window_title', 'ارسال گروهی لینک ورود')

@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
        <div>
            <h2 style="margin:0;">ارسال لینک کارتابل مشتری</h2>
            <p class="lead" style="margin:4px 0 0;">برای مهاجرت از سیستم قدیم؛ لینک ورود پیامکی کارتابل برای مشتریان ارسال می‌شود.</p>
            <p class="muted" style="margin:6px 0 0;font-size:12px;">لینک: <span dir="ltr">{{ $loginUrl }}</span> · تلفن دفتر: <span dir="ltr">{{ $officePhone }}</span></p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a class="btn btn-secondary" href="{{ route('portal-invites.report') }}">گزارش ارسال‌ها</a>
            <a class="btn btn-ghost" href="{{ route('customers.index') }}">مشتریان</a>
        </div>
    </div>

    <div class="emp-stat-row" style="margin-top:12px;">
        <div class="emp-stat"><span>کل مشتری</span><strong>{{ $stats['total'] }}</strong></div>
        <div class="emp-stat"><span>دارای موبایل</span><strong>{{ $stats['with_phone'] }}</strong></div>
        <div class="emp-stat tone-ok"><span>لینک موفق گرفته</span><strong>{{ $stats['invited_ok'] }}</strong></div>
        <div class="emp-stat tone-sms"><span>هنوز نگرفته</span><strong>{{ $stats['never_sent'] }}</strong></div>
        <div class="emp-stat tone-amber"><span>آخرین ارسال ناموفق</span><strong>{{ $stats['last_failed'] }}</strong></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:minmax(0,1.2fr) minmax(260px,.8fr);gap:10px;align-items:start;margin-top:10px;">
    <section class="panel">
        <h3 style="margin-top:0;">ارسال گروهی</h3>
        <form method="POST" action="{{ route('portal-invites.start') }}" style="display:grid;gap:10px;">
            @csrf
            <label>گیرندگان
                <select name="filter" required>
                    @foreach($filters as $key => $label)
                        <option value="{{ $key }}" @selected(old('filter', 'never_sent') === $key)>
                            {{ $label }} ({{ number_format($previewCounts[$key] ?? 0) }} نفر)
                        </option>
                    @endforeach
                </select>
            </label>
            <label>متن پیامک
                <textarea name="template" rows="8" maxlength="1000" required>{{ old('template', $template) }}</textarea>
            </label>
            <p class="muted" style="margin:0;font-size:11px;">متغیرها: {name} {shop} {phone} {login_url} {office_phone}</p>
            <label style="display:flex;gap:8px;align-items:center;font-weight:700;">
                <input type="checkbox" name="confirm" value="1" required>
                تأیید می‌کنم این پیامک برای گیرندگان انتخاب‌شده ارسال شود
            </label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn btn-primary" type="submit">شروع ارسال گروهی</button>
                <button class="btn btn-ghost" type="submit" formaction="{{ route('portal-invites.template') }}">فقط ذخیره متن</button>
            </div>
        </form>
        @if($stats['last_failed'] > 0)
            <form method="POST" action="{{ route('portal-invites.resend-failed') }}" style="margin-top:12px;">
                @csrf
                <button class="btn btn-secondary" type="submit">ارسال مجدد همه ناموفق‌ها ({{ $stats['last_failed'] }})</button>
            </form>
        @endif
    </section>

    <section class="panel">
        <h3 style="margin-top:0;">دسته‌های اخیر</h3>
        @forelse($batches as $batch)
            <div style="border-top:1px solid #e2e8f0;padding:8px 0;">
                <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                    <strong dir="ltr">{{ $batch->code }}</strong>
                    <span class="muted">{{ $batch->status }}</span>
                </div>
                <div class="muted" style="font-size:11px;margin-top:2px;">
                    {{ jalali_like($batch->created_at) }} · {{ $batch->filterLabel() }} · {{ $batch->sent_ok }} موفق / {{ $batch->sent_fail }} ناموفق از {{ $batch->total }}
                </div>
                <div style="margin-top:6px;display:flex;gap:6px;">
                    @if(! $batch->isFinished())
                        <a class="btn btn-ghost btn-sm" href="{{ route('portal-invites.run', $batch) }}">ادامه</a>
                    @endif
                    <a class="btn btn-ghost btn-sm" href="{{ route('portal-invites.report', ['batch_id' => $batch->id]) }}">گزارش</a>
                </div>
            </div>
        @empty
            <p class="muted">هنوز دسته‌ای ارسال نشده.</p>
        @endforelse
    </section>
</div>
@endsection
