@extends('layouts.app')

@section('title', 'ارجاع قبض به همکار | '.shop_name())
@section('page_title', 'ارجاع قبض به همکار شبکه')
@section('window_title', 'ارسال قبض کامل پس از انتخاب همکار')

@section('content')
<section class="panel" style="margin-bottom:12px;background:#f3f7ff;border-color:#b7c8e8;">
    <strong>همکار انتخاب‌شده</strong>
    <div style="margin-top:8px;">
        <div style="font-size:18px;font-weight:800;">{{ $partner->displayName() }}</div>
        @if($partner->address)
            <div style="margin-top:4px;">{{ $partner->address }}</div>
        @endif
        <div class="muted" style="margin-top:4px;">
            @if($partner->domain)<span dir="ltr">{{ $partner->domain }}</span> · @endif
            @if($partner->phone)<span dir="ltr">{{ $partner->phone }}</span>@endif
        </div>
    </div>
    <p class="muted" style="margin:10px 0 0;">قبض انتخابی با همان شماره قبض مبدأ برای این همکار ارسال می‌شود. در مقصد قبض جدید ساخته می‌شود و تا تأیید منشی در وضعیت «منتظر قطعه» می‌ماند.</p>
</section>

<section class="panel">
    <form method="POST" action="{{ route('partners.refer-send', $partner) }}">
        @csrf
        <div class="accept-row accept-row-2" style="align-items:end;">
            <div style="grid-column:1/-1;">
                <label>انتخاب قبض برای ارسال *</label>
                <select name="reception_id" required>
                    <option value="">— قبض را انتخاب کنید —</option>
                    @foreach($receptions as $r)
                        <option value="{{ $r->id }}">
                            {{ $r->receipt_no }}
                            @if($r->ticket_no) / {{ $r->ticket_no }} @endif
                            — {{ $r->customer?->name ?: 'بدون نام' }}
                            @if($r->product_name) — {{ $r->product_name }} @endif
                            @if($r->serial_number) ({{ $r->serial_number }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:1/-1;">
                <label>یادداشت برای همکار مقصد</label>
                <input type="text" name="note" value="{{ old('note') }}" placeholder="مثلاً قطعه همراه است / نیاز به تخصص">
            </div>
        </div>
        @if($receptions->isEmpty())
            <p class="muted" style="margin-top:12px;">قبض بازی برای ارجاع نیست. اول در پذیرش یک قبض بسازید، سپس اینجا انتخاب کنید.</p>
        @endif
        <div class="actions" style="margin-top:16px;">
            <button class="btn btn-primary" type="submit" @disabled($receptions->isEmpty())>ارسال ارجاع به این همکار</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">بازگشت به فهرست</a>
            <a class="btn btn-ghost" href="{{ route('partners.cartable', ['tab' => 'outbound']) }}">کارتابل ارجاع</a>
        </div>
    </form>
</section>
@endsection
