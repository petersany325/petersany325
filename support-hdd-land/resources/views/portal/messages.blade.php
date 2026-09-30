@extends('layouts.portal')
@section('title', 'پیام‌ها | '.shop_name())

@section('content')
<div class="portal-shell">
    <div class="p-card">
        <h2>پیام‌ها</h2>
        <p class="p-lead">پیام‌های تعمیرگاه و پیام‌های شما. برای هماهنگی با دفتر: <a href="tel:{{ shop_office_phone() }}" dir="ltr">{{ shop_office_phone() }}</a></p>

        <form method="POST" action="{{ route('portal.messages.store') }}" class="p-form">
            @csrf
            <label>قبض مربوطه
                <select name="reception_id">
                    <option value="">— عمومی / بدون قبض خاص —</option>
                    @foreach($tickets as $t)
                        <option value="{{ $t->id }}" @selected(old('reception_id', request('reception_id')) == $t->id)>
                            {{ $t->ticket_no }} — {{ $t->product_name }} ({{ $t->serial_number }})
                        </option>
                    @endforeach
                </select>
            </label>
            <label>اولویت
                <select name="priority">
                    <option value="normal" @selected(old('priority')==='normal')>عادی</option>
                    <option value="urgent" @selected(old('priority')==='urgent')>فوری</option>
                </select>
            </label>
            <label>متن پیام
                <textarea name="body" rows="4" required placeholder="مثلاً: لطفاً وضعیت تعمیر را بگویید.">{{ old('body') }}</textarea>
            </label>
            <button class="p-btn primary" type="submit">ارسال پیام به تعمیرگاه</button>
        </form>
    </div>

    <div class="p-card">
        <h3>صندوق پیام</h3>
        @forelse($messages as $m)
            @php $fromShop = $m->isFromShop(); @endphp
            <div style="border-top:1px solid var(--line);padding:10px 0;">
                <div style="font-size:12px;color:var(--muted);display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                    <span class="p-chip {{ $fromShop ? 'tone-teal' : 'tone-slate' }}">{{ $fromShop ? 'پیام تعمیرگاه' : 'پیام شما' }}</span>
                    <span>{{ jalali_like($m->created_at) }}</span>
                    @if($m->reception) <span>— {{ $m->reception->ticket_no }}</span> @endif
                    @if($m->preorder) <span>— {{ $m->preorder->code }}</span> @endif
                    @if($fromShop && $m->isUnread()) <span style="color:#b45309;font-weight:800;">جدید</span> @endif
                </div>
                <div style="margin-top:6px;white-space:pre-wrap;line-height:1.7;">{{ $m->body }}</div>
            </div>
        @empty
            <p class="muted">هنوز پیامی نیست.</p>
        @endforelse
        {{ $messages->links() }}
    </div>
</div>
@endsection
