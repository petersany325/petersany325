<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نتیجه پرداخت زرین‌پال | سرزمین هارد</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="portal-body">
<main class="p-wrap" style="padding:24px 16px;max-width:520px;margin:0 auto;">
    <section class="p-section">
        <h2 style="margin-top:0;">
            @if(!empty($ok))
                پرداخت موفق
            @elseif(!empty($pending))
                در حال بررسی
            @else
                پرداخت ناموفق
            @endif
        </h2>
        <p>{{ $message ?? '' }}</p>
        @if($transaction?->reception)
            <p class="muted">قبض: {{ $transaction->reception->ticket_no }}</p>
            <p class="muted">مبلغ: {{ number_format((int) $transaction->amount) }} تومان</p>
            @if($transaction->ref_id)
                <p class="muted" dir="ltr">Ref: {{ $transaction->ref_id }}</p>
            @endif
        @endif
        <div style="margin-top:14px;display:grid;gap:8px;">
            @if(session('portal_customer_id') && $transaction?->reception_id)
                <a class="p-btn primary" href="{{ route('portal.show', $transaction->reception_id) }}">بازگشت به قبض</a>
                <a class="p-btn ghost" href="{{ route('portal.home') }}">کارتابل مشتری</a>
            @elseif(auth()->check() && $transaction?->reception_id)
                <a class="p-btn primary" href="{{ route('receptions.show', $transaction->reception_id) }}">بازگشت به قبض</a>
            @else
                <a class="p-btn primary" href="{{ url('/cartable') }}">ورود به کارتابل</a>
            @endif
        </div>
    </section>
</main>
</body>
</html>
