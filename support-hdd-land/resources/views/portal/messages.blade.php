@extends('layouts.portal')
@section('title', 'پیام به تعمیرگاه | سرزمین هارد')

@section('content')
<div class="portal-shell">
    <div class="p-card">
        <h2>پیام به تعمیرگاه</h2>
        <p class="p-lead">سؤال، پیگیری یا درخواست خود را درباره قبض بنویسید. اعلان برای منشی، تعمیرکار و مدیر ارسال می‌شود.</p>

        @if(session('success'))
            <div class="p-alert ok">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="p-alert err">{{ $errors->first() }}</div>
        @endif

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
                <textarea name="body" rows="4" required placeholder="مثلاً: لطفاً وضعیت تعمیر هارد من را بگویید یا کار را زودتر انجام دهید.">{{ old('body') }}</textarea>
            </label>
            <button class="p-btn primary" type="submit">ارسال پیام</button>
        </form>
    </div>

    <div class="p-card">
        <h3>پیام‌های قبلی من</h3>
        @forelse($messages as $m)
            <div style="border-top:1px solid var(--line);padding:10px 0;">
                <div style="font-size:12px;color:var(--muted);">
                    {{ jalali_like($m->created_at?) }}
                    @if($m->reception) — {{ $m->reception->ticket_no }} @endif
                    — {{ $m->priorityLabel() }}
                    @if($m->isUnread()) · در صف بررسی @else · دیده شده @endif
                </div>
                <div style="margin-top:4px;">{{ $m->body }}</div>
            </div>
        @empty
            <p class="muted">هنوز پیامی نفرستاده‌اید.</p>
        @endforelse
        {{ $messages->links() }}
    </div>
</div>
@endsection
